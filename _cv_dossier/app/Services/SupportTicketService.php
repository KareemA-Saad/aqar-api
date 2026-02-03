<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\TicketCreated;
use App\Events\TicketReplied;
use App\Models\Admin;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Support Ticket Service
 *
 * Handles all support ticket-related business logic including:
 * - Ticket CRUD operations
 * - Ticket messaging
 * - Status management
 * - Statistics and reporting
 */
final class SupportTicketService
{
    /**
     * Get paginated list of tickets with filters (Admin).
     *
     * @param array{status?: int, priority?: int, department_id?: int, admin_id?: int, user_id?: int, search?: string, per_page?: int} $filters
     * @return LengthAwarePaginator
     */
    public function getTicketList(array $filters = []): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->with(['user', 'admin', 'department'])
            ->withCount('messages');

        // Status filter
        if (isset($filters['status']) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        // Priority filter
        if (isset($filters['priority']) && $filters['priority'] !== null) {
            $query->where('priority', $filters['priority']);
        }

        // Department filter
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // Assignee filter
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get user's tickets (User).
     *
     * @param User $user
     * @param array{status?: int, per_page?: int} $filters
     * @return LengthAwarePaginator
     */
    public function getUserTickets(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->where('user_id', $user->id)
            ->with(['department', 'admin'])
            ->withCount('messages');

        // Status filter
        if (isset($filters['status']) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get ticket by ID with messages.
     *
     * @param int $id
     * @return SupportTicket|null
     */
    public function getTicketById(int $id): ?SupportTicket
    {
        return SupportTicket::with([
            'user',
            'admin',
            'department',
            'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'messages.user',
            'messages.admin',
        ])->find($id);
    }

    /**
     * Get ticket by ID for a specific user.
     *
     * @param int $id
     * @param User $user
     * @return SupportTicket|null
     */
    public function getUserTicketById(int $id, User $user): ?SupportTicket
    {
        return SupportTicket::with([
            'department',
            'admin',
            'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
            'messages.user',
            'messages.admin',
        ])
            ->where('user_id', $user->id)
            ->find($id);
    }

    /**
     * Create a new support ticket.
     *
     * @param User $user
     * @param array{title: string, subject: string, description: string, priority: int, department_id: int, via?: ?string, attachment?: ?string} $data
     * @return SupportTicket
     */
    public function createTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticket = SupportTicket::create([
                'title' => $data['title'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'department_id' => $data['department_id'],
                'user_id' => $user->id,
                'status' => 0, // Open
                'via' => $data['via'] ?? 'api',
                'user_agent' => request()->userAgent(),
                'operating_system' => $this->detectOS(request()->userAgent()),
            ]);

            // Create initial message with description
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'message' => $data['description'],
                'attachment' => $data['attachment'] ?? null,
                'type' => 'user',
                'user_id' => $user->id,
                'notify' => true,
            ]);

            $ticket->load(['user', 'department', 'messages']);

            // Dispatch event
            event(new TicketCreated($ticket));

            Log::info('Support ticket created', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'department_id' => $data['department_id'],
            ]);

            return $ticket;
        });
    }

    /**
     * Add a reply to a ticket.
     *
     * @param SupportTicket $ticket
     * @param User|Admin $author
     * @param string $message
     * @param string|null $attachment
     * @return SupportTicketMessage
     */
    public function addReply(
        SupportTicket $ticket,
        User|Admin $author,
        string $message,
        ?string $attachment = null
    ): SupportTicketMessage {
        return DB::transaction(function () use ($ticket, $author, $message, $attachment) {
            $isAdmin = $author instanceof Admin;
            $type = $isAdmin ? 'admin' : 'user';

            $ticketMessage = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'message' => $message,
                'attachment' => $attachment,
                'type' => $type,
                'user_id' => $author->id,
                'notify' => true,
            ]);

            // Update ticket status based on who replied
            if ($isAdmin) {
                // Admin replied - set to pending (waiting for user)
                $ticket->update(['status' => 2, 'admin_id' => $author->id]);
            } else {
                // User replied - set to open
                $ticket->update(['status' => 0]);
            }

            $ticketMessage->load($isAdmin ? 'admin' : 'user');

            // Dispatch event
            event(new TicketReplied($ticket, $ticketMessage));

            Log::info('Ticket reply added', [
                'ticket_id' => $ticket->id,
                'message_id' => $ticketMessage->id,
                'author_type' => $type,
                'author_id' => $author->id,
            ]);

            return $ticketMessage;
        });
    }

    /**
     * Update ticket status.
     *
     * @param SupportTicket $ticket
     * @param int $status
     * @return bool
     */
    public function updateStatus(SupportTicket $ticket, int $status): bool
    {
        $result = $ticket->update(['status' => $status]);

        Log::info('Ticket status updated', [
            'ticket_id' => $ticket->id,
            'new_status' => $status,
            'status_label' => SupportTicket::STATUSES[$status] ?? 'unknown',
        ]);

        return $result;
    }

    /**
     * Update ticket details (Admin).
     *
     * @param SupportTicket $ticket
     * @param array{status?: int, priority?: int, admin_id?: ?int, department_id?: int} $data
     * @return SupportTicket
     */
    public function updateTicket(SupportTicket $ticket, array $data): SupportTicket
    {
        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['priority'])) {
            $updateData['priority'] = $data['priority'];
        }

        if (array_key_exists('admin_id', $data)) {
            $updateData['admin_id'] = $data['admin_id'];
        }

        if (isset($data['department_id'])) {
            $updateData['department_id'] = $data['department_id'];
        }

        if (!empty($updateData)) {
            $ticket->update($updateData);

            Log::info('Ticket updated', [
                'ticket_id' => $ticket->id,
                'changes' => array_keys($updateData),
            ]);
        }

        return $ticket->fresh(['user', 'admin', 'department']);
    }

    /**
     * Close a ticket.
     *
     * @param SupportTicket $ticket
     * @return bool
     */
    public function closeTicket(SupportTicket $ticket): bool
    {
        return $this->updateStatus($ticket, 1); // 1 = closed
    }

    /**
     * Get ticket statistics.
     *
     * @return array{total: int, open: int, pending: int, closed: int, by_priority: array, by_department: array}
     */
    public function getTicketStats(): array
    {
        $statusCounts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $priorityCounts = SupportTicket::query()
            ->where('status', '!=', 1) // Exclude closed
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $departmentCounts = SupportTicket::query()
            ->where('status', '!=', 1) // Exclude closed
            ->selectRaw('department_id, COUNT(*) as count')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->department?->name ?? 'Unknown' => $item->count,
            ])
            ->toArray();

        return [
            'total' => array_sum($statusCounts),
            'open' => $statusCounts[0] ?? 0,
            'closed' => $statusCounts[1] ?? 0,
            'pending' => $statusCounts[2] ?? 0,
            'by_priority' => [
                'low' => $priorityCounts[0] ?? 0,
                'medium' => $priorityCounts[1] ?? 0,
                'high' => $priorityCounts[2] ?? 0,
                'urgent' => $priorityCounts[3] ?? 0,
            ],
            'by_department' => $departmentCounts,
        ];
    }

    /**
     * Get all active departments.
     *
     * @return Collection<int, SupportDepartment>
     */
    public function getActiveDepartments(): Collection
    {
        return SupportDepartment::active()->orderBy('name')->get();
    }

    /**
     * Get all departments with ticket counts.
     *
     * @return LengthAwarePaginator
     */
    public function getDepartmentList(int $perPage = 15): LengthAwarePaginator
    {
        return SupportDepartment::query()
            ->withCount('tickets')
            ->orderBy('name')
            ->paginate(min($perPage, 100));
    }

    /**
     * Get department by ID.
     *
     * @param int $id
     * @return SupportDepartment|null
     */
    public function getDepartmentById(int $id): ?SupportDepartment
    {
        return SupportDepartment::withCount('tickets')->find($id);
    }

    /**
     * Create a new department.
     *
     * @param array{name: string, status: bool} $data
     * @return SupportDepartment
     */
    public function createDepartment(array $data): SupportDepartment
    {
        $department = SupportDepartment::create([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        Log::info('Department created', [
            'department_id' => $department->id,
            'name' => $department->name,
        ]);

        return $department;
    }

    /**
     * Update a department.
     *
     * @param SupportDepartment $department
     * @param array{name: string, status: bool} $data
     * @return SupportDepartment
     */
    public function updateDepartment(SupportDepartment $department, array $data): SupportDepartment
    {
        $department->update([
            'name' => $data['name'],
            'status' => $data['status'],
        ]);

        Log::info('Department updated', [
            'department_id' => $department->id,
            'name' => $department->name,
        ]);

        return $department->fresh();
    }

    /**
     * Delete a department.
     *
     * @param SupportDepartment $department
     * @return bool
     * @throws \Exception
     */
    public function deleteDepartment(SupportDepartment $department): bool
    {
        // Check if department has tickets
        if ($department->tickets()->exists()) {
            throw new \Exception('Cannot delete department with existing tickets');
        }

        $departmentId = $department->id;
        $department->delete();

        Log::info('Department deleted', ['department_id' => $departmentId]);

        return true;
    }

    /**
     * Detect operating system from user agent.
     *
     * @param string|null $userAgent
     * @return string|null
     */
    private function detectOS(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $osPatterns = [
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/macintosh|mac os x/i' => 'macOS',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iOS',
            '/android/i' => 'Android',
        ];

        foreach ($osPatterns as $pattern => $os) {
            if (preg_match($pattern, $userAgent)) {
                return $os;
            }
        }

        return null;
    }
}

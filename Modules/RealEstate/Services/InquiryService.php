<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Mail\InquiryConfirmationMail;
use Modules\RealEstate\Mail\NewInquiryMail;
use Modules\RealEstate\Notifications\InquiryAssignedNotification;
use Modules\RealEstate\Notifications\InquiryStatusUpdatedNotification;
use Modules\RealEstate\Notifications\NewInquiryNotification;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Inquiry Service
 * 
 * Handles all business logic for property inquiry/lead management including
 * CRUD operations, status transitions, notifications, and CRM functionality.
 */
class InquiryService
{
    /**
     * Get paginated inquiries with filters.
     */
    public function getPaginatedInquiries(array $filters = []): LengthAwarePaginator
    {
        $query = QueryBuilder::for(PropertyInquiry::class)
            ->allowedFilters([
                AllowedFilter::exact('property_id'),
                AllowedFilter::exact('compound_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('agent_id'),
                AllowedFilter::scope('date_range', 'dateRange'),
            ])
            ->allowedSorts(['created_at', 'status', 'name'])
            ->allowedIncludes(['property', 'agent'])
            ->with(['property', 'agent']);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single inquiry by ID.
     */
    public function getInquiry(int|string $id): ?PropertyInquiry
    {
        return PropertyInquiry::with(['property.compound', 'agent'])->find((int) $id);
    }

    /**
     * Create a new inquiry (from frontend submission).
     */
    public function createInquiry(array $data): PropertyInquiry
    {
        return DB::transaction(function () use ($data) {
            // Set default status
            $data['status'] = $data['status'] ?? 'new';

            // Set IP and user agent if available
            if (request()) {
                $data['ip_address'] = $data['ip_address'] ?? request()->ip();
                $data['user_agent'] = $data['user_agent'] ?? request()->userAgent();
            }

            // Set user_id if authenticated
            if (auth()->check() && empty($data['user_id'])) {
                $data['user_id'] = auth()->id();
            }

            \Log::info('RealEstate: Creating new inquiry', [
                'user_id' => $data['user_id'] ?? null,
                'property_id' => $data['property_id'] ?? null,
                'compound_id' => $data['compound_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'ip_address' => $data['ip_address'] ?? null,
            ]);

            // Create inquiry
            $inquiry = PropertyInquiry::create($data);

            \Log::info('RealEstate: Inquiry created successfully', [
                'inquiry_id' => $inquiry->id,
                'user_id' => $inquiry->user_id,
            ]);

            // Load relationships for notifications
            $inquiry->load(['property.compound', 'agent']);

            // Send notifications
            $this->sendNewInquiryNotifications($inquiry);

            return $inquiry;
        });
    }

    /**
     * Send notifications for new inquiry.
     */
    protected function sendNewInquiryNotifications(PropertyInquiry $inquiry): void
    {
        // Send confirmation email to customer
        if ($inquiry->email) {
            try {
                Mail::to($inquiry->email)->queue(new InquiryConfirmationMail($inquiry));
            } catch (\Exception $e) {
                \Log::error('Failed to send inquiry confirmation email: ' . $e->getMessage());
            }
        }

        // Notify assigned agent if exists
        if ($inquiry->agent_id && $inquiry->agent) {
            try {
                $inquiry->agent->notify(new NewInquiryNotification($inquiry));
            } catch (\Exception $e) {
                \Log::error('Failed to notify agent: ' . $e->getMessage());
            }
        }

        // Notify property agent if different from inquiry agent
        if ($inquiry->property && $inquiry->property->agent_id && $inquiry->property->agent_id !== $inquiry->agent_id) {
            try {
                $inquiry->property->agent->notify(new NewInquiryNotification($inquiry));
            } catch (\Exception $e) {
                \Log::error('Failed to notify property agent: ' . $e->getMessage());
            }
        }

        // Notify admins (users with realestate admin role)
        $this->notifyAdmins($inquiry);
    }

    /**
     * Notify admin users about new inquiry.
     */
    protected function notifyAdmins(PropertyInquiry $inquiry): void
    {
        try {
            // Get users with admin permissions for real estate
            $admins = User::permission('inquiry-list')->get();
            
            if ($admins->isEmpty()) {
                // Fallback to users with admin role
                $admins = User::role(['admin', 'super-admin'])->get();
            }

            foreach ($admins as $admin) {
                // Don't double-notify if already notified as agent
                if ($admin->id !== $inquiry->agent_id && 
                    (!$inquiry->property || $admin->id !== $inquiry->property->agent_id)) {
                    $admin->notify(new NewInquiryNotification($inquiry));
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify admins: ' . $e->getMessage());
        }
    }

    /**
     * Update inquiry status.
     */
    public function updateStatus(PropertyInquiry $inquiry, string $status): PropertyInquiry
    {
        if (!in_array($status, PropertyInquiry::$statuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $previousStatus = $inquiry->status;
        
        \Log::info('RealEstate: Updating inquiry status', [
            'inquiry_id' => $inquiry->id,
            'previous_status' => $previousStatus,
            'new_status' => $status,
            'user_id' => $inquiry->user_id,
            'agent_id' => $inquiry->agent_id,
        ]);

        $inquiry->update(['status' => $status]);

        // Notify about status change (to both agent and user if applicable)
        if ($previousStatus !== $status) {
            // Notify agent
            if ($inquiry->agent) {
                try {
                    $inquiry->agent->notify(new InquiryStatusUpdatedNotification($inquiry, $previousStatus));
                    \Log::debug('RealEstate: Agent notified of status change', [
                        'inquiry_id' => $inquiry->id,
                        'agent_id' => $inquiry->agent_id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('RealEstate: Failed to notify agent of status change', [
                        'inquiry_id' => $inquiry->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Notify user (customer) if authenticated inquiry
            if ($inquiry->user_id && $inquiry->user) {
                try {
                    $inquiry->user->notify(new InquiryStatusUpdatedNotification($inquiry, $previousStatus));
                    \Log::info('RealEstate: User notified of inquiry status change', [
                        'inquiry_id' => $inquiry->id,
                        'user_id' => $inquiry->user_id,
                        'new_status' => $status,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('RealEstate: Failed to notify user of status change', [
                        'inquiry_id' => $inquiry->id,
                        'user_id' => $inquiry->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                \Log::debug('RealEstate: No user to notify (guest inquiry)', [
                    'inquiry_id' => $inquiry->id,
                ]);
            }
        }

        return $inquiry->fresh();
    }

    /**
     * Mark as contacted.
     */
    public function markAsContacted(PropertyInquiry $inquiry): PropertyInquiry
    {
        $inquiry->markAsContacted();
        return $inquiry->fresh();
    }

    /**
     * Mark as qualified.
     */
    public function markAsQualified(PropertyInquiry $inquiry): PropertyInquiry
    {
        $inquiry->markAsQualified();
        return $inquiry->fresh();
    }

    /**
     * Mark as converted.
     */
    public function markAsConverted(PropertyInquiry $inquiry): PropertyInquiry
    {
        $inquiry->markAsConverted();
        return $inquiry->fresh();
    }

    /**
     * Mark as closed.
     */
    public function markAsClosed(PropertyInquiry $inquiry): PropertyInquiry
    {
        $inquiry->markAsClosed();
        return $inquiry->fresh();
    }

    /**
     * Assign agent to inquiry.
     */
    public function assignAgent(PropertyInquiry $inquiry, int $agentId): PropertyInquiry
    {
        $previousAgentId = $inquiry->agent_id;
        $inquiry->update(['agent_id' => $agentId]);

        // Load the agent relationship
        $inquiry->load(['agent', 'property.compound']);

        // Notify the newly assigned agent (if different from previous)
        if ($agentId !== $previousAgentId && $inquiry->agent) {
            try {
                $inquiry->agent->notify(new InquiryAssignedNotification($inquiry));
            } catch (\Exception $e) {
                \Log::error('Failed to notify assigned agent: ' . $e->getMessage());
            }
        }

        return $inquiry->fresh(['agent']);
    }

    /**
     * Add admin notes.
     */
    public function addNotes(PropertyInquiry $inquiry, string $notes): PropertyInquiry
    {
        $existingNotes = $inquiry->admin_notes ?? '';
        $timestamp = now()->format('Y-m-d H:i');
        $newNote = "[{$timestamp}] {$notes}";
        
        $inquiry->update([
            'admin_notes' => $existingNotes ? "{$existingNotes}\n\n{$newNote}" : $newNote,
        ]);

        return $inquiry->fresh();
    }

    /**
     * Delete an inquiry.
     */
    public function deleteInquiry(PropertyInquiry $inquiry): bool
    {
        return $inquiry->delete();
    }

    /**
     * Get inquiry statistics.
     */
    public function getStatistics(): array
    {
        $inquiries = PropertyInquiry::all();

        return [
            'total' => $inquiries->count(),
            'new' => $inquiries->where('status', 'new')->count(),
            'contacted' => $inquiries->where('status', 'contacted')->count(),
            'qualified' => $inquiries->where('status', 'qualified')->count(),
            'converted' => $inquiries->where('status', 'converted')->count(),
            'closed' => $inquiries->where('status', 'closed')->count(),
            'conversion_rate' => $inquiries->count() > 0 
                ? round(($inquiries->where('status', 'converted')->count() / $inquiries->count()) * 100, 2) 
                : 0,
            'today' => PropertyInquiry::whereDate('created_at', today())->count(),
            'this_week' => PropertyInquiry::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => PropertyInquiry::whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Get inquiries by property.
     */
    public function getInquiriesByProperty(int $propertyId): LengthAwarePaginator
    {
        return PropertyInquiry::where('property_id', $propertyId)
            ->with(['agent'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get inquiries by compound.
     */
    public function getInquiriesByCompound(int $compoundId): LengthAwarePaginator
    {
        return PropertyInquiry::where('compound_id', $compoundId)
            ->with(['property.compound', 'agent'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get inquiries assigned to an agent.
     */
    public function getAgentInquiries(int $agentId): LengthAwarePaginator
    {
        return PropertyInquiry::where('agent_id', $agentId)
            ->with(['property.compound'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Bulk update status.
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        if (!in_array($status, PropertyInquiry::$statuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        return PropertyInquiry::whereIn('id', $ids)->update(['status' => $status]);
    }

    /**
     * Bulk assign agent.
     */
    public function bulkAssignAgent(array $ids, int $agentId): int
    {
        return PropertyInquiry::whereIn('id', $ids)->update(['agent_id' => $agentId]);
    }

    /**
     * Bulk delete inquiries.
     */
    public function bulkDelete(array $ids): int
    {
        return PropertyInquiry::whereIn('id', $ids)->delete();
    }

    /**
     * Export inquiries to array (for CSV/Excel export).
     */
    public function exportInquiries(array $filters = []): array
    {
        $query = PropertyInquiry::with(['property', 'compound', 'user', 'agent']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get()->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'name' => $inquiry->name,
                'email' => $inquiry->email,
                'phone' => $inquiry->phone,
                'message' => $inquiry->message,
                'property' => $inquiry->property?->title,
                'compound' => $inquiry->compound?->name,
                'status' => $inquiry->status,
                'agent' => $inquiry->agent?->name,
                'created_at' => $inquiry->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }
}

<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\PropertyInquiry;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Inquiry Service
 * 
 * Handles all business logic for property inquiry/lead management including
 * CRUD operations, status transitions, and CRM functionality.
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
            ->allowedIncludes(['property', 'compound', 'user', 'agent'])
            ->with(['property', 'compound']);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single inquiry by ID.
     */
    public function getInquiry(int $id): ?PropertyInquiry
    {
        return PropertyInquiry::with(['property', 'compound', 'user', 'agent'])->find($id);
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

            // Create inquiry
            return PropertyInquiry::create($data);
        });
    }

    /**
     * Update inquiry status.
     */
    public function updateStatus(PropertyInquiry $inquiry, string $status): PropertyInquiry
    {
        if (!in_array($status, PropertyInquiry::$statuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $inquiry->update(['status' => $status]);

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
        $inquiry->update(['agent_id' => $agentId]);
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
            ->with(['user', 'agent'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get inquiries by compound.
     */
    public function getInquiriesByCompound(int $compoundId): LengthAwarePaginator
    {
        return PropertyInquiry::where('compound_id', $compoundId)
            ->with(['property', 'user', 'agent'])
            ->latest()
            ->paginate(15);
    }

    /**
     * Get inquiries assigned to an agent.
     */
    public function getAgentInquiries(int $agentId): LengthAwarePaginator
    {
        return PropertyInquiry::where('agent_id', $agentId)
            ->with(['property', 'compound', 'user'])
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

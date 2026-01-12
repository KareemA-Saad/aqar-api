<?php

declare(strict_types=1);

namespace Modules\Event\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Event\Entities\Event;
use Modules\Event\Entities\EventCategory;

/**
 * Service class for managing events.
 *
 * Handles event CRUD operations, ticket management, and related content retrieval.
 */
final class EventService
{
    /**
     * Get paginated list of events with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEvents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Event::query()->with(['category', 'metainfo'])->withCount('comments');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('organizer', 'like', "%{$search}%")
                    ->orWhere('venue_location', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        // Upcoming events filter
        if (!empty($filters['upcoming'])) {
            $query->where('date', '>=', now()->toDateString());
        }

        // Past events filter
        if (!empty($filters['past'])) {
            $query->where('date', '<', now()->toDateString());
        }

        // Ticket availability filter
        if (!empty($filters['available_tickets'])) {
            $query->where('available_ticket', '>', 0);
        }

        // Price range filter
        if (isset($filters['min_cost'])) {
            $query->where('cost', '>=', $filters['min_cost']);
        }
        if (isset($filters['max_cost'])) {
            $query->where('cost', '<=', $filters['max_cost']);
        }

        // Organizer filter
        if (!empty($filters['organizer'])) {
            $query->where('organizer', 'like', "%{$filters['organizer']}%");
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $allowedSorts = ['title', 'date', 'cost', 'created_at', 'updated_at', 'available_ticket'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published events for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedEvents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getEvents($filters, $perPage);
    }

    /**
     * Get upcoming published events.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUpcomingEvents(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedEvents([
            'upcoming' => true,
            'sort_by' => 'date',
            'sort_order' => 'asc'
        ], $perPage);
    }

    /**
     * Get events by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEventsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedEvents([
            'category_id' => $categoryId
        ], $perPage);
    }

    /**
     * Search events.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchEvents(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedEvents([
            'search' => $query
        ], $perPage);
    }

    /**
     * Create a new event.
     *
     * @param array<string, mixed> $data
     * @return Event
     */
    public function createEvent(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            // Set available tickets equal to total tickets if not provided
            $totalTicket = $data['total_ticket'] ?? 0;
            $availableTicket = $data['available_ticket'] ?? $totalTicket;

            $event = Event::create([
                'title' => SanitizeInput::esc_html($data['title']),
                'slug' => $slug,
                'content' => $data['content'],
                'category_id' => $data['category_id'] ?? null,
                'organizer' => SanitizeInput::esc_html($data['organizer']),
                'organizer_email' => $data['organizer_email'],
                'organizer_phone' => $data['organizer_phone'],
                'venue_location' => SanitizeInput::esc_html($data['venue_location']),
                'cost' => $data['cost'] ?? 0,
                'total_ticket' => $totalTicket,
                'available_ticket' => $availableTicket,
                'date' => $data['date'] ?? null,
                'time' => $data['time'] ?? null,
                'image' => $data['image'] ?? null,
                'status' => $data['status'] ?? false,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $event->metainfo()->create([
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                    'facebook_meta_tags' => $data['facebook_meta_tags'] ?? null,
                    'twitter_meta_tags' => $data['twitter_meta_tags'] ?? null,
                ]);
            }

            return $event->load(['category', 'metainfo']);
        });
    }

    /**
     * Update an existing event.
     *
     * @param Event $event
     * @param array<string, mixed> $data
     * @return Event
     */
    public function updateEvent(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            // Generate slug if title changed
            if (isset($data['title']) && $data['title'] !== $event->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->ensureUniqueSlug($slug, $event->id);
            }

            // Sanitize inputs
            if (isset($data['title'])) {
                $data['title'] = SanitizeInput::esc_html($data['title']);
            }
            if (isset($data['organizer'])) {
                $data['organizer'] = SanitizeInput::esc_html($data['organizer']);
            }
            if (isset($data['venue_location'])) {
                $data['venue_location'] = SanitizeInput::esc_html($data['venue_location']);
            }

            $event->update($data);

            // Update meta info if provided
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $metaData = [
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                    'facebook_meta_tags' => $data['facebook_meta_tags'] ?? null,
                    'twitter_meta_tags' => $data['twitter_meta_tags'] ?? null,
                ];

                if ($event->metainfo) {
                    $event->metainfo->update($metaData);
                } else {
                    $event->metainfo()->create($metaData);
                }
            }

            return $event->load(['category', 'metainfo']);
        });
    }

    /**
     * Delete an event.
     *
     * @param Event $event
     * @return bool
     */
    public function deleteEvent(Event $event): bool
    {
        return DB::transaction(function () use ($event) {
            // Delete meta info
            $event->metainfo()?->delete();

            // Delete the event (cascade will handle comments and payment logs)
            return $event->delete();
        });
    }

    /**
     * Clone an event.
     *
     * @param Event $event
     * @return Event
     */
    public function cloneEvent(Event $event): Event
    {
        return DB::transaction(function () use ($event) {
            $newEvent = $event->replicate();
            $newEvent->title = $event->title . ' (Copy)';
            $newEvent->slug = $this->ensureUniqueSlug(Str::slug($newEvent->title));
            $newEvent->available_ticket = $newEvent->total_ticket;
            $newEvent->save();

            // Clone meta info if exists
            if ($event->metainfo) {
                $newMetaInfo = $event->metainfo->replicate();
                $newMetaInfo->metainfoable_id = $newEvent->id;
                $newMetaInfo->metainfoable_type = Event::class;
                $newMetaInfo->save();
            }

            return $newEvent->load(['category', 'metainfo']);
        });
    }

    /**
     * Update ticket availability after booking.
     *
     * @param Event $event
     * @param int $quantity
     * @return bool
     */
    public function updateTicketAvailability(Event $event, int $quantity): bool
    {
        if ($event->available_ticket < $quantity) {
            return false;
        }

        $event->decrement('available_ticket', $quantity);
        return true;
    }

    /**
     * Get event statistics (for admin dashboard).
     *
     * @param Event $event
     * @return array<string, mixed>
     */
    public function getEventStatistics(Event $event): array
    {
        $paymentLogs = $event->paymentLogs ?? collect();
        
        $totalRevenue = $paymentLogs->where('status', true)->sum('amount');
        $totalBookings = $paymentLogs->where('status', true)->count();
        $totalTicketsSold = $paymentLogs->where('status', true)->sum('ticket_qty');
        $totalCheckedIn = $paymentLogs->where('check_in_status', true)->count();

        // Last 7 days revenue
        $last7DaysRevenue = $paymentLogs
            ->where('status', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d');
            })
            ->map(function ($logs) {
                return $logs->sum('amount');
            })
            ->toArray();

        return [
            'total_revenue' => $totalRevenue,
            'total_bookings' => $totalBookings,
            'total_tickets_sold' => $totalTicketsSold,
            'total_checked_in' => $totalCheckedIn,
            'available_tickets' => $event->available_ticket,
            'total_tickets' => $event->total_ticket,
            'sold_percentage' => $event->total_ticket > 0 
                ? round(($totalTicketsSold / $event->total_ticket) * 100, 2)
                : 0,
            'last_7_days_revenue' => $last7DaysRevenue,
        ];
    }

    /**
     * Get related events (same category, upcoming).
     *
     * @param Event $event
     * @param int $limit
     * @return Collection
     */
    public function getRelatedEvents(Event $event, int $limit = 4): Collection
    {
        return Event::where('category_id', $event->category_id)
            ->where('id', '!=', $event->id)
            ->where('status', true)
            ->where('date', '>=', now()->toDateString())
            ->with(['category'])
            ->orderBy('date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Ensure unique slug for event.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while (true) {
            $query = Event::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}

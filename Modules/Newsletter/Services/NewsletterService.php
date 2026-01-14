<?php

declare(strict_types=1);

namespace Modules\Newsletter\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Newsletter\Entities\Newsletter;

/**
 * Service class for managing newsletter subscriptions.
 */
final class NewsletterService
{
    /**
     * Get paginated list of newsletter subscriptions with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Newsletter::query();

        // Search filter
        if (!empty($filters['search'])) {
            $query->where('email', 'like', "%{$filters['search']}%");
        }

        // Verified filter
        if (isset($filters['verified'])) {
            $query->where('verified', (bool) $filters['verified']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Subscribe to newsletter.
     *
     * @param string $email
     * @return Newsletter
     */
    public function subscribe(string $email): Newsletter
    {
        return DB::transaction(function () use ($email) {
            // Check if already subscribed
            $existing = Newsletter::where('email', $email)->first();
            
            if ($existing) {
                // If already verified, return existing
                if ($existing->verified) {
                    return $existing;
                }
                
                // If not verified, regenerate token
                $existing->update([
                    'token' => Str::random(60),
                    'verified' => false,
                ]);
                
                return $existing;
            }

            // Create new subscription
            return Newsletter::create([
                'email' => $email,
                'token' => Str::random(60),
                'verified' => false,
            ]);
        });
    }

    /**
     * Verify newsletter subscription with token.
     *
     * @param string $token
     * @return Newsletter|null
     */
    public function verifySubscription(string $token): ?Newsletter
    {
        $newsletter = Newsletter::where('token', $token)
            ->where('verified', false)
            ->first();

        if ($newsletter) {
            $newsletter->update([
                'verified' => true,
                'token' => null, // Clear token after verification
            ]);
        }

        return $newsletter;
    }

    /**
     * Unsubscribe from newsletter.
     *
     * @param string $email
     * @return bool
     */
    public function unsubscribe(string $email): bool
    {
        $newsletter = Newsletter::where('email', $email)->first();
        
        if ($newsletter) {
            return $newsletter->delete();
        }

        return false;
    }

    /**
     * Delete a newsletter subscription.
     *
     * @param Newsletter $newsletter
     * @return bool
     */
    public function deleteSubscription(Newsletter $newsletter): bool
    {
        return $newsletter->delete();
    }

    /**
     * Get subscription statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'total' => Newsletter::count(),
            'verified' => Newsletter::where('verified', true)->count(),
            'pending' => Newsletter::where('verified', false)->count(),
        ];
    }

    /**
     * Export all verified email addresses.
     *
     * @return array<string>
     */
    public function exportVerifiedEmails(): array
    {
        return Newsletter::where('verified', true)
            ->pluck('email')
            ->toArray();
    }

    /**
     * Send email to all verified subscribers.
     *
     * @param string $subject
     * @param string $message
     * @return int Number of emails sent
     */
    public function sendEmailToAll(string $subject, string $message): int
    {
        $subscribers = Newsletter::where('verified', true)->get();
        $count = 0;

        foreach ($subscribers as $subscriber) {
            try {
                \Mail::to($subscriber->email)->send(
                    new \Modules\Newsletter\Mail\SubscriberMessage($subject, $message)
                );
                $count++;
            } catch (\Exception $e) {
                \Log::error('Failed to send newsletter email', [
                    'email' => $subscriber->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Send email to a specific subscriber.
     *
     * @param Newsletter $newsletter
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public function sendEmailToSubscriber(Newsletter $newsletter, string $subject, string $message): bool
    {
        if (!$newsletter->verified) {
            return false;
        }

        try {
            \Mail::to($newsletter->email)->send(
                new \Modules\Newsletter\Mail\SubscriberMessage($subject, $message)
            );
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send newsletter email to subscriber', [
                'email' => $newsletter->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

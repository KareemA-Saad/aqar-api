<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Wallet\Entities\WalletHistory;

class WalletHistoryService
{
    /**
     * Get paginated wallet histories with filters
     */
    public function getHistories(array $filters = []): LengthAwarePaginator
    {
        $query = WalletHistory::with('user');

        // Search by transaction ID or user
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by user_id
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by payment gateway
        if (!empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Filter by amount range
        if (isset($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        if (isset($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get user's wallet history
     */
    public function getUserHistory(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WalletHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get wallet history by ID
     */
    public function getHistoryById(int $id): ?WalletHistory
    {
        return WalletHistory::with('user')->find($id);
    }

    /**
     * Create wallet history record
     */
    public function createHistory(array $data): WalletHistory
    {
        return WalletHistory::create([
            'user_id' => $data['user_id'],
            'payment_gateway' => $data['payment_gateway'] ?? null,
            'payment_status' => $data['payment_status'] ?? 'completed',
            'amount' => $data['amount'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'manual_payment_image' => $data['manual_payment_image'] ?? null,
            'status' => $data['status'] ?? 1,
        ]);
    }

    /**
     * Update wallet history
     */
    public function updateHistory(int $id, array $data): bool
    {
        $history = WalletHistory::find($id);
        if (!$history) {
            return false;
        }

        return $history->update($data);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        $history = WalletHistory::find($id);
        if (!$history) {
            return false;
        }

        return $history->update(['payment_status' => $paymentStatus]);
    }

    /**
     * Delete wallet history
     */
    public function deleteHistory(int $id): bool
    {
        $history = WalletHistory::find($id);
        if (!$history) {
            return false;
        }

        return $history->delete();
    }

    /**
     * Bulk delete wallet histories
     */
    public function bulkDelete(array $historyIds): int
    {
        return WalletHistory::whereIn('id', $historyIds)->delete();
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(array $filters = []): array
    {
        $query = WalletHistory::query();

        // Apply user filter if provided
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Apply date range if provided
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_transactions' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'completed_transactions' => (clone $query)->where('payment_status', 'completed')->count(),
            'pending_transactions' => (clone $query)->where('payment_status', 'pending')->count(),
            'failed_transactions' => (clone $query)->where('payment_status', 'failed')->count(),
            'average_transaction' => $query->avg('amount'),
        ];
    }
}

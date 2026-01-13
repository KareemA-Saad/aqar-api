<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Wallet\Entities\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get paginated wallets with filters
     */
    public function getWallets(array $filters = []): LengthAwarePaginator
    {
        $query = Wallet::with(['user', 'walletSettings']);

        // Search by user name or email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by user_id
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by minimum balance
        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        // Filter by maximum balance
        if (isset($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get wallet by ID
     */
    public function getWalletById(int $id): ?Wallet
    {
        return Wallet::with(['user', 'walletSettings'])->find($id);
    }

    /**
     * Get wallet by user ID
     */
    public function getWalletByUserId(int $userId): ?Wallet
    {
        return Wallet::with('walletSettings')->where('user_id', $userId)->first();
    }

    /**
     * Create or get wallet for user
     */
    public function getOrCreateWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'status' => 1]
        );
    }

    /**
     * Update wallet balance
     */
    public function updateBalance(int $walletId, float $balance): bool
    {
        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            return false;
        }

        return $wallet->update(['balance' => $balance]);
    }

    /**
     * Add funds to wallet
     */
    public function addFunds(int $userId, float $amount, array $transactionData = []): bool
    {
        return DB::transaction(function () use ($userId, $amount, $transactionData) {
            $wallet = $this->getOrCreateWallet($userId);
            
            $newBalance = $wallet->balance + $amount;
            $wallet->update(['balance' => $newBalance]);

            // Create wallet history record if transaction data provided
            if (!empty($transactionData)) {
                app(WalletHistoryService::class)->createHistory(array_merge([
                    'user_id' => $userId,
                    'amount' => $amount,
                ], $transactionData));
            }

            return true;
        });
    }

    /**
     * Deduct funds from wallet
     */
    public function deductFunds(int $userId, float $amount, array $transactionData = []): bool
    {
        return DB::transaction(function () use ($userId, $amount, $transactionData) {
            $wallet = $this->getWalletByUserId($userId);
            
            if (!$wallet || $wallet->balance < $amount) {
                return false;
            }

            $newBalance = $wallet->balance - $amount;
            $wallet->update(['balance' => $newBalance]);

            // Create wallet history record if transaction data provided
            if (!empty($transactionData)) {
                app(WalletHistoryService::class)->createHistory(array_merge([
                    'user_id' => $userId,
                    'amount' => -$amount, // Negative for deduction
                ], $transactionData));
            }

            return true;
        });
    }

    /**
     * Update wallet status
     */
    public function updateStatus(int $walletId, int $status): bool
    {
        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            return false;
        }

        return $wallet->update(['status' => $status]);
    }

    /**
     * Delete wallet
     */
    public function deleteWallet(int $walletId): bool
    {
        $wallet = Wallet::find($walletId);
        if (!$wallet) {
            return false;
        }

        return $wallet->delete();
    }

    /**
     * Bulk delete wallets
     */
    public function bulkDelete(array $walletIds): int
    {
        return Wallet::whereIn('id', $walletIds)->delete();
    }

    /**
     * Bulk activate wallets
     */
    public function bulkActivate(array $walletIds): int
    {
        return Wallet::whereIn('id', $walletIds)->update(['status' => 1]);
    }

    /**
     * Bulk deactivate wallets
     */
    public function bulkDeactivate(array $walletIds): int
    {
        return Wallet::whereIn('id', $walletIds)->update(['status' => 0]);
    }

    /**
     * Get wallet statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_wallets' => Wallet::count(),
            'active_wallets' => Wallet::where('status', 1)->count(),
            'inactive_wallets' => Wallet::where('status', 0)->count(),
            'total_balance' => Wallet::sum('balance'),
            'average_balance' => Wallet::avg('balance'),
            'low_balance_count' => Wallet::where('balance', '<', 10)->count(),
        ];
    }

    /**
     * Get users with low wallet balance
     */
    public function getLowBalanceWallets(float $threshold = 10, int $limit = 10): Collection
    {
        return Wallet::with('user')
            ->where('balance', '<', $threshold)
            ->where('status', 1)
            ->orderBy('balance', 'asc')
            ->limit($limit)
            ->get();
    }
}

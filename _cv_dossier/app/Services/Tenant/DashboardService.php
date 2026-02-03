<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Tenant Dashboard Service
 *
 * Handles all dashboard-related business logic for tenant admin panel including:
 * - Dashboard statistics
 * - Revenue and orders charts
 * - Top products and recent activity
 */
final class DashboardService
{
    /**
     * Get dashboard statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'total_users' => $this->getTotalUsers(),
            'total_orders' => $this->getTotalOrders(),
            'total_revenue' => $this->getTotalRevenue(),
            'pending_orders' => $this->getPendingOrders(),
            'total_products' => $this->getTotalProducts(),
            'low_stock_count' => $this->getLowStockCount(),
            'total_blogs' => $this->getTotalBlogs(),
            'today_orders' => $this->getTodayOrders(),
            'today_revenue' => $this->getTodayRevenue(),
            'this_month_revenue' => $this->getThisMonthRevenue(),
            'growth' => $this->getGrowthPercentages(),
        ];
    }

    /**
     * Get revenue data for charts.
     *
     * @param string $period daily|weekly|monthly|yearly
     * @return array<string, mixed>
     */
    public function getRevenueData(string $period): array
    {
        $data = match ($period) {
            'daily' => $this->getDailyRevenueData(),
            'weekly' => $this->getWeeklyRevenueData(),
            'monthly' => $this->getMonthlyRevenueData(),
            'yearly' => $this->getYearlyRevenueData(),
            default => $this->getMonthlyRevenueData(),
        };

        return [
            'period' => $period,
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data['values'],
                ],
            ],
            'total' => array_sum($data['values']),
            'average' => count($data['values']) > 0 ? array_sum($data['values']) / count($data['values']) : 0,
        ];
    }

    /**
     * Get orders data for charts.
     *
     * @param string $period daily|weekly|monthly|yearly
     * @return array<string, mixed>
     */
    public function getOrdersData(string $period): array
    {
        $data = match ($period) {
            'daily' => $this->getDailyOrdersData(),
            'weekly' => $this->getWeeklyOrdersData(),
            'monthly' => $this->getMonthlyOrdersData(),
            'yearly' => $this->getYearlyOrdersData(),
            default => $this->getMonthlyOrdersData(),
        };

        return [
            'period' => $period,
            'labels' => $data['labels'],
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data['values'],
                ],
            ],
            'total' => array_sum($data['values']),
            'average' => count($data['values']) > 0 ? array_sum($data['values']) / count($data['values']) : 0,
        ];
    }

    /**
     * Get top selling products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopProducts(int $limit = 10): Collection
    {
        if (!$this->tableExists('products') || !$this->tableExists('product_orders')) {
            return collect();
        }

        return DB::table('products')
            ->select([
                'products.id',
                'products.title',
                'products.slug',
                'products.price',
                'products.image_id',
                DB::raw('COALESCE(SUM(product_order_items.quantity), 0) as total_sold'),
                DB::raw('COALESCE(SUM(product_order_items.total_price), 0) as total_revenue'),
            ])
            ->leftJoin('product_order_items', 'products.id', '=', 'product_order_items.product_id')
            ->leftJoin('product_orders', function ($join) {
                $join->on('product_order_items.order_id', '=', 'product_orders.id')
                    ->where('product_orders.payment_status', '=', 'complete');
            })
            ->groupBy('products.id', 'products.title', 'products.slug', 'products.price', 'products.image_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent orders.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentOrders(int $limit = 10): Collection
    {
        if (!$this->tableExists('product_orders')) {
            return collect();
        }

        return DB::table('product_orders')
            ->select([
                'id',
                'name',
                'email',
                'total_amount',
                'payment_status',
                'order_status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivity(int $limit = 20): Collection
    {
        $activities = collect();

        // Recent orders
        if ($this->tableExists('product_orders')) {
            $recentOrders = DB::table('product_orders')
                ->select([
                    DB::raw("'order' as type"),
                    'id',
                    'name as title',
                    'total_amount as amount',
                    'payment_status as status',
                    'created_at',
                ])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
            $activities = $activities->merge($recentOrders);
        }

        // Recent user registrations
        if ($this->tableExists('users')) {
            $recentUsers = DB::table('users')
                ->select([
                    DB::raw("'user_registration' as type"),
                    'id',
                    'name as title',
                    DB::raw('NULL as amount'),
                    DB::raw("'new' as status"),
                    'created_at',
                ])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
            $activities = $activities->merge($recentUsers);
        }

        // Recent blogs
        if ($this->tableExists('blogs')) {
            $recentBlogs = DB::table('blogs')
                ->select([
                    DB::raw("'blog' as type"),
                    'id',
                    'title',
                    DB::raw('NULL as amount'),
                    DB::raw("'published' as status"),
                    'created_at',
                ])
                ->where('status', 1)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
            $activities = $activities->merge($recentBlogs);
        }

        return $activities
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Get products with low stock.
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStockProducts(int $threshold = 10): Collection
    {
        if (!$this->tableExists('products')) {
            return collect();
        }

        $query = DB::table('products')
            ->select([
                'id',
                'title',
                'slug',
                'price',
                'image_id',
            ]);

        // Check if stock_count or inventory_count column exists
        if ($this->columnExists('products', 'stock_count')) {
            $query->addSelect('stock_count')
                ->where('stock_count', '<=', $threshold)
                ->where('stock_count', '>', 0)
                ->orderBy('stock_count');
        } elseif ($this->columnExists('products', 'inventory_count')) {
            $query->addSelect('inventory_count as stock_count')
                ->where('inventory_count', '<=', $threshold)
                ->where('inventory_count', '>', 0)
                ->orderBy('inventory_count');
        } else {
            return collect();
        }

        return $query->get();
    }

    /**
     * Get total users count.
     */
    private function getTotalUsers(): int
    {
        if (!$this->tableExists('users')) {
            return 0;
        }

        return (int) DB::table('users')->count();
    }

    /**
     * Get total orders count.
     */
    private function getTotalOrders(): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')->count();
    }

    /**
     * Get total revenue.
     */
    private function getTotalRevenue(): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get pending orders count.
     */
    private function getPendingOrders(): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->where('payment_status', 'pending')
            ->count();
    }

    /**
     * Get total products count.
     */
    private function getTotalProducts(): int
    {
        if (!$this->tableExists('products')) {
            return 0;
        }

        return (int) DB::table('products')->count();
    }

    /**
     * Get low stock count.
     */
    private function getLowStockCount(): int
    {
        return $this->getLowStockProducts()->count();
    }

    /**
     * Get total blogs count.
     */
    private function getTotalBlogs(): int
    {
        if (!$this->tableExists('blogs')) {
            return 0;
        }

        return (int) DB::table('blogs')->count();
    }

    /**
     * Get today's orders count.
     */
    private function getTodayOrders(): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * Get today's revenue.
     */
    private function getTodayRevenue(): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereDate('created_at', Carbon::today())
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get this month's revenue.
     */
    private function getThisMonthRevenue(): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get growth percentages.
     *
     * @return array<string, float>
     */
    private function getGrowthPercentages(): array
    {
        return [
            'revenue' => $this->calculateRevenueGrowth(),
            'orders' => $this->calculateOrdersGrowth(),
            'users' => $this->calculateUsersGrowth(),
        ];
    }

    /**
     * Calculate revenue growth percentage.
     */
    private function calculateRevenueGrowth(): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        $thisMonth = (float) DB::table('product_orders')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->where('payment_status', 'complete')
            ->sum('total_amount');

        $lastMonth = (float) DB::table('product_orders')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->where('payment_status', 'complete')
            ->sum('total_amount');

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Calculate orders growth percentage.
     */
    private function calculateOrdersGrowth(): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        $thisMonth = DB::table('product_orders')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonth = DB::table('product_orders')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Calculate users growth percentage.
     */
    private function calculateUsersGrowth(): float
    {
        if (!$this->tableExists('users')) {
            return 0.0;
        }

        $thisMonth = DB::table('users')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $lastMonth = DB::table('users')
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Get daily revenue data for the last 30 days.
     */
    private function getDailyRevenueData(): array
    {
        $labels = [];
        $values = [];
        $period = CarbonPeriod::create(Carbon::now()->subDays(29), Carbon::now());

        foreach ($period as $date) {
            $labels[] = $date->format('M d');
            $values[] = $this->getRevenueForDate($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get weekly revenue data for the last 12 weeks.
     */
    private function getWeeklyRevenueData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            $labels[] = 'Week ' . $start->weekOfYear;
            $values[] = $this->getRevenueForPeriod($start, $end);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get monthly revenue data for the last 12 months.
     */
    private function getMonthlyRevenueData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $values[] = $this->getRevenueForMonth($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get yearly revenue data for the last 5 years.
     */
    private function getYearlyRevenueData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 4; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $labels[] = (string) $year;
            $values[] = $this->getRevenueForYear($year);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get daily orders data for the last 30 days.
     */
    private function getDailyOrdersData(): array
    {
        $labels = [];
        $values = [];
        $period = CarbonPeriod::create(Carbon::now()->subDays(29), Carbon::now());

        foreach ($period as $date) {
            $labels[] = $date->format('M d');
            $values[] = $this->getOrdersForDate($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get weekly orders data for the last 12 weeks.
     */
    private function getWeeklyOrdersData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            $labels[] = 'Week ' . $start->weekOfYear;
            $values[] = $this->getOrdersForPeriod($start, $end);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get monthly orders data for the last 12 months.
     */
    private function getMonthlyOrdersData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $values[] = $this->getOrdersForMonth($date);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get yearly orders data for the last 5 years.
     */
    private function getYearlyOrdersData(): array
    {
        $labels = [];
        $values = [];

        for ($i = 4; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $labels[] = (string) $year;
            $values[] = $this->getOrdersForYear($year);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Get revenue for a specific date.
     */
    private function getRevenueForDate(Carbon $date): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereDate('created_at', $date->format('Y-m-d'))
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get revenue for a period.
     */
    private function getRevenueForPeriod(Carbon $start, Carbon $end): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get revenue for a month.
     */
    private function getRevenueForMonth(Carbon $date): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get revenue for a year.
     */
    private function getRevenueForYear(int $year): float
    {
        if (!$this->tableExists('product_orders')) {
            return 0.0;
        }

        return (float) DB::table('product_orders')
            ->whereYear('created_at', $year)
            ->where('payment_status', 'complete')
            ->sum('total_amount');
    }

    /**
     * Get orders count for a specific date.
     */
    private function getOrdersForDate(Carbon $date): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->whereDate('created_at', $date->format('Y-m-d'))
            ->count();
    }

    /**
     * Get orders count for a period.
     */
    private function getOrdersForPeriod(Carbon $start, Carbon $end): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();
    }

    /**
     * Get orders count for a month.
     */
    private function getOrdersForMonth(Carbon $date): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();
    }

    /**
     * Get orders count for a year.
     */
    private function getOrdersForYear(int $year): int
    {
        if (!$this->tableExists('product_orders')) {
            return 0;
        }

        return (int) DB::table('product_orders')
            ->whereYear('created_at', $year)
            ->count();
    }

    /**
     * Check if a table exists in the tenant database.
     */
    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * Check if a column exists in a table.
     */
    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}

<?php

namespace Modules\Product\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Entities\ProductOrder;
use Modules\Product\Entities\OrderProducts;
use Modules\Product\Entities\Cart;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductInventoryDetail;

class OrderService
{
    /**
     * Order status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_CANCELLED = 'cancel';

    /**
     * Payment status constants
     */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    /**
     * Get all orders with filters (admin)
     */
    public function getAllOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductOrder::with(['sale_details', 'getCountry', 'getState', 'shipping']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('payment_track', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['created_at', 'total_amount', 'id', 'status'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get orders for a specific user
     */
    public function getUserOrders(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProductOrder::with(['sale_details', 'getCountry', 'getState'])
            ->where('user_id', $userId);

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get single order by ID
     */
    public function getOrderById(int $id): ProductOrder
    {
        return ProductOrder::with(['sale_details', 'getCountry', 'getState', 'shipping'])
            ->findOrFail($id);
    }

    /**
     * Get single order by payment track
     */
    public function getOrderByTrack(string $track): ProductOrder
    {
        return ProductOrder::with(['sale_details', 'getCountry', 'getState', 'shipping'])
            ->where('payment_track', $track)
            ->firstOrFail();
    }

    /**
     * Create order from cart
     */
    public function createOrderFromCart(Cart $cart, array $customerData, array $paymentData = []): ProductOrder
    {
        return DB::transaction(function () use ($cart, $customerData, $paymentData) {
            // Generate order track number
            $orderTrack = $this->generateOrderTrack();

            // Decode shipping and billing addresses
            $shippingAddress = $cart->shipping_address ?? [];
            
            // Create order
            $order = ProductOrder::create([
                'payment_track' => $orderTrack,
                'user_id' => $cart->user_id,
                'name' => $customerData['name'] ?? $shippingAddress['name'] ?? null,
                'email' => $customerData['email'] ?? $shippingAddress['email'] ?? null,
                'phone' => $customerData['phone'] ?? $shippingAddress['phone'] ?? null,
                'address' => $shippingAddress['address'] ?? null,
                'city' => $shippingAddress['city'] ?? null,
                'state' => $shippingAddress['state'] ?? null,
                'country' => $shippingAddress['country_id'] ?? null,
                'zipcode' => $shippingAddress['postal_code'] ?? null,
                'message' => $customerData['notes'] ?? $cart->notes ?? null,

                'coupon' => $cart->coupon_code,
                'coupon_discounted' => $cart->discount_amount,
                'total_amount' => $cart->total,

                'status' => self::STATUS_PENDING,
                'payment_status' => self::PAYMENT_PENDING,
                'payment_gateway' => $paymentData['gateway'] ?? 'pending',
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'checkout_type' => $paymentData['checkout_type'] ?? 'online',

                'shipping_address_id' => $customerData['shipping_address_id'] ?? null,
                'selected_shipping_option' => json_encode([
                    'method_id' => $cart->shipping_method_id,
                    'cost' => $cart->shipping_cost,
                ]),

                'order_details' => json_encode([
                    'subtotal' => $cart->subtotal,
                    'discount' => $cart->discount_amount,
                    'shipping' => $cart->shipping_cost,
                    'items_count' => $cart->items_count,
                ]),
            ]);

            // Create order items and update inventory
            foreach ($cart->items as $item) {
                // Create order item
                $this->createOrderItem($order, $item);

                // Decrease stock
                $this->decreaseStock($item->product_id, $item->quantity, $item->variant_id);
            }

            // Clear the cart
            $cart->items()->delete();
            $cart->update([
                'coupon_code' => null,
                'discount_amount' => 0,
                'discount_type' => null,
                'shipping_method_id' => null,
                'shipping_cost' => 0,
                'shipping_address' => null,
                'billing_address' => null,
            ]);

            return $order->fresh(['sale_details']);
        });
    }

    /**
     * Create order item
     */
    protected function createOrderItem(ProductOrder $order, $cartItem): void
    {
        $variant = null;
        $variantInfo = null;

        if ($cartItem->variant_id) {
            $variant = ProductInventoryDetail::with(['productColor', 'productSize'])
                ->find($cartItem->variant_id);

            if ($variant) {
                $variantInfo = [
                    'color' => $variant->productColor?->name,
                    'size' => $variant->productSize?->name,
                ];
            }
        }

        // Check if there's a SaleDetails model or similar
        if (class_exists(\Modules\Product\Entities\SaleDetails::class)) {
            \Modules\Product\Entities\SaleDetails::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product?->name,
                'variant' => json_encode($variantInfo),
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
            ]);
        }
    }

    /**
     * Decrease product stock
     */
    protected function decreaseStock(int $productId, int $quantity, ?int $variantId = null): void
    {
        if ($variantId) {
            ProductInventoryDetail::where('id', $variantId)
                ->decrement('stock_count', $quantity);

            ProductInventoryDetail::where('id', $variantId)
                ->increment('sold_count', $quantity);
        } else {
            $product = Product::with('inventory')->find($productId);
            if ($product && $product->inventory) {
                $product->inventory->decrement('stock_count', $quantity);
                $product->inventory->increment('sold_count', $quantity);
            }
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): ProductOrder
    {
        $order = ProductOrder::findOrFail($orderId);

        $validStatuses = [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETE,
            self::STATUS_CANCELLED,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid order status: {$status}");
        }

        // If cancelling, restore stock
        if ($status === self::STATUS_CANCELLED && $order->status !== self::STATUS_CANCELLED) {
            $this->restoreStock($order);
        }

        $order->update(['status' => $status]);

        return $order->fresh();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $orderId, string $status, ?string $transactionId = null): ProductOrder
    {
        $order = ProductOrder::findOrFail($orderId);

        $validStatuses = [
            self::PAYMENT_PENDING,
            self::PAYMENT_PAID,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED,
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid payment status: {$status}");
        }

        $updateData = ['payment_status' => $status];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        // If payment successful, update order status to in_progress
        if ($status === self::PAYMENT_PAID && $order->status === self::STATUS_PENDING) {
            $updateData['status'] = self::STATUS_IN_PROGRESS;
        }

        $order->update($updateData);

        return $order->fresh();
    }

    /**
     * Restore stock on order cancellation
     */
    protected function restoreStock(ProductOrder $order): void
    {
        foreach ($order->sale_details as $item) {
            $variantInfo = json_decode($item->variant, true);

            if (!empty($variantInfo)) {
                // Find variant and restore stock
                $variant = ProductInventoryDetail::where('product_id', $item->product_id)
                    ->first();

                if ($variant) {
                    $variant->increment('stock_count', $item->quantity);
                    $variant->decrement('sold_count', min($item->quantity, $variant->sold_count));
                }
            } else {
                $product = Product::with('inventory')->find($item->product_id);
                if ($product && $product->inventory) {
                    $product->inventory->increment('stock_count', $item->quantity);
                    $product->inventory->decrement('sold_count', min($item->quantity, $product->inventory->sold_count));
                }
            }
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, ?string $reason = null): ProductOrder
    {
        $order = ProductOrder::findOrFail($orderId);

        if (in_array($order->status, [self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_COMPLETE])) {
            throw new \InvalidArgumentException('Cannot cancel order that has been shipped or completed');
        }

        return DB::transaction(function () use ($order, $reason) {
            // Restore stock
            $this->restoreStock($order);

            // Update order
            $order->update([
                'status' => self::STATUS_CANCELLED,
                'message' => $reason ? ($order->message . "\nCancellation reason: " . $reason) : $order->message,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics(): array
    {
        $stats = ProductOrder::selectRaw('
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN status = "complete" THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = "cancel" THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_orders
        ')->first();

        return [
            'total_orders' => $stats->total_orders ?? 0,
            'total_revenue' => round($stats->total_revenue ?? 0, 2),
            'pending_orders' => $stats->pending_orders ?? 0,
            'processing_orders' => $stats->processing_orders ?? 0,
            'completed_orders' => $stats->completed_orders ?? 0,
            'cancelled_orders' => $stats->cancelled_orders ?? 0,
            'paid_orders' => $stats->paid_orders ?? 0,
        ];
    }

    /**
     * Generate unique order track number
     */
    protected function generateOrderTrack(): string
    {
        do {
            $track = 'ORD-' . strtoupper(Str::random(8)) . '-' . time();
        } while (ProductOrder::where('payment_track', $track)->exists());

        return $track;
    }
}

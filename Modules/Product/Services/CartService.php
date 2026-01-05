<?php

namespace Modules\Product\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Product\Entities\Cart;
use Modules\Product\Entities\CartItem;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductInventoryDetail;
use Modules\CouponManage\Entities\Coupon;

class CartService
{
    /**
     * Cart instance
     */
    protected ?Cart $cart = null;

    /**
     * Get cart identifier from request
     */
    public function getCartIdentifier(): array
    {
        // Priority 1: Authenticated tenant user
        if ($userId = auth('api_tenant_user')->id()) {
            return ['type' => 'user', 'user_id' => $userId, 'cart_token' => null];
        }

        // Priority 2: Existing guest token from header
        if ($cartToken = request()->header('X-Cart-Token')) {
            return ['type' => 'guest', 'user_id' => null, 'cart_token' => $cartToken];
        }

        // Priority 3: Create new guest token
        $newToken = Str::uuid()->toString();
        return ['type' => 'guest', 'user_id' => null, 'cart_token' => $newToken, 'is_new' => true];
    }

    /**
     * Get or create cart for current user/guest
     */
    public function getOrCreateCart(): Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        $identifier = $this->getCartIdentifier();

        $query = Cart::query();

        if ($identifier['user_id']) {
            $query->where('user_id', $identifier['user_id']);
        } else {
            $query->where('cart_token', $identifier['cart_token']);
        }

        $cart = $query->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $identifier['user_id'],
                'cart_token' => $identifier['cart_token'],
                'expires_at' => $identifier['user_id'] ? null : now()->addDays(30),
            ]);
        }

        // Clean up expired guest carts
        if ($cart->isGuest() && $cart->isExpired()) {
            $cart->items()->delete();
            $cart->delete();

            return $this->getOrCreateCart();
        }

        $this->cart = $cart;
        return $this->cart;
    }

    /**
     * Get current cart (without creating)
     */
    public function getCart(): ?Cart
    {
        $identifier = $this->getCartIdentifier();

        if (isset($identifier['is_new'])) {
            return null;
        }

        $query = Cart::with(['items.product', 'items.variant']);

        if ($identifier['user_id']) {
            return $query->where('user_id', $identifier['user_id'])->first();
        }

        return $query->where('cart_token', $identifier['cart_token'])->first();
    }

    /**
     * Add item to cart
     */
    public function addItem(int $productId, int $quantity = 1, ?int $variantId = null, array $options = []): CartItem
    {
        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($productId);

        // Determine price
        $price = $this->getItemPrice($product, $variantId);

        // Check if item already exists in cart
        $existingItem = $cart->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();

        if ($existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;

            // Validate stock
            $this->validateStock($productId, $variantId, $newQuantity);

            // Validate min/max purchase
            $this->validatePurchaseLimits($product, $newQuantity);

            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price' => $price,
            ]);

            return $existingItem->fresh();
        }

        // Validate stock for new item
        $this->validateStock($productId, $variantId, $quantity);

        // Validate min/max purchase
        $this->validatePurchaseLimits($product, $quantity);

        return $cart->items()->create([
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price' => $price,
            'total_price' => $price * $quantity,
            'options' => $options,
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(int $itemId, int $quantity): CartItem
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();
            throw new \InvalidArgumentException('Item removed from cart');
        }

        // Validate stock
        $this->validateStock($item->product_id, $item->variant_id, $quantity);

        // Validate min/max purchase
        $this->validatePurchaseLimits($item->product, $quantity);

        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $itemId): bool
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->findOrFail($itemId);

        return $item->delete();
    }

    /**
     * Clear all items from cart
     */
    public function clearCart(): bool
    {
        $cart = $this->getCart();

        if (!$cart) {
            return true;
        }

        $cart->items()->delete();
        $cart->update([
            'coupon_code' => null,
            'discount_amount' => 0,
            'discount_type' => null,
            'shipping_method_id' => null,
            'shipping_cost' => 0,
        ]);

        return true;
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(string $couponCode): array
    {
        $cart = $this->getOrCreateCart();

        $coupon = Coupon::where('code', $couponCode)
            ->where('status', 1)
            ->first();

        if (!$coupon) {
            throw new \InvalidArgumentException(__('Invalid or expired coupon code'));
        }

        // Check if coupon is valid
        if ($coupon->expire_date && $coupon->expire_date < now()) {
            throw new \InvalidArgumentException(__('Coupon has expired'));
        }

        if ($coupon->max_use && $coupon->used_count >= $coupon->max_use) {
            throw new \InvalidArgumentException(__('Coupon usage limit reached'));
        }

        if ($coupon->min_amount && $cart->subtotal < $coupon->min_amount) {
            throw new \InvalidArgumentException(__('Minimum order amount not met for this coupon'));
        }

        // Calculate discount
        $discountAmount = 0;
        if ($coupon->discount_type === 'percentage') {
            $discountAmount = ($cart->subtotal * $coupon->discount) / 100;
            if ($coupon->max_discount && $discountAmount > $coupon->max_discount) {
                $discountAmount = $coupon->max_discount;
            }
        } else {
            $discountAmount = min($coupon->discount, $cart->subtotal);
        }

        $cart->update([
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
            'discount_type' => $coupon->discount_type,
        ]);

        return [
            'coupon_code' => $couponCode,
            'discount_type' => $coupon->discount_type,
            'discount_amount' => $discountAmount,
        ];
    }

    /**
     * Remove coupon from cart
     */
    public function removeCoupon(): bool
    {
        $cart = $this->getCart();

        if (!$cart) {
            return true;
        }

        $cart->update([
            'coupon_code' => null,
            'discount_amount' => 0,
            'discount_type' => null,
        ]);

        return true;
    }

    /**
     * Set shipping address
     */
    public function setShippingAddress(array $address): Cart
    {
        $cart = $this->getOrCreateCart();

        $cart->update(['shipping_address' => $address]);

        return $cart->fresh();
    }

    /**
     * Set billing address
     */
    public function setBillingAddress(array $address): Cart
    {
        $cart = $this->getOrCreateCart();

        $cart->update(['billing_address' => $address]);

        return $cart->fresh();
    }

    /**
     * Set shipping method
     */
    public function setShippingMethod(int $shippingMethodId, float $cost): Cart
    {
        $cart = $this->getOrCreateCart();

        $cart->update([
            'shipping_method_id' => $shippingMethodId,
            'shipping_cost' => $cost,
        ]);

        return $cart->fresh();
    }

    /**
     * Merge guest cart into user cart on login
     */
    public function mergeGuestCart(string $guestToken, int $userId): Cart
    {
        $guestCart = Cart::where('cart_token', $guestToken)->first();
        $userCart = Cart::where('user_id', $userId)->first();

        if (!$guestCart) {
            return $userCart ?? Cart::create(['user_id' => $userId]);
        }

        if (!$userCart) {
            // Transfer guest cart to user
            $guestCart->update([
                'user_id' => $userId,
                'cart_token' => null,
                'expires_at' => null,
            ]);

            return $guestCart;
        }

        // Merge items from guest cart to user cart
        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->where('variant_id', $guestItem->variant_id)
                    ->first();

                if ($existingItem) {
                    // Update quantity
                    $existingItem->update([
                        'quantity' => $existingItem->quantity + $guestItem->quantity,
                    ]);
                } else {
                    // Move item to user cart
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }

            // Delete guest cart
            $guestCart->delete();
        });

        return $userCart->fresh(['items']);
    }

    /**
     * Get cart summary
     */
    public function getCartSummary(): array
    {
        $cart = $this->getCart();

        if (!$cart) {
            return [
                'items_count' => 0,
                'subtotal' => 0,
                'discount' => 0,
                'shipping' => 0,
                'total' => 0,
            ];
        }

        return [
            'items_count' => $cart->items_count,
            'subtotal' => round($cart->subtotal, 2),
            'discount' => round($cart->discount_amount, 2),
            'shipping' => round($cart->shipping_cost, 2),
            'total' => round($cart->total, 2),
            'coupon_code' => $cart->coupon_code,
        ];
    }

    /**
     * Get item price (considering variant and sale price)
     */
    protected function getItemPrice(Product $product, ?int $variantId): float
    {
        $basePrice = $product->sale_price ?? $product->price;

        if ($variantId) {
            $variant = ProductInventoryDetail::find($variantId);
            if ($variant && $variant->additional_price) {
                $basePrice += $variant->additional_price;
            }
        }

        return $basePrice;
    }

    /**
     * Validate stock availability
     */
    protected function validateStock(int $productId, ?int $variantId, int $quantity): void
    {
        if ($variantId) {
            $variant = ProductInventoryDetail::findOrFail($variantId);
            if ($variant->stock_count < $quantity) {
                throw new \InvalidArgumentException(
                    __('Insufficient stock. Only :available available.', ['available' => $variant->stock_count])
                );
            }
        } else {
            $product = Product::with('inventory')->findOrFail($productId);
            if ($product->inventory && $product->inventory->stock_count < $quantity) {
                throw new \InvalidArgumentException(
                    __('Insufficient stock. Only :available available.', ['available' => $product->inventory->stock_count])
                );
            }
        }
    }

    /**
     * Validate min/max purchase limits
     */
    protected function validatePurchaseLimits(Product $product, int $quantity): void
    {
        if ($product->min_purchase && $quantity < $product->min_purchase) {
            throw new \InvalidArgumentException(
                __('Minimum purchase quantity is :min', ['min' => $product->min_purchase])
            );
        }

        if ($product->max_purchase && $quantity > $product->max_purchase) {
            throw new \InvalidArgumentException(
                __('Maximum purchase quantity is :max', ['max' => $product->max_purchase])
            );
        }
    }

    /**
     * Get new cart token for response header
     */
    public function getNewCartToken(): ?string
    {
        $identifier = $this->getCartIdentifier();

        if (isset($identifier['is_new'])) {
            return $identifier['cart_token'];
        }

        return null;
    }
}

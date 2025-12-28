<?php

declare(strict_types=1);

namespace Modules\Product\Services\Payment;

use Illuminate\Http\Request;
use Modules\Product\Entities\ProductOrder;

/**
 * Payment Gateway Interface
 *
 * Contract for all payment gateway implementations.
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Get the gateway display name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if the gateway is configured and available.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Process a payment for an order.
     *
     * @param ProductOrder $order
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function processPayment(ProductOrder $order, array $data): array;

    /**
     * Handle webhook/IPN from the payment gateway.
     *
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function handleWebhook(Request $request): void;

    /**
     * Verify a payment status.
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array;

    /**
     * Refund a payment.
     *
     * @param ProductOrder $order
     * @param float|null $amount
     * @return array
     * @throws \Exception
     */
    public function refund(ProductOrder $order, ?float $amount = null): array;
}

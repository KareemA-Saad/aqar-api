<?php

declare(strict_types=1);

namespace Modules\Product\Services\Payment;

use InvalidArgumentException;

/**
 * Payment Gateway Factory
 *
 * Creates payment gateway instances based on the gateway identifier.
 */
class PaymentGatewayFactory
{
    /**
     * Registered gateway classes.
     *
     * @var array<string, class-string<PaymentGatewayInterface>>
     */
    protected static array $gateways = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'cod' => CashOnDeliveryGateway::class,
    ];

    /**
     * Create a payment gateway instance.
     *
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public static function create(string $gateway): PaymentGatewayInterface
    {
        $gateway = strtolower($gateway);

        if (!isset(self::$gateways[$gateway])) {
            throw new InvalidArgumentException("Payment gateway [{$gateway}] is not supported.");
        }

        $gatewayClass = self::$gateways[$gateway];
        return app($gatewayClass);
    }

    /**
     * Register a new gateway.
     *
     * @param string $identifier
     * @param class-string<PaymentGatewayInterface> $gatewayClass
     * @return void
     */
    public static function register(string $identifier, string $gatewayClass): void
    {
        self::$gateways[strtolower($identifier)] = $gatewayClass;
    }

    /**
     * Get all registered gateways.
     *
     * @return array
     */
    public static function getRegisteredGateways(): array
    {
        return self::$gateways;
    }

    /**
     * Check if a gateway is registered.
     *
     * @param string $gateway
     * @return bool
     */
    public static function isRegistered(string $gateway): bool
    {
        return isset(self::$gateways[strtolower($gateway)]);
    }

    /**
     * Get all available (configured) gateways.
     *
     * @return array
     */
    public static function getAvailableGateways(): array
    {
        $available = [];

        foreach (self::$gateways as $identifier => $class) {
            try {
                $gateway = self::create($identifier);
                if ($gateway->isAvailable()) {
                    $available[$identifier] = [
                        'id' => $gateway->getIdentifier(),
                        'name' => $gateway->getName(),
                    ];
                }
            } catch (\Exception $e) {
                // Gateway not available
            }
        }

        return $available;
    }
}

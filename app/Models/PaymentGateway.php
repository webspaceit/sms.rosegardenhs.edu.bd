<?php
declare(strict_types=1);

namespace App\Models;

/**
 * A payment gateway the school can accept money through.
 */
final class PaymentGateway
{
    public static function module(): array
    {
        return [
            'table' => 'payment_gateways',
            'route' => '/payment-gateways',
            'title' => 'Payment Gateways',
            'singular' => 'gateway',
            'permission' => 'settings.manage',
            'managePermission' => 'settings.manage',
            'columns' => [
                ['name' => 'name', 'label' => 'Name'],
                ['name' => 'provider_key', 'label' => 'Provider key', 'format' => 'muted'],
                ['name' => 'merchant_id', 'label' => 'Merchant id', 'format' => 'muted'],
                ['name' => 'is_active', 'label' => 'Status', 'format' => 'bool', 'on' => 'Active', 'off' => 'Inactive'],
            ],
            'fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'max' => 150],
                ['name' => 'provider_key', 'label' => 'Provider key', 'type' => 'text', 'required' => true, 'max' => 100, 'hint' => 'For example bkash, nagad, rocket.'],
                ['name' => 'api_key', 'label' => 'API key', 'type' => 'text', 'max' => 255],
                ['name' => 'api_secret', 'label' => 'API secret', 'type' => 'text', 'max' => 255],
                ['name' => 'merchant_id', 'label' => 'Merchant id', 'type' => 'text', 'max' => 100],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checkboxLabel' => 'This gateway is live', 'default' => 0],
            ],
            'search' => ['name', 'provider_key', 'merchant_id'],
            'order' => 'name ASC',
        ];
    }
}

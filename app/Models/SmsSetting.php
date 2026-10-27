<?php
declare(strict_types=1);

namespace App\Models;

/**
 * An SMS provider the school sends notifications through.
 */
final class SmsSetting
{
    public static function module(): array
    {
        return [
            'table' => 'sms_settings',
            'route' => '/sms-settings',
            'title' => 'SMS Integration',
            'singular' => 'provider',
            'permission' => 'settings.manage',
            'managePermission' => 'settings.manage',
            'columns' => [
                ['name' => 'provider', 'label' => 'Provider'],
                ['name' => 'sender_id', 'label' => 'Sender id', 'format' => 'muted'],
                ['name' => 'api_base_url', 'label' => 'API endpoint', 'format' => 'muted'],
                ['name' => 'is_active', 'label' => 'Status', 'format' => 'bool', 'on' => 'Active', 'off' => 'Inactive'],
            ],
            'fields' => [
                ['name' => 'provider', 'label' => 'Provider', 'type' => 'text', 'required' => true, 'max' => 100, 'hint' => 'For example rocept, bulksmsbd.'],
                ['name' => 'api_key', 'label' => 'API key', 'type' => 'text', 'max' => 255],
                ['name' => 'api_secret', 'label' => 'API secret', 'type' => 'text', 'max' => 255],
                ['name' => 'sender_id', 'label' => 'Sender id', 'type' => 'text', 'max' => 50],
                ['name' => 'api_base_url', 'label' => 'API endpoint', 'type' => 'text', 'max' => 255],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checkboxLabel' => 'Use this provider for messages', 'default' => 0],
            ],
            'search' => ['provider', 'sender_id'],
            'order' => 'is_active DESC, provider ASC',
        ];
    }
}

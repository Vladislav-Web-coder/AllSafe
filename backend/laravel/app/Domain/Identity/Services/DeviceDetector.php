<?php

namespace App\Domain\Identity\Services;

use Jenssegers\Agent\Agent;

class DeviceDetector
{
    /**
     * Определяет устройство и браузер по User-Agent.
     */
    public function detect(?string $userAgent): array
    {
        if (! $userAgent) {
            return [
                'device' => 'Неизвестное устройство',
                'browser' => 'Неизвестный браузер',
                'platform' => 'Неизвестная ОС',
                'icon' => '💻',
            ];
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $browser = $agent->browser();
        $version = $agent->version($browser);
        $platform = $agent->platform();
        $device = $agent->device();

        // Определяем тип устройства
        $deviceType = 'desktop';
        $icon = '💻';

        if ($agent->isMobile()) {
            $deviceType = 'mobile';
            $icon = '📱';
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
            $icon = '📱';
        }

        // Формируем название устройства
        $deviceName = $device !== 'Unknown' ? $device : null;

        if ($deviceType === 'mobile' || $deviceType === 'tablet') {
            $deviceName = $deviceName ?: ($agent->isTablet() ? 'Планшет' : 'Мобильный телефон');
        } else {
            $deviceName = $platform ?: 'Компьютер';
        }

        // Формируем название браузера
        $browserName = $browser !== 'Unknown'
            ? "{$browser} {$version}"
            : 'Неизвестный браузер';

        return [
            'device' => $deviceName,
            'browser' => $browserName,
            'platform' => $platform,
            'device_type' => $deviceType,
            'icon' => $icon,
        ];
    }

    /**
     * Возвращает краткое описание для отображения.
     */
    public function getDisplayName(?string $userAgent): string
    {
        $info = $this->detect($userAgent);

        if ($info['device_type'] === 'mobile' || $info['device_type'] === 'tablet') {
            return "{$info['device']} — {$info['browser']}";
        }

        return "{$info['platform']} — {$info['browser']}";
    }
}

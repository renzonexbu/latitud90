<?php

namespace App\Helpers;

use App\Models\TermCondition;
use Illuminate\Http\Request;

class BuyerTrackingHelper
{
    /**
     * Extrae toda la información de tracking del comprador desde el request
     */
    public static function extractTrackingInfo(?Request $request = null): array
    {
        $request = $request ?? request();
        $userAgent = $request->userAgent() ?? '';

        // Obtener la versión actual de T&C
        $currentTermsCondition = TermCondition::getCurrentVersion();

        $trackingInfo = [
            'ip_address' => self::getClientIp($request),
            'user_agent' => $userAgent,
            'device_type' => self::detectDeviceType($userAgent),
            'browser' => self::detectBrowser($userAgent),
            'operating_system' => self::detectOS($userAgent),
            'geo_country' => null,
            'geo_region' => null,
            'geo_city' => null,
            'referrer_url' => $request->headers->get('referer'),
            'utm_source' => $request->input('utm_source') ?? $request->session()->get('utm_source'),
            'utm_medium' => $request->input('utm_medium') ?? $request->session()->get('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign') ?? $request->session()->get('utm_campaign'),
            'terms_accepted_at' => now(),
            'terms_condition_id' => $currentTermsCondition?->id,
        ];

        // Intentar obtener geolocalización por IP
        $geoInfo = self::getGeoLocationFromIp($trackingInfo['ip_address']);
        if ($geoInfo) {
            $trackingInfo['geo_country'] = $geoInfo['country'] ?? null;
            $trackingInfo['geo_region'] = $geoInfo['region'] ?? null;
            $trackingInfo['geo_city'] = $geoInfo['city'] ?? null;
        }

        return $trackingInfo;
    }

    /**
     * Detecta el tipo de dispositivo desde el User Agent
     */
    private static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        // Tablets primero (algunos tablets tienen "mobile" en el UA)
        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }

        // Mobile
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detecta el navegador desde el User Agent
     */
    private static function detectBrowser(string $userAgent): ?string
    {
        $browsers = [
            'Edge' => '/edg/i',
            'Opera' => '/opera|opr/i',
            'Chrome' => '/chrome/i',
            'Safari' => '/safari/i',
            'Firefox' => '/firefox/i',
            'IE' => '/msie|trident/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return null;
    }

    /**
     * Detecta el sistema operativo desde el User Agent
     */
    private static function detectOS(string $userAgent): ?string
    {
        $os = [
            'Windows' => '/windows/i',
            'macOS' => '/macintosh|mac os/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i',
        ];

        foreach ($os as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Obtiene la IP real del cliente considerando proxies y load balancers
     */
    public static function getClientIp(?Request $request = null): ?string
    {
        $request = $request ?? request();

        // Lista de headers a revisar en orden de prioridad
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);
            if ($ip) {
                // X-Forwarded-For puede tener múltiples IPs separadas por coma
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // Fallback al método de Laravel
        return $request->ip();
    }

    /**
     * Obtiene información de geolocalización basada en IP
     * Usa ip-api.com (gratis hasta 45 requests/minuto)
     */
    public static function getGeoLocationFromIp(?string $ip): ?array
    {
        if (!$ip || $ip === '127.0.0.1' || $ip === '::1') {
            return null;
        }

        // No hacer lookup para IPs privadas
        if (self::isPrivateIp($ip)) {
            return null;
        }

        try {
            // Usar cache para evitar requests repetidos
            $cacheKey = 'geo_ip_' . md5($ip);
            $cached = cache()->get($cacheKey);
            if ($cached) {
                return $cached;
            }

            // Llamar a ip-api.com (servicio gratuito)
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city");

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $geoInfo = [
                        'country' => $data['country'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                    ];

                    // Cachear por 24 horas
                    cache()->put($cacheKey, $geoInfo, now()->addHours(24));

                    return $geoInfo;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('BuyerTrackingHelper: Error getting geolocation', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Verifica si una IP es privada
     */
    private static function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}

<?php

namespace App\Services;

class ShippingService
{
    public const ZONE_LOCAL = 'local';

    public const ZONE_STANDARD = 'standard';

    public const ZONE_REMOTE = 'remote';

    /**
     * Resolve the delivery zone for a city. An empty city returns the
     * standard zone so checkout can show an estimate before entry.
     */
    public function zoneForCity(?string $city): string
    {
        $normalized = $this->normalize($city);

        if ($normalized === '') {
            return self::ZONE_STANDARD;
        }

        if ($this->matchesAny($normalized, config('shop.shipping_zones.local', []))) {
            return self::ZONE_LOCAL;
        }

        if ($this->matchesAny($normalized, config('shop.shipping_zones.standard', []))) {
            return self::ZONE_STANDARD;
        }

        return self::ZONE_REMOTE;
    }

    public function rateForCity(?string $city): float
    {
        return $this->rateForZone($this->zoneForCity($city));
    }

    public function rateForZone(string $zone): float
    {
        return match ($zone) {
            self::ZONE_LOCAL => (float) shop_config('shipping_local', config('shop.shipping_local')),
            self::ZONE_REMOTE => (float) shop_config('shipping_remote', config('shop.shipping_remote')),
            // Fall back to the legacy flat rate so an admin's previously
            // saved "Flat Shipping" setting keeps working after upgrade.
            default => (float) shop_config('shipping_standard', shop_config('shipping_flat', config('shop.shipping_standard'))),
        };
    }

    public function zoneLabel(?string $city): string
    {
        return match ($this->zoneForCity($city)) {
            self::ZONE_LOCAL => 'Karachi (local delivery)',
            self::ZONE_REMOTE => 'Nationwide delivery',
            default => 'Major city delivery',
        };
    }

    protected function normalize(?string $city): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower((string) $city)));
    }

    /**
     * A zone city matches when it equals the input or appears inside it,
     * so "Karachi Cantt" and "North Karachi" both resolve to local.
     */
    protected function matchesAny(string $normalized, array $cities): bool
    {
        foreach ($cities as $zoneCity) {
            $zoneCity = $this->normalize($zoneCity);

            if ($zoneCity !== '' && str_contains($normalized, $zoneCity)) {
                return true;
            }
        }

        return false;
    }
}

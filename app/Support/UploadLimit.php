<?php

namespace App\Support;

class UploadLimit
{
    public static function parseIniBytes(string|false|null $value): int
    {
        if ($value === false || $value === null) {
            return 0;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    public static function serverMaxBytes(): int
    {
        $limits = array_filter([
            self::parseIniBytes(ini_get('upload_max_filesize')),
            self::parseIniBytes(ini_get('post_max_size')),
        ], fn (int $bytes) => $bytes > 0);

        return $limits === [] ? 0 : min($limits);
    }

    public static function serverMaxMb(): ?int
    {
        $bytes = self::serverMaxBytes();

        return $bytes > 0 ? (int) floor($bytes / 1024 / 1024) : null;
    }

    public static function effectiveMb(int $configuredMb): int
    {
        $configuredMb = max(1, $configuredMb);
        $serverMb = self::serverMaxMb();

        return $serverMb === null ? $configuredMb : max(1, min($configuredMb, $serverMb));
    }
}

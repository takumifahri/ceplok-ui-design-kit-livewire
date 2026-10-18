<?php

namespace CeplokUi\DesignKit\Support;

use BackedEnum;
use InvalidArgumentException;

/**
 * Pemeriksa prop untuk komponen anonim.
 *
 * `@props` di Blade tidak bertipe, dan belum ada analyzer statis yang membaca
 * props komponen anonim. Padanan union type milik kit React dipasang di sini,
 * saat render: nilai yang tidak dikenal langsung menjadi error yang menyebut
 * komponen, prop, dan pilihan yang sah selama `app.debug` aktif (lokal dan
 * tes). Di production halamannya tidak ikut gagal: komponen memakai nilai
 * bawaannya dan kesalahannya dilaporkan ke log lewat `report()`.
 */
final class Prop
{
    /**
     * Memastikan nilai prop salah satu kasus enum, mis. `variant` Badge.
     * Menerima kasus enum-nya langsung atau nilai string-nya.
     *
     * @param  class-string<BackedEnum>  $enum
     */
    public static function oneOf(string $enum, mixed $value, BackedEnum $default, string $component, string $prop): string
    {
        if ($value instanceof $enum) {
            return (string) $value->value;
        }

        if ((is_string($value) || is_int($value)) && ($case = $enum::tryFrom($value)) !== null) {
            return (string) $case->value;
        }

        $allowed = implode(', ', array_map(fn (BackedEnum $case) => var_export($case->value, true), $enum::cases()));

        return self::fail(
            "Prop `{$prop}` pada <x-ceplok::{$component}> harus salah satu dari {$allowed}; diberikan ".self::describe($value).'.',
            (string) $default->value,
        );
    }

    /**
     * Memastikan nilai prop boolean.
     *
     * Atribut statis selalu tiba sebagai string, jadi `dismissible="false"`
     * bernilai "false" — truthy di PHP — dan diam-diam berarti kebalikannya.
     * String "true"/"false" (juga "1"/"0") diterima apa adanya; selain itu
     * ditolak supaya salah ketik tidak lolos.
     */
    public static function bool(mixed $value, bool $default, string $component, string $prop): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $parsed = match (is_string($value) || is_int($value) ? strtolower((string) $value) : null) {
            'true', '1' => true,
            'false', '0' => false,
            default => null,
        };

        if ($parsed !== null) {
            return $parsed;
        }

        return self::fail(
            "Prop `{$prop}` pada <x-ceplok::{$component}> harus boolean, mis. :{$prop}=\"true\"; diberikan ".self::describe($value).'.',
            $default,
        );
    }

    /**
     * Memastikan nilai prop bilangan bulat, mis. `current-page` Pagination.
     * String angka dari atribut statis ("3") diterima.
     */
    public static function int(mixed $value, int $default, string $component, string $prop, ?int $min = null): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if (is_int($parsed) && ($min === null || $parsed >= $min)) {
            return $parsed;
        }

        $minimum = $min === null ? '' : " minimal {$min}";

        return self::fail(
            "Prop `{$prop}` pada <x-ceplok::{$component}> harus bilangan bulat{$minimum}; diberikan ".self::describe($value).'.',
            $default,
        );
    }

    /**
     * Memastikan nilai prop nama tag HTML, mis. `as` pada Container. Nama tag
     * dicetak langsung ke markup, jadi hanya huruf kecil, angka, dan tanda
     * hubung yang diterima.
     */
    public static function tag(mixed $value, string $default, string $component, string $prop): string
    {
        if (is_string($value) && preg_match('/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', $value) === 1) {
            return $value;
        }

        return self::fail(
            "Prop `{$prop}` pada <x-ceplok::{$component}> harus nama tag HTML, mis. \"section\"; diberikan ".self::describe($value).'.',
            $default,
        );
    }

    /**
     * Kombinasi prop yang tidak sah, mis. baris aksi dan baris pilihan yang
     * dicampur dalam satu Dropdown: error saat `app.debug` aktif, laporan ke
     * log di production. Komponennya sendiri yang memutuskan cara melanjutkan.
     */
    public static function violation(string $message): void
    {
        self::fail($message, null);
    }

    /**
     * @template T
     *
     * @param  T  $default
     * @return T
     */
    private static function fail(string $message, mixed $default): mixed
    {
        $exception = new InvalidArgumentException($message);

        if (config('app.debug')) {
            throw $exception;
        }

        report($exception);

        return $default;
    }

    private static function describe(mixed $value): string
    {
        return match (true) {
            $value instanceof BackedEnum => $value::class.'::'.$value->name,
            is_object($value) => 'objek '.$value::class,
            is_array($value) => 'array',
            default => var_export($value, true),
        };
    }
}

<?php

namespace CeplokUi\DesignKit\Support;

use BackedEnum;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Component;
use Livewire\Livewire;
use stdClass;

/**
 * Nilai awal komponen yang diikat `wire:model`.
 *
 * Livewire dan Alpine baru menyelaraskan elemen dengan properti setelah
 * halaman berjalan. Tanpa ini, server merender Alert, Badge, dan Toast selalu
 * terbuka dan checkbox selalu kosong, sehingga keadaan sebenarnya baru muncul
 * sekejap kemudian. Di sini nilainya dibaca langsung dari komponen Livewire
 * yang sedang dirender, jadi HTML pertama sudah benar tanpa perlu menulis
 * `:open` atau `checked` di samping `wire:model`.
 */
final class Bound
{
    /** Penanda "tidak diikat", supaya properti bernilai null tetap terbedakan. */
    private static ?stdClass $missing = null;

    /** Apakah komponennya diikat lewat wire:model atau x-model. */
    public static function has(ComponentAttributeBag $attributes): bool
    {
        return $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();
    }

    /**
     * Nilai boolean properti yang diikat `wire:model`, atau `$default` bila
     * tidak sedang dirender oleh komponen Livewire (mis. x-model biasa) atau
     * propertinya tidak ditemukan.
     */
    public static function bool(ComponentAttributeBag $attributes, bool $default): bool
    {
        $value = self::value($attributes);

        return $value === self::missing() ? $default : (bool) $value;
    }

    /**
     * Apakah checkbox atau radio yang diikat `wire:model` tercentang, mengikuti
     * aturan Livewire: checkbox pada properti array tercentang bila `value`-nya
     * ada di array itu, checkbox lain mengikuti nilai boolean propertinya, dan
     * radio tercentang bila propertinya sama dengan `value`-nya. Null bila
     * tidak diikat ke properti Livewire.
     */
    public static function checked(ComponentAttributeBag $attributes, string $type): ?bool
    {
        $model = self::value($attributes);

        if ($model === self::missing()) {
            return null;
        }

        $value = (string) ($attributes->get('value') ?? 'on');
        $model = $model instanceof BackedEnum ? $model->value : $model;

        return match (true) {
            $type === 'radio' => is_scalar($model) && (string) $model === $value,
            is_iterable($model) => in_array($value, array_map(
                fn (mixed $item) => (string) ($item instanceof BackedEnum ? $item->value : $item),
                collect($model)->all(),
            ), true),
            default => (bool) $model,
        };
    }

    /**
     * Nilai mentah properti yang diikat `wire:model`, mis. rentang tanggal
     * `['start' => …, 'end' => …]`, atau null bila tidak diikat ke properti
     * Livewire.
     */
    public static function get(ComponentAttributeBag $attributes): mixed
    {
        $value = self::value($attributes);

        return $value === self::missing() ? null : $value;
    }

    /**
     * Nilai teks properti yang diikat `wire:model`, untuk atribut `value` awal
     * isian teks. Null bila tidak diikat ke properti Livewire, propertinya
     * null, atau nilainya bukan skalar.
     */
    public static function text(ComponentAttributeBag $attributes): ?string
    {
        $value = self::value($attributes);
        $value = $value instanceof BackedEnum ? $value->value : $value;

        return $value !== self::missing() && is_scalar($value) ? (string) $value : null;
    }

    /**
     * Nilai mentah properti yang diikat `wire:model`, atau penanda dari
     * missing() bila tidak sedang dirender oleh komponen Livewire atau
     * propertinya tidak ditemukan.
     */
    private static function value(ComponentAttributeBag $attributes): mixed
    {
        $path = collect($attributes->whereStartsWith('wire:model')->getAttributes())->first();

        if (! is_string($path) || $path === '' || ! class_exists(Livewire::class)) {
            return self::missing();
        }

        // Livewire::current() bernilai false, bukan null, di luar komponen Livewire.
        $component = Livewire::current();

        return $component instanceof Component ? data_get($component, $path, self::missing()) : self::missing();
    }

    private static function missing(): stdClass
    {
        return self::$missing ??= new stdClass;
    }
}

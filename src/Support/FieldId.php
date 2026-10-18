<?php

namespace CeplokUi\DesignKit\Support;

use Illuminate\View\ComponentAttributeBag;
use Livewire\Component;
use Livewire\Livewire;

/**
 * ID kontrol form untuk menautkan `<label for>` dan `aria-describedby`,
 * padanan useId di kit React.
 *
 * Di Blade ID-nya harus stabil antar-render: morph Livewire mengenali elemen
 * dari `id` (sesudah wire:id dan wire:key), jadi ID yang berganti setiap
 * render membuat input diganti utuh dan kehilangan fokus saat pengguna
 * mengetik. Karena itu ID diturunkan dari hal yang tetap: `wire:model`,
 * `x-model`, atau `name` (ditambah `value`, untuk radio dan daftar checkbox)
 * di dalam komponen Livewire yang sedang dirender. Tanpa semua itu dipakai
 * nomor urut per komponen Livewire; urutannya sama di setiap render selama
 * susunan field-nya tidak berubah.
 *
 * Field yang sama yang muncul lebih dari sekali dalam satu render — mis.
 * radio sekelompok tanpa `value`, atau satu `wire:model` yang dipasang di dua
 * tempat — dibedakan nomor kemunculannya, jadi ID di satu halaman tetap unik.
 */
final class FieldId
{
    /** @var array<string, int> */
    private array $counters = [];

    /** @var array<string, array<string, int>> */
    private array $claimed = [];

    public static function for(ComponentAttributeBag $attributes, string $component): string
    {
        $explicit = $attributes->get('id');

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        // Livewire::current() bernilai false, bukan null, di luar komponen Livewire.
        $livewire = class_exists(Livewire::class) ? Livewire::current() : null;
        $scope = $livewire instanceof Component ? $livewire->getId() : '';
        $name = collect($attributes->whereStartsWith(['wire:model', 'x-model'])->getAttributes())->first()
            ?? $attributes->get('name');
        $ids = app(self::class);

        if (is_string($name) && $name !== '') {
            $value = $attributes->get('value');
            $key = $ids->claim($scope, $component.'|'.$name.'|'.(is_scalar($value) ? (string) $value : ''));
        } else {
            $key = '#'.$ids->next($scope);
        }

        return "ceplok-{$component}-".substr(md5($scope.'|'.$key), 0, 8);
    }

    /** Nomor urut berikutnya dalam satu komponen Livewire (atau di luar Livewire). */
    public function next(string $scope): int
    {
        return $this->counters[$scope] = ($this->counters[$scope] ?? 0) + 1;
    }

    /**
     * Kunci field untuk kemunculannya yang sekarang: kemunculan pertama memakai
     * kuncinya apa adanya, berikutnya ditambah nomor kemunculan.
     */
    public function claim(string $scope, string $key): string
    {
        $count = $this->claimed[$scope][$key] = ($this->claimed[$scope][$key] ?? 0) + 1;

        return $count === 1 ? $key : $key.'|'.$count;
    }

    /**
     * Memulai ulang nomor urut dan kemunculan satu komponen. Dipanggil setiap
     * kali komponen Livewire mulai dirender (lihat DesignKitServiceProvider),
     * supaya render berikutnya menghasilkan ID yang sama.
     */
    public function reset(string $scope): void
    {
        unset($this->counters[$scope], $this->claimed[$scope]);
    }
}

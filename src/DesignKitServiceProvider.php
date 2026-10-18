<?php

namespace CeplokUi\DesignKit;

use BladeUI\Icons\Factory as IconFactory;
use CeplokUi\DesignKit\Support\FieldId;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;

class DesignKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nomor urut ID field per request (lihat FieldId). Scoped, jadi ikut
        // di-reset di antara request pada server yang berumur panjang seperti
        // Octane.
        $this->app->scoped(FieldId::class);

        // Set ikon didaftarkan pada fase register: blade-icons meregistrasikan
        // komponen Blade-nya saat ViewFactory pertama kali di-resolve, sebelum boot.
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory) {
            // Ikon Flowbite yang sama persis dengan kit React (lihat
            // scripts/sync-icons.mjs): `Bell` dari icons/outline di React
            // menjadi svg('ceplok-o-bell') atau <x-ceplok-o-bell />, dan `Bell`
            // dari icons/solid menjadi ceplok-s-bell.
            $factory->add('ceplok-flowbite', [
                'path' => __DIR__.'/../resources/svg/flowbite',
                'prefix' => 'ceplok',
            ]);

            // Ikon brand/sosial milik kit — <x-brand-facebook>, svg('brand-x'), ...
            // Selalu dekoratif seperti logo brand di kit React (aria-hidden
            // bawaan): nama brand-nya dibawa label tautan di sekitarnya.
            $factory->add('ceplok-brands', [
                'path' => __DIR__.'/../resources/svg/brands',
                'prefix' => 'brand',
                'attributes' => ['aria-hidden' => 'true'],
            ]);
        });
    }

    public function boot(): void
    {
        // Nomor urut ID field (FieldId) dimulai ulang setiap kali sebuah
        // komponen Livewire dirender, jadi render ulang menghasilkan ID yang
        // sama dan morph Livewire tidak mengganti input yang sedang diketik.
        if (function_exists('Livewire\on')) {
            \Livewire\on('render', function ($component) {
                app(FieldId::class)->reset($component->getId());
            });
        }

        $components = __DIR__.'/../resources/views/components';
        $overrides = resource_path('views/vendor/ceplok/components');

        // Komponen hasil `vendor:publish --tag=ceplok-views` didaftarkan lebih
        // dulu, jadi berkas yang diubah consumer menang atas bawaan kit, dan
        // yang tidak diubah tetap jatuh ke kit.
        if (is_dir($overrides)) {
            Blade::anonymousComponentPath($overrides, 'ceplok');
        }

        // <x-ceplok::button>, <x-ceplok::badge>, ...
        Blade::anonymousComponentPath($components, 'ceplok');

        // Blaze mengompilasi komponen anonim menjadi fungsi PHP biasa, jadi
        // komponen kecil yang dirender berulang di dalam Livewire tetap cepat.
        // Hanya mode bawaan (compile) yang dipakai. Memo dan fold menyimpan
        // atau membekukan hasil render, sehingga keliru untuk komponen yang
        // membaca state per request (mis. pesan validasi pada komponen form);
        // keduanya baru layak dipasang per komponen setelah diukur.
        $optimize = Blaze::optimize()->in($components);

        if (is_dir($overrides)) {
            $optimize->in($overrides);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $components => $overrides,
            ], 'ceplok-views');
        }
    }
}

<?php

namespace CeplokUi\DesignKit\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentSlot;

/**
 * Pembantu untuk isi yang bisa datang sebagai prop atau slot, padanan
 * ReactNode di kit React.
 */
final class Content
{
    /**
     * Apakah isi ini akan menampilkan sesuatu. Slot kosong, string kosong,
     * null, dan false dianggap tidak ada — sama seperti `{title && ...}` di
     * React yang melewatkan nilai falsy.
     */
    public static function filled(mixed $value): bool
    {
        return match (true) {
            $value === null, $value === false => false,
            $value instanceof ComponentSlot => $value->isNotEmpty(),
            $value instanceof Htmlable => trim($value->toHtml()) !== '',
            is_string($value) => trim($value) !== '',
            default => true,
        };
    }

    /**
     * Ikon yang diberikan lewat data, mis. item Breadcrumb atau ikon sosial
     * Footer. Menerima markup jadi (`Htmlable`, termasuk hasil `svg()`) atau
     * nama ikon blade-icons, mis. `ceplok-o-user` atau `brand-instagram`.
     */
    public static function icon(Htmlable|string $icon): string
    {
        return $icon instanceof Htmlable ? $icon->toHtml() : svg($icon)->toHtml();
    }
}

<?php

namespace CeplokUi\DesignKit\Support;

use Illuminate\Support\Js;

/**
 * Kelas satu elemen yang sebagian berganti mengikuti state Alpine, padanan
 * cn() bersyarat di kit React yang dihitung ulang setiap render.
 *
 * Server merender kelas untuk keadaan awal, lalu Alpine mengambil alih lewat
 * sintaks objek x-bind:class: kelas bernilai true dipasang dan yang false
 * dicabut, termasuk kelas yang sudah dirender server. Kelas yang sama di
 * kedua sisi sebuah pilihan dipasang statis dan tidak ikut diikat, karena
 * Alpine memproses kunci objek berurutan: kelas yang muncul di dua kunci bisa
 * tercabut lagi oleh kunci berikutnya. Karena alasan yang sama, pilihan yang
 * berbeda pada satu elemen jangan memakai kelas yang sama.
 */
final class ClassToggle
{
    /** @var list<string> */
    private array $initial = [];

    /** @var array<string, string> kelas → ekspresi Alpine */
    private array $bindings = [];

    /** Kelas yang selalu terpasang. */
    public function always(string $classes): self
    {
        array_push($this->initial, ...self::split($classes));

        return $this;
    }

    /**
     * Kelas `$on` saat ekspresi Alpine `$expression` bernilai benar, `$off`
     * saat salah. `$initial` adalah nilai ekspresi itu saat dirender server.
     */
    public function when(string $expression, bool $initial, string $on, string $off = ''): self
    {
        $on = self::split($on);
        $off = self::split($off);
        $common = array_values(array_intersect($on, $off));
        $onOnly = array_values(array_diff($on, $common));
        $offOnly = array_values(array_diff($off, $common));

        array_push($this->initial, ...$common, ...($initial ? $onOnly : $offOnly));

        if ($onOnly !== []) {
            $this->bindings[implode(' ', $onOnly)] = $expression;
        }

        if ($offOnly !== []) {
            $this->bindings[implode(' ', $offOnly)] = "! ({$expression})";
        }

        return $this;
    }

    /** Isi atribut `class` untuk HTML dari server. */
    public function classes(): string
    {
        return implode(' ', array_unique($this->initial));
    }

    /** Isi `x-bind:class`, atau null bila tidak ada kelas yang berganti. */
    public function binding(): ?string
    {
        if ($this->bindings === []) {
            return null;
        }

        return '{ '.collect($this->bindings)->map(fn (string $expression, string $classes) => Js::from($classes).': '.$expression)->implode(', ').' }';
    }

    /** @return list<string> */
    private static function split(string $classes): array
    {
        return preg_split('/\s+/', trim($classes), flags: PREG_SPLIT_NO_EMPTY);
    }
}

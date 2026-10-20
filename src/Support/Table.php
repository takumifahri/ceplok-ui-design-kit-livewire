<?php

namespace CeplokUi\DesignKit\Support;

use BackedEnum;
use CeplokUi\DesignKit\Enums\TableAlign;
use CeplokUi\DesignKit\Enums\TableFileType;
use CeplokUi\DesignKit\Enums\TableSortDirection;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use JsonSerializable;
use Stringable;

/**
 * Logika Table yang tidak menyangkut markup, padanan fungsi-fungsi pembantu di
 * Table.tsx kit React: susunan kolom bertingkat, pengurutan, siklus arah
 * sort, dan tebakan jenis berkas.
 *
 * Kolom berupa array dengan kunci kebab-case (`sort-value`, `header-align`,
 * …), padanan TableColumn di React. Nilai yang bergantung pada baris berupa
 * closure, mis. `'cell' => fn ($row, int $index) => …`.
 */
final class Table
{
    /**
     * Susunan kolom yang sudah dirapikan. Tanpa `$columns`, kolom dibuat dari
     * field baris pertama dengan judul hasil camelCase/snake_case → "Title case".
     *
     * @param  list<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    public static function columns(?array $columns, array $rows): array
    {
        if ($columns === null) {
            $first = $rows[0] ?? null;
            $fields = match (true) {
                $first instanceof Arrayable => $first->toArray(),
                is_array($first) => $first,
                is_object($first) => get_object_vars($first),
                default => [],
            };

            $columns = array_map(fn ($key) => ['key' => (string) $key, 'header' => self::humanize((string) $key)], array_keys($fields));
        }

        return array_values(array_map(self::column(...), $columns));
    }

    /**
     * Kolom daun — yang benar-benar punya sel — dari susunan kolom bertingkat.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    public static function leaves(array $columns): array
    {
        $leaves = [];

        foreach ($columns as $column) {
            array_push($leaves, ...(self::hasChildren($column) ? self::leaves($column['children']) : [$column]));
        }

        return $leaves;
    }

    /**
     * Baris-baris `<thead>`. Judul induk membentang selebar jumlah daunnya;
     * judul daun membentang ke bawah sampai baris terakhir, supaya garis bawah
     * header selalu milik daun. `top` adalah kunci kolom teratas yang
     * menaunginya, dipakai untuk menyembunyikan grup sekaligus.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return list<list<array{column: array<string, mixed>, group: bool, colspan: int, rowspan: int, leaf: int, top: string}>>
     */
    public static function headRows(array $columns): array
    {
        $depth = self::depth($columns);
        $rows = array_fill(0, $depth, []);
        $leaf = 0;

        $walk = function (array $list, int $level, ?string $top) use (&$walk, &$rows, &$leaf, $depth): void {
            foreach ($list as $column) {
                $owner = $top ?? $column['key'];

                if (self::hasChildren($column)) {
                    $index = count($rows[$level]);
                    $start = $leaf;
                    $rows[$level][] = ['column' => $column, 'group' => true, 'colspan' => 0, 'rowspan' => 1, 'leaf' => $start, 'top' => $owner];
                    $walk($column['children'], $level + 1, $owner);
                    $rows[$level][$index]['colspan'] = $leaf - $start;
                } else {
                    $rows[$level][] = ['column' => $column, 'group' => false, 'colspan' => 1, 'rowspan' => $depth - $level, 'leaf' => $leaf++, 'top' => $owner];
                }
            }
        };

        $walk($columns, 0, null);

        return $rows;
    }

    /**
     * Kunci kolom teratas untuk tiap kolom daun, urut sesuai daunnya.
     *
     * @param  list<array<string, mixed>>  $columns
     * @return list<string>
     */
    public static function leafOwners(array $columns): array
    {
        $owners = [];

        foreach ($columns as $column) {
            foreach (self::leaves([$column]) as $ignored) {
                $owners[] = $column['key'];
            }
        }

        return $owners;
    }

    /**
     * Posisi judul: `header-align` kolom, lalu `header-align` Table. Selebihnya
     * judul induk kolom bertingkat di tengah, karena ia menaungi kolom-kolom di
     * bawahnya; judul biasa ikut `align`, lalu kiri.
     *
     * @param  array<string, mixed>  $column
     */
    public static function headAlign(array $column, ?string $tableAlign): string
    {
        return $column['header-align'] ?? $tableAlign ?? (self::hasChildren($column) ? 'center' : ($column['align'] ?? 'left'));
    }

    /** Membaca field `$key` dari baris berupa array, objek, atau model Eloquent. */
    public static function read(mixed $row, string $key): mixed
    {
        return data_get($row, $key);
    }

    /** Kunci unik baris: nama field atau closure. */
    public static function key(mixed $row, string|Closure $rowKey): int|string
    {
        $key = $rowKey instanceof Closure ? $rowKey($row) : self::read($row, $rowKey);
        $key = $key instanceof BackedEnum ? $key->value : $key;

        return is_int($key) ? $key : (string) $key;
    }

    /** Nilai statis atau per baris (closure yang menerima baris). */
    public static function resolve(mixed $value, mixed $row, mixed ...$extra): mixed
    {
        return $value instanceof Closure ? $value($row, ...$extra) : $value;
    }

    /**
     * Urutan aktif yang sudah dirapikan: `['key' => …, 'direction' => 'asc'|'desc']`
     * atau null. Menerima array, objek, atau JSON dari properti Livewire.
     *
     * @return array{key: string, direction: string}|null
     */
    public static function sortState(mixed $sort): ?array
    {
        if ($sort instanceof JsonSerializable) {
            $sort = $sort->jsonSerialize();
        }

        $key = data_get($sort, 'key');
        $direction = data_get($sort, 'direction');
        $direction = $direction instanceof BackedEnum ? $direction->value : $direction;

        if (! is_string($key) || $key === '' || TableSortDirection::tryFrom((string) $direction) === null) {
            return null;
        }

        return ['key' => $key, 'direction' => (string) $direction];
    }

    /**
     * Arah yang dilewati tiap kali judul kolom ditekan: `sort-directions` kolom
     * (bawaannya naik → turun), lalu null (tanpa urutan) kecuali
     * `sort-resettable` dimatikan. Dikirim juga ke Alpine, yang menghitung
     * urutan berikutnya di peramban (lihat toggleSort di table.js).
     *
     * @param  array<string, mixed>  $column
     * @return list<?string>
     */
    public static function sortCycle(array $column): array
    {
        $cycle = array_map(
            fn ($direction) => $direction instanceof BackedEnum ? $direction->value : (string) $direction,
            array_values($column['sort-directions'] ?? ['asc', 'desc']),
        );

        if (($column['sort-resettable'] ?? true) !== false) {
            $cycle[] = null;
        }

        return $cycle;
    }

    /**
     * Urutan berikutnya saat judul kolom ditekan, mengikuti sortCycle().
     *
     * @param  array<string, mixed>  $column
     * @param  array{key: string, direction: string}|null  $sort
     * @return array{key: string, direction: string}|null
     */
    public static function nextSort(array $column, ?array $sort): ?array
    {
        $cycle = self::sortCycle($column);

        $current = $sort !== null && $sort['key'] === $column['key'] ? array_search($sort['direction'], $cycle, true) : false;
        $direction = $cycle[$current === false ? 0 : ($current + 1) % count($cycle)];

        return $direction === null ? null : ['key' => $column['key'], 'direction' => $direction];
    }

    /**
     * Baris yang sudah diurutkan. Pembandingnya `sort-fn` kolom, lalu
     * `sort-value`, lalu nilai field-nya. Pengurutan tetap berlaku walau
     * kolomnya sedang disembunyikan.
     *
     * @param  list<mixed>  $rows
     * @param  list<array<string, mixed>>  $leaves
     * @param  array{key: string, direction: string}|null  $sort
     * @return list<mixed>
     */
    public static function sort(array $rows, array $leaves, ?array $sort): array
    {
        if ($sort === null) {
            return $rows;
        }

        $column = collect($leaves)->first(fn (array $column) => $column['key'] === $sort['key']);

        if ($column === null) {
            return $rows;
        }

        $value = $column['sort-value'] ?? fn ($row) => self::read($row, $column['key']);
        $byAsc = $column['sort-fn'] ?? fn ($a, $b) => self::compare($value($a), $value($b));
        $factor = $sort['direction'] === 'asc' ? 1 : -1;

        // usort stabil sejak PHP 8, sama seperti Array.prototype.sort.
        usort($rows, fn ($a, $b) => $byAsc($a, $b) * $factor);

        return $rows;
    }

    /**
     * Pembanding bawaan: angka dengan angka, selebihnya teks dengan urutan
     * alami ("Record 9" sebelum "Record 10"), seperti localeCompare numeric.
     */
    public static function compare(mixed $a, mixed $b): int
    {
        if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
            return $a <=> $b;
        }

        return strnatcasecmp(self::text($a), self::text($b));
    }

    /** Nilai mentah sel → teks yang aman ditampilkan; kosong menjadi "—". */
    public static function format(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => '—',
            $value instanceof BackedEnum => (string) $value->value,
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value), $value instanceof JsonSerializable => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => self::text($value),
        };
    }

    /** `createdAt` / `created_at` → "Created at". */
    public static function humanize(string $key): string
    {
        $words = strtolower(trim((string) preg_replace(['/([a-z0-9])([A-Z])/', '/[_-]+/'], ['$1 $2', ' '], $key)));

        return ucfirst($words);
    }

    /** Menebak jenis berkas dari ekstensi nama atau alamatnya. */
    public static function fileType(string $name): string
    {
        $extension = strtolower(pathinfo(explode('?', $name)[0], PATHINFO_EXTENSION));

        return match (true) {
            $extension === 'pdf' => TableFileType::Pdf->value,
            in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif'], true) => TableFileType::Image->value,
            default => TableFileType::File->value,
        };
    }

    /** @param  array<string, mixed>  $column */
    public static function hasChildren(array $column): bool
    {
        return ! empty($column['children']);
    }

    /**
     * Satu kolom yang dirapikan: `key` wajib, `header` bawaannya kosong, dan
     * nilai berpilihan diperiksa seperti prop.
     *
     * @param  array<string, mixed>  $column
     * @return array<string, mixed>
     */
    private static function column(array $column): array
    {
        $column['key'] = (string) ($column['key'] ?? '');
        $column['header'] ??= '';

        foreach (['align', 'header-align'] as $prop) {
            if (isset($column[$prop])) {
                $column[$prop] = Prop::oneOf(TableAlign::class, $column[$prop], TableAlign::Center, 'table', "columns.{$prop}");
            }
        }

        if (self::hasChildren($column)) {
            $column['children'] = array_values(array_map(self::column(...), $column['children']));
        }

        return $column;
    }

    /** @param  list<array<string, mixed>>  $columns */
    private static function depth(array $columns): int
    {
        $depths = array_map(fn (array $column) => self::hasChildren($column) ? 1 + self::depth($column['children']) : 1, $columns);

        return max([1, ...$depths]);
    }

    private static function text(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof Htmlable => strip_tags($value->toHtml()),
            is_scalar($value), $value instanceof Stringable => (string) $value,
            default => '',
        };
    }
}

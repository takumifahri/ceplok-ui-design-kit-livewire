<?php

namespace CeplokUi\DesignKit\Support;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Tanggal untuk Datepicker: membaca nilai dari Blade/Livewire dan menulis
 * teks kotaknya, padanan tulis dan tulisRentang di kit React. Tanggal
 * disimpan sebagai `YYYY-MM-DD`, tanpa jam dan zona waktu.
 */
final class Dates
{
    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /** `YYYY-MM-DD` dari string tanggal atau DateTimeInterface; null bila bukan tanggal. */
    public static function iso(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return null;
    }

    /**
     * Rentang `['start' => …, 'end' => …]` dari array, objek, atau null.
     * Rentang yang kedua sisinya null tetap rentang (Semua Waktu tanpa batas).
     *
     * @return array{start: ?string, end: ?string}|null
     */
    public static function range(mixed $value): ?array
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $value = is_object($value) && ! $value instanceof DateTimeInterface ? (array) $value : $value;

        if (! is_array($value)) {
            $day = self::iso($value);

            return $day === null ? null : ['start' => $day, 'end' => $day];
        }

        return ['start' => self::iso($value['start'] ?? null), 'end' => self::iso($value['end'] ?? null)];
    }

    /** "17 Agustus 1945". */
    public static function write(string $iso): string
    {
        $date = new DateTimeImmutable($iso);

        return $date->format('j').' '.self::BULAN[(int) $date->format('n') - 1].' '.$date->format('Y');
    }

    /** Rentang dalam satu baris; bulan dan tahun yang sama tidak ditulis dua kali. */
    public static function writeRange(string $start, string $end): string
    {
        if ($start === $end) {
            return self::write($start);
        }

        $a = new DateTimeImmutable($start);
        $b = new DateTimeImmutable($end);

        return match (true) {
            $a->format('Y') !== $b->format('Y') => self::write($start).' – '.self::write($end),
            $a->format('n') !== $b->format('n') => $a->format('j').' '.self::BULAN[(int) $a->format('n') - 1].' – '.self::write($end),
            default => $a->format('j').'–'.self::write($end),
        };
    }
}

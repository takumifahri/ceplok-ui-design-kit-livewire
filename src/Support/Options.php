<?php

namespace CeplokUi\DesignKit\Support;

use BackedEnum;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Daftar pilihan Select: dari prop `options` atau dari `<option>` dan
 * `<optgroup>` yang ditulis di slot, padanan bacaOpsi di kit React.
 *
 * Bentuk hasilnya daftar kelompok: `label` (null untuk kelompok tanpa judul)
 * dan `options` berisi `value`, `label`, `disabled`, `selected`.
 *
 * @phpstan-type Option array{value: string, label: string, disabled: bool, selected: bool}
 * @phpstan-type Group array{label: ?string, options: list<Option>}
 */
final class Options
{
    /**
     * Prop `options`: daftar `['value' => …, 'label' => …, 'disabled' => …]`,
     * pasangan `nilai => label`, daftar teks biasa (nilainya sama dengan
     * labelnya), atau kelompok `['label' => …, 'options' => […]]` seperti
     * `<optgroup>`.
     *
     * @return list<Group>
     */
    public static function fromArray(iterable $options): array
    {
        $groups = [['label' => null, 'options' => []]];

        foreach (self::items($options) as $option) {
            if (isset($option['options'])) {
                $disabled = ! empty($option['disabled']);
                $groups[] = ['label' => (string) ($option['label'] ?? ''), 'options' => array_map(
                    fn (array $item) => self::option($item, $disabled),
                    self::items($option['options']),
                )];
                $groups[] = ['label' => null, 'options' => []];

                continue;
            }

            $groups[array_key_last($groups)]['options'][] = self::option($option);
        }

        return self::compact($groups);
    }

    /**
     * Isi slot: `<option>` dan `<optgroup>`, mengikuti aturan HTML. Tanpa
     * `value`, teksnya yang jadi nilai, dan `<optgroup disabled>` mematikan
     * seluruh isinya. `<option>` lepas sesudah sebuah `<optgroup>` masuk
     * kelompok tanpa judul yang baru.
     *
     * @return list<Group>
     */
    public static function fromHtml(string $html): array
    {
        $groups = [['label' => null, 'options' => []]];

        foreach (self::parse($html)->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($node->tagName === 'option') {
                $groups[array_key_last($groups)]['options'][] = self::fromElement($node, false);
            } elseif ($node->tagName === 'optgroup') {
                $disabled = $node->hasAttribute('disabled');
                $items = [];

                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->tagName === 'option') {
                        $items[] = self::fromElement($child, $disabled);
                    }
                }

                $groups[] = ['label' => $node->getAttribute('label'), 'options' => $items];
                $groups[] = ['label' => null, 'options' => []];
            }
        }

        return self::compact($groups);
    }

    /** Isi slot dengan atribut `selected` disetel ulang ke opsi bernilai `$value`. */
    public static function withSelected(string $html, string $value): string
    {
        $select = self::parse($html);

        foreach ($select->getElementsByTagName('option') as $option) {
            if (! $option instanceof DOMElement) {
                continue;
            }

            $optionValue = $option->hasAttribute('value') ? $option->getAttribute('value') : trim($option->textContent);
            $optionValue === $value ? $option->setAttribute('selected', '') : $option->removeAttribute('selected');
        }

        return implode('', array_map(fn (DOMNode $node) => $select->ownerDocument->saveHTML($node), iterator_to_array($select->childNodes)));
    }

    /**
     * Nilai yang terpilih menurut atribut `selected`, atau null.
     *
     * @param  list<Group>  $groups
     */
    public static function selected(array $groups): ?string
    {
        foreach ($groups as $group) {
            foreach ($group['options'] as $option) {
                if ($option['selected']) {
                    return $option['value'];
                }
            }
        }

        return null;
    }

    /**
     * Nilai opsi pertama yang tidak dimatikan; yang dipilih peramban untuk
     * `<select>` tanpa pilihan.
     *
     * @param  list<Group>  $groups
     */
    public static function first(array $groups): ?string
    {
        foreach ($groups as $group) {
            foreach ($group['options'] as $option) {
                if (! $option['disabled']) {
                    return $option['value'];
                }
            }
        }

        return null;
    }

    /**
     * Setiap butir sebagai array: pasangan `nilai => label` menjadi
     * `['value' => nilai, 'label' => label]`, dan teks dalam daftar biasa
     * menjadi nilai sekaligus labelnya.
     *
     * @return list<array<string, mixed>>
     */
    private static function items(iterable $options): array
    {
        $options = collect($options)->all();
        $list = array_is_list($options);

        return array_map(
            fn ($option, $key) => is_array($option) ? $option : ['value' => $list ? $option : $key, 'label' => $option],
            $options,
            array_keys($options),
        );
    }

    /** @return Option */
    private static function option(array $item, bool $disabled = false): array
    {
        $value = $item['value'] ?? '';
        $label = $item['label'] ?? $value;

        return [
            'value' => (string) ($value instanceof BackedEnum ? $value->value : $value),
            'label' => (string) $label,
            'disabled' => $disabled || ! empty($item['disabled']),
            'selected' => ! empty($item['selected']),
        ];
    }

    /** @return Option */
    private static function fromElement(DOMElement $option, bool $disabled): array
    {
        $label = trim($option->textContent);

        return [
            'value' => $option->hasAttribute('value') ? $option->getAttribute('value') : $label,
            'label' => $label,
            'disabled' => $disabled || $option->hasAttribute('disabled'),
            'selected' => $option->hasAttribute('selected'),
        ];
    }

    private static function parse(string $html): DOMElement
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<html><head><meta charset="utf-8"></head><body><select>'.$html.'</select></body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document->getElementsByTagName('select')->item(0);
    }

    /**
     * @param  list<Group>  $groups
     * @return list<Group>
     */
    private static function compact(array $groups): array
    {
        return array_values(array_filter($groups, fn (array $group) => $group['options'] !== []));
    }
}

/**
 * Select dan kategori Search: tombol yang menampilkan pilihan aktif, dengan
 * `<select>` tersembunyi di belakangnya sebagai pembawa nilai. `name`,
 * `wire:model`, `x-model`, `required`, dan pengiriman formulir bekerja
 * seperti pada `<select>` biasa.
 *
 * `value` adalah pilihan awal yang belum dibawa markup (`<select>` tanpa
 * atribut `selected`, mis. kategori Search yang terkendali seperti di React);
 * null bila markup atau wire:model sudah membawanya. `fallback` adalah teks
 * tombol bila nilainya tidak ada di daftar (placeholder). Label tiap pilihan
 * dibaca dari `<option>`-nya, jadi opsi yang diganti server — mis. daftar
 * kabupaten yang mengikuti provinsi — langsung terbaca tanpa x-data baru.
 * Ekspresi x-data yang berubah saat Livewire merender ulang membuat Alpine
 * menginisialisasi ulang datanya, jadi isinya sengaja tidak memuat nilai
 * yang berganti-ganti.
 *
 * Baris panel Dropdown memanggil `ceplokChoose(nilai)`, yang menyetel nilai
 * `<select>` lewat setter bawaannya lalu melepas event `input` dan `change`
 * sungguhan, seperti setelSelect di kit React, supaya wire:model dan x-model
 * ikut terbarui.
 *
 * Arah sebaliknya juga diikuti: nilai yang diganti tanpa event — wire:model
 * dan x-model menyetel `option.selected`, skrip pemakai menyetel `value` —
 * ditangkap lewat setter elemen-elemen itu, sedangkan opsi yang diganti
 * Livewire saat merender ulang ditangkap MutationObserver.
 */
const watched = new WeakSet();

function watch(el, proto, prop, onSet) {
    const native = Object.getOwnPropertyDescriptor(proto, prop);
    Object.defineProperty(el, prop, {
        configurable: true,
        get() {
            return native.get.call(this);
        },
        set(value) {
            native.set.call(this, value);
            onSet();
        },
    });
}

export default function select(value = null, fallback = '') {
    // Disimpan di closure, bukan dicari lewat $root: ceplokChoose dipanggil
    // dari baris panel, yang $root-nya pembungkus Dropdown, bukan Select.
    let field = null;

    return {
        ceplokValue: '',
        /** Pasangan [nilai, label] dari `<option>` yang sedang ada. */
        ceplokChoices: [],

        get ceplokLabel() {
            return this.ceplokChoices.find(([choice]) => choice === this.ceplokValue)?.[1] ?? fallback;
        },

        init() {
            field = this.$root.querySelector('select');

            // Pilihan awal dari server, seperti `value` terkendali di React:
            // disetel lewat properti, jadi <select> tidak perlu atribut `selected`.
            if (value !== null && field.value !== value && [...field.options].some((option) => option.value === value)) field.value = value;

            const read = () => {
                this.ceplokChoices = [...field.options].map((option) => [option.value, option.text]);
            };
            let queued = false;
            const sync = () => {
                if (queued) return;
                queued = true;
                queueMicrotask(() => {
                    queued = false;
                    this.ceplokValue = field.value;
                });
            };
            const watchOptions = () => {
                for (const option of field.options) {
                    if (watched.has(option)) continue;
                    watched.add(option);
                    watch(option, HTMLOptionElement.prototype, 'selected', sync);
                }
            };

            watch(field, HTMLSelectElement.prototype, 'value', sync);
            watch(field, HTMLSelectElement.prototype, 'selectedIndex', sync);
            watchOptions();
            new MutationObserver(() => {
                watchOptions();
                read();
                sync();
            }).observe(field, { childList: true, subtree: true, attributes: true, characterData: true });

            field.addEventListener('change', sync);
            // form.reset() mengembalikan pilihan tanpa event pada <select>-nya.
            field.form?.addEventListener('reset', () => setTimeout(sync));

            // Langsung, bukan lewat microtask: tombol dan warnanya dihitung dari sini.
            read();
            this.ceplokValue = field.value;
        },

        ceplokChoose(next) {
            field.value = next;
            field.dispatchEvent(new Event('input', { bubbles: true }));
            field.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
}

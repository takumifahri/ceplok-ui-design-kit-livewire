import { clearField } from './input-field.js';

/**
 * Floating Label: label naik ke garis atas saat field difokus atau berisi,
 * seperti state `focused`/`filled` di kit React.
 *
 * Keterisiannya diperbarui saat pengguna mengetik, dan juga saat nilainya
 * diganti lewat JavaScript — wire:model setelah server merender ulang (mis.
 * form dikosongkan sesudah disimpan), x-model, atau skrip pemakai. Perubahan
 * semacam itu tidak memicu event input, jadi setter `value` milik elemen ini
 * dibungkus.
 *
 * Keadaan awalnya dibaca dari input-nya sendiri, bukan dari parameter x-data:
 * server sudah merender kelasnya sesuai nilai awal, dan ekspresi x-data yang
 * berubah saat Livewire merender ulang membuat Alpine menginisialisasi ulang
 * datanya — fokus yang sedang berjalan ikut hilang.
 *
 * Nama state diawali `ceplok` supaya tidak menutupi variabel pemakai yang
 * dipakai x-model di input yang dibungkus komponen ini.
 */
export default function floatingLabel() {
    return {
        ceplokFocused: false,
        ceplokFilled: false,

        init() {
            const input = this.$root.querySelector('input');
            const native = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
            const sync = () => {
                this.ceplokFilled = input.value.length > 0;
            };

            Object.defineProperty(input, 'value', {
                configurable: true,
                get() {
                    return native.get.call(this);
                },
                set(value) {
                    native.set.call(this, value);
                    sync();
                },
            });

            input.addEventListener('input', sync);
            input.addEventListener('focus', () => (this.ceplokFocused = true));
            input.addEventListener('blur', () => (this.ceplokFocused = false));
            // Peramban bisa sudah memulihkan isian form, atau memberi fokus lewat autofocus.
            sync();
            this.ceplokFocused = document.activeElement === input;
        },

        ceplokClear() {
            clearField(this.$root.querySelector('input'));
        },
    };
}

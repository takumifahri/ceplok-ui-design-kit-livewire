/**
 * Input Field: tombol mata pada type="password" dan tombol hapus (×).
 *
 * Event `clear`, padanan onClear di React, dikirim dari elemen input-nya,
 * karena atribut pemakai (termasuk wire:clear dan x-on:clear) diteruskan ke
 * sana. Sebelum itu isiannya dikosongkan dan event input/change dipicu, supaya
 * wire:model dan x-model ikut terbarui.
 *
 * Nama state diawali `ceplok` karena scope Alpine komponen ini membungkus
 * input milik pemakai: nama umum seperti `revealed` bisa menutupi variabel
 * pemakai yang dipakai x-model.
 */
export function clearField(input) {
    input.value = '';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.dispatchEvent(new CustomEvent('clear', { bubbles: true }));
}

export default function inputField() {
    return {
        ceplokRevealed: false,

        ceplokClear() {
            clearField(this.$root.querySelector('input'));
        },
    };
}

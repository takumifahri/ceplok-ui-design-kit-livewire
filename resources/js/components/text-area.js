/**
 * Text Area editor: tombol toolbar bawaan dan tombol kirim.
 *
 * Keduanya mengirim event dari elemen textarea-nya, karena atribut pemakai
 * (wire:*, x-on:*) diteruskan ke sana:
 * - `toolbar-action` dengan `detail.action`, padanan onToolbarAction di React,
 *   mis. `wire:toolbar-action="sisipkan($event.detail.action)"`.
 * - `submit`, padanan onSubmit, mis. `wire:submit="kirim"`. Event ini sengaja
 *   tidak menggelembung: kalau menggelembung, wire:submit milik <form>
 *   pembungkusnya ikut terpanggil.
 */
export default function textArea() {
    return {
        ceplokToolbar(action) {
            this.$root.querySelector('textarea').dispatchEvent(new CustomEvent('toolbar-action', { detail: { action }, bubbles: true }));
        },

        ceplokSubmit() {
            this.$root.querySelector('textarea').dispatchEvent(new CustomEvent('submit', { bubbles: false, cancelable: true }));
        },
    };
}

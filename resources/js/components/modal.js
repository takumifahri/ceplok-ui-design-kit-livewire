/**
 * Modal — dialog di atas halaman berbasis `<dialog>` bawaan, port Modal di kit
 * React. Peramban yang mengurus top layer, latar yang ikut mati, dan jebakan
 * fokus; di sini hanya keadaan buka-tutupnya.
 *
 * `open` diikat x-modelable, jadi `wire:model` atau `x-model` di tag Modal
 * mengendalikannya, padanan `open` + `onOpenChange` di React. Setiap
 * permintaan buka atau tutup — trigger, tombol X, Escape, klik latar, atau
 * `close()` di footer — juga mengirim event `open-change` (`detail.open`).
 *
 * Nilai awal dibaca dari `data-open` pembungkus, dan pilihan `close-on-escape`
 * serta `close-on-backdrop` dari atribut `data-*` dialog saat event-nya
 * terjadi, bukan dari parameter x-data: ekspresi x-data yang berubah saat
 * Livewire merender ulang membuat Alpine menginisialisasi ulang datanya.
 */
export default function modal() {
    return {
        open: false,

        init() {
            this.open = this.$el.dataset.open === 'true';
            this.$watch('open', (value) => this.sync(value));
            this.$nextTick(() => this.sync(this.open));
        },

        /** Menyamakan elemen `<dialog>` dengan `open`. */
        sync(value) {
            const dialog = this.$refs.dialog;
            if (!dialog) return;

            if (value && !dialog.open) dialog.showModal();
            else if (!value && dialog.open) dialog.close();
        },

        request(next) {
            if (next === this.open) return;
            this.open = next;
            this.$dispatch('open-change', { open: next });
        },

        show() {
            this.request(true);
        },

        close() {
            this.request(false);
        },

        /** Escape memicu `cancel`; dicegah supaya penutupan tetap lewat satu jalan. */
        onCancel(event) {
            event.preventDefault();
            if (this.$refs.dialog.dataset.closeOnEscape !== 'false') this.close();
        },

        /** Penutupan dari luar kendali Modal, mis. `<form method="dialog">`. */
        onNativeClose() {
            if (this.open) this.request(false);
        },

        /**
         * Klik latar mengenai `<dialog>` itu sendiri. Bubbling dari isi dan klik
         * di permukaan panel diabaikan dengan memeriksa target serta batasnya.
         */
        onClick(event) {
            const dialog = this.$refs.dialog;
            if (dialog.dataset.closeOnBackdrop === 'false' || event.target !== dialog) return;

            const rect = dialog.getBoundingClientRect();
            const outside = event.clientX < rect.left
                || event.clientX > rect.right
                || event.clientY < rect.top
                || event.clientY > rect.bottom;

            if (outside) this.close();
        },
    };
}

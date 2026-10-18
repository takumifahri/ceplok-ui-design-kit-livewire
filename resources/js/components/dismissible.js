/**
 * Buka/tutup Alert, Badge, dan Toast — padanan hook useDismissible di kit React.
 *
 * Tiga keadaan dipisah seperti di React:
 * - `open`: keadaan yang diminta. Diikat x-modelable, jadi bisa dikendalikan
 *   dari luar lewat wire:model atau x-model.
 * - `shown`: menggerakkan x-show. Tetap true selama transisi keluar berjalan.
 * - `leaving`: memasang gaya keluar (lihat `leaveStyle` di komponennya).
 *
 * Kalau `shown` ikut turun bersamaan dengan `open`, elemennya sudah hilang
 * sebelum transisi keluarnya sempat terlihat. Transisi masuk tidak diatur di
 * sini: animasi CSS `animate-ceplok-*` berjalan sendiri setiap elemen muncul.
 *
 * Keadaan awalnya dibaca dari markup server — yang tertutup dirender dengan
 * `display: none` — bukan dari parameter x-data. Ekspresi x-data yang berubah
 * saat Livewire merender ulang membuat Alpine menginisialisasi ulang datanya,
 * dan Alert yang ditutup dari server lalu hilang seketika tanpa transisi.
 */
export default function dismissible(duration = 200) {
    return {
        open: true,
        shown: true,
        leaving: false,
        timer: null,

        init() {
            this.open = this.shown = this.$el.style.display !== 'none';
            this.$watch('open', (value) => (value ? this.enter() : this.leave()));
        },

        destroy() {
            clearTimeout(this.timer);
        },

        enter() {
            clearTimeout(this.timer);
            this.leaving = false;
            this.shown = true;
        },

        leave() {
            clearTimeout(this.timer);
            this.leaving = true;
            this.timer = setTimeout(() => {
                this.shown = false;
            }, duration);
        },

        /** Dipanggil tombol tutup (×). Event `dismiss` padanan onDismiss di React. */
        close() {
            this.open = false;
            this.$dispatch('dismiss');
        },
    };
}

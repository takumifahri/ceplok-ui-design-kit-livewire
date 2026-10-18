/**
 * Pagination dengan tombol, padanan onPageChange di kit React.
 *
 * `page` diikat x-modelable, jadi `wire:model.live="page"` cukup untuk
 * menyambungkannya ke properti Livewire: klik mengganti properti itu, lalu
 * Livewire merender ulang daftar halamannya. Setiap pergantian juga mengirim
 * event `page-change` dengan `detail.page` untuk ditangani sendiri, mis.
 * `wire:page-change="muat($event.detail.page)"`.
 *
 * Batas halaman tidak disimpan di sini. Data Alpine tidak diinisialisasi ulang
 * saat Livewire merender ulang, jadi jumlah halaman yang disalin saat awal bisa
 * basi setelah data difilter. Yang menjaga batas adalah markup dari server:
 * tombol halaman aktif tidak memanggil `go()`, dan tombol sebelumnya/berikutnya
 * dinonaktifkan di ujung daftar.
 *
 * Daftar halaman dan tanda halaman aktif dirender di server, jadi tampilannya
 * baru berubah setelah render ulang. Untuk halaman Blade biasa tanpa Livewire,
 * pakai prop `url` supaya setiap halaman menjadi tautan.
 */
export default function pagination(page = 1) {
    return {
        page,

        go(target) {
            this.page = target;
            this.$dispatch('page-change', { page: target });
        },
    };
}

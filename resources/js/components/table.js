/**
 * Table — keadaan tabel yang hidup di peramban, port Table di kit React.
 *
 * Pembagian kerjanya:
 * - Server (Blade/Livewire) merender baris, mengurutkan, dan membagi halaman.
 *   Di sini judul kolom dan tombol halaman hanya mengirim event: `sort-change`
 *   (`detail.sort`), `page-change` (`detail.page`, dikirim Pagination), dan
 *   `page-size-change` (`detail.pageSize`). Komponen Livewire yang menangkapnya
 *   lalu merender ulang tabel dengan urutan atau halaman yang baru.
 * - Alpine memegang hal yang murni tampilan: baris yang dicentang (`selected`)
 *   dan kolom yang disembunyikan lewat Filter Data (`hidden`). Keduanya
 *   langsung berubah tanpa menunggu server.
 *
 * `selected` diikat x-modelable, jadi `wire:model="selected"` di tag Table
 * menyambungkannya ke properti array Livewire, padanan selectedKeys +
 * onSelectionChange. Setiap perubahan juga mengirim `selection-change`
 * (`detail.keys`); filter kolom mengirim `hidden-columns-change`.
 *
 * Kunci baris halaman yang sedang tampil tidak disimpan di sini, karena data
 * Alpine tidak diinisialisasi ulang saat Livewire merender halaman lain.
 * Kuncinya ditulis langsung di ekspresi tiap elemen oleh server, jadi
 * ekspresinya ikut berganti bersama halamannya.
 */

/** Kunci dibandingkan sebagai teks: properti Livewire bisa berisi 1 atau "1". */
const same = (a, b) => String(a) === String(b);

export default function table() {
    let watcher = null;

    return {
        selected: [],
        hidden: [],
        sort: null,

        init() {
            const read = (name, fallback) => JSON.parse(this.$el.dataset[name] ?? fallback);

            this.selected = read('selected', '[]');
            this.hidden = read('hiddenColumns', '[]');
            this.sort = read('sort', 'null');

            // Urutan dari server menang setiap kali tabel dirender ulang, mis.
            // setelah komponen Livewire mengatur ulang urutannya sendiri.
            watcher = new MutationObserver(() => {
                this.sort = read('sort', 'null');
            });
            watcher.observe(this.$el, { attributes: true, attributeFilter: ['data-sort'] });
        },

        destroy() {
            watcher?.disconnect();
        },

        isSelected(key) {
            return this.selected.some((k) => same(k, key));
        },

        toggleRow(key, checked) {
            const rest = this.selected.filter((k) => !same(k, key));
            this.select(checked ? [...rest, key] : rest);
        },

        /** Jumlah baris halaman ini yang tercentang. */
        countSelected(keys) {
            return keys.filter((key) => this.isSelected(key)).length;
        },

        allSelected(keys) {
            return keys.length > 0 && this.countSelected(keys) === keys.length;
        },

        /** Satu baris tercentang belum dianggap "sebagian": garis datarnya baru muncul mulai dua baris. */
        someSelected(keys) {
            return this.countSelected(keys) > 1 && !this.allSelected(keys);
        },

        /** "Pilih semua" hanya mencakup baris di halaman yang sedang tampil. */
        toggleAll(keys, checked) {
            const rest = this.selected.filter((k) => !keys.some((key) => same(k, key)));
            this.select(checked ? [...rest, ...keys] : rest);
        },

        select(keys) {
            this.selected = keys;
            this.$dispatch('selection-change', { keys });
        },

        isHidden(key) {
            return this.hidden.includes(key);
        },

        /** Kolom terakhir yang tampil tidak bisa dimatikan, supaya tabel tidak pernah kosong. */
        isLastVisible(key, keys) {
            return !this.isHidden(key) && keys.filter((k) => !this.isHidden(k)).length === 1;
        },

        toggleColumn(key, visible) {
            const rest = this.hidden.filter((k) => k !== key);
            this.hidden = visible ? rest : [...rest, key];
            this.$dispatch('hidden-columns-change', { keys: this.hidden });
        },

        /**
         * Judul kolom ditekan. Urutan berikutnya dihitung di sini dari urutan
         * terakhir, bukan ditulis server di ekspresi tombol: ekspresi itu baru
         * berganti setelah render ulang selesai, jadi klik cepat beruntun akan
         * mengirim urutan yang sama dua kali. `cycle` adalah siklus arah kolom
         * itu, mis. ['asc', 'desc', null] (null: kembali tanpa urutan).
         */
        toggleSort(key, cycle) {
            const current = this.sort?.key === key ? cycle.indexOf(this.sort.direction) : -1;
            const direction = cycle[(current + 1) % cycle.length];

            this.sort = direction ? { key, direction } : null;
            this.$dispatch('sort-change', { sort: this.sort });
        },

        /** Ganti jumlah baris per halaman; halaman kembali ke 1. */
        setPageSize(pageSize) {
            this.$dispatch('page-size-change', { pageSize });
            this.$dispatch('page-change', { page: 1 });
        },
    };
}

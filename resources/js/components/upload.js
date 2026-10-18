/**
 * Upload: nama dan ukuran berkas terpilih, tombol ganti/hapus, dan area
 * seret-lepas, padanan state `file`/`seret` di kit React.
 *
 * Di dalamnya tetap `<input type="file">` sungguhan, jadi `wire:model`
 * (unggahan Livewire), `name`, dan pengiriman formulir bekerja apa adanya.
 * Berkas hasil seret-lepas dipasang ke input itu lalu event `change`
 * dilepas, supaya wire:model ikut mengunggahnya. Setiap berkas berganti,
 * event `files-change` (`detail.files`: FileList atau null), padanan
 * onFilesChange, dikirim dari input-nya.
 *
 * Yang ditampilkan selalu isi input yang sebenarnya: setter `value` milik
 * input dibungkus, karena Livewire mengosongkannya tanpa event (saat diklik
 * dan saat properti wire:model diset null). Menghapus berkas ikut menyetel
 * properti wire:model-nya ke null, supaya berkas yang sudah terunggah di
 * server ikut terlepas.
 */
const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

/** Sama dengan formatSize di kit React: 1024 per tingkat, dua angka desimal. */
export function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${parseFloat((bytes / 1024 ** i).toFixed(2))} ${UNITS[i]}`;
}

export default function upload() {
    let input = null;

    return {
        ceplokFile: null,
        ceplokDrag: false,

        get ceplokSize() {
            return this.ceplokFile ? formatSize(this.ceplokFile.size) : '';
        },

        init() {
            input = this.$root.querySelector('input[type="file"]');
            const native = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

            Object.defineProperty(input, 'value', {
                configurable: true,
                get() {
                    return native.get.call(this);
                },
                set: (value) => {
                    native.set.call(input, value);
                    this.ceplokSync();
                },
            });

            input.addEventListener('change', () => {
                this.ceplokSync();
                this.ceplokNotify(input.files);
            });
            this.ceplokSync();
        },

        ceplokSync() {
            const file = input.files?.[0];
            this.ceplokFile = file ? { name: file.name, size: file.size } : null;
        },

        ceplokNotify(files) {
            input.dispatchEvent(new CustomEvent('files-change', { detail: { files }, bubbles: true }));
        },

        ceplokBrowse() {
            input.click();
        },

        ceplokDragEnter() {
            if (!input.disabled) this.ceplokDrag = true;
        },

        ceplokDrop(event) {
            this.ceplokDrag = false;
            if (input.disabled) return;

            // Hanya berkas pertama, supaya ini selalu unggahan tunggal.
            const transfer = new DataTransfer();
            if (event.dataTransfer.files.length > 0) transfer.items.add(event.dataTransfer.files[0]);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        ceplokRemove() {
            input.value = '';
            this.ceplokNotify(null);

            const model = [...input.attributes].find((attribute) => attribute.name.startsWith('wire:model'))?.value;
            if (model) {
                try {
                    this.$wire.set(model, null);
                } catch {
                    // Tidak di dalam komponen Livewire.
                }
            }
        },
    };
}

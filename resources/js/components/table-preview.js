/**
 * Pratinjau TableImage dan TableFile: gambar atau PDF besar di atas latar
 * gelap, port dialog pratinjau di Table.tsx kit React.
 *
 * Dialognya `<dialog>` bawaan yang baru dibuat saat dibuka (`x-if`) dan
 * dipindah ke `body` (`x-teleport`), padanan createPortal di React: tidak
 * mewarisi gaya sel (teks rata tengah, `nowrap`), dan seratus baris tidak
 * berarti seratus dialog. Peramban yang mengurus jebakan fokus dan Escape;
 * Escape dan klik di latar menutupnya lewat `close()`.
 */
export default function tablePreview() {
    return {
        open: false,

        show() {
            this.open = true;
        },

        close() {
            this.open = false;
        },
    };
}

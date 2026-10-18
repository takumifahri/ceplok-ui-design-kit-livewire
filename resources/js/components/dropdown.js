/**
 * Dropdown — panel yang dibuka dari sebuah tombol, port Dropdown di kit React.
 *
 * Dipasang pada pembungkus yang anaknya tepat dua: tombol pemicu
 * (`popovertarget` ke panel) lalu panelnya (`popover="auto"`). Visibilitasnya
 * diurus Popover API: peramban yang menutup panel saat pengguna menekan di
 * luar atau menekan Escape dan mengangkatnya ke top layer. Di sini keadaannya
 * hanya dicerminkan ke `aria-expanded` (`ceplokOpen`).
 *
 * Punya dua peran, mengikuti panelnya:
 * - menu aksi: baris berupa tombol atau tautan, ditelusuri dengan Tab;
 * - daftar pilihan (`role="listbox"`): baris `option` ditelusuri dengan panah,
 *   Home/End, dan Enter/Spasi; panel membuka dengan fokus di baris terpilih
 *   dan menutup dengan fokus kembali ke tombolnya. Peran ini yang dipakai
 *   Select dan kategori Search.
 * Di kedua peran, mengetik melompat ke baris yang cocok seperti `<select>`
 * bawaan, dan menekan sebuah baris menutup panelnya.
 *
 * Bentuk `attached` (`ceplokDropdown(true)`) menempelkan panel pada
 * tombolnya: lebar minimum selebar tombol, dijepit di dalam viewport, dibalik
 * ke atas bila di bawah tidak muat, dan diukur ulang saat halaman digulir,
 * jendela diubah, atau tombolnya bergerak. Tanpa itu panel ditempatkan CSS
 * anchor positioning.
 */

/** Jarak panel dari tombol dan dari tepi layar, dalam piksel. */
const GAP = 4;

/** Jeda antar-ketikan yang masih dianggap satu kata kunci: satu detik, sama dengan `<select>` bawaan. */
const TYPE_DELAY = 1000;

/** Teks yang dicocokkan dengan ketikan: huruf kecil, tanpa diakritik, spasi dirapikan. */
const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').toLowerCase();

/** Apakah `key` muncul di awal salah satu kata `text`, mis. "jakarta" di "dki jakarta". */
const atWordStart = (text, key) => {
    for (let i = text.indexOf(key); i !== -1; i = text.indexOf(key, i + 1)) {
        if (i === 0 || /[\s/(),.-]/.test(text[i - 1])) return true;
    }
    return false;
};

/** Label sebuah baris untuk dicocokkan dengan ketikan; keterangan di bawahnya tidak ikut. */
const rowName = (el) => {
    const all = el.textContent ?? '';
    const note = el.querySelector('[data-keterangan]')?.textContent ?? '';
    return normalize(all.slice(0, all.length - note.length)).trim();
};

export default function dropdown(attached = false) {
    let typed = { text: '', time: 0 };
    let spaceHeld = false;
    let frame = 0;
    let follow = 0;
    let resize = null;

    return {
        ceplokOpen: false,

        init() {
            const panel = this.ceplokPanel();
            const trigger = this.ceplokTrigger();
            const selection = panel.getAttribute('role') === 'listbox';

            const onMove = () => this.ceplokRequestPlace();
            const stopFollowing = () => {
                window.removeEventListener('scroll', onMove, true);
                window.removeEventListener('resize', onMove);
                resize?.disconnect();
                resize = null;
                cancelAnimationFrame(follow);
                cancelAnimationFrame(frame);
                follow = frame = 0;
            };

            // `beforetoggle` menempatkan panel sebelum ia tampil supaya tidak
            // terlihat melompat; `toggle` mengulanginya setelah tingginya terukur.
            panel.addEventListener('beforetoggle', (event) => {
                this.ceplokOpen = event.newState === 'open';
                if (!this.ceplokOpen) stopFollowing();
                else if (attached) this.ceplokPlace();
            });

            panel.addEventListener('toggle', (event) => {
                if (event.newState !== 'open') {
                    stopFollowing();
                    // Ditutup selagi fokus di dalamnya (Escape, klik di luar, atau
                    // sebuah pilihan diambil), jadi fokus kembali ke tombolnya.
                    if (selection && panel.contains(document.activeElement)) trigger.focus();
                    return;
                }

                if (attached) {
                    this.ceplokPlace();
                    // `true` supaya guliran pada pembungkus mana pun ikut terdengar.
                    window.addEventListener('scroll', onMove, true);
                    window.addEventListener('resize', onMove);
                    resize = new ResizeObserver(onMove);
                    resize.observe(trigger);

                    // Perpindahan posisi tidak punya observer DOM, jadi selama
                    // panel terbuka kotak tombol dibandingkan per frame.
                    let box = trigger.getBoundingClientRect();
                    const track = () => {
                        const next = trigger.getBoundingClientRect();
                        if (next.left !== box.left || next.top !== box.top) {
                            box = next;
                            this.ceplokRequestPlace();
                        }
                        follow = requestAnimationFrame(track);
                    };
                    follow = requestAnimationFrame(track);
                }

                // Pada daftar pilihan, fokus jatuh ke baris yang sedang aktif
                // supaya panah bergerak dari sana, seperti `<select>` bawaan.
                if (selection) {
                    const target = panel.querySelector('[data-baris][aria-selected="true"]:not([aria-disabled="true"])') ?? this.ceplokRows()[0];
                    (target ?? trigger).focus();
                }
            });

            // Livewire yang merender ulang selagi panel terbuka mengganti atribut
            // `style` panel dengan versi server, yang tidak membawa posisinya;
            // posisinya dipasang lagi sebelum layar sempat digambar.
            if (attached) {
                new MutationObserver(() => {
                    if (this.ceplokOpen && panel.style.top === '') this.ceplokPlace();
                }).observe(panel, { attributes: true, attributeFilter: ['style'] });
            }

            panel.addEventListener('keydown', (event) => this.ceplokKeydown(event, selection));
            panel.addEventListener('keyup', (event) => this.ceplokHoldSpace(event));
            panel.addEventListener('click', (event) => {
                const row = event.target.closest('[data-baris]');
                if (row && panel.contains(row) && row.getAttribute('aria-disabled') !== 'true') this.ceplokClose();
            });

            // Menu aksi yang baru dibuka belum memindahkan fokus dari tombolnya,
            // jadi ketikan pertama ditangkap di tombol lalu dilanjutkan di panel.
            trigger.addEventListener('keydown', (event) => {
                if (!event.defaultPrevented && this.ceplokOpen && !selection) this.ceplokCapture(event);
            });
            trigger.addEventListener('keyup', (event) => this.ceplokHoldSpace(event));
        },

        destroy() {
            cancelAnimationFrame(follow);
            cancelAnimationFrame(frame);
            resize?.disconnect();
        },

        ceplokTrigger() {
            return this.$root.querySelector(':scope > button');
        },

        ceplokPanel() {
            return this.$root.querySelector(':scope > [popover]');
        },

        /** Baris yang masih bisa dipilih, urut tampilan. */
        ceplokRows() {
            return [...this.ceplokPanel().querySelectorAll('[data-baris]:not([aria-disabled="true"]):not(:disabled)')];
        },

        /** Menutup panel. Dicek dulu: `hidePopover()` pada panel yang tertutup melempar. */
        ceplokClose() {
            const panel = this.ceplokPanel();
            if (panel?.isConnected && panel.matches(':popover-open')) panel.hidePopover();
        },

        /**
         * Panel berada di top layer, jadi posisinya diukur sendiri dari kotak
         * tombol. Dibalik ke atas hanya bila tidak muat di bawah sekaligus
         * lebih lapang di atas; kalau dua-duanya sempit, tetap di bawah supaya
         * arah bukanya tidak berubah-ubah.
         */
        ceplokPlace() {
            const panel = this.ceplokPanel();
            const box = this.ceplokTrigger().getBoundingClientRect();

            panel.style.minWidth = `${Math.min(box.width, window.innerWidth - GAP * 2)}px`;

            const width = panel.offsetWidth || box.width;
            panel.style.left = `${Math.max(GAP, Math.min(box.left, window.innerWidth - width - GAP))}px`;

            const height = panel.offsetHeight;
            const below = window.innerHeight - box.bottom - GAP;
            panel.style.top = height > below && box.top - GAP > below ? `${Math.max(GAP, box.top - height - GAP)}px` : `${box.bottom + GAP}px`;
        },

        ceplokRequestPlace() {
            if (frame) return;
            frame = requestAnimationFrame(() => {
                frame = 0;
                this.ceplokPlace();
            });
        },

        /** Masih dalam satu rangkaian ketikan? */
        ceplokTyping() {
            return typed.text !== '' && Date.now() - typed.time < TYPE_DELAY;
        },

        /**
         * Mengetik melompat ke baris yang cocok. Ketikan yang berdekatan
         * digabung jadi satu kata kunci, spasi termasuk, jadi "jawa t" sampai
         * ke Jawa Tengah. Yang dicari dulu baris yang diawali kata kunci itu,
         * lalu baris yang salah satu katanya diawali kata kunci itu ("jakarta"
         * → DKI Jakarta). Huruf yang sama diketik berulang berpindah ke baris
         * berikutnya yang diawali huruf itu.
         */
        ceplokJump(letter) {
            const rows = this.ceplokRows();
            if (!rows.length) return;
            const current = rows.indexOf(document.activeElement);
            const text = normalize((this.ceplokTyping() ? typed.text : '') + letter);
            typed = { text, time: Date.now() };

            const repeated = [...text].every((ch) => ch === text[0]);
            const key = repeated ? text[0] : text;
            // Huruf baru atau ulangan dicari mulai baris sesudah yang aktif; kata
            // kunci yang bertambah panjang boleh tetap di baris yang aktif.
            const start = repeated ? current + 1 : Math.max(current, 0);
            const ordered = [...rows.slice(start), ...rows.slice(0, start)];

            (ordered.find((el) => rowName(el).startsWith(key)) ?? ordered.find((el) => atWordStart(rowName(el), key)))?.focus();
        },

        /**
         * Menangkap satu ketikan untuk lompat-ketik. Spasi hanya ditangkap di
         * tengah rangkaian ketikan; di luar itu ia tetap tombol memilih. Spasi
         * yang ditangkap juga ditahan saat dilepas, karena tombol baru bereaksi
         * pada `keyup` — tanpa itu, baris menu yang sedang difokus ikut tertekan.
         */
        ceplokCapture(event) {
            if (event.key.length !== 1 || event.ctrlKey || event.metaKey || event.altKey) return;
            if (event.key === ' ' && !this.ceplokTyping()) return;
            event.preventDefault();
            if (event.key === ' ') spaceHeld = true;
            this.ceplokJump(event.key);
        },

        ceplokHoldSpace(event) {
            if (event.key !== ' ' || !spaceHeld) return;
            event.preventDefault();
            spaceHeld = false;
        },

        ceplokKeydown(event, selection) {
            const rows = this.ceplokRows();
            if (!rows.length) return;

            // Navigasi panah, Home/End, dan Enter/Spasi hanya untuk daftar
            // pilihan. Menu aksi tetap memakai Tab dan Enter/Spasi bawaan
            // tombolnya; di sana yang ditambahkan hanya lompat-ketik.
            if (selection) {
                const current = rows.indexOf(document.activeElement);
                const go = (i) => {
                    event.preventDefault();
                    rows[i]?.focus();
                    // Bergerak dengan tombol memulai kata kunci baru pada ketikan berikutnya.
                    typed = { text: '', time: 0 };
                };

                switch (event.key) {
                    case 'ArrowDown':
                        return go(current < 0 ? 0 : (current + 1) % rows.length);
                    case 'ArrowUp':
                        return go(current < 0 ? rows.length - 1 : (current - 1 + rows.length) % rows.length);
                    case 'Home':
                        return go(0);
                    case 'End':
                        return go(rows.length - 1);
                    case 'Enter':
                    case ' ':
                        // Spasi di tengah ketikan adalah bagian kata kunci, bukan tanda memilih.
                        if (event.key === ' ' && this.ceplokTyping()) break;
                        // `option` bukan elemen interaktif, jadi pilihannya dijalankan di sini.
                        if (current >= 0) {
                            event.preventDefault();
                            rows[current].click();
                        }
                        return;
                    case 'Tab':
                        return this.ceplokClose();
                }
            }

            this.ceplokCapture(event);
        },
    };
}

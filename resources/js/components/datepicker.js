/**
 * Datepicker — pemilih tanggal dengan kalender di panel melayang, port
 * Datepicker di kit React.
 *
 * Nilainya `ceplokModel`, diikat x-modelable sehingga `wire:model` dan
 * `x-model` cukup dipasang di elemen terluar: `'YYYY-MM-DD'` untuk `single`,
 * `{ start, end }` (masing-masing `'YYYY-MM-DD'` atau null) untuk `period`
 * dan `multiple`, atau null bila kosong. Semua Waktu tanpa `min`/`max`
 * bernilai `{ start: null, end: null }` — rentang tanpa batas, berbeda dari
 * belum diisi. Setiap nilai berganti, event `change` (`detail.value`, bentuk
 * yang sama), padanan onChange, dikirim dari elemen terluar.
 *
 * Panelnya memakai Popover API seperti Dropdown. Tanggal ditelusuri dengan
 * panah, Home/End (awal/akhir minggu), dan PageUp/PageDown (bulan; dengan
 * Shift, tahun) — atau langsung diketik, mis. "17 agustus 1945", lalu Enter.
 *
 * Nama kelas dikirim dari Blade (`config.classes`) supaya ikut terpindai
 * Tailwind di aplikasi pemakai.
 */

const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

/** Jeda antar-ketikan yang masih dianggap satu rangkaian — sama dengan Dropdown. */
const TYPE_DELAY = 1000;

/** Jarak panel dari kotak tanggal, dan dari tepi layar. */
const GAP = 8;

/** Lebar panel per bentuk dalam piksel, untuk menempatkan panel sebelum ia bisa diukur. */
const PANEL_PX = { single: 284, period: 325, multiple: 600 };

/** Nama bulan untuk dicocokkan dengan ketikan: bahasa Indonesia dulu, lalu Inggris. */
const MONTH_NAMES = [
    BULAN.map((b) => b.toLowerCase()),
    ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'],
];

const monthFrom = (word) => {
    for (const names of MONTH_NAMES) {
        const i = names.findIndex((name) => name.startsWith(word));
        if (i !== -1) return i;
    }
    return null;
};

/**
 * Tanggal yang dimaksud sebuah ketikan, mis. "17", "17 agu", "17/8/1945",
 * "agustus 1945", atau "1945" — urutan hari-bulan-tahun. Bagian yang tidak
 * diketik diambil dari `anchor`, dan hari dipotong ke akhir bulannya.
 */
export function dateFromTyping(text, anchor) {
    let day = null;
    let month = null;
    let year = null;

    for (const part of text.toLowerCase().match(/\d+|[a-z]+/g) ?? []) {
        if (/^\d/.test(part)) {
            const n = Number(part);
            if (part.length === 4 && n >= 1000) {
                if (year === null) year = n;
            } else if (part.length <= 2 && day === null && n >= 1 && n <= 31) {
                day = n;
            } else if (part.length <= 2 && month === null && n >= 1 && n <= 12) {
                month = n - 1;
            }
        } else {
            const m = monthFrom(part);
            if (m === null) return null;
            if (month === null) month = m;
        }
    }

    if (day === null && month === null && year === null) return null;
    const y = year ?? anchor.getFullYear();
    const m = month ?? anchor.getMonth();
    const last = new Date(y, m + 1, 0).getDate();
    return new Date(y, m, Math.min(day ?? anchor.getDate(), last));
}

const plain = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
const monthStart = (d, shift = 0) => new Date(d.getFullYear(), d.getMonth() + shift, 1);
const addMonths = (d, n) => {
    const last = new Date(d.getFullYear(), d.getMonth() + n + 1, 0).getDate();
    return new Date(d.getFullYear(), d.getMonth() + n, Math.min(d.getDate(), last));
};
const key = (d) => d.getFullYear() * 10000 + d.getMonth() * 100 + d.getDate();
const sameMonth = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
const monthsBetween = (a, b) => (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth();
const two = (n) => String(n).padStart(2, '0');
export const iso = (d) => `${d.getFullYear()}-${two(d.getMonth() + 1)}-${two(d.getDate())}`;
export const fromIso = (s) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s ?? '');
    return m ? new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : null;
};
const write = (d) => `${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`;

/** Rentang dalam satu baris; bulan dan tahun yang sama tidak ditulis dua kali. */
function writeRange(a, b) {
    if (key(a) === key(b)) return write(a);
    if (a.getFullYear() !== b.getFullYear()) return `${write(a)} – ${write(b)}`;
    if (a.getMonth() !== b.getMonth()) return `${a.getDate()} ${BULAN[a.getMonth()]} – ${write(b)}`;
    return `${a.getDate()}–${write(b)}`;
}

/** Minggu-minggu utuh yang tampil untuk satu bulan, dimulai hari Minggu. */
function monthGrid(month) {
    const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
    const rows = Math.ceil((month.getDay() + days) / 7);
    const first = addDays(month, -month.getDay());
    return Array.from({ length: rows }, (_, r) => ({
        key: key(addDays(first, r * 7)),
        days: Array.from({ length: 7 }, (_, c) => {
            const date = addDays(first, r * 7 + c);
            return { key: key(date), date };
        }),
    }));
}

const inBounds = (d, b) => (!b.start || key(d) >= key(b.start)) && (!b.end || key(d) <= key(b.end));
const clamp = (d, b) => (b.start && key(d) < key(b.start) ? b.start : b.end && key(d) > key(b.end) ? b.end : d);

/**
 * Nilai sebuah pintasan, dipotong ke batas data. Semua Waktu adalah seluruh
 * rentang data. `undefined` bila rentangnya jatuh sepenuhnya di luar batas.
 */
function shortcutRange(name, today, b) {
    let r;
    switch (name) {
        case 'hapus':
            return null;
        case 'semua':
            return { start: b.start, end: b.end };
        case 'hari':
            r = { start: today, end: today };
            break;
        case 'minggu': {
            const first = addDays(today, -today.getDay());
            r = { start: first, end: addDays(first, 6) };
            break;
        }
        case 'bulan':
            r = { start: monthStart(today), end: new Date(today.getFullYear(), today.getMonth() + 1, 0) };
            break;
    }
    const start = b.start && key(r.start) < key(b.start) ? b.start : r.start;
    const end = b.end && key(r.end) > key(b.end) ? b.end : r.end;
    return key(start) <= key(end) ? { start, end } : undefined;
}

export default function datepicker(config) {
    const { type, mobile, classes: c } = config;
    const double = type === 'multiple';
    const bounds = { start: fromIso(config.min), end: fromIso(config.max) };
    let typed = { text: '', time: 0, anchor: null };
    let spaceHeld = false;
    let restoreFocus = false;
    let trigger = null;
    let place = null;

    return {
        ceplokModel: config.value,
        ceplokOpen: false,
        ceplokEnd: 'start',
        ceplokToday: plain(new Date()),
        ceplokLeft: null,
        ceplokRight: null,
        ceplokFocus: null,

        init() {
            const range = this.ceplokRange;
            this.ceplokLeft = monthStart(range?.start ?? this.ceplokToday);
            this.ceplokRight = monthStart(range?.start ?? this.ceplokToday, 1);
            this.ceplokFocus = range?.start ?? this.ceplokToday;

            const panel = this.$refs.panel;
            place = () => this.ceplokPlace();

            // `beforetoggle` menempatkan panel sebelum tampil supaya tidak
            // terlihat melompat; `toggle` mengulanginya setelah tingginya terukur.
            panel.addEventListener('beforetoggle', (event) => {
                this.ceplokOpen = event.newState === 'open';
                if (this.ceplokOpen) {
                    this.ceplokPlace();
                } else {
                    // Dicatat sebelum panel hilang: sesudahnya fokus sudah terlepas darinya.
                    restoreFocus = panel.contains(document.activeElement);
                    window.removeEventListener('scroll', place, true);
                    window.removeEventListener('resize', place);
                }
            });

            panel.addEventListener('toggle', (event) => {
                if (event.newState === 'open') {
                    this.ceplokPlace();
                    window.addEventListener('scroll', place, true);
                    window.addEventListener('resize', place);
                    // Fokus langsung jatuh ke tanggal terpilih — atau hari ini —
                    // supaya panah bisa dipakai tanpa Tab lebih dulu.
                    queueMicrotask(() => panel.querySelector('[data-fokus]')?.focus());
                } else if (restoreFocus) {
                    // Ditutup selagi fokus di dalamnya, jadi fokus kembali ke kotak yang membukanya.
                    restoreFocus = false;
                    trigger?.focus();
                }
            });

            // Livewire yang merender ulang selagi panel terbuka — mis. wire:model.live
            // setelah tanggal mulai dipilih — mengganti atribut `style` panel dengan
            // versi server, yang tidak membawa posisinya; posisinya dipasang lagi
            // sebelum layar sempat digambar.
            new MutationObserver(() => {
                if (this.ceplokOpen && panel.style.top === '') this.ceplokPlace();
            }).observe(panel, { attributes: true, attributeFilter: ['style'] });

            // Tinggi panel ikut jumlah minggu di bulan yang tampil.
            this.$watch('ceplokLeft', () => this.ceplokOpen && this.$nextTick(() => this.ceplokPlace()));
            this.$watch('ceplokRight', () => this.ceplokOpen && this.$nextTick(() => this.ceplokPlace()));
        },

        // Dilepas dari halaman selagi panelnya terbuka: pendengar di window ikut dilepas.
        destroy() {
            window.removeEventListener('scroll', place, true);
            window.removeEventListener('resize', place);
        },

        /** Nilai sebagai rentang Date tanpa jam — `single` pun disimpan begitu. */
        get ceplokRange() {
            const v = this.ceplokModel;
            if (v === null || v === undefined || v === '') return null;
            if (typeof v === 'string') {
                const d = fromIso(v);
                return d ? { start: d, end: d } : null;
            }
            return { start: fromIso(v.start), end: fromIso(v.end) };
        },

        get ceplokAllTime() {
            const r = this.ceplokRange;
            return r !== null && !r.start && !r.end;
        },

        ceplokSet(range) {
            const value = range === null ? null : type === 'single' ? (range.start ? iso(range.start) : null) : { start: range.start ? iso(range.start) : null, end: range.end ? iso(range.end) : null };
            this.ceplokModel = value;
            this.$root.dispatchEvent(new CustomEvent('change', { detail: { value }, bubbles: true }));
        },

        /** Teks kotak: rentang pada period, satu tanggal pada single dan tiap kotak multiple. */
        ceplokText(end = 'start') {
            const r = this.ceplokRange;
            if (this.ceplokAllTime) return 'Semua Waktu';
            if (!r) return null;
            if (double) {
                const d = end === 'start' ? r.start : r.end;
                return d ? write(d) : null;
            }
            if (type === 'single') return r.start ? write(r.start) : null;
            if (r.start && r.end) return writeRange(r.start, r.end);
            return r.start ? write(r.start) : null;
        },

        ceplokIso(end) {
            const d = this.ceplokRange?.[end];
            return d ? iso(d) : '';
        },

        ceplokActive(end) {
            return this.ceplokOpen && (!double || this.ceplokEnd === end);
        },

        /** Kotak ditekan: menyiapkan bulan dan fokus sebelum panel terbuka. */
        ceplokPrepare(event) {
            // Selagi panel terbuka, menekan kotak tidak mengulang pilihan yang
            // sedang berjalan — tanggal mulai tetap menunggu tanggal selesai.
            if (this.ceplokOpen) return;
            trigger = event.currentTarget;
            const today = plain(new Date());
            this.ceplokToday = today;
            this.ceplokEnd = 'start';

            const range = this.ceplokRange;
            const anchor = clamp(range?.start ?? today, bounds);
            const left = monthStart(anchor);
            this.ceplokFocus = anchor;
            this.ceplokLeft = left;
            this.ceplokRight = range?.end && monthsBetween(left, range.end) >= 1 ? monthStart(range.end) : monthStart(left, 1);
        },

        ceplokVisible(d) {
            return sameMonth(d, this.ceplokLeft) || (double && sameMonth(d, this.ceplokRight));
        },

        /** Tanggal yang menerima Tab: yang difokus, atau tanggal 1 bulan pertama bila ia sudah tidak tampil. */
        get ceplokTarget() {
            return this.ceplokFocus && this.ceplokVisible(this.ceplokFocus) ? this.ceplokFocus : clamp(this.ceplokLeft, bounds);
        },

        ceplokMonth(side) {
            return side === 'right' ? this.ceplokRight : this.ceplokLeft;
        },

        ceplokTitle(side) {
            const m = this.ceplokMonth(side);
            return m ? `${BULAN[m.getMonth()]} ${m.getFullYear()}` : '';
        },

        /** Minggu-minggu kalender, tiap tanggal lengkap dengan keadaannya — dihitung sekali per render. */
        ceplokWeeks(side) {
            const m = this.ceplokMonth(side);
            if (!m) return [];
            return monthGrid(m).map((week) => ({
                key: week.key,
                days: week.days.map((day) => ({ key: day.key, date: day.date, n: day.date.getDate(), ...this.ceplokDay(day, side) })),
            }));
        },

        ceplokCanPrev(side) {
            return !bounds.start || key(this.ceplokMonth(side)) > key(bounds.start);
        },

        ceplokCanNext(side) {
            return !bounds.end || key(monthStart(this.ceplokMonth(side), 1)) <= key(bounds.end);
        },

        /** Keadaan satu tanggal di satu kalender: kelas, terpilih, fokus. */
        ceplokDay(day, side) {
            const d = day.date;
            const month = this.ceplokMonth(side);
            const neighbour = double ? this.ceplokMonth(side === 'left' ? 'right' : 'left') : null;
            const r = this.ceplokRange;
            const ks = r?.start ? key(r.start) : null;
            const ke = r?.end ? key(r.end) : null;
            const full = ks !== null && ke !== null && ks !== ke;
            const k = key(d);
            const inMonth = sameMonth(d, month);
            // Tanggal luar bulan yang juga tampil di kalender sebelah hanya disorot di sana.
            const twin = !inMonth && !!neighbour && sameMonth(d, neighbour);
            const isStart = !twin && k === ks;
            const isEnd = !twin && k === ke;
            const selected = isStart || isEnd;
            const between = !twin && full && k > ks && k < ke;
            const enabled = inBounds(d, bounds);
            const focus = inMonth && k === key(this.ceplokTarget);

            return {
                selected: selected || between,
                focus,
                enabled,
                current: k === key(this.ceplokToday),
                label: `${HARI[d.getDay()]}, ${write(d)}`,
                class: [
                    // Pada rentang, ujungnya hanya membulat di sisi luar supaya menyambung.
                    selected && full ? (isStart ? 'rounded-l-lg' : 'rounded-r-lg') : between ? '' : 'rounded-lg',
                    selected ? c.selected : between ? `${c.between} ${c.date}` : !enabled ? c.outOfBounds : `${inMonth ? c.date : c.outside} ${c.hover}`,
                ].filter(Boolean).join(' '),
            };
        },

        ceplokPick(d) {
            this.ceplokFocus = d;
            if (type === 'single') {
                this.ceplokSet({ start: d, end: d });
                this.ceplokClose();
                return;
            }

            // Klik pertama mengisi tanggal mulai, klik kedua tanggal selesai —
            // baru setelah itu panel tertutup. Tanggal selesai yang jatuh
            // sebelum tanggal mulai memulai ulang dari tanggal itu.
            const start = this.ceplokRange?.start ?? null;
            if (this.ceplokEnd === 'start' || !start || key(d) < key(start)) {
                this.ceplokSet({ start: d, end: null });
                this.ceplokEnd = 'end';
            } else {
                this.ceplokSet({ start, end: d });
                this.ceplokClose();
            }
        },

        ceplokShortcutDisabled(name) {
            return shortcutRange(name, this.ceplokToday, bounds) === undefined;
        },

        ceplokApply(name) {
            const result = shortcutRange(name, this.ceplokToday, bounds);
            if (result === undefined) return;
            this.ceplokSet(result);
            this.ceplokClose();
        },

        ceplokClose() {
            const panel = this.$refs.panel;
            if (panel?.isConnected && panel.matches(':popover-open')) panel.hidePopover();
        },

        /**
         * Kalender kiri dan kanan pada `multiple` bisa digeser sendiri-sendiri;
         * yang kiri selalu dijaga lebih awal dari yang kanan.
         */
        ceplokShift(side, n) {
            if (side === 'left') {
                const left = monthStart(this.ceplokLeft, n);
                this.ceplokLeft = left;
                if (monthsBetween(left, this.ceplokRight) < 1) this.ceplokRight = monthStart(left, 1);
            } else {
                const right = monthStart(this.ceplokRight, n);
                this.ceplokRight = right;
                if (monthsBetween(this.ceplokLeft, right) < 1) this.ceplokLeft = monthStart(right, -1);
            }
        },

        /** Menggeser bulan yang tampil supaya tanggal hasil papan ketik ikut terlihat. */
        ceplokShow(d) {
            if (!double) {
                if (!sameMonth(d, this.ceplokLeft)) this.ceplokLeft = monthStart(d);
                return;
            }
            if (sameMonth(d, this.ceplokLeft) || sameMonth(d, this.ceplokRight)) return;
            if (key(d) < key(this.ceplokLeft)) {
                this.ceplokLeft = monthStart(d);
                this.ceplokRight = monthStart(d, 1);
            } else {
                this.ceplokLeft = monthStart(d, -1);
                this.ceplokRight = monthStart(d);
            }
        },

        ceplokFocusDate(d) {
            this.ceplokFocus = d;
            this.ceplokShow(d);
            // queueMicrotask, bukan $nextTick: $nextTick menunggu setTimeout,
            // jadi tombol berikutnya yang sudah antre bisa jatuh ke tombol tanggal
            // lama yang baru dibuang x-for. Pembaruan DOM Alpine sendiri berjalan
            // di microtask yang diantrekan lebih dulu, jadi di sini kisinya sudah baru.
            queueMicrotask(() => this.$refs.panel.querySelector('[data-fokus]')?.focus());
        },

        ceplokGridKeydown(event) {
            const steps = {
                ArrowLeft: (d) => addDays(d, -1),
                ArrowRight: (d) => addDays(d, 1),
                ArrowUp: (d) => addDays(d, -7),
                ArrowDown: (d) => addDays(d, 7),
                Home: (d) => addDays(d, -d.getDay()),
                End: (d) => addDays(d, 6 - d.getDay()),
                PageUp: (d) => addMonths(d, event.shiftKey ? -12 : -1),
                PageDown: (d) => addMonths(d, event.shiftKey ? 12 : 1),
            };
            const step = steps[event.key];
            if (!step) return this.ceplokType(event);
            event.preventDefault();
            // Bergerak dengan tombol memulai ketikan baru pada huruf berikutnya.
            typed.text = '';
            // Berhenti di tepi batas data: tanggal di luarnya tidak bisa difokus.
            this.ceplokFocusDate(clamp(step(this.ceplokTarget), bounds));
        },

        /**
         * Mengetik tanggal melompat langsung ke sana. Ketikan dengan jeda di
         * bawah satu detik digabung; spasi di tengahnya dan Backspace ikut
         * dihitung. Enter lalu memilih tanggalnya, seperti biasa.
         */
        ceplokType(event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.isComposing) return;
            const typing = typed.text !== '' && event.timeStamp - typed.time < TYPE_DELAY;

            let text;
            if (event.key === 'Backspace') {
                if (!typing) return;
                text = typed.text.slice(0, -1);
            } else if (event.key.length === 1 && /[a-z\d\s/.,-]/i.test(event.key)) {
                // Spasi di luar rangkaian ketikan tetap tombol memilih tanggal.
                if (event.key === ' ' && !typing) return;
                text = (typing ? typed.text : '') + event.key;
            } else {
                return;
            }

            event.preventDefault();
            if (event.key === ' ') spaceHeld = true;
            const anchor = typing && typed.anchor ? typed.anchor : this.ceplokTarget;
            typed = { text, time: event.timeStamp, anchor };
            // Ketikan yang dihapus habis kembali ke tanggal sebelum mulai mengetik.
            const target = text.trim() === '' ? anchor : dateFromTyping(text, anchor);
            if (target) this.ceplokFocusDate(clamp(target, bounds));
        },

        // Spasi yang jadi bagian ketikan ditahan juga saat dilepas: tombol
        // tanggal baru bereaksi pada keyup.
        ceplokGridKeyup(event) {
            if (event.key !== ' ' || !spaceHeld) return;
            event.preventDefault();
            spaceHeld = false;
        },

        /**
         * Panel di top layer, jadi posisinya diukur dari kotak tanggalnya: rata
         * kiri, 8px di bawahnya, dan dibalik ke atas bila tidak muat di bawah
         * sekaligus lebih lapang di atas. Bila tidak muat di sisi mana pun,
         * tingginya dibatasi dan isinya digulir di dalam panel.
         */
        ceplokPlace() {
            const panel = this.$refs.panel;
            const box = this.$refs.anchor.getBoundingClientRect();
            // Pada mobile panel selebar kotaknya, tetapi tidak lebih sempit dari
            // panel single, supaya kisi 7 × 36px tetap muat.
            const follow = mobile ? Math.max(box.width, PANEL_PX.single) : null;
            panel.style.width = follow ? `${follow}px` : '';
            const width = panel.offsetWidth || Math.min(follow ?? PANEL_PX[type], window.innerWidth - GAP * 2);
            panel.style.left = `${Math.max(GAP, Math.min(box.left, window.innerWidth - width - GAP))}px`;

            const height = panel.scrollHeight;
            const below = window.innerHeight - box.bottom - GAP * 2;
            const above = box.top - GAP * 2;
            const up = height > below && above > below;
            const room = Math.max(up ? above : below, 0);
            panel.style.maxHeight = height > room ? `${room}px` : '';
            panel.style.top = up ? `${box.top - GAP - Math.min(height, room)}px` : `${box.bottom + GAP}px`;
        },
    };
}

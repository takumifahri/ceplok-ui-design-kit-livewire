/**
 * Popover — panel informasi non-modal yang ditambatkan pada satu trigger,
 * port Popover di kit React. Peramban yang mengurus top layer, Escape, dan
 * light dismiss lewat Popover API (`popover="auto"`); di sini keadaan
 * buka-tutup dan posisinya.
 *
 * `open` diikat x-modelable, jadi `wire:model` atau `x-model` di tag Popover
 * mengendalikannya, padanan `open` + `onOpenChange`. Setiap perubahan juga
 * mengirim event `open-change` (`detail.open`). Nilai awalnya dari
 * `data-open`, dan sisi yang diminta dari `data-side` panel saat posisi
 * dihitung, jadi ekspresi x-data tetap sama di setiap render Livewire.
 *
 * Posisi, sisi panah, dan surface panah disimpan sebagai state lalu dipasang
 * markup lewat x-bind, bukan diubah langsung di `style`: morph Livewire
 * mengembalikan atribut yang diubah JS, tapi menghormati x-bind.
 */

const PANEL_GAP = 8;
const VIEWPORT_GUTTER = 8;
const ARROW_WRAPPER_HALF = 8;
const ARROW_VISUAL_HALF = (10 * Math.SQRT2) / 2;
const ARROW_DIVIDER_GAP = 1;
const DEFAULT_PANEL_WIDTH = 255;

const opposite = { top: 'bottom', right: 'left', bottom: 'top', left: 'right' };

const clamp = (value, min, max) => Math.min(Math.max(value, min), Math.max(min, max));

const isDisabled = (el) => el.matches(':disabled') || el.getAttribute('aria-disabled') === 'true';

const isFocusable = (el) => !isDisabled(el)
    && el.matches('button, a[href], input, select, textarea, [contenteditable="true"], [tabindex]:not([tabindex="-1"])');

export default function popover() {
    let frame = 0;
    let observer = null;
    let anchorOutside = false;
    let restoreFocus = false;
    let schedule = () => {};
    let markEscape = () => {};

    return {
        open: false,
        ready: false,
        side: 'right',
        arrowOffset: DEFAULT_PANEL_WIDTH / 2,
        arrowSurface: 'body',
        left: 0,
        top: 0,

        init() {
            const panel = this.$refs.panel;
            this.side = panel.dataset.side ?? 'right';
            this.arrowSurface = this.side === 'bottom' ? 'header' : 'body';
            this.open = this.$el.dataset.open === 'true';

            // Posisi dihitung paling banyak sekali per frame saat menggulir atau mengubah ukuran.
            schedule = () => {
                if (frame) return;
                frame = requestAnimationFrame(() => {
                    frame = 0;
                    this.position();
                });
            };
            markEscape = (event) => {
                if (event.key === 'Escape' && panel.contains(document.activeElement)) restoreFocus = true;
            };
            document.addEventListener('keydown', markEscape, true);

            panel.addEventListener('toggle', (event) => {
                const actual = event.newState === 'open';

                if (actual) {
                    window.addEventListener('scroll', schedule, true);
                    window.addEventListener('resize', schedule);
                    observer = new ResizeObserver(schedule);
                    observer.observe(this.$refs.trigger);
                    observer.observe(panel);
                    this.position();
                } else {
                    this.ready = false;
                    this.stopFollowing();
                    this.restore();
                }

                // Light dismiss, Escape, atau Popover lain yang terbuka.
                if (actual !== this.open) {
                    this.open = actual;
                    this.$dispatch('open-change', { open: actual });
                }
            });

            this.$watch('open', (value) => this.sync(value));
            this.$nextTick(() => this.sync(this.open));
        },

        destroy() {
            document.removeEventListener('keydown', markEscape, true);
            this.stopFollowing();
        },

        stopFollowing() {
            window.removeEventListener('scroll', schedule, true);
            window.removeEventListener('resize', schedule);
            observer?.disconnect();
            observer = null;
            cancelAnimationFrame(frame);
            frame = 0;
        },

        /** Menyamakan panel dengan `open`. */
        sync(value) {
            const panel = this.$refs.panel;
            const trigger = this.$refs.trigger;
            if (!panel || !trigger?.isConnected) return;

            const shown = panel.matches(':popover-open');
            if (value && !shown) {
                anchorOutside = false;
                this.ready = false;
                // `source` menautkan panel ke trigger, jadi klik trigger tidak
                // dianggap klik di luar (light dismiss) lalu membukanya lagi.
                try {
                    panel.showPopover({ source: trigger });
                } catch {
                    panel.showPopover();
                }
                this.position();
            } else if (!value && shown) {
                if (panel.contains(document.activeElement)) restoreFocus = true;
                panel.hidePopover();
            }
        },

        request(next) {
            if (next === this.open) return;
            this.open = next;
            this.$dispatch('open-change', { open: next });
        },

        toggle(event) {
            if (event?.defaultPrevented || isDisabled(this.$refs.trigger)) return;
            this.request(!this.open);
        },

        /** Fokus kembali ke trigger setelah ditutup dengan Escape dari dalam panel. */
        restore() {
            const trigger = this.$refs.trigger;
            if (!restoreFocus) return;
            restoreFocus = false;
            if (!trigger?.isConnected || !isFocusable(trigger)) return;

            requestAnimationFrame(() => {
                if (trigger.isConnected && !this.$refs.panel.matches(':popover-open')) {
                    trigger.focus({ preventScroll: true });
                }
            });
        },

        position() {
            const panel = this.$refs.panel;
            const anchor = this.$refs.trigger;
            if (!panel || !anchor?.isConnected) return;

            const preferred = panel.dataset.side ?? 'right';
            const rect = anchor.getBoundingClientRect();
            const outside = rect.bottom <= 0 || rect.top >= innerHeight || rect.right <= 0 || rect.left >= innerWidth;

            // Trigger tergulir keluar layar: panel ditutup, bukan melayang sendirian.
            if (outside) {
                this.ready = false;
                if (!anchorOutside) {
                    anchorOutside = true;
                    this.request(false);
                }
                return;
            }

            anchorOutside = false;
            const width = panel.offsetWidth || DEFAULT_PANEL_WIDTH;
            const height = panel.offsetHeight || panel.scrollHeight;
            const spaces = {
                top: rect.top - VIEWPORT_GUTTER,
                right: innerWidth - rect.right - VIEWPORT_GUTTER,
                bottom: innerHeight - rect.bottom - VIEWPORT_GUTTER,
                left: rect.left - VIEWPORT_GUTTER,
            };
            const needed = preferred === 'left' || preferred === 'right' ? width + PANEL_GAP : height + PANEL_GAP;
            const other = opposite[preferred];
            const side = spaces[preferred] < needed && spaces[other] > spaces[preferred] ? other : preferred;

            let left = rect.left + (rect.width - width) / 2;
            let top = rect.top + (rect.height - height) / 2;

            if (side === 'top') top = rect.top - height - PANEL_GAP;
            if (side === 'right') left = rect.right + PANEL_GAP;
            if (side === 'bottom') top = rect.bottom + PANEL_GAP;
            if (side === 'left') left = rect.left - width - PANEL_GAP;

            left = clamp(left, VIEWPORT_GUTTER, innerWidth - width - VIEWPORT_GUTTER);
            top = clamp(top, VIEWPORT_GUTTER, innerHeight - height - VIEWPORT_GUTTER);

            // Panah di sisi kiri/kanan tidak boleh menimpa garis bawah header.
            const horizontal = side === 'top' || side === 'bottom';
            const header = panel.lastElementChild?.firstElementChild;
            const headerHeight = header?.offsetHeight ?? 0;
            const dividerSafe = headerHeight + ARROW_VISUAL_HALF + ARROW_DIVIDER_GAP;
            const overlapsDivider = (offset) => !horizontal
                && headerHeight > 0
                && offset + ARROW_VISUAL_HALF + ARROW_DIVIDER_GAP >= headerHeight
                && offset - ARROW_VISUAL_HALF - ARROW_DIVIDER_GAP <= headerHeight;

            if (!horizontal) {
                const center = rect.top + rect.height / 2;
                if (overlapsDivider(center - top)) {
                    top = clamp(center - dividerSafe, VIEWPORT_GUTTER, innerHeight - height - VIEWPORT_GUTTER);
                }
            }

            const raw = horizontal ? rect.left + rect.width / 2 - left : rect.top + rect.height / 2 - top;
            const adjusted = overlapsDivider(raw) ? Math.max(raw, dividerSafe) : raw;
            const arrowOffset = clamp(adjusted, 16, (horizontal ? width : height) - 16);

            this.left = left;
            this.top = top;
            this.side = side;
            this.arrowOffset = arrowOffset;
            this.arrowSurface = side === 'bottom' || (!horizontal && arrowOffset + ARROW_VISUAL_HALF <= headerHeight)
                ? 'header'
                : 'body';
            this.ready = true;
        },

        /** Gaya panel, dipasang lewat x-bind:style. */
        panelStyle() {
            return { left: `${this.left}px`, top: `${this.top}px` };
        },

        /** Gaya panah: bergeser sepanjang tepi panel yang menghadap trigger. */
        arrowStyle() {
            const offset = `${this.arrowOffset - ARROW_WRAPPER_HALF}px`;
            return this.side === 'top' || this.side === 'bottom' ? { left: offset, top: '' } : { top: offset, left: '' };
        },
    };
}

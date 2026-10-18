/**
 * Search: event `search` (padanan onSearch di React) dan `category-change`
 * (padanan onCategoryChange), keduanya dikirim dari elemen input-nya karena
 * atribut pemakai diteruskan ke sana, mis. `wire:search="cari($event.detail.value)"`.
 * `detail.value` isi field, `detail.category` kategori yang aktif.
 *
 * `search` dikirim saat Enter ditekan di dalam field atau tombol cari
 * ditekan; dengan trigger `change`/`auto` juga setiap isian berubah, dengan
 * jeda `debounce` milidetik, dan setiap kategori diganti. Pembungkusnya
 * `role="search"`, bukan `<form>` (form bersarang tidak sah), jadi Enter
 * ditangani di sini.
 */
export default function search(trigger = 'enter', debounce = 300) {
    let timer = null;
    let input = null;
    let category = null;

    return {
        init() {
            input = this.$root.querySelector('input');
            category = this.$root.querySelector('select');
            const live = trigger === 'change' || trigger === 'auto';

            // Didaftarkan sesudah directive Alpine milik pemakai terpasang,
            // supaya x-on:keydown pemakai berjalan lebih dulu dan bisa
            // membatalkannya, seperti onKeyDown di React.
            queueMicrotask(() =>
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.defaultPrevented) {
                        event.preventDefault();
                        this.ceplokSubmit();
                    }
                }),
            );

            if (live) {
                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    // Isian dan kategori dicatat saat mengetik, bukan saat jedanya habis.
                    const value = input.value;
                    const chosen = category?.value ?? '';
                    const fire = () => this.ceplokEmit(value, chosen);
                    if (debounce > 0) timer = setTimeout(fire, debounce);
                    else fire();
                });
            }

            category?.addEventListener('change', () => {
                input.dispatchEvent(new CustomEvent('category-change', { detail: { value: category.value }, bubbles: true }));
                if (live) this.ceplokEmit(input.value);
            });
        },

        destroy() {
            clearTimeout(timer);
        },

        ceplokSubmit() {
            clearTimeout(timer);
            this.ceplokEmit(input.value);
        },

        ceplokEmit(value, chosen = category?.value ?? '') {
            input.dispatchEvent(new CustomEvent('search', { detail: { value, category: chosen }, bubbles: true }));
        },
    };
}

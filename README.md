# ceplok-ui/design-kit-livewire

Design token, tipografi, dan komponen **Blade + Alpine.js (Tailwind v4)** untuk
Ceplok Design System, untuk aplikasi Laravel dan Livewire. Padanan
`@ceplok-ui/design-kit-react`: markup, class, token, dan ikonnya sama persis
dengan kit React. Kesetaraannya
diuji dengan membandingkan DOM, atribut, dan computed style kedua kit di
browser.

Semua komponen adalah komponen Blade anonim yang dikompilasi
[Blaze](https://github.com/livewire/blaze), jadi tetap ringan walau dirender
berulang kali di dalam komponen Livewire.

## Isi

- **Token** (`@theme`): palet warna, alias semantik (`brand`, `feedback-*`,
  `content`, `surface`, `border`), token komponen (`alert-*`, `toast-*`,
  `breadcrumb-*`, …), skala tipografi (`display` → `caption`), spacing,
  radius, shadow, font Lato serta font alternatif Inter (`font-alt`) dan Arial
  (`font-alt-2`). Disalin apa adanya dari kit React.
- **Komponen**: `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `card`,
  `container`, `dropdown`, `footer`, `hero`, `icon`, `pagination`, `spinner`,
  `toast`.
- **Elemen form**: `checkbox`, `datepicker`, `floating-label`, `input-field`,
  `radio`, `search`, `select`, `text-area`, `toggle`, `upload`.
- **Ikon**: 411 ikon outline dan 340 ikon solid Flowbite (salinan persis
  `flowbite-react-icons` yang dipakai kit React), plus 30 logo brand.
- **Plugin Alpine** untuk perilaku interaktif: buka/tutup Alert, Badge, dan
  Toast, foto Avatar yang gagal dimuat, Pagination, panel Dropdown beserta
  daftar pilihan Select dan kategori Search, kalender Datepicker, Upload,
  label Floating Label, kata sandi dan tombol hapus Input Field, serta
  toolbar Text Area.

## Persyaratan

- PHP 8.2+, Laravel 11, 12, atau 13.
- Tailwind CSS v4 di aplikasi consumer (paket mengirim CSS sumber, bukan CSS
  yang sudah dikompilasi).
- Alpine.js untuk komponen interaktif. Livewire 4 sudah membawanya.

## Install

```bash
composer require ceplok-ui/design-kit-livewire
```

Service provider terdaftar otomatis (package discovery). Selama paket belum
dipublikasikan ke registry, pasang lewat path repository; lihat
[packages/README.md](../README.md).

### CSS

Di `resources/css/app.css`:

```css
@import '../../vendor/ceplok-ui/design-kit-livewire/resources/css/styles.css';
@source '../../vendor/ceplok-ui/design-kit-livewire/resources/views/**/*.blade.php';
```

Jangan menambahkan `@import 'tailwindcss'` lagi: `styles.css` sudah
memanggilnya, berikut token, font, base layer, class `.ds-*`, dan animasi
masuk komponen. Baris `@source` wajib ada karena Tailwind v4 tidak memindai
`vendor/`. Tanpa baris itu komponen tetap dirender, tetapi tanpa satu pun
class utility-nya, dan tidak ada error yang muncul.

Bila aplikasi sudah punya base style sendiri dan hanya butuh token:

```css
@import 'tailwindcss';
@import '../../vendor/ceplok-ui/design-kit-livewire/resources/css/tokens.css';
@source '../../vendor/ceplok-ui/design-kit-livewire/resources/views/**/*.blade.php';
```

Dengan cara ini font, base layer, class `.ds-*`, dan animasi masuk Alert,
Badge, serta Toast ikut hilang.

Font Lato dan Inter dimuat dari Google Fonts oleh browser, jadi build tidak
butuh koneksi internet.

### JavaScript

Dengan Livewire 4, bundel Livewire dan Alpine-nya di `resources/js/app.js`,
lalu daftarkan plugin kit sebelum Livewire berjalan:

```js
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import ceplok from '../../vendor/ceplok-ui/design-kit-livewire/resources/js/index.js';

Alpine.plugin(ceplok);
Livewire.start();
```

Layout-nya memakai `@livewireStyles` dan `@livewireScriptConfig`, bukan
`@livewireScripts`. Jangan memuat Alpine terpisah: Alpine yang dimuat dua kali
gagal diinisialisasi.

Tanpa Livewire:

```js
import Alpine from 'alpinejs';
import ceplok from '../../vendor/ceplok-ui/design-kit-livewire/resources/js/index.js';

Alpine.plugin(ceplok);
Alpine.start();
```

## Pakai

```blade
<x-ceplok::card title="Halo" description="Kartu dari design kit">
    <x-slot:actions>
        <x-ceplok::button :left-icon="svg('ceplok-o-check')">Simpan</x-ceplok::button>
    </x-slot:actions>
</x-ceplok::card>
```

Prop ditulis kebab-case (`dark-mode`, `icon-only`, `current-page`). Nilai
pilihan bisa string atau kasus enum di `CeplokUi\DesignKit\Enums`, mis.
`:variant="ButtonVariant::Outline"`, berguna sebagai properti Livewire yang
bertipe.

### Prop diperiksa saat render

Blade tidak punya tipe untuk prop komponen anonim, jadi kit memeriksanya
sendiri. Nilai yang tidak dikenal, mis. `<x-ceplok::badge variant="hijau">`,
langsung menjadi error yang menyebut komponen, prop, dan pilihan yang sah
selama `app.debug` aktif (lokal dan tes). Di production halaman tidak ikut
gagal: komponen memakai nilai bawaannya dan kesalahannya dilaporkan ke log.

Atribut statis selalu tiba sebagai string, jadi `dismissible="false"` di Blade
biasa berarti **true**. Kit menerima `"true"`/`"false"` (juga `"1"`/`"0"`) apa
adanya dan menolak nilai lain, tetapi bentuk yang dianjurkan tetap
`:dismissible="false"`.

## Komponen & props

| Komponen | Props utama |
| --- | --- |
| `button` | `variant`: `filled \| outline`, `theme`: `primary \| green \| gray \| purple \| orange \| yellow`, `tone`: `light \| dark`, `size`: `xs \| s \| base \| l \| xl`, `type`, `icon-only`, `as`: `button \| anchor`, slot/prop `left-icon`, `right-icon` |
| `badge` | `variant`: `gray \| brand \| danger \| warning \| success`, `size`: `sm \| lg`, `icon`, `dismissible`, `open`, `dark-mode` |
| `avatar` | `src`, `alt`, `initials` (atau slot), `size`: `small \| default \| large`, `dark-mode` |
| `spinner` | `size`: `default \| large`, `aria-label`, `aria-hidden` |
| `alert` | `variant`: `success \| danger \| warning \| info \| purple`, `heading`, `icon` (slot, atau `:icon="false"`), `dismissible`, `open`, slot `actions`, `dark-mode` |
| `toast` | `variant`, `heading`, `icon`, `dismissible`, `open`, slot `actions`, `dark-mode` |
| `card` | `image`, `image-alt`, `title`, `description` (prop atau slot), `href`, `link-label`, slot `actions`, `dark-mode` |
| `container` | `size`: `prose \| default \| wide \| full`, `as` (bawaan `div`), `padded`, `dark-mode` |
| `icon` | slot berisi `<svg>` yang memakai `currentColor` |
| `breadcrumb` | `items` (`label`, `href`, `icon`, `attributes`), slot `separator`, `size`: `sm \| base`, `background`: `bg \| none`, `dark-mode` |
| `pagination` | `current-page`, `total-pages`, `theme`: `default \| primary \| purple`, `size`: `base \| s \| responsive`, `url`, `dark-mode` |
| `hero` | `type`, `centered-content`, `platform`, `heading`, `sub-heading`, `description`, `show-heading`, `show-button`, `button-label`, `button-href`, `button-attributes`, `image`, `image-alt`, `image-orientation`, slot `image-content`, `dark-mode` |
| `footer` | `logo`/`logo-alt` atau slot `logo-content`, `menus`, `copyright` (prop atau slot), `socials`, `fluid` |
| `dropdown` | slot `trigger` (atribut slot menjadi props Button kit, atau `<button>` polos dengan `raw`), `items` atau `groups` (`label`, `separator`, `items`), slot isi bebas, `attached`, `content-label`, `content-class`, `panel-id`, `dark-mode`. Tiap item: `label`, `description`, `icon`, `href`, `tone`: `default \| danger`, `disabled`, `selected`, `selected-when`, `attributes` |
| `input-field` | `label`, `helper-text`, `icon` (slot, atau `:icon="false"` untuk melepas gembok password), `type`, `platform`: `default \| mobile`, `state`: `default \| typing \| inactive \| failed`, `application`: `default \| purple`, `clearable`, `dark-mode` |
| `floating-label` | `label`, `helper-text`, `icon`, `platform`, `state`: `default \| active \| error`, `application`, `clearable`, `dark-mode` |
| `text-area` | `label`, `hint`, `helper-text`, `type`: `default \| editor`, `platform`, `application`, slot `toolbar`, `submit-label`, `dark-mode` |
| `select` | `label`, `info`, `helper-text`, `placeholder`, `options` (atau `<option>`/`<optgroup>` di slot), `value`, `state`: `default \| inactive`, `application`, `dark-mode` |
| `search` | `label`, `helper-text`, `button-label`, `trigger`: `enter \| change \| auto`, `debounce`, `platform`: `default \| mobile \| compact`, `show-button`, `application`, `categories`, `category`, `category-placeholder`, `category-attributes`, `dark-mode` |
| `datepicker` | `type`: `single \| period \| multiple`, `label`, `placeholder`, `end-placeholder`, `shortcuts`, `min`, `max`, `platform`, `value`, `name`, `disabled`, `dark-mode` |
| `upload` | `label`, `helper-text`, `type`: `default \| attach`, `platform`, `application`, `button-label`, `replace-label`, `remove-label`, `placeholder`, `attach-label`, `attach-hint`, `dark-mode` |
| `checkbox`, `radio`, `toggle` | `label`, `helper-text`, `platform`: `default \| mobile`, `state`: `default \| inactive`, `application`: `default \| purple`, `dark-mode` |

Setiap komponen meneruskan atribut lain (`class`, `id`, `wire:click`,
`aria-*`, …) ke elemen terluarnya. Class pemakai disambung ke class bawaan,
sama seperti `cn()` di kit React.

Elemen form berbeda: atribut lain (`name`, `value`, `wire:model`, `required`,
`placeholder`, …) diteruskan ke kontrol aslinya — `<input>`, `<textarea>`,
atau `<select>` tersembunyi pada Select — sedangkan `class` dan `wire:key` ke
pembungkus terluar. Datepicker dan Dropdown meneruskan semuanya ke pembungkus
terluar. `id` kontrol dibuat stabil bila tidak diisi, jadi `<label for>` dan
`aria-describedby` tetap tertaut tanpa perlu menulis id sendiri.

## Livewire

| Komponen | Integrasi |
| --- | --- |
| `badge`, `alert`, `toast` | `wire:model` mengikat tampil/sembunyi ke properti boolean, dan nilai awalnya dibaca dari properti itu, jadi HTML pertama dari server sudah benar. Tombol tutup memicu event `dismiss`: `wire:dismiss="hapusFilter('aktif')"`. |
| `pagination` | `wire:model.live="page"` mengikat halaman aktif. Setiap klik juga mengirim event `page-change` dengan `detail.page`. Untuk halaman Blade biasa, `url="/pengajuan?page={page}"` atau closure membuat setiap halaman menjadi tautan. |
| `breadcrumb` | `'attributes' => ['wire:navigate' => true]` pada item diteruskan ke tautannya. |
| `hero` | `:button-attributes="['wire:click' => 'mulai']"` diteruskan ke tombolnya. |
| `input-field`, `floating-label`, `text-area` | `wire:model` diteruskan ke kontrolnya, dan nilai awalnya dirender server (kecuali kata sandi). Tombol hapus memicu event `clear`; Text Area editor memicu `submit` dan `toolbar-action` (`detail.action`): `wire:submit="kirim"`, `wire:toolbar-action="sisipkan($event.detail.action)"`. |
| `select` | `wire:model` dibawa `<select>` tersembunyi, dengan pilihan awal yang dirender server. Opsi boleh berganti dari server (mis. kabupaten yang mengikuti provinsi); labelnya ikut terbaca dari opsi terbaru. |
| `search` | Event `search` (`detail.value`, `detail.category`) saat Enter atau tombol cari ditekan, atau setiap isian berubah dengan `trigger="auto"`: `wire:search="cari($event.detail.value)"`. Kategori diikat lewat `:category-attributes="['wire:model.live' => 'kategori']"`. |
| `datepicker` | `wire:model` dipasang di tag komponennya: `'YYYY-MM-DD'` untuk single, `['start' => …, 'end' => …]` untuk period dan multiple. Event `change` (`detail.value`) di setiap pilihan; dengan `wire:model.live` rentang yang baru terisi tanggal mulainya tetap bisa diselesaikan. |
| `upload` | `wire:model` untuk unggahan Livewire (`WithFileUploads`), termasuk berkas yang dijatuhkan ke area attach. Tombol Remove ikut mengosongkan propertinya. Event `files-change` (`detail.files`). |
| `checkbox`, `radio`, `toggle` | `wire:model` dengan aturan Livewire: radio tercentang bila propertinya sama dengan `value`-nya, checkbox pada properti array bila `value`-nya ada di array itu, selebihnya mengikuti nilai boolean. Keadaan awalnya dirender server. |
| `dropdown` | `'attributes' => ['wire:click' => 'hapus(3)']` per item; panel menutup sendiri setelah baris ditekan. Pada daftar pilihan, `selected` dibaca saat render dan `selected-when` (ekspresi Alpine) mengikutinya di browser. |

```blade
{{-- Filter yang bisa dihapus, terikat ke $filters['aktif'] --}}
<x-ceplok::badge variant="brand" dismissible wire:model.live="filters.aktif">
    Status: Aktif
</x-ceplok::badge>

{{-- Daftar dengan halaman yang tercatat di alamat lewat #[Url] --}}
<x-ceplok::pagination :current-page="$page" :total-pages="$this->totalPages" wire:model.live="page" />
```

Dengan `x-model` (tanpa Livewire), beri juga `:open` sesuai nilai awalnya.

Keadaan Alpine komponen kit bertahan saat Livewire merender ulang. Ekspresi
`x-data`-nya sengaja tidak memuat nilai yang ikut berganti — Alpine
menginisialisasi ulang data yang ekspresinya berubah — jadi fokus, panel
yang terbuka, dan rentang tanggal yang sedang dipilih tidak ikut diulang.

## Tema gelap

Warna komponen berasal dari token semantik, jadi tema gelap cukup mengganti
nilai token lewat atribut `data-theme="dark"`:

- Prop `dark-mode` memasang `data-theme="dark"` pada elemen terluar `card`,
  `hero`, `container`, `alert`, `toast`, `breadcrumb`, `input-field`,
  `floating-label`, `text-area`, `select`, `search`, `checkbox`, `radio`, dan
  `toggle`. Panel pilihan Select tetap terang, sesuai desain.
- Komponen tersebut ikut gelap bila salah satu pembungkusnya memakai
  `data-theme="dark"`. Bagian di dalamnya bisa dikembalikan terang dengan
  `data-theme="light"`.
- `badge`, `avatar`, `pagination`, `dropdown`, `datepicker`, dan `upload`
  belum berbasis token: `dark-mode` mengganti class warnanya langsung, sama
  seperti di kit React.

## Ikon

```blade
{{ svg('ceplok-o-bell', 'size-5 text-gray-500') }}   {{-- outline --}}
<x-ceplok-s-bell class="size-5 text-primary-600" />  {{-- solid --}}
<x-brand-github class="size-5" />                     {{-- logo brand --}}
```

`Bell` dari `icons/outline` di React menjadi `ceplok-o-bell`, dan dari
`icons/solid` menjadi `ceplok-s-bell`. Logo brand selalu dekoratif
(`aria-hidden="true"` bawaan); nama brand-nya dibawa label tautan di
sekitarnya. Ikon antarmuka tidak: bila ikon menjadi satu-satunya isi tombol,
beri tombolnya `aria-label`.

## Aksesibilitas

- `button` dengan `icon-only` wajib diberi `aria-label`, begitu pula trigger
  Dropdown yang hanya berisi ikon.
- Elemen form tanpa `label` perlu `aria-label`; Select, Search, dan
  Datepicker meneruskannya ke kontrol yang terlihat.
- `avatar`: `alt` adalah nama pemiliknya. Kosongkan bila nama itu sudah
  tertulis di sebelah avatar, supaya tidak diumumkan dua kali.
- `spinner` diumumkan sebagai `role="status"` berlabel "Loading". Bila ada
  teks pendamping, pasang `aria-hidden="true"`; role dan label-nya ikut
  dilepas.
- Blade mencetak atribut boolean `true` sebagai `nama="nama"`, jadi
  `:aria-pressed="true"` menjadi `aria-pressed="aria-pressed"`, nilai yang
  tidak sah untuk ARIA. Tulis nilai `aria-*` sebagai string, mis.
  `:aria-pressed="$aktif ? 'true' : 'false'"`. Spinner sudah menormalkan
  `aria-hidden`-nya sendiri.

## Menyesuaikan komponen

```bash
php artisan vendor:publish --tag=ceplok-views
```

Salinan komponen masuk ke `resources/views/vendor/ceplok/components`. Berkas
yang diubah menang atas bawaan kit, berkas yang tidak diubah tetap jatuh ke
kit, dan keduanya tetap dikompilasi Blaze. Hapus salinan yang tidak diubah
supaya perbaikan kit berikutnya tetap terbawa.

Blaze dipasang hanya dalam mode compile. Memo dan fold menyimpan atau
membekukan hasil render, sehingga keliru untuk komponen yang membaca state per
request; keduanya baru layak dipasang per komponen setelah diukur.

## Lisensi

MIT. Ikon Flowbite dilisensikan MIT oleh Themesberg; lihat
[resources/svg/flowbite/LICENSE.md](resources/svg/flowbite/LICENSE.md).

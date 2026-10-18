@php
    use CeplokUi\DesignKit\Enums\TextAreaApplication;
    use CeplokUi\DesignKit\Enums\TextAreaPlatform;
    use CeplokUi\DesignKit\Enums\TextAreaType;
    use CeplokUi\DesignKit\Support\Bound;
    use CeplokUi\DesignKit\Support\Content;
    use CeplokUi\DesignKit\Support\FieldId;
    use CeplokUi\DesignKit\Support\Prop;
@endphp

{{--
    Text Area — isian teks banyak baris.

    Type `default` menampilkan kotak polos setinggi 162px (120px di mobile);
    `editor` menambahkan toolbar 40px di atas area isian dan tombol kirim
    opsional di bawahnya. Warna garis saat difokus dan tombol kirim mengikuti
    `application`.

    Tombol toolbar bawaan memicu event `toolbar-action` dengan
    `detail.action` (attachment, code, emoji, list, settings, date, download),
    padanan onToolbarAction di React; slot `toolbar` menggantikan tombol bawaan
    itu. Tombol kirim muncul bila `submit-label` diisi dan memicu event
    `submit`, padanan onSubmit (mis. `wire:submit="kirim"`). Kedua event dikirim
    dari elemen textarea-nya.

    Atribut lain (`name`, `wire:model`, `placeholder`, `rows`, …) diteruskan ke
    `<textarea>`-nya; `value` menjadi isi awalnya, dan di dalam Livewire nilai
    awal `wire:model` ikut dirender server. `class` dan `wire:key` ke
    pembungkus terluar. `label`, `hint`, `helper-text`, dan `submit-label` bisa
    prop atau slot. `dark-mode` memasang `data-theme="dark"`.
--}}
@props([
    'label' => null,
    'hint' => null,
    'helperText' => null,
    'type' => 'default',
    'platform' => 'default',
    'application' => 'default',
    'toolbar' => null,
    'submitLabel' => null,
    'darkMode' => false,
])

@php
    $type = Prop::oneOf(TextAreaType::class, $type, TextAreaType::Default, 'text-area', 'type');
    $platform = Prop::oneOf(TextAreaPlatform::class, $platform, TextAreaPlatform::Default, 'text-area', 'platform');
    $application = Prop::oneOf(TextAreaApplication::class, $application, TextAreaApplication::Default, 'text-area', 'application');
    $darkMode = Prop::bool($darkMode, false, 'text-area', 'dark-mode');

    $id = FieldId::for($attributes, 'text-area');
    $helperId = $id.'-helper';
    $disabled = ! in_array($attributes->get('disabled', false), [false, null, 'false'], true);
    $isEditor = $type === 'editor';
    $hasLabel = Content::filled($label);
    $hasHint = Content::filled($hint);
    $hasHelper = Content::filled($helperText);
    $hasSubmit = $isEditor && Content::filled($submitLabel);
    $customToolbar = Content::filled($toolbar);
    $value = $attributes->get('value') ?? Bound::text($attributes);

    // Tinggi kotak isian pada type default: 162px di desktop, 120px di mobile.
    $boxes = ['default' => 'h-40.5', 'mobile' => 'h-30'];

    // Tombol kirim gelap satu tingkat lebih terang (token text-area-submit).
    // Button sudah membawa bg-primary-700 sendiri; tanda `!` membuat warna
    // token ini selalu menang atas urutan CSS Tailwind.
    $accents = [
        'default' => [
            'focus' => 'focus-within:border-primary-600',
            'submit' => 'bg-text-area-submit! hover:bg-text-area-submit-hover! focus-visible:outline-text-area-submit',
        ],
        'purple' => [
            'focus' => 'focus-within:border-purple-700',
            'submit' => 'bg-text-area-submit-purple! hover:bg-text-area-submit-purple-hover! focus-visible:outline-text-area-submit-purple',
        ],
    ];
    $accent = $accents[$application];

    // Ikon toolbar disalin dari desain: gaya isi, bukan garis, dan digeser ke
    // kotak 16×16 masing-masing. Ikon kode, daftar, dan unduh memakai garis
    // 1px yang mengikuti tepi bentuknya.
    $toolbarItems = [
        'attachment' => ['Lampirkan berkas', '<path d="M8 16C6.586 15.999 5.23 15.493 4.23 14.593C3.23 13.693 2.668 12.473 2.666 11.2V4.8C2.666 4.588 2.76 4.384 2.927 4.234C3.094 4.084 3.32 4 3.555 4C3.791 4 4.017 4.084 4.184 4.234C4.351 4.384 4.444 4.588 4.444 4.8V11.2C4.444 12.049 4.819 12.863 5.486 13.463C6.153 14.063 7.057 14.4 8 14.4C8.943 14.4 9.847 14.063 10.514 13.463C11.181 12.863 11.555 12.049 11.555 11.2V3.6C11.555 3.07 11.321 2.561 10.904 2.186C10.488 1.811 9.923 1.6 9.333 1.6C8.744 1.6 8.179 1.811 7.762 2.186C7.345 2.561 7.111 3.07 7.111 3.6V10.4C7.111 10.612 7.205 10.816 7.371 10.966C7.538 11.116 7.764 11.2 8 11.2C8.236 11.2 8.462 11.116 8.628 10.966C8.795 10.816 8.889 10.612 8.889 10.4V4.8C8.889 4.588 8.982 4.384 9.149 4.234C9.316 4.084 9.542 4 9.778 4C10.013 4 10.24 4.084 10.406 4.234C10.573 4.384 10.667 4.588 10.667 4.8V10.4C10.667 11.036 10.386 11.647 9.886 12.097C9.385 12.547 8.707 12.8 8 12.8C7.293 12.8 6.614 12.547 6.114 12.097C5.614 11.647 5.333 11.036 5.333 10.4V3.6C5.333 2.645 5.755 1.73 6.505 1.054C7.255 0.379 8.272 0 9.333 0C10.394 0 11.412 0.379 12.162 1.054C12.912 1.73 13.333 2.645 13.333 3.6V11.2C13.332 12.473 12.769 13.693 11.77 14.593C10.77 15.493 9.414 15.999 8 16Z" />'],
        'code' => ['Sisipkan kode', '<path d="M8.839 1.836C8.912 1.847 8.983 1.889 9.033 1.959C9.072 2.013 9.095 2.078 9.099 2.147L9.096 2.217L7.496 13.883C7.484 13.968 7.444 14.042 7.389 14.092C7.333 14.142 7.265 14.166 7.199 14.166C7.16 14.166 7.12 14.158 7.082 14.14C7.044 14.122 7.008 14.095 6.978 14.06C6.949 14.024 6.927 13.981 6.913 13.933C6.9 13.885 6.895 13.834 6.902 13.783V13.783L8.503 2.117C8.516 2.023 8.563 1.944 8.627 1.894C8.691 1.844 8.767 1.825 8.839 1.836ZM4.007 4.324C4.044 4.323 4.081 4.33 4.116 4.345C4.152 4.36 4.186 4.383 4.215 4.413C4.244 4.444 4.269 4.481 4.285 4.524C4.302 4.566 4.31 4.612 4.31 4.66C4.309 4.707 4.3 4.753 4.282 4.795C4.265 4.837 4.24 4.874 4.211 4.904L4.205 4.91L1.57 7.654L1.238 8L1.57 8.346L4.205 11.09C4.249 11.136 4.28 11.197 4.293 11.265C4.306 11.333 4.3 11.404 4.274 11.467C4.249 11.53 4.207 11.581 4.158 11.616C4.109 11.65 4.055 11.666 4 11.666L3.945 11.661C3.891 11.65 3.838 11.622 3.795 11.577L0.595 8.243H0.594C0.536 8.182 0.5 8.095 0.5 8C0.5 7.929 0.521 7.863 0.556 7.808L0.595 7.757L3.795 4.424L3.801 4.417C3.83 4.386 3.863 4.363 3.898 4.347C3.933 4.331 3.97 4.324 4.007 4.324ZM11.993 4.324C12.03 4.324 12.067 4.331 12.102 4.347C12.137 4.363 12.17 4.386 12.199 4.417L12.205 4.424L15.405 7.757C15.464 7.818 15.5 7.906 15.5 8C15.5 8.095 15.464 8.182 15.405 8.243L12.205 11.577C12.147 11.637 12.073 11.666 12 11.666L11.919 11.654C11.892 11.645 11.866 11.633 11.842 11.616C11.793 11.581 11.751 11.53 11.726 11.467C11.7 11.404 11.694 11.333 11.707 11.265C11.72 11.197 11.751 11.136 11.795 11.09L14.43 8.346L14.762 8L14.43 7.654L11.795 4.91L11.789 4.904L11.749 4.854C11.737 4.836 11.727 4.816 11.718 4.795C11.7 4.753 11.691 4.707 11.69 4.66C11.69 4.612 11.698 4.566 11.715 4.524C11.731 4.481 11.756 4.444 11.785 4.413C11.814 4.383 11.848 4.36 11.884 4.345C11.919 4.33 11.956 4.323 11.993 4.324Z" stroke="currentColor" />'],
        'emoji' => ['Sisipkan emoji', '<path d="M8 0C3.589 0 0 3.589 0 8C0 12.411 3.589 16 8 16C12.411 16 16 12.411 16 8C16 3.589 12.411 0 8 0ZM10.8 4C11.462 4 12 4.538 12 5.2C12 5.862 11.462 6.4 10.8 6.4C10.138 6.4 9.6 5.862 9.6 5.2C9.6 4.538 10.138 4 10.8 4ZM5.2 4C5.862 4 6.4 4.538 6.4 5.2C6.4 5.862 5.862 6.4 5.2 6.4C4.538 6.4 4 5.862 4 5.2C4 4.538 4.538 4 5.2 4ZM8 12.8C5.562 12.8 3.6 10.612 3.6 8.8C3.6 8.579 3.779 8.4 4 8.4C4.221 8.4 4.4 8.579 4.4 8.8C4.554 8.954 5.909 9.266 8 9.266C10.038 9.266 11.377 8.97 11.604 8.746C11.608 8.717 11.615 8.689 11.625 8.662C11.655 8.479 11.811 8.365 12.009 8.365C12.23 8.365 12.399 8.58 12.399 8.801C12.4 10.612 10.438 12.8 8 12.8Z" />'],
        'list' => ['Daftar berpoin', '<path d="M5 11.38H15C15.139 11.38 15.269 11.434 15.361 11.522C15.453 11.61 15.5 11.725 15.5 11.84C15.5 11.955 15.453 12.07 15.361 12.158C15.269 12.247 15.139 12.3 15 12.3H5C4.861 12.3 4.731 12.247 4.639 12.158C4.547 12.07 4.5 11.955 4.5 11.84C4.5 11.725 4.547 11.61 4.639 11.522C4.731 11.434 4.861 11.38 5 11.38ZM0.5 11.38H1.5V12.3H0.5V11.38ZM5 7.54H15C15.139 7.54 15.269 7.593 15.361 7.681C15.453 7.769 15.5 7.885 15.5 8C15.5 8.115 15.453 8.23 15.361 8.318C15.269 8.407 15.139 8.46 15 8.46H5C4.861 8.46 4.731 8.407 4.639 8.318C4.547 8.23 4.5 8.115 4.5 8C4.5 7.885 4.547 7.769 4.639 7.681C4.731 7.593 4.861 7.54 5 7.54ZM0.5 7.54H1.5V8.46H0.5V7.54ZM5 3.7H15C15.139 3.7 15.269 3.753 15.361 3.842C15.453 3.93 15.5 4.045 15.5 4.16C15.5 4.275 15.453 4.39 15.361 4.478C15.269 4.567 15.139 4.62 15 4.62H5C4.861 4.62 4.731 4.567 4.639 4.478C4.547 4.39 4.5 4.275 4.5 4.16C4.5 4.045 4.547 3.93 4.639 3.842C4.708 3.775 4.798 3.729 4.897 3.71L5 3.7ZM0.5 3.7H1.5V4.62H0.5V3.7Z" stroke="currentColor" />'],
        'settings' => ['Pengaturan', '<path d="M15.111 6.222H14.438L13.81 4.705L14.285 4.228C14.452 4.062 14.546 3.836 14.546 3.6C14.546 3.364 14.452 3.138 14.285 2.972L13.028 1.715C12.862 1.548 12.636 1.454 12.4 1.454C12.164 1.454 11.938 1.548 11.772 1.715L11.295 2.19L9.778 1.562V0.889C9.778 0.653 9.684 0.427 9.517 0.26C9.351 0.094 9.125 0 8.889 0H7.111C6.875 0 6.649 0.094 6.483 0.26C6.316 0.427 6.222 0.653 6.222 0.889V1.562L4.705 2.19L4.228 1.715C4.062 1.548 3.836 1.454 3.6 1.454C3.364 1.454 3.138 1.548 2.972 1.715L1.715 2.972C1.548 3.138 1.454 3.364 1.454 3.6C1.454 3.836 1.548 4.062 1.715 4.228L2.191 4.705L1.562 6.222H0.889C0.653 6.222 0.427 6.316 0.26 6.483C0.094 6.649 0 6.875 0 7.111V8.889C0 9.125 0.094 9.351 0.26 9.517C0.427 9.684 0.653 9.778 0.889 9.778H1.562C1.865 10.509 1.888 10.564 2.19 11.295L1.715 11.772C1.548 11.938 1.454 12.164 1.454 12.4C1.454 12.636 1.548 12.862 1.715 13.028L2.972 14.285C3.138 14.452 3.364 14.546 3.6 14.546C3.836 14.546 4.062 14.452 4.228 14.285L4.705 13.81L6.222 14.438V15.111C6.222 15.347 6.316 15.573 6.483 15.74C6.649 15.906 6.875 16 7.111 16H8.889C9.125 16 9.351 15.906 9.517 15.74C9.684 15.573 9.778 15.347 9.778 15.111V14.438L11.295 13.809L11.772 14.285C11.938 14.452 12.164 14.546 12.4 14.546C12.636 14.546 12.862 14.452 13.028 14.285L14.285 13.028C14.452 12.862 14.546 12.636 14.546 12.4C14.546 12.164 14.452 11.938 14.285 11.772L13.81 11.295L14.438 9.778H15.111C15.347 9.778 15.573 9.684 15.74 9.517C15.906 9.351 16 9.125 16 8.889V7.111C16 6.875 15.906 6.649 15.74 6.483C15.573 6.316 15.347 6.222 15.111 6.222ZM8 11.556C7.297 11.556 6.609 11.347 6.025 10.956C5.44 10.566 4.984 10.01 4.715 9.361C4.446 8.711 4.376 7.996 4.513 7.306C4.65 6.617 4.989 5.983 5.486 5.486C5.983 4.989 6.617 4.65 7.306 4.513C7.996 4.376 8.711 4.446 9.361 4.715C10.01 4.984 10.566 5.44 10.956 6.025C11.347 6.609 11.556 7.297 11.556 8C11.556 8.943 11.181 9.847 10.514 10.514C9.847 11.181 8.943 11.556 8 11.556Z" />'],
        'date' => ['Sisipkan tanggal', '<path d="M0 14.4C0 14.824 0.169 15.231 0.469 15.531C0.769 15.831 1.176 16 1.6 16H14.4C14.824 16 15.231 15.831 15.531 15.531C15.831 15.231 16 14.824 16 14.4V6.4H0V14.4ZM11.2 8.4C11.2 8.294 11.242 8.192 11.317 8.117C11.392 8.042 11.494 8 11.6 8H12.4C12.506 8 12.608 8.042 12.683 8.117C12.758 8.192 12.8 8.294 12.8 8.4V9.2C12.8 9.306 12.758 9.408 12.683 9.483C12.608 9.558 12.506 9.6 12.4 9.6H11.6C11.494 9.6 11.392 9.558 11.317 9.483C11.242 9.408 11.2 9.306 11.2 9.2V8.4ZM11.2 11.6C11.2 11.494 11.242 11.392 11.317 11.317C11.392 11.242 11.494 11.2 11.6 11.2H12.4C12.506 11.2 12.608 11.242 12.683 11.317C12.758 11.392 12.8 11.494 12.8 11.6V12.4C12.8 12.506 12.758 12.608 12.683 12.683C12.608 12.758 12.506 12.8 12.4 12.8H11.6C11.494 12.8 11.392 12.758 11.317 12.683C11.242 12.608 11.2 12.506 11.2 12.4V11.6ZM7.2 8.4C7.2 8.294 7.242 8.192 7.317 8.117C7.392 8.042 7.494 8 7.6 8H8.4C8.506 8 8.608 8.042 8.683 8.117C8.758 8.192 8.8 8.294 8.8 8.4V9.2C8.8 9.306 8.758 9.408 8.683 9.483C8.608 9.558 8.506 9.6 8.4 9.6H7.6C7.494 9.6 7.392 9.558 7.317 9.483C7.242 9.408 7.2 9.306 7.2 9.2V8.4ZM7.2 11.6C7.2 11.494 7.242 11.392 7.317 11.317C7.392 11.242 7.494 11.2 7.6 11.2H8.4C8.506 11.2 8.608 11.242 8.683 11.317C8.758 11.392 8.8 11.494 8.8 11.6V12.4C8.8 12.506 8.758 12.608 8.683 12.683C8.608 12.758 8.506 12.8 8.4 12.8H7.6C7.494 12.8 7.392 12.758 7.317 12.683C7.242 12.608 7.2 12.506 7.2 12.4V11.6ZM3.2 8.4C3.2 8.294 3.242 8.192 3.317 8.117C3.392 8.042 3.494 8 3.6 8H4.4C4.506 8 4.608 8.042 4.683 8.117C4.758 8.192 4.8 8.294 4.8 8.4V9.2C4.8 9.306 4.758 9.408 4.683 9.483C4.608 9.558 4.506 9.6 4.4 9.6H3.6C3.494 9.6 3.392 9.558 3.317 9.483C3.242 9.408 3.2 9.306 3.2 9.2V8.4ZM3.2 11.6C3.2 11.494 3.242 11.392 3.317 11.317C3.392 11.242 3.494 11.2 3.6 11.2H4.4C4.506 11.2 4.608 11.242 4.683 11.317C4.758 11.392 4.8 11.494 4.8 11.6V12.4C4.8 12.506 4.758 12.608 4.683 12.683C4.608 12.758 4.506 12.8 4.4 12.8H3.6C3.494 12.8 3.392 12.758 3.317 12.683C3.242 12.608 3.2 12.506 3.2 12.4V11.6Z" /><path d="M16 3.2C16 2.776 15.831 2.369 15.531 2.069C15.231 1.769 14.824 1.6 14.4 1.6H12.8V0.8C12.8 0.588 12.716 0.384 12.566 0.234C12.416 0.084 12.212 0 12 0C11.788 0 11.584 0.084 11.434 0.234C11.284 0.384 11.2 0.588 11.2 0.8V1.6H8.8V0.8C8.8 0.588 8.716 0.384 8.566 0.234C8.416 0.084 8.212 0 8 0C7.788 0 7.584 0.084 7.434 0.234C7.284 0.384 7.2 0.588 7.2 0.8V1.6H4.8V0.8C4.8 0.588 4.716 0.384 4.566 0.234C4.416 0.084 4.212 0 4 0C3.788 0 3.584 0.084 3.434 0.234C3.284 0.384 3.2 0.588 3.2 0.8V1.6H1.6C1.176 1.6 0.769 1.769 0.469 2.069C0.169 2.369 0 2.776 0 3.2V4.8H16V3.2Z" />'],
        'download' => ['Unduh', '<path d="M1.6 9.935H3.429L5.662 12.226C5.968 12.539 6.331 12.789 6.732 12.96C7.134 13.13 7.565 13.218 8 13.218C8.435 13.218 8.866 13.13 9.268 12.96C9.669 12.789 10.032 12.539 10.338 12.226L12.571 9.935H14.4C14.688 9.936 14.967 10.053 15.174 10.266C15.381 10.479 15.5 10.771 15.5 11.077V14.359C15.5 14.666 15.381 14.957 15.174 15.17C14.967 15.383 14.688 15.5 14.4 15.5H1.6C1.312 15.5 1.033 15.383 0.826 15.17C0.619 14.957 0.5 14.666 0.5 14.359V11.077C0.5 10.771 0.619 10.479 0.826 10.266C1.033 10.053 1.312 9.936 1.6 9.935ZM13.055 11.531C12.743 11.399 12.399 11.364 12.066 11.432C11.734 11.499 11.43 11.666 11.193 11.909C10.957 12.152 10.797 12.46 10.732 12.793C10.668 13.126 10.701 13.471 10.828 13.786C10.955 14.101 11.171 14.373 11.45 14.565C11.73 14.756 12.061 14.859 12.4 14.859C12.855 14.859 13.289 14.673 13.606 14.348C13.924 14.022 14.1 13.583 14.1 13.128C14.1 12.789 14.001 12.456 13.816 12.172C13.631 11.888 13.367 11.664 13.055 11.531ZM8 0.5C8.075 0.5 8.151 0.531 8.208 0.59C8.265 0.649 8.3 0.732 8.3 0.82V9.095L9.158 8.215L10.992 6.333C11.049 6.274 11.125 6.244 11.2 6.244C11.275 6.244 11.35 6.275 11.407 6.333L11.408 6.334C11.466 6.393 11.501 6.476 11.501 6.565C11.501 6.653 11.466 6.736 11.409 6.795L8.209 10.077V10.078C8.181 10.107 8.147 10.129 8.111 10.145C8.076 10.16 8.039 10.167 8.001 10.167C7.963 10.167 7.925 10.16 7.89 10.145C7.854 10.13 7.821 10.107 7.793 10.078L7.792 10.077L4.593 6.795L4.586 6.789L4.547 6.741C4.535 6.724 4.525 6.705 4.517 6.685C4.5 6.645 4.491 6.601 4.49 6.557C4.49 6.513 4.498 6.469 4.514 6.429C4.53 6.389 4.553 6.353 4.582 6.323C4.611 6.294 4.645 6.271 4.681 6.256C4.717 6.241 4.755 6.233 4.793 6.233C4.831 6.234 4.869 6.242 4.904 6.258C4.94 6.274 4.974 6.297 5.002 6.327L5.008 6.333L6.842 8.215L7.7 9.095V0.82C7.7 0.732 7.735 0.649 7.792 0.59C7.835 0.546 7.888 0.517 7.943 0.506L8 0.5Z" stroke="currentColor" />'],
    ];

    $root = $attributes->only(['class', 'wire:key'])->merge($darkMode ? ['data-theme' => 'dark'] : []);
    $field = $attributes
        ->except(['class', 'wire:key', 'id', 'type', 'value'])
        ->merge(['aria-describedby' => $hasHelper ? $helperId : null])
        ->class([
            'block w-full resize-none p-4 text-sm outline-none disabled:cursor-not-allowed',
            'text-content placeholder:text-content-muted disabled:text-content-disabled',
            // Editor: tinggi area isian 166px, di bawah toolbar 40px (total 206px).
            $isEditor ? 'h-41.5 bg-surface' : 'rounded-lg border transition-colors border-field-border bg-field '.$boxes[$platform].' '.$accent['focus'],
        ]);
@endphp

<div {{ $root->class('w-full') }} @if ($isEditor) x-data="ceplokTextArea" @endif>
    @if ($hasLabel || $hasHint)
        <div class="mb-2 flex items-baseline justify-between gap-3">
            @if ($hasLabel)
                <label for="{{ $id }}" class="text-sm font-bold text-content">{{ $label }}</label>
            @endif
            @if ($hasHint)
                <span class="text-sm text-gray-500">{{ $hint }}</span>
            @endif
        </div>
    @endif

    @if ($isEditor)
        <div @class(['overflow-hidden rounded-lg border transition-colors border-gray-300', $accent['focus']])>
            {{-- Jarak antarikon 15px, sesuai desain. --}}
            <div class="flex h-10 items-center gap-3.75 px-4 bg-field">
                @if ($customToolbar)
                    {{ $toolbar }}
                @else
                    @foreach ($toolbarItems as $action => [$title, $paths])
                        @if ($loop->index === 3)
                            {{-- Garis pemisah alat teks dari alat sisipan: 16px dari ikon sebelumnya, 15px dari sesudahnya. --}}
                            <span aria-hidden="true" class="ml-px h-4 w-px bg-gray-300"></span>
                        @endif
                        <button
                            type="button"
                            x-on:click="ceplokToolbar(@js($action))"
                            @disabled($disabled)
                            aria-label="{{ $title }}"
                            title="{{ $title }}"
                            class="shrink-0 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current disabled:cursor-not-allowed disabled:opacity-50 text-content-muted hover:text-text-area-tool-hover"
                        >
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-4">{!! $paths !!}</svg>
                        </button>
                    @endforeach
                @endif
            </div>
            <textarea id="{{ $id }}" {{ $field }}>{{ $value }}</textarea>
        </div>
    @else
        <textarea id="{{ $id }}" {{ $field }}>{{ $value }}</textarea>
    @endif

    @if ($hasHelper)
        <p id="{{ $helperId }}" class="mt-2 text-xs text-text-area-helper">{{ $helperText }}</p>
    @endif

    @if ($hasSubmit)
        <div class="mt-2">
            <x-ceplok::button x-on:click="ceplokSubmit()" :disabled="$disabled" :class="$accent['submit']">{{ $submitLabel }}</x-ceplok::button>
        </div>
    @endif
</div>

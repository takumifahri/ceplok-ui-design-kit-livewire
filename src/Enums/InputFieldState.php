<?php

namespace CeplokUi\DesignKit\Enums;

/** State Input Field. `typing` = tampilan saat difokus, `inactive` menonaktifkan input, `failed` menandainya aria-invalid. */
enum InputFieldState: string
{
    case Default = 'default';
    case Typing = 'typing';
    case Inactive = 'inactive';
    case Failed = 'failed';
}

<?php

namespace CeplokUi\DesignKit\Enums;

/** State Floating Label. `active` dan `error` mengunci label di atas; `error` sekaligus memasang aria-invalid. */
enum FloatingLabelState: string
{
    case Default = 'default';
    case Active = 'active';
    case Error = 'error';
}

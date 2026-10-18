<?php

namespace CeplokUi\DesignKit\Enums;

/** Warna Alert, sama maknanya dengan Badge dan token warna lain. */
enum AlertVariant: string
{
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
    case Purple = 'purple';
}

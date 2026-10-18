<?php

namespace CeplokUi\DesignKit\Enums;

/** Atribut `type` HTML pada <button>: submit dan reset untuk tombol di dalam <form>. */
enum ButtonType: string
{
    case Button = 'button';
    case Submit = 'submit';
    case Reset = 'reset';
}

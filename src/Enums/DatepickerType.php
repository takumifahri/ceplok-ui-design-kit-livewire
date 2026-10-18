<?php

namespace CeplokUi\DesignKit\Enums;

/** Bentuk Datepicker — satu tanggal (single), satu kalender dengan pintasan periode (period), atau rentang dengan dua kalender (multiple). */
enum DatepickerType: string
{
    case Single = 'single';
    case Period = 'period';
    case Multiple = 'multiple';
}

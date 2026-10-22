<?php

namespace CeplokUi\DesignKit\Enums;

/** Preferensi sisi panel Popover terhadap trigger; bisa berbalik bila ruangnya kurang. */
enum PopoverSide: string
{
    case Top = 'top';
    case Right = 'right';
    case Bottom = 'bottom';
    case Left = 'left';
}

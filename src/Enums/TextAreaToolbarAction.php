<?php

namespace CeplokUi\DesignKit\Enums;

/** Tombol toolbar bawaan Text Area editor, dikirim lewat event `toolbar-action` (`detail.action`). */
enum TextAreaToolbarAction: string
{
    case Attachment = 'attachment';
    case Code = 'code';
    case Emoji = 'emoji';
    case List = 'list';
    case Settings = 'settings';
    case Date = 'date';
    case Download = 'download';
}

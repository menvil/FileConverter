<?php

namespace App\Enums;

enum FileFormat: string
{
    case Png = 'png';
    case Jpg = 'jpg';
    case Webp = 'webp';
    case Pdf = 'pdf';
}

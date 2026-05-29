<?php

namespace App\Enums;

enum FileStatus: string
{
    case Uploaded = 'uploaded';
    case Analyzed = 'analyzed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Deleted = 'deleted';
}

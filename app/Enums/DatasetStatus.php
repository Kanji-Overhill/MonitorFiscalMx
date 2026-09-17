<?php

namespace App\Enums;

enum DatasetStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Active = 'active';
    case Failed = 'failed';
    case Superseded = 'superseded';
}

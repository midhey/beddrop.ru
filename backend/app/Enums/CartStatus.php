<?php

namespace App\Enums;

enum CartStatus: string
{
    case ACTIVE = 'ACTIVE';
    case ORDERED = 'ORDERED';
    case ABANDONED = 'ABANDONED';
}

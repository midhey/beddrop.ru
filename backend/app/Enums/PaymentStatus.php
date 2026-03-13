<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'PENDING';
    case AUTHORIZED = 'AUTHORIZED';
    case PAID = 'PAID';
    case REFUNDED = 'REFUNDED';
    case FAILED = 'FAILED';
}

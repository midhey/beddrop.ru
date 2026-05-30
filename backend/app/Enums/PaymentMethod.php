<?php

namespace App\Enums;

enum PaymentMethod: string
{
    // Deprecated for new orders until payment-on-delivery workflow exists.
    case CASH = 'CASH';
    // Deprecated for new orders until payment-on-delivery workflow exists.
    case CARD = 'CARD';
    case ONLINE = 'ONLINE';
}

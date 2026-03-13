<?php

namespace App\Enums;

enum RestaurantStaffRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case STAFF = 'STAFF';
}

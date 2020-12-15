<?php

namespace Enum;

use MyCLabs\Enum\Enum;

class OrderStatus extends Enum
{
    const OPEN = 1;
    const PREPARING = 2;
    const CLOSED = 3;
    const VOTED = 4;

    public static function IsValidStatus($status)
    {
        switch ($status) {
            case OrderStatus::OPEN:
                return true;
            case OrderStatus::PREPARING:
                return true;
            case OrderStatus::CLOSED:
                return true;
            case OrderStatus::VOTED:
                return true;
            default:
                return false;
        }
    }
}

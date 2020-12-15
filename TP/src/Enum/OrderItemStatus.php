<?php

namespace Enum;

use MyCLabs\Enum\Enum;

class OrderItemStatus extends Enum
{
    const WAITING = 0; // "esperando a ser preparado"
    const WORKING = 1; // "preparando"
    const READY = 2; // “listo para servir”,
    const CANCELLED = 3; // “listo para servir”,

    public static function IsValidStatus($status)
    {
        switch ($status) {
            case OrderItemStatus::WAITING:
                return true;
            case OrderItemStatus::WORKING:
                return true;
            case OrderItemStatus::READY:
                return true;
            case OrderItemStatus::CANCELLED:
                return true;
            default:
                return false;
        }
    }
}

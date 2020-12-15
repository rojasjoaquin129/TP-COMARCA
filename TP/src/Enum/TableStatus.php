<?php

namespace Enum;

use MyCLabs\Enum\Enum;

class TableStatus extends Enum
{
    const OPEN = -1; // “con cliente esperando pedido”
    const WAITING = 0; // “con cliente esperando pedido”
    const EATING = 1; // ,”con clientes comiendo” 
    const PAYING = 2; // “con clientes pagando”
    const CLOSED = 3; // “cerrada”

    public static function IsValidStatus($status)
    {
        switch ($status) {
            case TableStatus::OPEN:
                return true;
            case TableStatus::WAITING:
                return true;
            case TableStatus::EATING:
                return true;
            case TableStatus::PAYING:
                return true;
            case TableStatus::CLOSED:
                return true;
            default:
                return false;
        }
    }
}

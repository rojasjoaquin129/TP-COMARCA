<?php

namespace Enum;

use MyCLabs\Enum\Enum;

class UserRole extends Enum
{
    // Socio.
    const ADMIN = -1;
    const MOZO = 1;
    const BARTENDER = 2;
    const COCINERO = 3;
    const CERVECERO = 4;

    public static function IsValidArea($area)
    {
        switch ($area) {
            case "ADMIN":
                return true;
            case "MOZO":
                return true;
            case "BARTENDER":
                return true;
            case "COCINERO":
                return true;
            case "CERVECERO":
                return true;
            default:
                return false;
        }
    }

    public static function GetDescription($role)
    {
        switch ($role) {
            case UserRole::ADMIN:
                return "ADMIN";
            case UserRole::MOZO:
                return "MOZO";
            case UserRole::BARTENDER:
                return "BARTENDER";
            case UserRole::COCINERO:
                return "COCINERO";
            case UserRole::CERVECERO:
                return "CERVECERO";
        }
    }

    public static function GetVal($role)
    {
        switch ($role) {
            case "ADMIN":
                return UserRole::ADMIN;
            case "MOZO":
                return UserRole::MOZO;
            case "BARTENDER":
                return UserRole::BARTENDER;
            case "COCINERO":
                return UserRole::COCINERO;
            case "CERVECERO":
                return UserRole::CERVECERO;
            default:
                return false;
        }
    }
}

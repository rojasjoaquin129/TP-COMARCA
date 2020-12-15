<?php
namespace Components;

class PassManager
{
    public static function Hash(string $pass)
    {
        return hash('SHA512', $pass);
    }
}

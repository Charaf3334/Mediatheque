<?php

namespace App\Enum;

enum Plan: string
{
    case Basic = 'basic';
    case Premium = 'premium';

    public function maxLoans(): int
    {
        return $this === self::Premium ? 6 : 3;
    }

    public function days(): int
    {
        return $this === self::Premium ? 28 : 14;
    }
}
<?php

namespace App\Enum;

enum RoundStatus: string
{
    case ACTIVE = 'active';
    case CANCELED = 'canceled';
    case REVEALED = 'revealed';
}

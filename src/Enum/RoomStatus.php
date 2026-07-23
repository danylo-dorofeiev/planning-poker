<?php

namespace App\Enum;

enum RoomStatus: string
{
    case PENDING = 'pending';
    case VOTING = 'voting';
    case FINISHED = 'finished';
}

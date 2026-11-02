<?php

namespace App\Enum;

enum TicketStatus: string
{
    case PENDING = 'pending';
    case VOTING = 'voting';
    case REVEALED = 'revealed';
    case FINISHED = 'finished';
}

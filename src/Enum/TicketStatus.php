<?php

namespace App\Enum;

enum TicketStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
}

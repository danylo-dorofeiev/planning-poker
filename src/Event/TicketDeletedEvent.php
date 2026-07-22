<?php

namespace App\Event;

use App\Entity\Ticket;

class TicketDeletedEvent
{
    public function __construct(
        private Ticket $ticket
    ) {}

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }
}

<?php

namespace App\Event;

use App\Entity\Ticket;

class TicketCreatedEvent
{
    public function __construct(
        private Ticket $ticket
    ) {}

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }
}

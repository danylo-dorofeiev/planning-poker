<?php

namespace App\Event;

use App\Entity\Room;

class TicketCardListEvent
{
    public function __construct(
        private Room $room
    ) {}

    public function getRoom(): Room
    {
        return $this->room;
    }
}

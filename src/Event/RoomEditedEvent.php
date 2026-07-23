<?php

namespace App\Event;

use App\Entity\Room;

class RoomEditedEvent
{
    public function __construct(
        private Room $room
    ) {}

    public function getRoom(): Room
    {
        return $this->room;
    }
}

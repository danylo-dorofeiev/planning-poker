<?php

namespace App\Event;

use App\Entity\Room;
use App\Entity\User;

class RoomSettingsEvent
{
    public function __construct(
        private Room $room,
        private User $user,
    ) {}

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}

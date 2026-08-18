<?php

namespace App\Event;

use App\Entity\Round;

class RoundRevealedEvent
{
    public function __construct(
        private Round $round
    ) {}

    public function getRound(): Round
    {
        return $this->round;
    }
}

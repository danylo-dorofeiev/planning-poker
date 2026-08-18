<?php

namespace App\EventListener;

use App\Event\RoundStartedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class RoundStartedListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig
    ) {}

    public function __invoke(RoundStartedEvent $event): void
    {
        $round = $event->getRound();
        $ticket = $round->getTicket();
        $room = $ticket->getRoom();

        $roomUpdate = $this->twig->render('room/stream/started.html.twig', [
            'ticket' => $ticket,
            'round' => $round,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $roomUpdate));
    }
}


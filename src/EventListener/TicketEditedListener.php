<?php

namespace App\EventListener;

use App\Event\TicketEditedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class TicketEditedListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig
    ) {}

    public function __invoke(TicketEditedEvent $event): void
    {
        $ticket = $event->getTicket();
        $room = $ticket->getRoom();

        $ticketsUpdate = $this->twig->render('ticket/stream/edited.html.twig', [
            'ticket' => $ticket,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $ticketsUpdate));
    }
}


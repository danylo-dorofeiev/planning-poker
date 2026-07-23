<?php

namespace App\EventListener;

use App\Event\TicketCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class TicketCreatedListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig
    ) {}

    public function __invoke(TicketCreatedEvent $event): void
    {
        $ticket = $event->getTicket();
        $room = $ticket->getRoom();

        $ticketsUpdate = $this->twig->render('ticket/stream/created.html.twig', [
            'ticket' => $ticket,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $ticketsUpdate));
    }
}


<?php

namespace App\EventListener;

use App\Event\RoomEditedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

#[AsEventListener]
class RoomEditedListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig
    ) {}

    public function __invoke(RoomEditedEvent $event): void
    {
        $room = $event->getRoom();

        $roomUpdate = $this->twig->render('room/stream/edited.html.twig', [
            'room' => $room,
        ]);

        $this->hub->publish(new Update($room->getUuid(), $roomUpdate));
    }
}


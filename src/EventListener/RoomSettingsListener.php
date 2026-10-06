<?php

namespace App\EventListener;

use App\Event\RoomSettingsEvent;
use App\Form\RoomType;
use App\Repository\DeckRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsEventListener]
class RoomSettingsListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig,
        private DeckRepository  $deckRepository,
        private FormFactoryInterface   $formFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(RoomSettingsEvent $event): void
    {
        $room = $event->getRoom();
        $user = $event->getUser();
        $decks = $this->deckRepository->findAllByOwner($user);

        $roomSettingsForm = $this->formFactory->create(RoomType::class, $room, [
            'attr' => [
                'id' => 'room_edit_form',
            ],
            'action' => $this->urlGenerator->generate('room_edit', [
                'room_id' => $room->getUuid(),
            ]),
            'method' => 'POST',
        ]);

        $roomSettingsUpdate = $this->twig->render('room/stream/_room_settings.html.twig', [
            'room' => $room,
            'decks' => $decks,
            'roomSettingsForm' => $roomSettingsForm->createView(),
        ]);

        $this->hub->publish(new Update($room->getUuid(), $roomSettingsUpdate));
    }
}


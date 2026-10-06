<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Room;
use App\Event\RoomSettingsEvent;
use App\Form\DeckType;
use App\Form\JoinRoomType;
use App\Repository\DeckRepository;
use App\Repository\RoomRepository;
use App\Security\Voter\DeckVoter;
use App\Security\Voter\RoomVoter;
use App\Service\DeckService;
use App\Service\RoomService;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
final class DeckController extends AbstractController
{
    #[Route('/deck/list', name: 'deck_list', methods: ['GET'])]
    public function list(
        Security $security,
        DeckService $deckService,
        RoomRepository $roomRepository,
        DeckRepository $deckRepository,
        Request $request,
    ): Response {
        $user = $security->getUser();

        $deck = new Deck();
        $deck->setOwner($user);

        $deck->addCard(new Card());
        $deck->addCard(new Card());

        $deckCreateForm = $this->createForm(DeckType::class, $deck, [
            'attr' => [
                'id' => 'deck_create_form',
            ],
            'action' => $this->generateUrl('deck_create'),
            'method' => 'POST',
        ]);

        $customDecks = $deckService->findAllByOwner($user);
        $systemDecks = $deckRepository->findSystemDecks();

        $joinRoomForm = $this->createForm(JoinRoomType::class, null, [
            'attr' => [
                'id' => 'room_join_form',
            ],
            'method' => 'POST',
        ]);

        $joinRoomForm->handleRequest($request);

        if ($joinRoomForm->isSubmitted() && $joinRoomForm->isValid()) {
            $data = $joinRoomForm->getData();
            $uuid = $data['uuid'];

            if (!Uuid::isValid($uuid)) {
                throw $this->createNotFoundException('Invalid Room ID');
            }

            $room = $roomRepository->findOneBy([
                'uuid' => $uuid,
            ]);

            if(!$room) {
                return $this->redirectToRoute('app', [
                    'room_id' => $uuid,
                    'error' => 'room_not_found',
                ]);
            }

            return $this->redirectToRoute('room_join', [
                'room_id' => $uuid
            ]);
        }

        $error = $request->query->get('error');

        return $this->render('deck/list.html.twig', [
            'error' => $error,
            'joinRoomForm' => $joinRoomForm->createView(),
            'customDecks' => $customDecks,
            'systemDecks' => $systemDecks,
            'deckCreateForm' => $deckCreateForm,
        ]);
    }

    #[Route('/deck/create', name: 'deck_create', methods: ['POST'])]
    public function create(
        Security $security,
        Request $request,
        DeckService $deckService
    ): Response {
        $user = $security->getUser();

        $deck = new Deck();
        $deck->setOwner($user);

        $deckCreateForm = $this->createForm(DeckType::class, $deck);
        $deckCreateForm->handleRequest($request);

        if ($deckCreateForm->isSubmitted() && $deckCreateForm->isValid()) {
            foreach ($deck->getCards() as $card) {
                $card->setDeck($deck);
            }

            $deckService->createDeck($deck);

            return $this->redirectToRoute('deck_list');
        }

        return new Response('', 302);
    }

    #[Route('/deck/{deck_id}', name: 'deck_show', methods: ['GET', 'POST'])]
    public function show(
        #[MapEntity(mapping: ['deck_id' => 'uuid'])] Deck $deck,
    ): Response {
        $deckEditForm = $this->createForm(DeckType::class, $deck, [
            'attr' => [
                'id' => 'deck_edit_form',
            ],
            'action' => $this->generateUrl('deck_edit', [
                'deck_id' => $deck->getUuid(),
            ]),
            'method' => 'POST',
        ]);

        return $this->render('deck/stream/_deck_overview.html.twig', [
            'deck' => $deck,
            'deckEditForm' => $deckEditForm,
        ]);
    }

    #[Route('/deck/{deck_id}/edit', name: 'deck_edit', methods: ['POST'])]
    public function edit(
        #[MapEntity(mapping: ['deck_id' => 'uuid'])]
        Deck $deck,
        DeckService $deckService,
        Request $request
    ): Response {
        if ($deck->isSystem()) {
            throw new \LogicException('This deck cannot be edited because it is used as a default deck.');
        }

        $this->denyAccessUnlessGranted(DeckVoter::DELETE, $deck);

        $deckEditForm = $this->createForm(DeckType::class, $deck);
        $deckEditForm->handleRequest($request);

        if ($deckEditForm->isSubmitted() && $deckEditForm->isValid()) {
            $deckService->updateDeck($deck);

            return $this->redirectToRoute('deck_list');
        }

        return new Response('', 302);
    }

    #[Route('/room/{room_id}/deck/{deck_id}/select', name: 'deck_select', methods: ['POST'])]
    public function selectDeck(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['deck_id' => 'uuid'])] Deck $deck,
        RoomService $roomService,
        HubInterface $hub,
    ): Response {
        $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);

        $room->setDeck($deck);
        $roomService->updateRoom($room);

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'room_settings',
                    'event' => 'room_settings:update',
                    'url' => $this->generateUrl('room_settings_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        return new Response(status: 204);
    }

    #[Route('/deck/{deck_id}/delete', name: 'deck_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['deck_id' => 'uuid'])] Deck $deck,
        DeckService $deckService,
        DeckRepository $deckRepository,
        RoomRepository $roomRepository,
    ): Response {
        if ($deck->isSystem()) {
            throw new \LogicException('This deck cannot be deleted because it is used as a default deck.');
        }

        $this->denyAccessUnlessGranted(DeckVoter::DELETE, $deck);

        $defaultDeck = $deckRepository->find(1);
        $rooms = $roomRepository->findBy(['deck' => $deck]);

        foreach ($rooms as $room) {
            $room->setDeck($defaultDeck);
        }

        $deckService->deleteDeck($deck);

        return $this->redirectToRoute('deck_list');
    }
}

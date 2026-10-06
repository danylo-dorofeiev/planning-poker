<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\Ticket;
use App\Event\RoomSettingsEvent;
use App\Form\RoomType;
use App\Form\TicketType;
use App\Repository\DeckRepository;
use App\Repository\RoomMemberRepository;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use App\Repository\VoteRepository;
use App\Security\Voter\RoomVoter;
use App\Service\DeckService;
use App\Service\RoomMemberService;
use App\Service\RoomService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class RoomController extends AbstractController
{
    #[Route('/room/create', name: 'room_create', methods: ['POST'])]
    public function create(
        Request $request,
        RoomService $roomService,
        DeckRepository $deckRepository,
    ): Response {
        $user = $this->getUser();

        $room = new Room();
        $room->setOwner($user);

        $deck = $deckRepository->find(1);
        $room->setDeck($deck);

        $roomCreateForm = $this->createForm(RoomType::class, $room);
        $roomCreateForm->handleRequest($request);

        if ($roomCreateForm->isSubmitted() && $roomCreateForm->isValid()) {
            $room->setUpdatedAt();

            $roomService->createRoom($room);

            return $this->redirectToRoute('room_join', [
                'room_id' => $room->getUuid(),
            ]);
        }
    }

    #[Route('/room/{room_id}/join', name: 'room_join', methods: ['GET', 'POST'])]
    public function join(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoomMemberService $roomMemberService,
    ): Response {
        $user = $this->getUser();
        $now = new \DateTimeImmutable();

        $roomMember = $roomMemberService->findMember($room, $user);

        if (!$roomMember) {
            $roomMember = (new RoomMember())
                ->setRoom($room)
                ->setUser($user)
                ->setLastSeen($now);

            $roomMemberService->createRoomMember($roomMember);
        } else {
            $roomMember->setLastSeen($now);
            $roomMemberService->updateRoomMember($roomMember);
        }

        return $this->redirectToRoute('room_show', [
            'room_id' => $room->getUuid(),
        ]);
    }

    #[Route('/room/{room_id}', name: 'room_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoundRepository $roundRepository,
        TicketRepository $ticketRepository,
        RoomMemberRepository $roomMemberRepository,
        VoteRepository $voteRepository,
        DeckService $deckService,
    ): Response {
        $user = $this->getUser();
        $roomMember = $roomMemberRepository->findMember($room, $user);

        if (!$roomMember) {
            return $this->redirectToRoute('room_join', [
                'room_id' => $room->getUuid()
            ]);
        }

        $ticket = new Ticket();
        $ticket->setRoom($room);

        $ticketCreateForm = $this->createForm(TicketType::class, $ticket, [
            'attr' => [
                'id' => 'ticket_create_form',
            ],
            'action' => $this->generateUrl('ticket_create', [
                'room_id' => $room->getUuid(),
            ]),
            'method' => 'POST',
        ]);

        $roomSettingsForm = $this->createForm(RoomType::class, $room, [
            'attr' => [
                'id' => 'room_settings_form',
            ],
            'action' => $this->generateUrl('room_edit', [
                'room_id' => $room->getUuid(),
            ]),
            'method' => 'POST',
        ]);

        $round = null;
        $activeRound = $roundRepository->findActiveRoundByRoom($room);
        if ($activeRound) {
            $round = $activeRound;
        }

        $votedUserIds = [];
        if ($activeRound) {
            $votedUserIds = $voteRepository->findVotedUserIds($activeRound);
        }

        $decks = $deckService->findAllByOwner($user);
        $tickets = $ticketRepository->findBy(['room' => $room], ['updatedAt' => 'DESC']);

        $ticketEditForms = [];
        foreach($tickets as $ticket) {
            $ticketEditForms[$ticket->getUuid()->toRfc4122()] = $this->createForm(TicketType::class, $ticket, [
                'attr' => [
                    'id' => 'ticket_' . $ticket->getUuid()->toRfc4122() . '_edit_form',
                ],
                'action' => $this->generateUrl('ticket_edit', [
                    'room_id' => $room->getUuid(),
                    'ticket_id' => $ticket->getUuid(),
                ]),
                'method' => 'POST',
            ])->createView();
        }

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'tickets' => $tickets,
            'ticketCreateForm' => $ticketCreateForm,
            'roomSettingsForm' => $roomSettingsForm,
            'ticketEditForms' => $ticketEditForms,
            'round' => $round,
            'votedUserIds' => $votedUserIds,
            'decks' => $decks
        ]);
    }

    #[Route('/room/{room_id}/edit', name: 'room_edit', methods: ['POST'])]
    public function edit(
        #[MapEntity(mapping: ['room_id' => 'uuid'])]
        Room $room,
        Request $request,
        RoomService $roomService,
        HubInterface $hub,
    ): Response {
        $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);

        $roomSettingsForm = $this->createForm(RoomType::class, $room);
        $roomSettingsForm->handleRequest($request);

        if ($roomSettingsForm->isSubmitted() && $roomSettingsForm->isValid()) {
            $roomService->updateRoom($room);

            $room->setUpdatedAt();
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

            return $this->redirectToRoute('room_show', [
                'room_id' => $room->getUuid(),
            ]);
        }
    }

    #[Route('/room/{room_id}/delete', name: 'room_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoomService $roomService,
    ): Response {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE, $room);

        $roomService->deleteRoom($room);

        return $this->redirectToRoute('app');
    }
}

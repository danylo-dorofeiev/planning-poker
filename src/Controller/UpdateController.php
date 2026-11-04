<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Round;
use App\Entity\Ticket;
use App\Form\RoomType;
use App\Form\TicketType;
use App\Repository\DeckRepository;
use App\Repository\RoomMemberRepository;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use App\Repository\VoteRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UpdateController extends AbstractController
{
    #[Route('/room/{room_id}/ticket_list/update', name: 'ticket_list_update')]
    public function ticket_list_update(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        TicketRepository $ticketRepository,
        RoundRepository $roundRepository,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $tickets = $ticketRepository->findBy(
            ['room' => $room],
            ['updatedAt' => 'DESC'],
        );

        $round = $roundRepository->findActiveRoundByRoom($room);

        $ticketEditForms = [];
        foreach ($tickets as $ticket) {
            $uuid = $ticket->getUuid()->toRfc4122();

            $ticketEditForms[$uuid] = $formFactory
                ->create(TicketType::class, $ticket, [
                    'action' => $urlGenerator->generate('ticket_edit', [
                        'room_id' => $room->getUuid(),
                        'ticket_id' => $ticket->getUuid(),
                    ]),
                    'method' => 'POST',
                ])
                ->createView();
        }

        return $this->render('ticket/stream/_ticket_card_list.html.twig', [
            'room' => $room,
            'tickets' => $tickets,
            'round' => $round,
            'ticketEditForms' => $ticketEditForms,
        ]);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/create', name: 'ticket_card_create')]
    public function ticket_card_create(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        RoundRepository $roundRepository,
        TicketRepository $ticketRepository,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $tickets = $ticketRepository->findBy(
            ['room' => $room],
            ['updatedAt' => 'DESC'],
        );

        $ticketEditForms = [];
        foreach ($tickets as $currentTicket) {
            $uuid = $currentTicket->getUuid()->toRfc4122();

            $ticketEditForms[$uuid] = $formFactory
                ->create(TicketType::class, $currentTicket, [
                    'action' => $urlGenerator->generate('ticket_edit', [
                        'room_id' => $room->getUuid(),
                        'ticket_id' => $currentTicket->getUuid(),
                    ]),
                    'method' => 'POST',
                ])
                ->createView();
        }

        $round = $roundRepository->findActiveRoundByRoom($room);

        return $this->render('ticket/stream/_ticket_card.html.twig', [
            'action' => "prepend",
            'room' => $room,
            'ticket' => $ticket,
            'round' => $round,
            'ticketEditForms' => $ticketEditForms,
        ], new Response(headers: [
            'Content-Type' => 'text/vnd.turbo-stream.html',
        ]));
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/update', name: 'ticket_card_update')]
    public function ticket_card_update(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        RoundRepository $roundRepository,
        TicketRepository $ticketRepository,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $tickets = $ticketRepository->findBy(
            ['room' => $room],
            ['updatedAt' => 'DESC'],
        );

        $ticketEditForms = [];
        foreach ($tickets as $currentTicket) {
            $uuid = $currentTicket->getUuid()->toRfc4122();

            $ticketEditForms[$uuid] = $formFactory
                ->create(TicketType::class, $currentTicket, [
                    'action' => $urlGenerator->generate('ticket_edit', [
                        'room_id' => $room->getUuid(),
                        'ticket_id' => $currentTicket->getUuid(),
                    ]),
                    'method' => 'POST',
                ])
                ->createView();
        }

        $round = $roundRepository->findActiveRoundByRoom($room);

        return $this->render('ticket/stream/_ticket_card.html.twig', [
            'action' => "replace",
            'room' => $room,
            'ticket' => $ticket,
            'round' => $round,
            'ticketEditForms' => $ticketEditForms,
        ], new Response(headers: [
            'Content-Type' => 'text/vnd.turbo-stream.html',
        ]));
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/remove', name: 'ticket_card_remove')]
    public function ticket_card_remove(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        string $ticket_id,
        RoundRepository $roundRepository,
    ): Response {
        return $this->render('ticket/stream/_ticket_card.html.twig', [
            'action' => "remove",
            'ticket_id' => $ticket_id,
        ], new Response(headers: [
            'Content-Type' => 'text/vnd.turbo-stream.html',
        ]));
    }

    #[Route('/room/{room_id}/poker_table/update', name: 'poker_table_update')]
    public function poker_table_update(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoundRepository $roundRepository,
        VoteRepository $voteRepository,
    ): Response {
        $activeRound = $roundRepository->findActiveRoundByRoom($room);

        $votedUserIds = [];
        if ($activeRound) {
            $votedUserIds = $voteRepository->findVotedUserIds($activeRound);
        }

        return $this->render('room/stream/_poker_table.html.twig', [
            'room' => $room,
            'round' => $activeRound,
            'votedUserIds' => $votedUserIds,
        ]);
    }

    #[Route('/room/{room_id}/room_settings/update', name: 'room_settings_update')]
    public function room_settings_update(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        DeckRepository $deckRepository,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $user = $this->getUser();
        $systemDecks = $deckRepository->findSystemDecks();
        $userDecks = $deckRepository->findAllByOwner($user);

        $roomSettingsForm = $formFactory->create(RoomType::class, $room, [
            'attr' => [
                'id' => 'room_edit_form',
            ],
            'action' => $urlGenerator->generate('room_edit', [
                'room_id' => $room->getUuid(),
            ]),
            'method' => 'POST',
        ]);

        return $this->render('room/stream/_room_settings.html.twig', [
            'room' => $room,
            'systemDecks' => $systemDecks,
            'userDecks' => $userDecks,
            'roomSettingsForm' => $roomSettingsForm->createView(),
        ]);
    }

    #[Route('/room/{room_id}/member_list/update', name: 'member_list_update')]
    public function member_list_update(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoomMemberRepository $roomMemberRepository,
        RoundRepository $roundRepository,
        VoteRepository $voteRepository,
    ): Response {
        $members = $roomMemberRepository->findMembersWithOnlineStatus($room);

        $activeRound = $roundRepository->findActiveRoundByRoom($room);

        $votedUserIds = [];
        if ($activeRound) {
            $votedUserIds = $voteRepository->findVotedUserIds($activeRound);
        }

        return $this->render('room/stream/_member_list.html.twig', [
            'room' => $room,
            'members' => $members,
            'votedUserIds' => $votedUserIds,
        ]);
    }
}

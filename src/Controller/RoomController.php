<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\Ticket;
use App\Event\RoomEditedEvent;
use App\Event\RoundStartedEvent;
use App\Form\JoinRoomType;
use App\Form\RoomType;
use App\Form\TicketType;
use App\Repository\RoomMemberRepository;
use App\Repository\RoundRepository;
use App\Repository\VoteRepository;
use App\Security\Voter\RoomVoter;
use App\Service\DeckService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class RoomController extends AbstractController
{
    #[Route('/room/list', name: 'room_list', methods: ['GET'])]
    public function list(
        Security $security,
        RoomService $roomService
    ): Response {
        $user = $security->getUser();
        $rooms = $roomService->findAllByOwner($user);

        return $this->render('room/list.html.twig', [
            'rooms' => $rooms,
        ]);
    }

    #[Route('/room/create', name: 'room_create', methods: ['GET', 'POST'])]
    public function create(
        Security $security,
        Request $request,
        RoomService $roomService,
        DeckService $deckService
    ): Response {
        $user = $security->getUser();

        $room = new Room();
        $room->setOwner($user);
        $form = $this->createForm(RoomType::class, $room, [
            'decks' => $deckService->findAllByOwner($user)
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roomService->createRoom($room);

            return $this->redirectToRoute('room_show', [
                'room_id'=>$room->getUuid(),
            ]);
        }

        return $this->render('room/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/join', name: 'room_join')]
    public function join(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(JoinRoomType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uuid = $form->get('uuid')->getData();
            $room = $entityManager
                ->getRepository(Room::class)
                ->findOneBy(['uuid' => $uuid]);

            if (!$room) {
                $this->addFlash('error', 'Room not found.');
                return $this->redirectToRoute('room_join');
            }

            return $this->redirectToRoute('room_show', [
                'room_id' => $room->getUuid(),
            ]);
        }
        return $this->render('room/join.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/{room_id}', name: 'room_show', methods: ['GET', 'POST'])]
    public function show(
        #[MapEntity(mapping: ['room_id' => 'uuid'])]
        Room $room,
        RoundRepository $roundRepository,
        RoomMemberRepository $roomMemberRepository,
        EntityManagerInterface $entityManager
    ): Response {

        $ticket = new Ticket();
        $ticket->setRoom($room);
        $form = $this->createForm(TicketType::class, $ticket);

        $activeRound = $roundRepository->findActiveRoundByRoom($room);

        $user = $this->getUser();
        $roomMember = $roomMemberRepository->findMember($room, $user);

        if (!$roomMember) {
            $roomMember = new RoomMember();
            $roomMember
                ->setRoom($room)
                ->setUser($user);
        }

        $roomMember->setLastSeen(new \DateTimeImmutable());

        $entityManager->persist($roomMember);
        $entityManager->flush();

        return $this->render('room/show.html.twig', [
            'room' => $room,
            'form' => $form,
            'activeRound' => $activeRound,
        ]);
    }

    #[Route('/room/{room_id}/heartbeat', name: 'room_heartbeat', methods: ['POST'])]
    public function heartbeat(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        EntityManagerInterface $entityManager,
    ): Response {

        $user = $this->getUser();
        $member = $entityManager
            ->getRepository(RoomMember::class)
            ->findOneBy([
                'room' => $room,
                'user' => $user,
            ]);

        if (!$member) {
            return new Response(status: 403);
        }

        $member->setLastSeen(new \DateTimeImmutable());

        $entityManager->flush();

        return new Response(status: 204);
    }

    #[Route('/room/{room_id}/members', name: 'room_members', methods: ['GET'])]
    public function members(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        RoomMemberRepository $roomMemberRepository,
        RoundRepository $roundRepository,
        VoteRepository $voteRepository,
    ): Response {

        $members = $roomMemberRepository->findOnlineMembers($room);
        $round = $roundRepository->findActiveRoundByRoom($room);

        $votedUserIds = [];
        if ($round) {
            $votedUserIds = $voteRepository->findVotedUserIds($round);
        }

        return $this->render('room/elements/_members.html.twig', [
            'members' => $members,
            'votedUserIds' => $votedUserIds,
        ]);
    }

    #[Route('/room/{room_id}/edit', name: 'room_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['room_id' => 'uuid'])]
        Room $room,
        Request $request,
        RoomService $roomService,
        DeckService $deckService,
        Security $security,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $user = $security->getUser();
        $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);

        $form = $this->createForm(RoomType::class, $room, [
            'decks' => $deckService->findAllByOwner($user)
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roomService->updateRoom($room);

            $room->setUpdatedAt();
            $roomService->updateRoom($room);

            $eventDispatcher->dispatch(
                new RoomEditedEvent($room),
            );

            return $this->redirectToRoute('room_show', [
                'room_id' => $room->getUuid(),
            ]);
        }

        return $this->render('room/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/{room_id}/delete', name: 'room_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['room_id' => 'uuid'])]
        Room $room,
        RoomService $roomService
    ): Response {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE, $room);

        $roomService->deleteRoom($room);

        return $this->redirectToRoute('room_list');
    }
}

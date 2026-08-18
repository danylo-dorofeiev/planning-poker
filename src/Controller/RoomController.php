<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Event\RoomEditedEvent;
use App\Event\RoundStartedEvent;
use App\Form\RoomType;
use App\Form\TicketType;
use App\Repository\RoundRepository;
use App\Security\Voter\RoomVoter;
use App\Service\DeckService;
use App\Service\RoomService;
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
    public function list(RoomService $roomService, Security $security): Response {
        return $this->render('room/list.html.twig', [
            'rooms'=>$roomService->findAllByOwner($security->getUser()),
        ]);
    }

    #[Route('/room/create', name: 'room_create', methods: ['GET', 'POST'])]
    public function create(Request $request, RoomService $roomService, DeckService $deckService, Security $security): Response {
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
                'uuid'=>$room->getUuid(),
            ]);
        }

        return $this->render('room/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/{uuid}', name: 'room_show', methods: ['GET', 'POST'])]
    public function show(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, RoundRepository $roundRepository, EventDispatcherInterface $eventDispatcher): Response {
        $ticket = new Ticket();
        $ticket->setRoom($room);

        $form = $this->createForm(TicketType::class, $ticket);

        $activeRound = $roundRepository->findActiveRoundByRoom($room);

        return $this->render('room/show.html.twig', [
            'room'=>$room,
            'form'=>$form,
            'activeRound'=>$activeRound,
        ]);
    }

    #[Route('/room/{uuid}/edit', name: 'room_edit', methods: ['GET', 'POST'])]
    public function edit(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, RoomService $roomService, DeckService $deckService, Security $security, EventDispatcherInterface $eventDispatcher): Response {
        $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);

        $user = $security->getUser();

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
                'uuid' => $room->getUuid(),
            ]);
        }

        return $this->render('room/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/{uuid}/delete', name: 'room_delete', methods: ['POST'])]
    public function delete(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, RoomService $roomService): Response {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE, $room);

        $roomService->deleteRoom($room);

        return $this->redirectToRoute('room_list');
    }
}

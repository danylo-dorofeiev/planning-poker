<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Form\RoomType;
use App\Form\TicketType;
use App\Security\Voter\RoomVoter;
use App\Service\DeckService;
use App\Service\RoomService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
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
    public function create(Request $request, RoomService $roomService, DeckService $deckService): Response
    {
        $user = $this->getUser();

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

    #[Route('/room/{uuid}', name: 'room_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room): Response {
        $ticket = new Ticket();
        $ticket->setRoom($room);

        $form = $this->createForm(TicketType::class, $ticket);

        return $this->render('room/show.html.twig',
            [
                'room'=>$room,
                'form'=>$form,
            ]
        );
    }

    #[Route('/room/{uuid}/edit', name: 'room_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, RoomService $roomService): Response {
        $this->denyAccessUnlessGranted(RoomVoter::EDIT, $room);

        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roomService->updateRoom($room);

            return $this->redirectToRoute('room_show', [
                'uuid' => $room->getUuid(),
            ]);
        }

        return $this->render('room/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/room/{uuid}/delete', name: 'room_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, RoomService $roomService): Response {
        $this->denyAccessUnlessGranted(RoomVoter::DELETE, $room);

        $roomService->deleteRoom($room);

        return $this->redirectToRoute('room_list');
    }
}

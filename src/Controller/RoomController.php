<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\RoomRepository;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class RoomController extends AbstractController
{
    #[Route('/room/list', name: 'room_list')]
    public function list(RoomRepository $roomRepository): Response {
        return $this->render('room/list.html.twig', [
            'rooms'=>$roomRepository->findAll(),
        ]);
    }

    #[Route('/room/create', name: 'room_create')]
    public function create(Request $request, RoomService $roomService): Response
    {
        $room = new Room();

        $form = $this->createForm(RoomType::class, $room);
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

    #[Route('/room/{uuid}', name: 'room_show')]
    public function show(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room): Response {
        return $this->render('room/show.html.twig',
            [
                'room'=>$room,
                'room.uuid'=>$room->getUuid(),
                'room.name'=>$room->getName(),
                'room.description'=>$room->getDescription(),
                'room.maxUsers'=>$room->getMaxUsers(),
                'room.createdAt'=>$room->getCreatedAt(),
            ]
        );
    }

    #[Route('/room/{uuid}/join', name: 'room_join')]
    public function join(Room $room, HubInterface $hub): Response {
        $update = new Update(
            $room->getMercureTopic(),
            json_encode([
                'type' => 'join',
                'message' => 'New player joined the room',
            ])
        );

        $hub->publish($update);

        return new Response('Joined');
    }

    #[Route('/room/{uuid}/edit', name: 'room_edit')]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, RoomService $roomService): Response {
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
}

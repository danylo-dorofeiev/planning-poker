<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Service\DeckService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeckController extends AbstractController
{
    #[Route('/deck/list', name: 'deck_list', methods: ['GET'])]
    public function list(DeckService $deckService): Response {
        $deck = new Deck();

        $form = $this->createForm(DeckType::class, $deck);

        return $this->render('deck/list.html.twig', [
            'decks'=>$deckService->findAll(),
            'form' => $form,
        ]);
    }

    #[Route('/deck/create', name: 'deck_create')]
    public function create(Request $request, DeckService $deckService): Response {
        $deck = new Deck();

        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($deck->getCards() as $card) {
                $card->setDeck($deck);
            }

            $deckService->createDeck($deck);

            return $this->redirectToRoute('deck_list');
        }

        return $this->render('deck/create.html.twig', [
            'form' => $form,
        ]);
    }
}

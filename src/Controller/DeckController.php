<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Form\DeckType;
use App\Security\Voter\DeckVoter;
use App\Service\DeckService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DeckController extends AbstractController
{
    #[Route('/deck/list', name: 'deck_list', methods: ['GET'])]
    public function list(DeckService $deckService, Security $security): Response {
        $deck = new Deck();
        $deck->setOwner($this->getUser());

        $form = $this->createForm(DeckType::class, $deck);

        return $this->render('deck/list.html.twig', [
            'decks'=>$deckService->findAllByOwner($security->getUser()),
            'form' => $form,
        ]);
    }

    #[Route('/deck/create', name: 'deck_create', methods: ['GET', 'POST'])]
    public function create(Request $request, DeckService $deckService): Response {
        $deck = new Deck();
        $deck->setOwner($this->getUser());

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

    #[Route('/deck/{uuid}/edit', name: 'deck_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Deck $deck, DeckService $deckService, Request $request): Response {
        if ($deck->isSystem()) {
            throw new \LogicException('This deck cannot be edited because it is used as a default deck.');
        }

        $this->denyAccessUnlessGranted(DeckVoter::DELETE, $deck);

        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deckService->updateDeck($deck);

            return $this->redirectToRoute('deck_list');
        }

        return $this->render('deck/_edit_form.html.twig', [
            'deck'=>$deck,
            'form' => $form,
        ]);
    }

    #[Route('/deck/{uuid}/delete', name: 'deck_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Deck $deck, DeckService $deckService): Response {
        if ($deck->isSystem()) {
            throw new \LogicException('This deck cannot be deleted because it is used as a default deck.');
        }

        $this->denyAccessUnlessGranted(DeckVoter::DELETE, $deck);

        $deckService->deleteDeck($deck);

        return $this->redirectToRoute('deck_list');
    }
}

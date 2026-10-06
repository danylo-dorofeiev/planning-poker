<?php

namespace App\EventListener;

use App\Event\TicketCardListEvent;
use App\Form\TicketType;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[AsEventListener]
class TicketCardListListener
{
    public function __construct(
        private HubInterface    $hub,
        private Environment     $twig,
        private TicketRepository $ticketRepository,
        private RoundRepository $roundRepository,
        private FormFactoryInterface   $formFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(TicketCardListEvent $event): void
    {

    }
}


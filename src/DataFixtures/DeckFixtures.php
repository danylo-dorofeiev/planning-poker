<?php

namespace App\DataFixtures;

use App\Entity\Deck;
use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeckFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->Fibonacci($manager);
        $this->TShirt($manager);
        $this->HalfCard($manager);
        $this->Emojis($manager);
        $this->TrafficLights($manager);
    }

    private function Fibonacci(ObjectManager $manager): void
    {
        $deck = new Deck();
        $deck->setName('Fibonacci');
        $deck->setSystem(true);

        foreach (['0', '1', '2', '3', '5', '8', '13', '21', '34', '55', '89'] as $value) {
            $card = new Card();
            $card->setValue($value);
            $deck->addCard($card);
            $manager->persist($card);
        }

        $manager->persist($deck);
        $manager->flush();
    }

    private function TShirt(ObjectManager $manager): void
    {
        $deck = new Deck();
        $deck->setName('T-Shirt');
        $deck->setSystem(true);

        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $value) {
            $card = new Card();
            $card->setValue($value);
            $deck->addCard($card);
            $manager->persist($card);
        }

        $manager->persist($deck);
        $manager->flush();
    }

    private function HalfCard(ObjectManager $manager): void
    {
        $deck = new Deck();
        $deck->setName('Half Card');
        $deck->setSystem(true);

        foreach (['1', '1½', '2', '2½', '3', '3½', '4'] as $value) {
            $card = new Card();
            $card->setValue($value);
            $deck->addCard($card);
            $manager->persist($card);
        }

        $manager->persist($deck);
        $manager->flush();
    }

    private function Emojis(ObjectManager $manager): void
    {
        $deck = new Deck();
        $deck->setName('Emojis');
        $deck->setSystem(true);

        foreach (['🤢', '🤮', '🤧', '😐', '😊', '🥰', '😍'] as $value) {
            $card = new Card();
            $card->setValue($value);
            $deck->addCard($card);
            $manager->persist($card);
        }

        $manager->persist($deck);
        $manager->flush();
    }

    private function TrafficLights(ObjectManager $manager): void
    {
        $deck = new Deck();
        $deck->setName('Traffic Lights');
        $deck->setSystem(true);

        foreach (['🔴', '🟡', '🟢'] as $value) {
            $card = new Card();
            $card->setValue($value);
            $deck->addCard($card);
            $manager->persist($card);
        }

        $manager->persist($deck);
        $manager->flush();
    }
}

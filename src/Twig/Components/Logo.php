<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Logo
{
    public string $size="medium";
    public bool $isText=true;
    public bool $isDemo=true;
}

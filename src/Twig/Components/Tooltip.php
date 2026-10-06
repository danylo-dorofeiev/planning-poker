<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Tooltip
{
    public string $position="bottom center";
    public string $type="text";
    public int $delay=0;
    public string $pointer="auto";
    public string $css="";
}

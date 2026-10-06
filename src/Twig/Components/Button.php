<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Button
{
    public string $link="";
    public string $target="_self";

    public string $turbo="true";
    public bool $preload=false;

    public string $type="smooth";
    public string $size="small";

    public string $modal="";

    public string $func="button";

    public string $name="";
    public string $value="";

    public string $data_action="";

    public string $custom_param="";
    public string $css="";
}

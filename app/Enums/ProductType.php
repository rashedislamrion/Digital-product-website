<?php

namespace App\Enums;

enum ProductType: string
{
    case Software = 'software';
    case Theme = 'theme';
    case Ebook = 'ebook';
    case Bundle = 'bundle';
}

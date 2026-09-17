<?php

namespace App\Enums;

enum ProductVisibility: string
{
    case Published = 'published';
    case Draft = 'draft';
    case Unlisted = 'unlisted';
    case Archived = 'archived';
}

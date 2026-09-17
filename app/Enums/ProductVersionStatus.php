<?php

namespace App\Enums;

enum ProductVersionStatus: string
{
    case Quarantined = 'quarantined';
    case Published = 'published';
    case Deprecated = 'deprecated';
}

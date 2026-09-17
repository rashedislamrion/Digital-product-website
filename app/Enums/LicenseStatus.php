<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Issued = 'issued';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';
    case Revoked = 'revoked';
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum StudentDocumentType: string
{
    case BirthCertificate = 'birth_certificate';
    case Cnic = 'cnic';
    case Marksheet = 'marksheet';
    case TransferCertificate = 'transfer_certificate';
    case Medical = 'medical';
    case Photo = 'photo';
    case Other = 'other';
}

<?php

namespace App\Enums;

enum HealthRecordType: string
{
    case ClinicVisit = 'clinic_visit';
    case Vaccination = 'vaccination';
    case Allergy = 'allergy';
    case MedicalCondition = 'medical_condition';
    case Medication = 'medication';
    case CheckUp = 'check_up';

    public function label(): string
    {
        return match ($this) {
            self::ClinicVisit => 'Clinic Visit',
            self::Vaccination => 'Vaccination',
            self::Allergy => 'Allergy',
            self::MedicalCondition => 'Medical Condition',
            self::Medication => 'Medication',
            self::CheckUp => 'Check-Up',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ClinicVisit => 'primary',
            self::Vaccination => 'success',
            self::Allergy => 'danger',
            self::MedicalCondition => 'warning',
            self::Medication => 'info',
            self::CheckUp => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ClinicVisit => 'heroicon-o-building-office-2',
            self::Vaccination => 'heroicon-o-shield-check',
            self::Allergy => 'heroicon-o-exclamation-triangle',
            self::MedicalCondition => 'heroicon-o-heart',
            self::Medication => 'heroicon-o-beaker',
            self::CheckUp => 'heroicon-o-clipboard-document-check',
        };
    }
}

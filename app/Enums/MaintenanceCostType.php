<?php

namespace App\Enums;

enum MaintenanceCostType: string
{
    case Labor = 'labor';
    case Material = 'material';
    case ContractorFee = 'contractor_fee';
    case Transportation = 'transportation';
    case Miscellaneous = 'miscellaneous';

    public function label(): string
    {
        return match($this) {
            self::Labor => 'Labor',
            self::Material => 'Material',
            self::ContractorFee => 'Contractor Fee',
            self::Transportation => 'Transportation',
            self::Miscellaneous => 'Miscellaneous',
        };
    }
}

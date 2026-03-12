<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Refunded = 'refunded';
    case Failed   = 'failed';

    public function label(): string
    {
        return match($this) {
            TransactionStatus::Pending  => 'Pendente',
            TransactionStatus::Approved => 'Aprovada',
            TransactionStatus::Refunded => 'Reembolsada',
            TransactionStatus::Failed   => 'Falhou',
        };
    }
}

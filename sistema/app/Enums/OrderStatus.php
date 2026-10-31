<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Reservado = 'reservado';
    case Entregado = 'entregado';
    case Cobrado = 'cobrado';
    case Anulado = 'anulado';

    public function label(): string
    {
        return match ($this) {
            self::Reservado => 'Reservado',
            self::Entregado => 'Entregado',
            self::Cobrado => 'Cobrado',
            self::Anulado => 'Anulado',
        };
    }
}

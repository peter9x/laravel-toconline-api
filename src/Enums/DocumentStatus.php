<?php

namespace Mupy\TOConline\Enums;

/**
 * Status de um documento:
 *
 * 0 - documento em preparação
 * 1 - documento finalizado
 * 2 - documento pendente vencido
 * 3 - documento pago
 * 4 - documento anulado
 */
enum DocumentStatus: int
{
    case DRAFT = 0;
    case FINISHED = 1;
    case PENDING = 2;
    case PAID = 3;
    case CANCELLED = 4;
}

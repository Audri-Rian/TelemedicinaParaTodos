<?php

namespace App\MedicalRecord\Domain\Exceptions;

use Exception;

/**
 * Exceção de domínio: prescrição sem assinatura digital válida (RN-PRESC-002).
 * Uso futuro quando ICP-Brasil for implementado.
 */
class PrescriptionWithoutSignatureException extends Exception
{
    public function __construct(string $message = 'Prescrição não pode ser emitida sem assinatura digital ICP-Brasil válida.')
    {
        parent::__construct($message);
    }
}

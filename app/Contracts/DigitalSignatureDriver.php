<?php

namespace App\Contracts;

use App\Support\Signatures\SignatureResult;

/**
 * Driver de assinatura digital de documentos médicos.
 *
 * Implementações:
 * - NullSignatureDriver: dev/staging — gera hash SHA-256 + verification_code, sem ICP-Brasil real.
 * - IcpBrasilSignatureDriver: produção — integra com provedor (Soluti/Certisign/Safeweb).
 *   Requer Resolução CFM 2.314/2022 Art. 8 (assinatura ICP-Brasil obrigatória).
 *
 * Selecionado via config('telemedicine.signature.driver').
 */
interface DigitalSignatureDriver
{
    /**
     * Assina conteúdo canônico de um documento (prescription, certificate).
     *
     * @param  string  $canonicalPayload  String determinística que representa o documento.
     */
    public function sign(string $canonicalPayload): SignatureResult;

    /**
     * Verifica se hash + verification_code são válidos para o conteúdo recomputado.
     */
    public function verify(string $canonicalPayload, string $hash): bool;

    /**
     * Identificador do driver (null, icp_brasil, ...). Usado em logs e auditoria.
     */
    public function name(): string;

    /**
     * Indica se o driver produz assinatura com validade legal CFM/ICP-Brasil.
     * NullSignatureDriver retorna false — útil para frontend exibir aviso.
     */
    public function hasLegalValidity(): bool;
}

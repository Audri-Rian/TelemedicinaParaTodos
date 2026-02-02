<?php

namespace App\MedicalRecord\Domain\Contracts;

use App\MedicalRecord\Domain\ValueObjects\CertificateSignature;

/**
 * Interface para integração com ICP-Brasil (Infraestrutura de Chaves Públicas Brasileira).
 *
 * Conformidade CFM: Art. 8º – Resolução 2.314/2022
 * "Os documentos médicos resultantes de atendimento por telemedicina deverão conter
 * identificação e assinatura do médico."
 *
 * Implementações reais devem integrar com provedores como Soluti, Certisign, Safeweb, etc.
 * Certificados A1 (arquivo) ou A3 (token/smartcard) são aceitos.
 */
interface ICPBrasilAdapter
{
    /**
     * Assina digitalmente o conteúdo do documento.
     *
     * @param  string  $documentContent  Conteúdo canônico a ser assinado (ex: JSON do documento)
     * @param  array{doctor_id: string, document_type: string}  $context  Contexto da assinatura
     * @return CertificateSignature Hash e código de verificação
     *
     * @throws \App\MedicalRecord\Domain\Exceptions\PrescriptionWithoutSignatureException
     *         Quando certificado inválido ou não configurado
     */
    public function signDocument(string $documentContent, array $context): CertificateSignature;

    /**
     * Verifica se o adapter está configurado e pronto para assinar.
     * Útil para feedback na UI antes de tentar emitir documento.
     */
    public function isConfigured(): bool;
}

<?php

namespace App\Contracts;

use App\Models\Doctor;

/**
 * Assina digitalmente um PDF com PAdES (PDF Advanced Electronic Signature).
 *
 * Pipeline esperado: gerar PDF → signPdf() → persistir PDF assinado.
 *
 * Implementações:
 * - NullPdfSigner: dev/staging — retorna PDF sem modificação, sem validade legal.
 * - A1PdfSigner: produção — PAdES-BES com certificado A1 (PFX local).
 */
interface PdfSigner
{
    /**
     * Assina os bytes do PDF e retorna os bytes do PDF assinado.
     *
     * @param  string  $pdfBytes  Conteúdo binário do PDF gerado (ex.: saída do DomPDF).
     * @param  Doctor  $doctor  Médico titular da assinatura.
     * @param  string  $reason  Motivo textual da assinatura (impresso no PDF).
     * @return string Bytes do PDF com a assinatura PAdES embutida.
     */
    public function signPdf(string $pdfBytes, Doctor $doctor, string $reason): string;

    /**
     * Identificador do driver (null, a1_local, …). Usado em auditoria.
     */
    public function name(): string;

    /**
     * Indica se o driver produz assinatura com validade legal ICP-Brasil / CFM.
     * NullPdfSigner retorna false — exibir aviso no frontend.
     */
    public function hasLegalValidity(): bool;
}

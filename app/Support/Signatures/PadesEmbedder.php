<?php

namespace App\Support\Signatures;

use RuntimeException;

/**
 * Embeds a PAdES-BES (adbe.pkcs7.detached) digital signature into an existing PDF
 * via an incremental update, as specified by ISO 32000-1 Section 12.8.
 *
 * Algorithm:
 *   1. Parse existing PDF: find catalog object number and last object number.
 *   2. Append incremental update containing:
 *        - Signature dictionary object (with /ByteRange and /Contents placeholders)
 *        - Signature field widget object
 *        - Updated catalog referencing the new AcroForm
 *   3. Compute /ByteRange = [0, offsetBeforeContents, offsetAfterContents, remainingLen].
 *   4. Sign the designated byte ranges using openssl_cms_sign() → DER output.
 *   5. Hex-encode the DER, pad to PLACEHOLDER_HEX_LEN, fill /Contents placeholder.
 */
final class PadesEmbedder
{
    // 15 000 bytes (30 000 hex chars) — enough for RSA-2048 + full ICP-Brasil chain
    private const PLACEHOLDER_HEX_LEN = 30000;

    // Fixed-length placeholder so byte positions don't shift when we fill it
    private const BYTERANGE_PLACEHOLDER = '[0000000000 0000000000 0000000000 0000000000]';

    public function embed(
        string $pdf,
        string $certPem,
        string $privateKeyPem,
        string $reason,
        string $signerName,
        array $extraCertsPem = [],
    ): string {
        $origLen = strlen($pdf);
        $catalogObjNum = $this->findCatalogObjNum($pdf);
        $lastObjNum = $this->getMaxObjNum($pdf);
        $origStartXref = $this->getStartXref($pdf);

        $sigObjNum = $lastObjNum + 1;
        $fieldObjNum = $lastObjNum + 2;

        $date = 'D:'.gmdate('YmdHis')."+00'00'";

        // ── Signature dictionary (with placeholders) ────────────────────────
        $sigDictHeader =
            "{$sigObjNum} 0 obj\n".
            "<<\n".
            "/Type /Sig\n".
            "/Filter /Adobe.PPKLite\n".
            "/SubFilter /adbe.pkcs7.detached\n".
            '/Reason ('.$this->pdfStr($reason).")\n".
            '/Name ('.$this->pdfStr($signerName).")\n".
            "/M ({$date})\n".
            '/ByteRange '.self::BYTERANGE_PLACEHOLDER."\n".
            '/Contents <';

        $contentsPlaceholder = str_repeat('0', self::PLACEHOLDER_HEX_LEN);
        $sigDictFooter = ">\n>>\nendobj\n";
        $sigObj = $sigDictHeader.$contentsPlaceholder.$sigDictFooter;

        // ── Signature field widget ───────────────────────────────────────────
        $fieldObj =
            "{$fieldObjNum} 0 obj\n".
            "<<\n".
            "/Type /Annot\n".
            "/Subtype /Widget\n".
            "/FT /Sig\n".
            "/Rect [0 0 0 0]\n".
            "/T (Signature)\n".
            "/V {$sigObjNum} 0 R\n".
            "/F 132\n".
            ">>\n".
            "endobj\n";

        // ── Updated catalog with AcroForm ────────────────────────────────────
        $existingEntries = $this->extractCatalogEntries($pdf, $catalogObjNum);
        $updatedCatalog =
            "{$catalogObjNum} 0 obj\n".
            "<<\n".
            "/Type /Catalog\n".
            $existingEntries.
            "/AcroForm <<\n".
            "/Fields [{$fieldObjNum} 0 R]\n".
            "/SigFlags 3\n".
            ">>\n".
            ">>\n".
            "endobj\n";

        // ── Compute object byte offsets (absolute from file start) ───────────
        $sigObjOffset = $origLen;
        $fieldObjOffset = $sigObjOffset + strlen($sigObj);
        $catalogOffset = $fieldObjOffset + strlen($fieldObj);
        $xrefOffset = $catalogOffset + strlen($updatedCatalog);

        // ── Build xref for incremental update ───────────────────────────────
        $xrefEntries = [
            $sigObjNum => sprintf("%010d 00000 n \n", $sigObjOffset),
            $fieldObjNum => sprintf("%010d 00000 n \n", $fieldObjOffset),
            $catalogObjNum => sprintf("%010d 00000 n \n", $catalogOffset),
        ];
        $xrefStr = $this->buildXref($xrefEntries);

        $maxObjInUpdate = max($sigObjNum, $fieldObjNum, $catalogObjNum);

        $trailer =
            "trailer\n".
            "<<\n".
            '/Size '.($maxObjInUpdate + 1)."\n".
            "/Root {$catalogObjNum} 0 R\n".
            "/Prev {$origStartXref}\n".
            ">>\n".
            "startxref\n".
            $xrefOffset."\n".
            "%%EOF\n";

        // ── Assemble full document with placeholders ─────────────────────────
        $full = $pdf.$sigObj.$fieldObj.$updatedCatalog.$xrefStr.$trailer;

        // ── Compute /ByteRange values ────────────────────────────────────────
        // /Contents value starts right after the '<' in "/Contents <"
        $contentsOffset = $origLen + strlen($sigDictHeader);
        $contentsEnd = $contentsOffset + self::PLACEHOLDER_HEX_LEN;
        $totalLen = strlen($full);
        $afterLen = $totalLen - $contentsEnd;

        $byteRange = "[0 {$contentsOffset} {$contentsEnd} {$afterLen}]";
        $paddedByteRange = str_pad($byteRange, strlen(self::BYTERANGE_PLACEHOLDER));

        // Replace the /ByteRange placeholder (appears exactly once)
        $full = substr_replace(
            $full,
            $paddedByteRange,
            strpos($full, self::BYTERANGE_PLACEHOLDER),
            strlen(self::BYTERANGE_PLACEHOLDER),
        );

        // ── Sign the byte ranges ─────────────────────────────────────────────
        $before = substr($full, 0, $contentsOffset);
        $after = substr($full, $contentsEnd, $afterLen);
        $dataToSign = $before.$after;

        $derSig = $this->computeCmsSignature($dataToSign, $certPem, $privateKeyPem, $extraCertsPem);
        $hexSig = bin2hex($derSig);

        if (strlen($hexSig) > self::PLACEHOLDER_HEX_LEN) {
            throw new RuntimeException(sprintf(
                'PAdES: CMS signature (%d hex chars) exceeds placeholder (%d). Increase PLACEHOLDER_HEX_LEN.',
                strlen($hexSig),
                self::PLACEHOLDER_HEX_LEN,
            ));
        }

        // ── Fill /Contents placeholder ───────────────────────────────────────
        $paddedHex = str_pad($hexSig, self::PLACEHOLDER_HEX_LEN, '0');

        return substr_replace($full, $paddedHex, $contentsOffset, self::PLACEHOLDER_HEX_LEN);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Create a detached CMS/PKCS7 signature using PHP's openssl_cms_sign().
     * Returns raw DER bytes ready for hex-encoding into /Contents.
     */
    private function computeCmsSignature(
        string $data,
        string $certPem,
        string $privateKeyPem,
        array $extraCertsPem,
    ): string {
        $tmpIn = tempnam(sys_get_temp_dir(), 'pades_in_');
        $tmpOut = tempnam(sys_get_temp_dir(), 'pades_out_');
        $tmpExtra = null;

        try {
            // 0600: restrict temp PDF content to owner process — LGPD compliance
            chmod($tmpIn, 0600);
            file_put_contents($tmpIn, $data);

            if ($extraCertsPem !== []) {
                $tmpExtra = tempnam(sys_get_temp_dir(), 'pades_chain_');
                file_put_contents($tmpExtra, implode("\n", $extraCertsPem));
            }

            $cert = openssl_x509_read($certPem);
            $key = openssl_pkey_get_private($privateKeyPem);

            if (! $cert || ! $key) {
                throw new RuntimeException('PAdES: failed to load certificate or private key. '.openssl_error_string());
            }

            // OPENSSL_CMS_DETACHED:  signature doesn't include original content
            // OPENSSL_CMS_BINARY:    treat input as binary (no CRLF normalization)
            // OPENSSL_CMS_NOSMIMECAP: omit S/MIME capabilities list (smaller output)
            // OPENSSL_ENCODING_DER:  output raw DER bytes (not SMIME/PEM)
            $ok = openssl_cms_sign(
                $tmpIn,
                $tmpOut,
                $cert,
                $key,
                [],
                OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY | OPENSSL_CMS_NOSMIMECAP,
                OPENSSL_ENCODING_DER,
                $tmpExtra,
            );

            if (! $ok) {
                throw new RuntimeException('PAdES: openssl_cms_sign failed. '.openssl_error_string());
            }

            $der = file_get_contents($tmpOut);
            if ($der === false || $der === '') {
                throw new RuntimeException('PAdES: empty DER output from openssl_cms_sign.');
            }

            return $der;
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
            if ($tmpExtra !== null) {
                @unlink($tmpExtra);
            }
        }
    }

    /**
     * Find the object number of the document catalog (/Root in the trailer).
     */
    private function findCatalogObjNum(string $pdf): int
    {
        // Search from the end of the file for the most recent /Root entry
        if (preg_match_all('/\/Root\s+(\d+)\s+\d+\s+R/', $pdf, $m)) {
            return (int) end($m[1]);
        }
        throw new RuntimeException('PAdES: could not find /Root in PDF trailer.');
    }

    /**
     * Return the highest object number present in the PDF.
     */
    private function getMaxObjNum(string $pdf): int
    {
        preg_match_all('/^(\d+)\s+0\s+obj/m', $pdf, $m);

        return empty($m[1]) ? 1 : (int) max($m[1]);
    }

    /**
     * Return the byte offset of the most recent startxref.
     */
    private function getStartXref(string $pdf): int
    {
        if (preg_match('/startxref\s+(\d+)\s*%%EOF\s*$/s', $pdf, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Extract the content of the catalog dictionary (everything except /Type /Catalog
     * and any existing /AcroForm, since we're replacing those).
     */
    private function extractCatalogEntries(string $pdf, int $objNum): string
    {
        // Find the object body using depth-tracking for nested dicts
        $pattern = '/(?<!\d)'.$objNum.'\s+0\s+obj\s*<</s';
        if (! preg_match($pattern, $pdf, $match, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $startPos = $match[0][1] + strlen($match[0][0]);
        $depth = 1;
        $pos = $startPos;
        $len = strlen($pdf);

        while ($pos < $len && $depth > 0) {
            if ($pdf[$pos] === '<' && $pos + 1 < $len && $pdf[$pos + 1] === '<') {
                $depth++;
                $pos += 2;

                continue;
            }
            if ($pdf[$pos] === '>' && $pos + 1 < $len && $pdf[$pos + 1] === '>') {
                $depth--;
                if ($depth === 0) {
                    break;
                }
                $pos += 2;

                continue;
            }
            $pos++;
        }

        $body = substr($pdf, $startPos, $pos - $startPos);

        // Strip entries we're replacing
        $body = preg_replace('/\/Type\s+\/Catalog\s*/', '', $body);
        $body = preg_replace('/\/AcroForm\s+\d+\s+\d+\s+R\s*/', '', $body);
        // Remove inline /AcroForm dict (single-level)
        $body = preg_replace('/\/AcroForm\s*<<[^<>]*>>\s*/', '', $body);

        $body = trim($body);

        return $body !== '' ? $body."\n" : '';
    }

    /**
     * Build a PDF xref table from an associative array of [objNum => entry].
     * Groups contiguous object numbers into xref sections.
     */
    private function buildXref(array $objEntries): string
    {
        ksort($objEntries);
        $sections = [];
        $currentStart = null;
        $currentEntries = [];

        foreach ($objEntries as $num => $entry) {
            if ($currentStart === null) {
                $currentStart = $num;
                $currentEntries = [$entry];
            } elseif ($num === $currentStart + count($currentEntries)) {
                $currentEntries[] = $entry;
            } else {
                $sections[] = [$currentStart, $currentEntries];
                $currentStart = $num;
                $currentEntries = [$entry];
            }
        }

        if ($currentStart !== null) {
            $sections[] = [$currentStart, $currentEntries];
        }

        $xref = "xref\n";
        foreach ($sections as [$start, $entries]) {
            $xref .= "{$start} ".count($entries)."\n";
            $xref .= implode('', $entries);
        }

        return $xref;
    }

    private function pdfStr(string $s): string
    {
        // ISO 32000-1 §7.3.4.2 — escape backslash, parens, CR, LF and control chars
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $s = str_replace(["\r", "\n"], ['\\r', '\\n'], $s);

        return preg_replace_callback('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f-\xff]/', function ($m) {
            return sprintf('\\%03o', ord($m[0]));
        }, $s);
    }
}

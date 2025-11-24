<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atestado Médico</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .section { margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 12px; }
        .label { font-weight: bold; color: #111827; }
        .value { margin-bottom: 4px; }
        .footer { font-size: 11px; color: #6b7280; text-align: center; margin-top: 32px; }
        .signature { margin-top: 32px; text-align: center; }
        .signature-line { border-top: 1px solid #9ca3af; width: 240px; margin: 0 auto; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="title">Atestado Médico</div>

    <div class="section">
        <div class="label">Paciente:</div>
        <div class="value">{{ $patient['user']['name'] ?? '' }}</div>
        <div class="label">Período:</div>
        <div class="value">
            {{ \Carbon\Carbon::parse($certificate->start_date)->format('d/m/Y') }}
            -
            {{ $certificate->end_date ? \Carbon\Carbon::parse($certificate->end_date)->format('d/m/Y') : 'Indeterminado' }}
        </div>
        <div class="label">Dias de afastamento:</div>
        <div class="value">{{ $certificate->days }}</div>
    </div>

    <div class="section">
        <div class="label">Motivo clínico:</div>
        <div class="value">{{ $certificate->reason }}</div>
        <div class="label">Restrições:</div>
        <div class="value">{{ $certificate->restrictions ?? 'Não informado' }}</div>
    </div>

    <div class="section">
        <div class="label">Dados do médico responsável:</div>
        <div class="value">{{ $doctor['name'] ?? '' }} — CRM {{ $doctor['crm'] ?? 'N/A' }}</div>
    </div>

    <div class="signature">
        <div class="signature-line">{{ $doctor['name'] ?? '' }}</div>
        <div>Assinatura</div>
    </div>

    <div class="footer">
        Código de verificação: {{ $certificate->verification_code }} · Documento emitido em {{ $certificate->created_at?->format('d/m/Y H:i') }}
    </div>
</body>
</html>



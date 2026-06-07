<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Receita Médica</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; }
        .title { font-size: 18px; font-weight: bold; color: #1d4ed8; }
        .subtitle { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .section { margin-bottom: 14px; }
        .label { font-weight: bold; color: #111827; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; }
        .value { margin-bottom: 4px; }
        .medication { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; margin-bottom: 6px; }
        .medication-name { font-weight: bold; }
        .medication-detail { font-size: 11px; color: #374151; }
        .footer { font-size: 10px; color: #6b7280; text-align: center; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .signature { margin-top: 32px; text-align: center; }
        .signature-line { border-top: 1px solid #9ca3af; width: 240px; margin: 0 auto; padding-top: 4px; font-size: 11px; }
        .signature-label { font-size: 10px; color: #6b7280; }
        .dev-warning { background: #fef9c3; border: 1px solid #fde047; padding: 4px 8px; font-size: 10px; color: #713f12; text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    @if(isset($devMode) && $devMode)
    <div class="dev-warning">DOCUMENTO DE DESENVOLVIMENTO — sem validade legal ICP-Brasil</div>
    @endif

    <div class="header">
        <div class="title">Receita Médica</div>
        <div class="subtitle">Emitida pela plataforma Telemedicina Para Todos</div>
    </div>

    <div class="section">
        <div class="label">Paciente</div>
        <div class="value">{{ $patient['user']['name'] ?? '' }}</div>
    </div>

    <div class="section">
        <div class="label">Medicamentos prescritos</div>
        @forelse($medications as $med)
        <div class="medication">
            <div class="medication-name">{{ $med['name'] ?? $med }}</div>
            @if(isset($med['dosage']))
            <div class="medication-detail">Dose: {{ $med['dosage'] }}</div>
            @endif
            @if(isset($med['frequency']))
            <div class="medication-detail">Frequência: {{ $med['frequency'] }}</div>
            @endif
            @if(isset($med['duration']))
            <div class="medication-detail">Duração: {{ $med['duration'] }}</div>
            @endif
            @if(isset($med['instructions']))
            <div class="medication-detail">Instruções: {{ $med['instructions'] }}</div>
            @endif
        </div>
        @empty
        <div class="value">—</div>
        @endforelse
    </div>

    @if($instructions)
    <div class="section">
        <div class="label">Orientações gerais</div>
        <div class="value">{{ $instructions }}</div>
    </div>
    @endif

    <div class="section">
        <div class="label">Validade</div>
        <div class="value">
            @if($validUntil)
                {{ \Carbon\Carbon::parse($validUntil)->format('d/m/Y') }}
            @else
                30 dias a partir da data de emissão
            @endif
        </div>
    </div>

    <div class="section">
        <div class="label">Médico responsável</div>
        <div class="value">{{ $doctor['name'] ?? '' }} — CRM {{ $doctor['crm'] ?? 'N/A' }}</div>
        <div class="value" style="font-size:11px;color:#6b7280;">Emitido em: {{ $issuedAt }}</div>
    </div>

    <div class="signature">
        <div class="signature-line">{{ $doctor['name'] ?? '' }}</div>
        <div class="signature-label">Assinatura do médico</div>
    </div>

    <div class="footer">
        Receita emitida eletronicamente · Código de verificação: {{ $verificationCode ?? '—' }}
        @if($verificationUrl ?? null)
        · Verificar em: {{ $verificationUrl }}
        @endif
    </div>
</body>
</html>

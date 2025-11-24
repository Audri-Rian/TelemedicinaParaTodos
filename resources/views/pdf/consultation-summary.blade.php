<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resumo de Consulta</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; margin: 32px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 16px; margin: 16px 0 8px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .grid { display: flex; gap: 24px; }
        .grid > div { flex: 1; }
        .label { font-weight: bold; color: #374151; }
        .value { margin-bottom: 4px; }
        ul { margin: 0; padding-left: 18px; }
    </style>
</head>
<body>
    <h1>Resumo de Consulta</h1>
    <p class="value">Gerado em {{ \Carbon\Carbon::parse($appointment['scheduled_at'] ?? now())->format('d/m/Y H:i') }}</p>

    <h2>Paciente</h2>
    <div class="value">{{ $patient['user']['name'] ?? '' }}</div>

    <h2>Médico</h2>
    <div class="value">{{ $doctor['name'] ?? '' }} · CRM {{ $doctor['crm'] ?? 'N/A' }}</div>

    <h2>Informações da consulta</h2>
    <div class="grid">
        <div>
            <div class="label">Status</div>
            <div class="value">{{ $appointment['status'] ?? '—' }}</div>
        </div>
        <div>
            <div class="label">Diagnóstico</div>
            <div class="value">{{ $appointment['diagnosis'] ?? 'Não informado' }}</div>
        </div>
        <div>
            <div class="label">CID-10</div>
            <div class="value">{{ $appointment['cid10'] ?? '—' }}</div>
        </div>
    </div>
    <div class="label">Anotações:</div>
    <div class="value">{{ $appointment['notes'] ?? 'Não registrado' }}</div>

    <h2>Prescrições</h2>
    @if(!empty($appointment['prescriptions']))
        <ul>
            @foreach($appointment['prescriptions'] as $prescription)
                <li>
                    <span class="label">{{ $prescription['doctor']['name'] ?? 'Médico' }}:</span>
                    {{ collect($prescription['medications'] ?? [])->pluck('name')->join(', ') }}
                </li>
            @endforeach
        </ul>
    @else
        <p class="value">Nenhuma prescrição emitida.</p>
    @endif

    <h2>Exames solicitados</h2>
    @if(!empty($appointment['examinations']))
        <ul>
            @foreach($appointment['examinations'] as $exam)
                <li>{{ $exam['name'] }} · {{ $exam['status'] }}</li>
            @endforeach
        </ul>
    @else
        <p class="value">Nenhum exame cadastrado.</p>
    @endif

    <h2>Sinais vitais</h2>
    @if(!empty($appointment['vital_signs']))
        <ul>
            @foreach($appointment['vital_signs'] as $vital)
                <li>
                    {{ \Carbon\Carbon::parse($vital['recorded_at'])->format('d/m/Y H:i') }} ·
                    PA: {{ data_get($vital, 'blood_pressure.systolic') }}/{{ data_get($vital, 'blood_pressure.diastolic') }} mmHg ·
                    FC: {{ $vital['heart_rate'] }} bpm ·
                    Temp: {{ $vital['temperature'] }} °C
                </li>
            @endforeach
        </ul>
    @else
        <p class="value">Sem registros nesta consulta.</p>
    @endif
</body>
</html>



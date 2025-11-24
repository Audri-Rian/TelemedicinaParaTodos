<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Prontuário Médico - {{ $patient['user']['name'] }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1f2933; }
        h1, h2, h3 { color: #0f172a; margin-bottom: 6px; }
        h1 { font-size: 20px; }
        h2 { font-size: 16px; border-bottom: 1px solid #cbd5f5; padding-bottom: 4px; }
        h3 { font-size: 14px; margin-top: 12px; }
        .section { margin-bottom: 18px; page-break-inside: avoid; }
        .card { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px; margin-bottom: 10px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .muted { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
        th { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Prontuário Médico</h1>
    <p class="muted">Gerado em {{ \Carbon\Carbon::parse($generated_at ?? now())->format('d/m/Y H:i') }}</p>

    <div class="section">
        <h2>Dados do Paciente</h2>
        <div class="grid">
            <div><strong>Nome:</strong> {{ $patient['user']['name'] }}</div>
            <div><strong>Gênero:</strong> {{ $patient['gender'] ?? '—' }}</div>
            <div><strong>Data de Nascimento:</strong> {{ $patient['date_of_birth'] ? \Carbon\Carbon::parse($patient['date_of_birth'])->format('d/m/Y') : '—' }}</div>
            <div><strong>Idade:</strong> {{ $patient['age'] ?? '—' }} anos</div>
            <div><strong>Tipo sanguíneo:</strong> {{ $patient['blood_type'] ?? '—' }}</div>
            <div><strong>IMC:</strong> {{ $patient['bmi'] ?? '—' }} ({{ $patient['bmi_category'] ?? '—' }})</div>
        </div>
        <p><strong>Alergias:</strong> {{ $patient['allergies'] ?? 'Não informado' }}</p>
        <p><strong>Medicações atuais:</strong> {{ $patient['current_medications'] ?? 'Não informado' }}</p>
        <p><strong>Histórico Médico:</strong> {{ $patient['medical_history'] ?? 'Não informado' }}</p>
    </div>

    <div class="section">
        <h2>Consultas Realizadas</h2>
        @forelse ($consultations as $consultation)
            <div class="card">
                <strong>{{ \Carbon\Carbon::parse($consultation['scheduled_at'])->format('d/m/Y H:i') }}</strong> ·
                {{ $consultation['doctor']['user']['name'] }}
                <div class="muted">Status: {{ $consultation['status'] }}</div>
                <p><strong>Diagnóstico:</strong> {{ $consultation['diagnosis'] ?? '—' }}</p>
                <p><strong>CID-10:</strong> {{ $consultation['cid10'] ?? '—' }}</p>
                <p><strong>Sintomas:</strong> {{ $consultation['symptoms'] ?? '—' }}</p>
                <p><strong>Exames solicitados:</strong> {{ $consultation['requested_exams'] ?? '—' }}</p>
                <p><strong>Orientações:</strong> {{ $consultation['instructions'] ?? '—' }}</p>
            </div>
        @empty
            <p>Sem consultas registradas.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Prescrições</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Médico</th>
                    <th>Medicações</th>
                    <th>Status</th>
                    <th>Validade</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($prescriptions as $prescription)
                <tr>
                    <td>{{ $prescription['issued_at'] ? \Carbon\Carbon::parse($prescription['issued_at'])->format('d/m/Y') : '—' }}</td>
                    <td>{{ $prescription['doctor']['name'] ?? '—' }}</td>
                    <td>
                        @foreach ($prescription['medications'] as $med)
                            <div>{{ $med['name'] ?? 'Medicamento' }} - {{ $med['dosage'] ?? '' }} {{ $med['frequency'] ?? '' }}</div>
                        @endforeach
                    </td>
                    <td>{{ ucfirst($prescription['status']) }}</td>
                    <td>{{ $prescription['valid_until'] ? \Carbon\Carbon::parse($prescription['valid_until'])->format('d/m/Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sem prescrições registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Exames</h2>
        @forelse ($examinations as $exam)
            <div class="card">
                <strong>{{ $exam['name'] }}</strong> ({{ ucfirst($exam['type']) }})
                <div class="muted">Status: {{ ucfirst($exam['status']) }}</div>
                <p><strong>Solicitado em:</strong> {{ $exam['requested_at'] ? \Carbon\Carbon::parse($exam['requested_at'])->format('d/m/Y') : '—' }}</p>
                <p><strong>Resultado:</strong> {{ $exam['results']['summary'] ?? '—' }}</p>
            </div>
        @empty
            <p>Sem exames registrados.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Documentos</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>Origem</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td>{{ $document['name'] }}</td>
                    <td>{{ ucfirst($document['category']) }}</td>
                    <td>{{ $document['uploaded_at'] ? \Carbon\Carbon::parse($document['uploaded_at'])->format('d/m/Y') : '—' }}</td>
                    <td>{{ $document['uploaded_by']['name'] ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sem documentos anexados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Sinais Vitais Recentes</h2>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>PA</th>
                    <th>FC</th>
                    <th>Temp.</th>
                    <th>SatO₂</th>
                    <th>Peso</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($vital_signs as $vital)
                <tr>
                    <td>{{ $vital['recorded_at'] ? \Carbon\Carbon::parse($vital['recorded_at'])->format('d/m/Y H:i') : '—' }}</td>
                    <td>{{ $vital['blood_pressure']['systolic'] }}/{{ $vital['blood_pressure']['diastolic'] }} mmHg</td>
                    <td>{{ $vital['heart_rate'] }} bpm</td>
                    <td>{{ $vital['temperature'] }} ºC</td>
                    <td>{{ $vital['oxygen_saturation'] }}%</td>
                    <td>{{ $vital['weight'] }} kg</td>
                </tr>
            @empty
                <tr><td colspan="6">Sem registros de sinais vitais.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>



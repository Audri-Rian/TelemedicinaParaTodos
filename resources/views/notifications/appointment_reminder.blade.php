<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lembrete de Consulta</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #f97316;">🔔 Lembrete de Consulta</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Este é um lembrete da sua consulta:</p>
        
        <div style="background: #fed7aa; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Médico:</strong> {{ $metadata['doctor_name'] ?? 'Médico' }}</p>
            <p><strong>Data e Hora:</strong> {{ \Carbon\Carbon::parse($metadata['scheduled_at'])->format('d/m/Y H:i') }}</p>
            @if(isset($metadata['time_until']))
            <p><strong>Faltam:</strong> {{ $metadata['time_until'] }}</p>
            @endif
        </div>
        
        <p>Por favor, esteja disponível no horário agendado.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta Agendada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb;">✅ Consulta Agendada</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Sua consulta foi agendada com sucesso:</p>
        
        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Médico:</strong> {{ $metadata['doctor_name'] ?? 'Médico' }}</p>
            <p><strong>Data e Hora:</strong> {{ \Carbon\Carbon::parse($metadata['scheduled_at'])->format('d/m/Y H:i') }}</p>
        </div>
        
        <p>Lembre-se de estar disponível no horário agendado.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



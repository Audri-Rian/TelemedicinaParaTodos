<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta Reagendada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #f59e0b;">🔄 Consulta Reagendada</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Sua consulta foi reagendada:</p>
        
        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Médico:</strong> {{ $metadata['doctor_name'] ?? 'Médico' }}</p>
            <p><strong>Data Anterior:</strong> {{ \Carbon\Carbon::parse($metadata['old_scheduled_at'])->format('d/m/Y H:i') }}</p>
            <p><strong>Nova Data:</strong> {{ \Carbon\Carbon::parse($metadata['new_scheduled_at'])->format('d/m/Y H:i') }}</p>
        </div>
        
        <p>Por favor, anote a nova data e hora da sua consulta.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



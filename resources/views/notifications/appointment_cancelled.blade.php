<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta Cancelada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #dc2626;">❌ Consulta Cancelada</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Informamos que sua consulta foi cancelada:</p>
        
        <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Médico:</strong> {{ $metadata['doctor_name'] ?? 'Médico' }}</p>
            <p><strong>Data e Hora:</strong> {{ \Carbon\Carbon::parse($metadata['scheduled_at'])->format('d/m/Y H:i') }}</p>
            @if(isset($metadata['reason']))
            <p><strong>Motivo:</strong> {{ $metadata['reason'] }}</p>
            @endif
        </div>
        
        <p>Se desejar, você pode agendar uma nova consulta através da plataforma.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exame Solicitado</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #8b5cf6;">🔬 Exame Solicitado</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Dr(a). {{ $metadata['doctor_name'] ?? 'Médico' }} solicitou um exame para você:</p>
        
        <div style="background: #ede9fe; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Exame:</strong> {{ $metadata['examination_name'] ?? 'Exame' }}</p>
            <p><strong>Tipo:</strong> {{ $metadata['examination_type'] ?? 'N/A' }}</p>
        </div>
        
        <p>Você pode visualizar os detalhes do exame na plataforma.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



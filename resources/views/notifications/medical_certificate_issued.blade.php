<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atestado Emitido</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #6366f1;">📄 Atestado Emitido</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Dr(a). {{ $metadata['doctor_name'] ?? 'Médico' }} emitiu um atestado médico para você.</p>
        
        <div style="background: #e0e7ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Código de Verificação:</strong> {{ $metadata['verification_code'] ?? 'N/A' }}</p>
            <p>Você pode visualizar e baixar o atestado na plataforma.</p>
        </div>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



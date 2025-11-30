<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prescrição Emitida</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #10b981;">💊 Prescrição Emitida</h2>
        
        <p>Olá, {{ $user->name }}!</p>
        
        <p>Dr(a). {{ $metadata['doctor_name'] ?? 'Médico' }} emitiu uma nova prescrição para você.</p>
        
        <div style="background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p>Você pode visualizar a prescrição completa na plataforma.</p>
        </div>
        
        <p>Lembre-se de seguir as orientações médicas e tomar os medicamentos conforme prescrito.</p>
        
        <p>Atenciosamente,<br>Equipe Telemedicina para Todos</p>
    </div>
</body>
</html>



<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Falha de Integração</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 640px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #b91c1c;">Falha de integração</h2>

        <p>Uma integração externa falhou e precisa de acompanhamento operacional.</p>

        <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Parceiro:</strong> {{ $partner->name }}</p>
            <p><strong>Slug:</strong> {{ $partner->slug }}</p>
            <p><strong>Partner ID:</strong> {{ $partner->id }}</p>
            <p><strong>Event ID:</strong> {{ $integrationEvent->id }}</p>
            <p><strong>Tipo:</strong> {{ $integrationEvent->event_type }}</p>
            <p><strong>Horário:</strong> {{ optional($integrationEvent->created_at)->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</p>
        </div>

        <p><strong>Erro sanitizado:</strong></p>
        <p style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px;">
            {{ $sanitizedError }}
        </p>

        <p>Este alerta evita dados clínicos completos e referencia somente identificadores operacionais.</p>
    </div>
</body>
</html>

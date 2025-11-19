# Timezone da Aplicação

## Contexto
- O fluxo de agenda e disponibilidade do médico depende de comparações de horário (slots passados vs. futuros).
- A aplicação estava configurada em `UTC`, enquanto a operação real acontece no horário oficial brasileiro (BRT/BRTS).
- Consequência: consultas ainda não iniciadas eram marcadas como “Expiradas” porque o servidor (UTC) considerava o horário já no passado.

## Decisão
- Ajustamos `config/app.php` para usar `America/Sao_Paulo`, com fallback configurável via `APP_TIMEZONE`.
- Todos os serviços de data/hora (Carbon, validações de disponibilidade, logs) passam a operar no mesmo fuso horário que os médicos.
- **Importante**: essa configuração vale apenas para o MVP. Caso o produto escale para outros países ou times distribuídos, retorne o timezone padrão para `UTC` e trate as conversões por médico/usuário.

## Impactos
- Horários exibidos e comparações de `Carbon::now()` ficam consistentes com a percepção do usuário.
- Logs/Jobs agendados passam a registrar horário local; se alguma integração exigir UTC, converter explicitamente (`->copy()->setTimezone('UTC')`).
- Ambientes fora do Brasil que precisarem outro fuso devem sobrescrever `APP_TIMEZONE` no `.env`.


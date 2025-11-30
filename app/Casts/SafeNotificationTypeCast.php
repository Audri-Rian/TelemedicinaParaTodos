<?php

namespace App\Casts;

use App\Enums\NotificationType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class SafeNotificationTypeCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return NotificationType
     */
    public function get(Model $model, string $key, $value, array $attributes): NotificationType
    {
        try {
            // Se já for uma instância do enum, retornar diretamente
            if ($value instanceof NotificationType) {
                return $value;
            }
            
            // Se for null ou vazio, retornar padrão
            if ($value === null || $value === '') {
                return NotificationType::APPOINTMENT_CREATED;
            }
            
            // Tentar obter o valor do array de atributos se necessário
            if (!is_string($value) && isset($attributes[$key])) {
                $value = $attributes[$key];
            }
            
            // Se for string e válido, fazer o cast
            if (is_string($value)) {
                if (NotificationType::isValid($value)) {
                    return NotificationType::from($value);
                }
                
                // Se o valor não for válido, logar e retornar padrão
                \Log::warning('Tipo de notificação inválido encontrado', [
                    'notification_id' => $model->id ?? null,
                    'type_value' => $value,
                ]);
                return NotificationType::APPOINTMENT_CREATED;
            }
            
            // Se chegou aqui, o valor não é do tipo esperado
            return NotificationType::APPOINTMENT_CREATED;
        } catch (\ValueError $e) {
            // Erro específico do enum (valor inválido)
            \Log::warning('Valor inválido para NotificationType: ' . $e->getMessage(), [
                'notification_id' => $model->id ?? null,
                'type_value' => $value ?? 'null',
            ]);
            return NotificationType::APPOINTMENT_CREATED;
        } catch (\Throwable $e) {
            // Qualquer outro erro
            \Log::error('Erro ao fazer cast do tipo de notificação: ' . $e->getMessage(), [
                'notification_id' => $model->id ?? null,
                'type_value' => $value ?? 'null',
                'trace' => $e->getTraceAsString(),
            ]);
            return NotificationType::APPOINTMENT_CREATED;
        }
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  NotificationType|string  $value
     * @param  array  $attributes
     * @return string
     */
    public function set(Model $model, string $key, $value, array $attributes): string
    {
        if ($value instanceof NotificationType) {
            return $value->value;
        }
        
        if (is_string($value) && NotificationType::isValid($value)) {
            return $value;
        }
        
        // Se o valor não for válido, retornar um padrão
        return NotificationType::APPOINTMENT_CREATED->value;
    }
}


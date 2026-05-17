<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TolerantEncryptedCast implements CastsAttributes
{
    /**
     * @param  mixed  $value
     */
    public function get(Model $model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $exception) {
            if (preg_match('/^\d+$/', $value) === 1) {
                Log::warning('Campo sensível em plaintext legado detectado.', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'field' => $key,
                ]);

                return $value;
            }

            Log::error('Falha ao descriptografar campo sensível.', [
                'model' => $model::class,
                'model_id' => $model->getKey(),
                'field' => $key,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  mixed  $value
     */
    public function set(Model $model, string $key, $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}

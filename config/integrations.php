<?php

/**
 * Configuração da camada de Interoperabilidade
 *
 * Parâmetros para integrações com parceiros externos (laboratórios, farmácias,
 * hospitais, convênios) e com a RNDS.
 *
 * Documentação de origem:
 * - execute/MVP1.md
 * - execute/ResilienciaOperacional.md
 * - execute/SegurancaAPIPublica.md
 * - execute/PadroesRegulatorios.md
 * - docs/interoperabilidade/Arquitetura.md
 */

return [
    /*
    |--------------------------------------------------------------------------
    | FHIR Configuration
    |--------------------------------------------------------------------------
    |
    | Configuração do padrão FHIR R4 usado como lingua franca da camada
    | de interoperabilidade.
    |
    */

    'fhir' => [
        'version' => 'R4',
        'system_url' => env('FHIR_SYSTEM_URL', 'https://telemedicina.example.com/fhir'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RNDS Configuration
    |--------------------------------------------------------------------------
    |
    | Rede Nacional de Dados em Saúde — barramento federal obrigatório.
    | Autenticação via certificado e-CNPJ + OAuth2 Client Credentials.
    |
    */

    'rnds' => [
        'enabled' => env('RNDS_ENABLED', false),
        'environment' => env('RNDS_ENVIRONMENT', 'homologation'), // homologation, production
        'base_url' => env('RNDS_BASE_URL', 'https://ehr-services-hmg.saude.gov.br/api'),
        'auth_url' => env('RNDS_AUTH_URL', 'https://ehr-auth-hmg.saude.gov.br/api'),
        'cnes' => env('RNDS_CNES'),
        'certificate_path' => env('RNDS_CERTIFICATE_PATH'),
        'certificate_password' => env('RNDS_CERTIFICATE_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeouts (por tipo de parceiro)
    |--------------------------------------------------------------------------
    |
    | Timeouts de conexão e resposta em segundos.
    | Referência: execute/ResilienciaOperacional.md
    |
    */

    'timeouts' => [
        'laboratory' => [
            'connect' => (int) env('INTEGRATION_LAB_CONNECT_TIMEOUT', 5),
            'response' => (int) env('INTEGRATION_LAB_RESPONSE_TIMEOUT', 15),
        ],
        'pharmacy' => [
            'connect' => (int) env('INTEGRATION_PHARMACY_CONNECT_TIMEOUT', 3),
            'response' => (int) env('INTEGRATION_PHARMACY_RESPONSE_TIMEOUT', 10),
        ],
        'hospital' => [
            'connect' => (int) env('INTEGRATION_HOSPITAL_CONNECT_TIMEOUT', 5),
            'response' => (int) env('INTEGRATION_HOSPITAL_RESPONSE_TIMEOUT', 15),
        ],
        'insurance' => [
            'connect' => (int) env('INTEGRATION_INSURANCE_CONNECT_TIMEOUT', 5),
            'response' => (int) env('INTEGRATION_INSURANCE_RESPONSE_TIMEOUT', 15),
        ],
        'rnds' => [
            'connect' => (int) env('INTEGRATION_RNDS_CONNECT_TIMEOUT', 10),
            'response' => (int) env('INTEGRATION_RNDS_RESPONSE_TIMEOUT', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Configuração do circuit breaker por tipo de parceiro.
    | Estado armazenado em Redis. Referência: execute/ResilienciaOperacional.md
    |
    */

    'circuit_breaker' => [
        'laboratory' => [
            'failure_threshold' => (int) env('CB_LAB_FAILURES', 5),
            'cooling_timeout' => (int) env('CB_LAB_COOLING', 60),
            'half_open_attempts' => 1,
        ],
        'pharmacy' => [
            'failure_threshold' => (int) env('CB_PHARMACY_FAILURES', 3),
            'cooling_timeout' => (int) env('CB_PHARMACY_COOLING', 30),
            'half_open_attempts' => 1,
        ],
        'hospital' => [
            'failure_threshold' => (int) env('CB_HOSPITAL_FAILURES', 5),
            'cooling_timeout' => (int) env('CB_HOSPITAL_COOLING', 60),
            'half_open_attempts' => 1,
        ],
        'rnds' => [
            'failure_threshold' => (int) env('CB_RNDS_FAILURES', 10),
            'cooling_timeout' => (int) env('CB_RNDS_COOLING', 120),
            'half_open_attempts' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Backoff exponencial com jitter para operações falhadas.
    | Fórmula: min(base_delay * 2^(attempt-1), max_delay) + random(0, jitter)
    |
    */

    'retry' => [
        'send_exam_order' => [
            'max_attempts' => (int) env('RETRY_EXAM_ORDER_MAX', 5),
            'base_delay' => 30,
            'max_delay' => 7200,
            'jitter' => 10,
        ],
        'fetch_exam_result' => [
            'max_attempts' => (int) env('RETRY_EXAM_RESULT_MAX', 3),
            'base_delay' => 60,
            'max_delay' => 1800,
            'jitter' => 10,
        ],
        'send_prescription' => [
            'max_attempts' => (int) env('RETRY_PRESCRIPTION_MAX', 5),
            'base_delay' => 30,
            'max_delay' => 7200,
            'jitter' => 10,
        ],
        'submit_rnds' => [
            'max_attempts' => (int) env('RETRY_RNDS_MAX', 10),
            'base_delay' => 60,
            'max_delay' => 21600,
            'jitter' => 15,
        ],
        'webhook_outbound' => [
            'max_attempts' => (int) env('RETRY_WEBHOOK_MAX', 5),
            'base_delay' => 10,
            'max_delay' => 3600,
            'jitter' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (API pública)
    |--------------------------------------------------------------------------
    |
    | Limites de requisições por tipo de parceiro.
    | Referência: execute/SegurancaAPIPublica.md
    |
    */

    'rate_limits' => [
        'laboratory' => [
            'per_minute' => (int) env('RATE_LIMIT_LAB_PER_MINUTE', 60),
            'per_hour' => (int) env('RATE_LIMIT_LAB_PER_HOUR', 1000),
            'per_day' => (int) env('RATE_LIMIT_LAB_PER_DAY', 10000),
        ],
        'pharmacy' => [
            'per_minute' => (int) env('RATE_LIMIT_PHARMACY_PER_MINUTE', 30),
            'per_hour' => (int) env('RATE_LIMIT_PHARMACY_PER_HOUR', 500),
            'per_day' => (int) env('RATE_LIMIT_PHARMACY_PER_DAY', 5000),
        ],
        'hospital' => [
            'per_minute' => (int) env('RATE_LIMIT_HOSPITAL_PER_MINUTE', 120),
            'per_hour' => (int) env('RATE_LIMIT_HOSPITAL_PER_HOUR', 2000),
            'per_day' => (int) env('RATE_LIMIT_HOSPITAL_PER_DAY', 20000),
        ],
        'insurance' => [
            'per_minute' => (int) env('RATE_LIMIT_INSURANCE_PER_MINUTE', 60),
            'per_hour' => (int) env('RATE_LIMIT_INSURANCE_PER_HOUR', 1000),
            'per_day' => (int) env('RATE_LIMIT_INSURANCE_PER_DAY', 10000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Catalog
    |--------------------------------------------------------------------------
    |
    | Lista de parceiros disponíveis para conexão pelo wizard Connect.
    | Adicionar novos parceiros aqui para que apareçam no frontend sem deploy.
    |
    */

    'partner_catalog' => [
        [
            'key' => 'hermes-pardini',
            'name' => 'Hermes Pardini',
            'description' => 'Líder em medicina diagnóstica e preventiva no Brasil.',
            'type' => 'laboratory',
        ],
        [
            'key' => 'fleury',
            'name' => 'Fleury',
            'description' => 'Excelência médica e técnica em análises clínicas.',
            'type' => 'laboratory',
        ],
        [
            'key' => 'a-plus-medicina',
            'name' => 'A+ Medicina',
            'description' => 'Atendimento humanizado e resultados precisos.',
            'type' => 'laboratory',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Schedule
    |--------------------------------------------------------------------------
    |
    | Frequência do cron de sincronização automática (pull de resultados).
    |
    */

    'sync' => [
        'exam_results_cron' => env('SYNC_EXAM_RESULTS_CRON', '*/15 * * * *'),
        'retry_queue_cron' => env('SYNC_RETRY_QUEUE_CRON', '*/5 * * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Configuração de segurança para webhooks recebidos.
    |
    */

    'webhook' => [
        'signature_header' => 'X-Webhook-Signature',
        'timestamp_header' => 'X-Webhook-Timestamp',
        'timestamp_tolerance_seconds' => (int) env('WEBHOOK_TIMESTAMP_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Nome da fila dedicada para jobs de integração.
    | Usa RabbitMQ separado dos jobs internos.
    |
    */

    'queue' => [
        'connection' => env('INTEGRATION_QUEUE_CONNECTION', 'rabbitmq'),
        'name' => env('INTEGRATION_QUEUE_NAME', 'integrations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerts
    |--------------------------------------------------------------------------
    |
    | Alertas operacionais para falhas de integração externa.
    |
    */

    'alerts' => [
        'emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('INTEGRATION_ALERT_EMAILS', ''))
        ))),
        'failure_throttle_seconds' => (int) env('INTEGRATION_FAILURE_ALERT_THROTTLE_SECONDS', 900),
        'error_excerpt_length' => (int) env('INTEGRATION_FAILURE_ERROR_EXCERPT_LENGTH', 300),
    ],
];

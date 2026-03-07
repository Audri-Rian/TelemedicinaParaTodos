<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Telemedicina para Todos API',
    version: '1.0.0',
    description: 'Documentação da API do projeto Telemedicina para Todos. Endpoints internos (sessão) e preparação para API pública de interoperabilidade.'
)]
#[OA\Server(url: '/', description: 'Servidor da aplicação (base atual)')]
#[OA\Server(url: '/api/v1', description: 'API pública versionada (futuro – interoperabilidade)')]
#[OA\Tag(name: 'Especializações', description: 'Listagem e opções de especializações médicas')]
#[OA\Tag(name: 'Disponibilidade', description: 'Disponibilidade de médicos por data')]
#[OA\Tag(name: 'Agendamentos', description: 'Disponibilidade para agendamento')]
#[OA\Tag(name: 'Timeline', description: 'Eventos de timeline (educação, cursos, certificados)')]
#[OA\Tag(name: 'Mensagens', description: 'Conversas e mensagens entre usuários')]
#[OA\Tag(name: 'Notificações', description: 'Notificações do usuário')]
#[OA\Tag(name: 'Avatar', description: 'Upload e gestão de avatar')]
#[OA\Tag(name: 'API pública', description: 'Endpoints de interoperabilidade (futuro)')]
class OpenApiSpec
{
}

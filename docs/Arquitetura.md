# Arquitetura do Sistema - Telemedicina Para Todos

## Visão Geral

Este documento descreve a arquitetura e estruturação do projeto **Telemedicina Para Todos**, um sistema de telemedicina que atende dois tipos de usuários distintos: **Médicos (Doctors)** e **Pacientes (Patients)**.

## Contexto do Projeto

O sistema é dividido em dois tipos de usuários com funcionalidades, páginas e estruturas distintas:

- **Doctors**: Interface e funcionalidades específicas para médicos
- **Patients**: Interface e funcionalidades específicas para pacientes
- **Shared**: Componentes, páginas e estruturas compartilhadas entre ambos os tipos de usuário

## Arquitetura de Comunicação Padrão

O sistema segue uma arquitetura em camadas bem definida:

```
[Migrations] → definem a estrutura do banco de dados
         ↘
[Eloquent Models] → schema, relacionamentos, casts, scopes, accessors
         ↘
[DTOs] ↔ (entrada/saída entre Controller e Service)
         ↘
[Services] → contém lógica de negócio, orquestra repositórios/modelos
         ↘
[Repositories] (opcional, evite usar) → abstração de acesso aos dados
         ↘
[Database / APIs externas]
```

### Responsabilidades das Camadas

#### Controllers
- Recebem as requisições HTTP
- Constroem DTOs para entrada/saída
- Interagem com Services
- Retornam respostas adequadas

#### DTOs (Data Transfer Objects)
- Encapsulam dados de forma clara e segura entre as camadas
- Garantem tipagem e validação de dados
- Facilitam a comunicação entre Controller e Service

#### Services
- Contêm a lógica de negócio principal
- Agregam regras de negócio
- Coordenam fluxos complexos
- Utilizam repositórios ou modelos diretamente

#### Repositories (Opcional)
- Lidam com persistência de dados
- Abstraem queries complexas
- Fornecem abstração de fontes de dados
- **Nota**: Evite usar quando não necessário

## Estrutura do Backend

### Organização por Domínio
O backend foi estruturado seguindo uma abordagem **DDD Light**, organizando as responsabilidades por domínio dentro das pastas padrão do Laravel:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DoctorControllers/     # Controllers específicos para médicos
│   │   ├── PatientControllers/    # Controllers específicos para pacientes
│   │   └── SharedControllers/     # Controllers compartilhados
│   ├── Middleware/
│   └── Requests/
├── Models/
│   ├── Doctor.php
│   ├── Patient.php
│   └── User.php
└── Services/
    ├── DoctorServices/
    ├── PatientServices/
    └── SharedServices/
```

### Padrões de Código

- **PSR-12**: Seguir padrões de codificação PSR-12
- **Nomenclatura**: Usar inglês consistente em todo o projeto
- **Migrations**: Sempre incluir timestamps
- **Testes**: Todo método crítico deve ter teste unitário

## Estrutura do Frontend

### Monorepo com SPAs Múltiplas
O frontend segue uma estrutura de **Monorepo** com duas **SPAs (Single Page Applications)**:

```
resources/js/
├── components/
│   ├── doctor/           # Componentes específicos para médicos
│   ├── patient/          # Componentes específicos para pacientes
│   └── shared/           # Componentes compartilhados
├── pages/
│   ├── doctor/           # Páginas específicas para médicos
│   ├── patient/          # Páginas específicas para pacientes
│   └── shared/           # Páginas compartilhadas
├── layouts/
│   ├── doctor/           # Layouts específicos para médicos
│   ├── patient/          # Layouts específicos para pacientes
│   └── shared/           # Layouts compartilhados
└── wayfinder/            # Sistema de navegação
```

### Compartilhamento de Recursos
- **UI Package**: Componentes de interface compartilhados
- **SDK**: Biblioteca de comunicação com APIs
- **Layouts**: Templates base compartilhados
- **Utilitários**: Funções e helpers comuns


## Fluxo de Desenvolvimento

### 1. Migrations
Definir estrutura do banco de dados com migrations do Laravel

### 2. Models
Criar modelos Eloquent com relacionamentos, casts, scopes e accessors

### 3. DTOs
Definir objetos de transferência de dados para entrada/saída

### 4. Services
Implementar lógica de negócio nos services

### 5. Controllers
Criar controllers que utilizam services e DTOs

### 6. Frontend
Desenvolver componentes Vue.js e páginas correspondentes

### 7. Testes
Implementar testes unitários para métodos críticos

## Convenções de Nomenclatura

### Backend
- **Controllers**: `DoctorController`, `PatientController`
- **Services**: `DoctorService`, `PatientService`
- **Models**: `Doctor`, `Patient`, `User`
- **DTOs**: `CreateDoctorDTO`, `UpdatePatientDTO`

### Frontend
- **Components**: `DoctorDashboard.vue`, `PatientProfile.vue`
- **Pages**: `doctor/Dashboard.vue`, `patient/Profile.vue`
- **Layouts**: `DoctorLayout.vue`, `PatientLayout.vue`

*Este documento deve ser atualizado conforme a evolução do projeto.*


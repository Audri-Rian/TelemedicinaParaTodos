# Guia de Transição: MySQL → PostgreSQL

Este documento fornece instruções completas para migrar o projeto **TelemedicinaParaTodos** de MySQL para PostgreSQL.

## 📋 Índice

1. [Pré-requisitos](#pré-requisitos)
2. [Instalação e Configuração do PostgreSQL](#instalação-e-configuração-do-postgresql)
3. [Alterações Necessárias no Código](#alterações-necessárias-no-código)
4. [Migração de Dados](#migração-de-dados)
5. [Configuração do Ambiente](#configuração-do-ambiente)
6. [Checklist de Testes](#checklist-de-testes)
7. [Diferenças Importantes MySQL vs PostgreSQL](#diferenças-importantes-mysql-vs-postgresql)
8. [Troubleshooting](#troubleshooting)

---

## 🔧 Pré-requisitos

### Software Necessário

- **PostgreSQL 12+** instalado e rodando
- **PHP 8.2+** com extensão `pdo_pgsql` habilitada
- **Composer** atualizado
- Acesso ao banco MySQL atual (para exportação de dados, se necessário)

### Verificar Extensão PHP

```bash
php -m | grep pdo_pgsql
```

Se não estiver instalada:

**Windows:**
- Edite `php.ini` e descomente: `extension=pdo_pgsql`
- Reinicie o servidor web

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install php-pgsql
sudo systemctl restart php8.2-fpm  # ou sua versão do PHP
```

**macOS:**
```bash
brew install php-pgsql
```

---

## 🗄️ Instalação e Configuração do PostgreSQL

### 1. Instalar PostgreSQL

**Windows:**
- Baixe do site oficial: https://www.postgresql.org/download/windows/
- Durante instalação, anote a senha do usuário `postgres`

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

**macOS:**
```bash
brew install postgresql@14
brew services start postgresql@14
```

### 2. Criar Banco de Dados e Usuário

```bash
# Acessar PostgreSQL como superusuário
sudo -u postgres psql  # Linux
# ou
psql -U postgres       # Windows/macOS

# Criar banco de dados
CREATE DATABASE telemedicina_para_todos;

# Criar usuário (substitua 'senha_segura' por uma senha forte)
CREATE USER telemedicina_user WITH PASSWORD 'senha_segura';

# Conceder privilégios
GRANT ALL PRIVILEGES ON DATABASE telemedicina_para_todos TO telemedicina_user;

# Conectar ao banco e conceder privilégios no schema
\c telemedicina_para_todos
GRANT ALL ON SCHEMA public TO telemedicina_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO telemedicina_user;

# Sair
\q
```

### 3. Instalar Extensão UUID (se necessário)

```bash
psql -U telemedicina_user -d telemedicina_para_todos

# Criar extensão para UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

\q
```

---

## 🔨 Alterações Necessárias no Código

### 1. Corrigir Query com DATE_ADD (CRÍTICO)

**Arquivo:** `app/Services/AppointmentService.php`

**Localização:** Linha 338

**Problema:** A função `DATE_ADD` é específica do MySQL e não funciona no PostgreSQL.

**Solução:** Substituir por sintaxe compatível com PostgreSQL ou usar cálculo no PHP.

#### Opção 1: Usar sintaxe PostgreSQL (Recomendado)

```php
// ANTES (MySQL):
->whereRaw('DATE_ADD(scheduled_at, INTERVAL ? MINUTE) > ?', [
    $duration,
    $startTime->toDateTimeString()
]);

// DEPOIS (PostgreSQL):
->whereRaw("scheduled_at + INTERVAL '{$duration} minutes' > ?", [
    $startTime->toDateTimeString()
]);
```

#### Opção 2: Solução Portável (Funciona em ambos)

```php
// Calcular no PHP usando Carbon
$appointmentEndTime = $appointment->scheduled_at->copy()->addMinutes($duration);
$q2->where('scheduled_at', '<=', $startTime)
   ->where('scheduled_at', '>', $startTime->copy()->subMinutes($duration));
```

**⚠️ IMPORTANTE:** A Opção 2 é mais segura e funciona em ambos os bancos, mas requer refatoração da lógica.

### 2. Verificar ENUMs (Opcional)

O Laravel trata ENUMs de forma diferente no PostgreSQL. Se você encontrar problemas, considere:

- **Opção A:** Manter como está (Laravel abstrai bem)
- **Opção B:** Converter para CHECK constraints (mais robusto no PostgreSQL)

Se optar pela Opção B, crie uma migration:

```php
// database/migrations/XXXX_XX_XX_convert_enums_to_check_constraints.php
public function up()
{
    // Exemplo para tabela doctors
    DB::statement("ALTER TABLE doctors DROP CONSTRAINT IF EXISTS doctors_status_check");
    DB::statement("ALTER TABLE doctors ADD CONSTRAINT doctors_status_check 
                   CHECK (status IN ('active', 'inactive', 'suspended'))");
    
    // Repetir para outras tabelas com ENUMs
}
```

**Nota:** Na maioria dos casos, não é necessário fazer isso. O Laravel gerencia bem.

### 3. Verificar Charset e Collation

PostgreSQL usa `utf8` por padrão (não `utf8mb4`). A configuração em `config/database.php` já está correta:

```php
'charset' => env('DB_CHARSET', 'utf8'),
```

---

## 📦 Migração de Dados

### Opção 1: Usar Migrations do Laravel (Recomendado)

Se você está em desenvolvimento e pode recriar os dados:

```bash
# 1. Configurar .env para PostgreSQL
# 2. Executar migrations
php artisan migrate:fresh --seed
```

### Opção 2: Migrar Dados Existentes

Se você tem dados em produção que precisam ser preservados:

#### Passo 1: Exportar do MySQL

```bash
# Exportar estrutura
mysqldump -u root -p --no-data telemedicina_para_todos > structure.sql

# Exportar dados
mysqldump -u root -p --no-create-info telemedicina_para_todos > data.sql
```

#### Passo 2: Converter para PostgreSQL

Use ferramentas como:
- **pgloader** (recomendado): https://github.com/dimitri/pgloader
- **MySQL Workbench** (exportar como SQL e converter manualmente)

**Exemplo com pgloader:**

```bash
# Instalar pgloader
# Ubuntu/Debian:
sudo apt install pgloader

# macOS:
brew install pgloader

# Migrar
pgloader mysql://usuario:senha@localhost/telemedicina_para_todos \
         postgresql://telemedicina_user:senha@localhost/telemedicina_para_todos
```

#### Passo 3: Ajustar Dados Importados

Após importar, execute ajustes:

```sql
-- Conectar ao PostgreSQL
psql -U telemedicina_user -d telemedicina_para_todos

-- Verificar e ajustar tipos de dados se necessário
-- Converter ENUMs se necessário
-- Verificar foreign keys
```

---

## ⚙️ Configuração do Ambiente

### 1. Atualizar arquivo `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=telemedicina_para_todos
DB_USERNAME=telemedicina_user
DB_PASSWORD=sua_senha_aqui
DB_CHARSET=utf8
```

### 2. Limpar Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Testar Conexão

```bash
php artisan tinker

# No tinker:
DB::connection()->getPdo();
# Deve retornar: PDO object sem erros
```

---

## ✅ Checklist de Testes

Execute todos os testes abaixo para garantir que a migração foi bem-sucedida.

### 🔐 Autenticação e Autorização

- [ ] **Registro de Usuário (Paciente)**
  - Criar novo paciente
  - Verificar se dados são salvos corretamente
  - Verificar validações de campos obrigatórios

- [ ] **Registro de Usuário (Médico)**
  - Criar novo médico
  - Verificar relacionamento com User
  - Verificar campos específicos (CRM, license_number)

- [ ] **Login**
  - Login com credenciais válidas
  - Login com credenciais inválidas
  - Verificar sessão após login

- [ ] **Autorização**
  - Médico acessa apenas seus próprios appointments
  - Paciente acessa apenas seus próprios appointments
  - Verificar políticas de acesso

### 👥 Gestão de Usuários

- [ ] **Listagem de Médicos**
  - Listar todos os médicos
  - Filtrar por especialização
  - Filtrar por status (active/inactive/suspended)
  - Buscar por nome/CRM

- [ ] **Listagem de Pacientes**
  - Listar pacientes
  - Filtrar por status
  - Buscar pacientes

- [ ] **Perfis de Usuário**
  - Visualizar perfil próprio
  - Editar perfil próprio
  - Upload de avatar

### 📅 Appointments (Consultas)

- [ ] **Criar Appointment**
  - Criar novo appointment
  - Validar conflito de horário (testar a query corrigida)
  - Verificar se access_code é gerado
  - Verificar validação de paciente completo

- [ ] **Listar Appointments**
  - Listar appointments do médico
  - Listar appointments do paciente
  - Filtrar por status
  - Filtrar por data (date_from, date_to)
  - Filtrar upcoming/past
  - Ordenação por scheduled_at

- [ ] **Validar Conflitos de Horário** ⚠️ **CRÍTICO**
  - Tentar criar appointment em horário ocupado
  - Verificar se a query `validateNoConflict` funciona corretamente
  - Testar edge cases:
    - Appointment que começa durante outro
    - Appointment que termina durante outro
    - Appointment que engloba outro completamente

- [ ] **Atualizar Appointment**
  - Atualizar notes
  - Reagendar (reschedule)
  - Validar conflito ao reagendar

- [ ] **Transições de Status**
  - Iniciar appointment (scheduled → in_progress)
  - Finalizar appointment (in_progress → completed)
  - Cancelar appointment
  - Marcar como no-show
  - Reagendar appointment

- [ ] **Validações de Regras de Negócio**
  - `canBeStarted()` - verificar lead_minutes
  - `canBeCancelled()` - verificar cancel_before_hours
  - `isUpcoming()` - verificar lógica de datas
  - `isPast()` - verificar lógica de datas

### 📊 Relacionamentos e Queries Complexas

- [ ] **Relacionamentos Eloquent**
  - `Appointment->doctor` - carregar médico
  - `Appointment->patient` - carregar paciente
  - `Appointment->logs` - carregar logs
  - `Doctor->specializations` - carregar especializações
  - `Specialization->doctors` - carregar médicos

- [ ] **Eager Loading**
  - `with(['doctor.user', 'patient.user'])` - verificar performance
  - `withCount('doctors')` - verificar contagens

- [ ] **Scopes**
  - `scopeActive()` - filtrar médicos/pacientes ativos
  - `scopeScheduled()` - filtrar appointments agendados
  - `scopeUpcoming()` - filtrar appointments futuros
  - `scopePast()` - filtrar appointments passados
  - `scopeByDateRange()` - filtrar por intervalo de datas

### 📝 Logs e Auditoria

- [ ] **Appointment Logs**
  - Criar log ao criar appointment
  - Criar log ao iniciar appointment
  - Criar log ao finalizar appointment
  - Criar log ao cancelar appointment
  - Criar log ao reagendar appointment
  - Verificar se payload JSON é salvo corretamente

### 🔍 Queries e Filtros

- [ ] **Busca e Filtros**
  - Buscar especializações por nome
  - Filtrar médicos por especialização
  - Filtrar appointments com múltiplos critérios
  - Verificar performance de queries com índices

- [ ] **Queries com JSON**
  - Salvar `metadata` JSON em appointments
  - Salvar `availability_schedule` JSON em doctors
  - Consultar campos JSON (se houver queries)

### 🗑️ Soft Deletes

- [ ] **Soft Delete**
  - Deletar appointment (soft delete)
  - Verificar se `deleted_at` é preenchido
  - Verificar se registros deletados não aparecem em listagens
  - Restaurar registro deletado

### 📈 Performance

- [ ] **Índices**
  - Verificar se índices foram criados corretamente
  - Testar queries que usam índices
  - Verificar EXPLAIN ANALYZE em queries complexas

- [ ] **Queries N+1**
  - Verificar se eager loading está funcionando
  - Monitorar número de queries executadas

### 🧪 Testes Automatizados

- [ ] **Executar Test Suite**
  ```bash
  php artisan test
  ```

- [ ] **Testes Específicos**
  - Executar testes de Feature
  - Executar testes de Unit
  - Verificar cobertura de testes críticos

### 🔄 Operações CRUD Completas

Para cada entidade principal, testar:

- [ ] **Users**
  - Create, Read, Update, Delete
  - Validações
  - Relacionamentos

- [ ] **Doctors**
  - Create, Read, Update, Delete
  - Relacionamento com Specializations
  - Validações de CRM, license_number

- [ ] **Patients**
  - Create, Read, Update, Delete
  - Validações de campos obrigatórios
  - Validação de segunda etapa (emergency_contact)

- [ ] **Appointments**
  - Create, Read, Update, Delete
  - Todas as transições de status
  - Validações de conflito

- [ ] **Specializations**
  - Create, Read, Update, Delete
  - Relacionamento com Doctors

- [ ] **AppointmentLogs**
  - Create, Read
  - Verificar eventos registrados

---

## 🔍 Diferenças Importantes MySQL vs PostgreSQL

### 1. Tipos de Dados

| MySQL | PostgreSQL | Notas |
|-------|------------|-------|
| `VARCHAR(n)` | `VARCHAR(n)` | Compatível |
| `TEXT` | `TEXT` | Compatível |
| `INT` | `INTEGER` | Laravel abstrai |
| `BIGINT` | `BIGINT` | Compatível |
| `DECIMAL(p,s)` | `DECIMAL(p,s)` | Compatível |
| `DATETIME` | `TIMESTAMP` | Laravel abstrai |
| `ENUM` | `ENUM` ou `CHECK` | Laravel gerencia |
| `JSON` | `JSONB` | Laravel usa JSON |

### 2. Funções de Data/Hora

| MySQL | PostgreSQL |
|-------|------------|
| `DATE_ADD(date, INTERVAL n MINUTE)` | `date + INTERVAL 'n minutes'` |
| `NOW()` | `NOW()` ou `CURRENT_TIMESTAMP` |
| `DATE_FORMAT()` | `TO_CHAR()` |

### 3. Strings e Concatenação

- **MySQL:** `CONCAT(str1, str2)`
- **PostgreSQL:** `str1 || str2` ou `CONCAT(str1, str2)`

### 4. Case Sensitivity

- **MySQL:** Case-insensitive por padrão (depende de collation)
- **PostgreSQL:** Case-sensitive por padrão

### 5. Auto Increment

- **MySQL:** `AUTO_INCREMENT`
- **PostgreSQL:** `SERIAL` ou `GENERATED ALWAYS AS IDENTITY`

**Nota:** Seu projeto usa UUIDs, então isso não é relevante.

### 6. Limites e Constraints

- **MySQL:** Mais permissivo com tipos
- **PostgreSQL:** Mais rigoroso com tipos e constraints

---

## 🐛 Troubleshooting

### Erro: "could not find driver"

**Causa:** Extensão `pdo_pgsql` não está instalada/habilitada.

**Solução:**
```bash
# Verificar extensões instaladas
php -m | grep pdo

# Instalar extensão (veja seção Pré-requisitos)
```

### Erro: "password authentication failed"

**Causa:** Credenciais incorretas no `.env`.

**Solução:**
1. Verificar usuário e senha no PostgreSQL
2. Verificar arquivo `.env`
3. Limpar cache: `php artisan config:clear`

### Erro: "relation does not exist"

**Causa:** Tabelas não foram criadas ou estão em schema diferente.

**Solução:**
```bash
# Verificar se migrations foram executadas
php artisan migrate:status

# Executar migrations
php artisan migrate

# Verificar schema no PostgreSQL
psql -U telemedicina_user -d telemedicina_para_todos -c "\dt"
```

### Erro com ENUMs

**Causa:** PostgreSQL trata ENUMs de forma diferente.

**Solução:**
- Se usar migrations do Laravel, geralmente funciona automaticamente
- Se persistir, considere converter para CHECK constraints (veja seção Alterações)

### Erro: "invalid input syntax for type uuid"

**Causa:** String não está no formato UUID válido.

**Solução:**
- Verificar se está usando `HasUuids` trait nos models
- Verificar se está gerando UUIDs corretamente
- Verificar se extensão `uuid-ossp` está instalada

### Query DATE_ADD não funciona

**Causa:** Sintaxe MySQL não funciona no PostgreSQL.

**Solução:**
- Aplicar correção na seção [Alterações Necessárias no Código](#1-corrigir-query-com-date_add-crítico)

### Performance Lenta

**Causa:** Índices não foram criados ou queries não otimizadas.

**Solução:**
```sql
-- Verificar índices existentes
\di

-- Analisar query
EXPLAIN ANALYZE SELECT ...;

-- Criar índices se necessário
CREATE INDEX idx_nome ON tabela(coluna);
```

### Problemas com JSON

**Causa:** PostgreSQL usa JSONB (binário) que é mais eficiente.

**Solução:**
- Laravel gerencia automaticamente
- Se precisar consultar JSON, use sintaxe PostgreSQL:
```php
->whereRaw("metadata->>'campo' = ?", [$valor])
```

---

## 📚 Recursos Adicionais

- [Documentação PostgreSQL](https://www.postgresql.org/docs/)
- [Laravel Database: PostgreSQL](https://laravel.com/docs/database#postgresql)
- [pgloader - Ferramenta de Migração](https://github.com/dimitri/pgloader)
- [PostgreSQL vs MySQL](https://www.postgresql.org/about/featurecomparison/)

---

## ✅ Checklist Final de Migração

Antes de considerar a migração completa:

- [ ] PostgreSQL instalado e configurado
- [ ] Extensão PHP `pdo_pgsql` habilitada
- [ ] Banco de dados criado
- [ ] Usuário e permissões configurados
- [ ] Arquivo `.env` atualizado
- [ ] Código corrigido (DATE_ADD)
- [ ] Migrations executadas com sucesso
- [ ] Dados migrados (se aplicável)
- [ ] Todos os testes do checklist executados
- [ ] Testes automatizados passando
- [ ] Performance verificada
- [ ] Logs verificados (sem erros)
- [ ] Backup do MySQL criado (se produção)

---

## 🎯 Próximos Passos Após Migração

1. **Monitorar Logs:** Acompanhar logs de erro nas primeiras semanas
2. **Performance:** Monitorar queries lentas
3. **Backup:** Configurar backups automáticos do PostgreSQL
4. **Documentação:** Atualizar documentação do projeto
5. **Deploy:** Se em produção, planejar janela de manutenção

---

**Última atualização:** 2025-01-XX  
**Versão do Laravel:** 12.x  
**Versão do PostgreSQL recomendada:** 12+


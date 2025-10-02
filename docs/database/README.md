# 💾 Modelo de Dados

Esta pasta contém a documentação sobre a estrutura do banco de dados, relacionamentos e migrações.

## 📁 Arquivos

- **[🗄️ Diagrama do Banco de Dados](diagrama_banco_dados.md)** - Estrutura das tabelas e relacionamentos

## 🎯 Propósito

Esta documentação define **como** os dados são estruturados e relacionados:

- **Diagrama ERD** das entidades principais
- **Relacionamentos** entre tabelas
- **Estrutura** de cada tabela
- **Índices** e chaves estrangeiras

## 🔗 Navegação

- **DBA**: Foque no [Diagrama do Banco](diagrama_banco_dados.md)
- **Desenvolvedores**: Use para entender relacionamentos
- **Arquitetos**: Consulte para validação do modelo

## 🏗️ Estrutura do Banco

### Entidades Principais
- **Users** - Entidade base (polimórfica)
- **Doctors** - Médicos cadastrados
- **Patients** - Pacientes cadastrados
- **Appointments** - Consultas agendadas
- **Specializations** - Especialidades médicas

### Relacionamentos
- **1:1** User ↔ Doctor/Patient
- **1:N** Doctor ↔ Appointments
- **1:N** Patient ↔ Appointments
- **N:N** Doctor ↔ Specializations

## 📊 Implementação

- **Migrações**: [database/migrations/](../../database/migrations/)
- **Models**: [app/Models/](../../app/Models/)
- **Seeders**: [database/seeders/](../../database/seeders/)

---

*Última atualização: Dezembro 2024*


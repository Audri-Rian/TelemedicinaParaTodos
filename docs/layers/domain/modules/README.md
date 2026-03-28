# ⚙️ Módulos Específicos

Esta pasta contém a documentação detalhada de cada módulo específico do sistema.

## 📁 Módulos

### 🔐 [Autenticação](auth/)
- **[📝 Lógica de Registro](auth/RegistrationLogic.md)** - Fluxos de registro e login
- **[🎨 Diagrama de Login](auth/Diagrama%20Login%20Audri.drawio)** - Visualização do fluxo

### 📅 [Consultas](appointments/)
- **[⚙️ Lógica de Consultas](appointments/AppointmentsLogica.md)** - Regras de agendamento e fluxos
- **[🔧 Implementação de Consultas](appointments/AppointmentsImplementationStudy.md)** - Detalhes técnicos
- **[📋 README](appointments/README.md)** - Visão geral do módulo

### 📹 [Videochamadas](videocall/)
- **[📋 README](videocall/README.md)** - Visão geral do módulo
- **[🔧 Back-end SFU](videocall/BackendSFU.md)** - Estrutura e funcionalidades do back-end (Call, Room, CallManagerService, eventos)
- **[📐 Implementação SFU/MediaSoup](../../videocall/IMPLEMENTACAO_SFU_MEDIASOUP.md)** - Especificação da arquitetura e checklist

### 🩺 [Prontuários Médicos](MedicalRecords/)
- **[👨‍⚕️ Visão do Médico](MedicalRecords/MedicalRecordsDoctor.md)** - Gestão completa de prontuários
- **[👤 Visão do Paciente](MedicalRecords/MedicalRecordsPatient.md)** - Visualização de prontuários

## 🎯 Propósito

Cada módulo documenta:

- **Lógica de negócio** específica do módulo
- **Fluxos** e transições de estado
- **Implementação técnica** detalhada
- **Casos de uso** específicos
- **APIs** e endpoints

## 🔗 Navegação

- **Por Funcionalidade**: Navegue pelo módulo específico
- **Por Papel**: Use os links de navegação em cada módulo
- **Por Implementação**: Consulte os documentos técnicos

## 📊 Rastreabilidade

Cada módulo está conectado à:
- **[Matriz de Requisitos](../index/MatrizRequisitos.md)** - Requisitos implementados
- **[Arquitetura](../architecture/Arquitetura.md)** - Padrões utilizados
- **[Regras de Negócio](../requirements/SystemRules.md)** - Regras aplicadas

## 🏗️ Estrutura por Módulo

Cada módulo segue a estrutura:
- **README.md** - Índice do módulo
- **Lógica/Fluxo** - Regras de negócio
- **Implementação** - Detalhes técnicos
- **Diagramas** - Visualizações (quando aplicável)

---

*Última atualização: Janeiro 2025*
*Versão: 2.0*


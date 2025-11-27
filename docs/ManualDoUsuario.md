# Manual do Usuário - Telemedicina para Todos

## 📋 Índice

1. [Introdução](#introdução)
2. [Primeiros Passos](#primeiros-passos)
3. [Manual para Pacientes](#manual-para-pacientes)
4. [Manual para Médicos](#manual-para-médicos)
5. [Funcionalidades Compartilhadas](#funcionalidades-compartilhadas)
6. [Dúvidas Frequentes](#dúvidas-frequentes)
7. [Suporte](#suporte)

---

## Introdução

Bem-vindo ao **Telemedicina para Todos**, uma plataforma moderna e segura que conecta pacientes e profissionais da saúde através de consultas online. Este manual foi criado para ajudá-lo a utilizar todas as funcionalidades da plataforma de forma eficiente.

### O que é o Telemedicina para Todos?

O Telemedicina para Todos é uma plataforma web que permite:

- **Agendar consultas** com médicos de diversas especialidades
- **Realizar consultas online** por videoconferência em tempo real
- **Acessar prontuários digitais** com todo seu histórico médico
- **Receber prescrições e documentos** digitais de forma segura
- **Gerenciar sua agenda** de forma prática e eficiente

### Requisitos do Sistema

Para utilizar a plataforma, você precisa de:

- **Navegador atualizado**: Chrome, Firefox, Edge ou Safari (versões recentes)
- **Conexão com internet**: Estável, preferencialmente banda larga
- **Dispositivo com câmera e microfone**: Para participar das videoconferências
- **Permissões do navegador**: Permitir acesso à câmera e microfone quando solicitado

---

## Primeiros Passos

### Acessando a Plataforma

1. Abra seu navegador e acesse o endereço da plataforma
2. Você verá a página inicial com opções de **Login** ou **Cadastro**

### Criando sua Conta

A plataforma possui dois tipos de cadastro, dependendo do seu perfil:

#### Para Pacientes

1. Clique em **"Cadastrar como Paciente"** ou acesse `/register/patient`
2. Preencha os dados obrigatórios:
   - Nome completo
   - E-mail (será usado para login)
   - Senha (mínimo 8 caracteres)
   - Gênero
   - Data de nascimento
   - Telefone
3. Preencha os dados opcionais (podem ser completados depois):
   - Contato de emergência (nome e telefone)
   - Histórico médico
   - Alergias
   - Medicamentos em uso
   - Tipo sanguíneo
   - Altura e peso
   - Plano de saúde
4. Clique em **"Cadastrar"**
5. Você será redirecionado para completar o cadastro (etapa 2) com o contato de emergência, que é obrigatório para agendar consultas

#### Para Médicos

1. Clique em **"Cadastrar como Médico"** ou acesse `/register/doctor`
2. Preencha os dados obrigatórios:
   - Nome completo
   - E-mail (será usado para login)
   - Senha (mínimo 8 caracteres)
   - CRM (número único do Conselho Regional de Medicina)
   - Especializações (selecione pelo menos uma)
3. Clique em **"Cadastrar"**
4. Após o cadastro, seu perfil será criado e você poderá configurar sua agenda

### Fazendo Login

1. Acesse a página de login
2. Informe seu **E-mail** ou **CPF**
3. Digite sua **Senha**
4. Clique em **"Entrar"**
5. Você será redirecionado para seu dashboard

### Recuperando sua Senha

Se você esqueceu sua senha:

1. Na página de login, clique em **"Esqueci minha senha"**
2. Informe seu e-mail cadastrado
3. Você receberá um e-mail com instruções para redefinir sua senha
4. Siga as instruções do e-mail para criar uma nova senha

---

## Manual para Pacientes

### Dashboard do Paciente

Após fazer login, você será direcionado para o dashboard, onde pode:

- Ver suas próximas consultas
- Acessar seu histórico médico
- Buscar médicos e agendar consultas
- Visualizar prescrições e documentos

### Buscando e Agendando Consultas

#### Como Buscar um Médico

1. Acesse **"Buscar Consultas"** ou `/patient/search-consultations`
2. Use os filtros disponíveis:
   - **Especialidade**: Selecione a especialidade desejada
   - **Nome do médico**: Digite o nome para buscar
   - **Data**: Selecione a data desejada
3. Visualize a lista de médicos disponíveis
4. Clique no médico desejado para ver mais detalhes

#### Visualizando Disponibilidade

1. Ao selecionar um médico, você verá:
   - Perfil completo do médico
   - Especializações
   - Timeline profissional (formação, cursos, certificados)
   - Locais de atendimento
2. Selecione uma data no calendário
3. O sistema mostrará os horários disponíveis para aquela data
4. Horários já agendados aparecerão como indisponíveis

#### Agendando uma Consulta

1. Selecione um horário disponível
2. Revise os dados da consulta:
   - Médico selecionado
   - Data e horário
   - Local de atendimento (teleconsulta, consultório, etc.)
3. Confirme o agendamento
4. Você receberá uma confirmação com:
   - Código de acesso único da consulta
   - Detalhes da consulta
   - Instruções para participar

**Importante**: Você precisa ter completado seu cadastro, incluindo o contato de emergência, para poder agendar consultas.

### Gerenciando suas Consultas

#### Visualizando Consultas Agendadas

1. Acesse **"Minhas Consultas"** ou `/patient/appointments`
2. Você verá:
   - **Próximas consultas**: Consultas agendadas e em andamento
   - **Histórico**: Consultas finalizadas, canceladas ou não comparecidas

#### Detalhes da Consulta

Ao clicar em uma consulta, você verá:

- Informações do médico
- Data, horário e local
- Status da consulta (agendada, em andamento, finalizada, etc.)
- Código de acesso
- Link para participar da videoconferência (quando disponível)

#### Reagendando uma Consulta

1. Acesse os detalhes da consulta
2. Clique em **"Reagendar"**
3. Selecione uma nova data e horário disponível
4. Confirme o reagendamento

**Observação**: Você só pode reagendar consultas que estão com status "agendada" ou "reagendada", e dentro da janela de tempo permitida (geralmente até 2 horas antes do horário agendado).

#### Cancelando uma Consulta

1. Acesse os detalhes da consulta
2. Clique em **"Cancelar"**
3. Informe o motivo do cancelamento (opcional)
4. Confirme o cancelamento

**Observação**: Você só pode cancelar consultas que estão com status "agendada" ou "reagendada", e dentro da janela de tempo permitida.

### Participando de Consultas Online

#### Antes da Consulta

1. **Prepare seu ambiente**:
   - Escolha um local silencioso e bem iluminado
   - Teste sua câmera e microfone
   - Certifique-se de ter uma conexão estável com a internet
   - Tenha em mãos documentos relevantes (se necessário)

2. **Acesse a consulta**:
   - Entre na plataforma alguns minutos antes do horário
   - Acesse **"Minhas Consultas"**
   - Clique na consulta agendada
   - Quando o médico iniciar, você verá o botão **"Entrar na Consulta"**

#### Durante a Consulta

1. **Permitir acesso à câmera e microfone**:
   - Quando solicitado pelo navegador, clique em **"Permitir"**
   - Isso é necessário para a videoconferência funcionar

2. **Participando da videoconferência**:
   - Você verá o vídeo do médico e o seu próprio vídeo
   - Use os controles para:
     - Ligar/desligar o microfone
     - Ligar/desligar a câmera
     - Ajustar o volume

3. **Durante a consulta**:
   - Siga as orientações do médico
   - Anote informações importantes (se necessário)
   - O médico pode compartilhar informações do seu prontuário

#### Após a Consulta

1. Após a finalização, você receberá notificações sobre:
   - Prescrições emitidas
   - Exames solicitados
   - Atestados emitidos
   - Documentos anexados

2. Acesse **"Meu Prontuário"** para visualizar:
   - Diagnósticos registrados
   - Prescrições ativas e expiradas
   - Exames solicitados e resultados
   - Atestados emitidos
   - Histórico completo de consultas

### Acessando seu Prontuário Médico

#### Visualizando o Prontuário

1. Acesse **"Meu Prontuário"** ou `/patient/medical-records`
2. Você verá:
   - **Histórico de Consultas**: Todas as consultas realizadas
   - **Diagnósticos**: Diagnósticos registrados (quando disponíveis)
   - **Prescrições**: Prescrições ativas e expiradas
   - **Exames**: Exames solicitados e resultados
   - **Atestados**: Atestados médicos emitidos
   - **Documentos**: Documentos anexados ao prontuário
   - **Sinais Vitais**: Registros de sinais vitais (quando disponíveis)

**Observação**: Algumas informações podem estar marcadas como privadas pelo médico e não estarão visíveis para você.

#### Visualizando Prescrições

1. Acesse **"Meu Prontuário"** → **"Prescrições"**
2. Você verá:
   - Prescrições ativas (válidas)
   - Prescrições expiradas (histórico)
3. Cada prescrição mostra:
   - Data de emissão
   - Medicamentos prescritos
   - Instruções de uso
   - Validade

#### Visualizando Exames

1. Acesse **"Meu Prontuário"** → **"Exames"**
2. Você verá:
   - Exames solicitados (pendentes)
   - Resultados de exames (quando disponíveis)
3. Cada exame mostra:
   - Tipo de exame
   - Data de solicitação
   - Status (solicitado, em andamento, concluído)
   - Resultados (quando disponíveis)

#### Anexando Documentos

Você pode anexar documentos ao seu prontuário:

1. Acesse **"Meu Prontuário"** → **"Documentos"**
2. Clique em **"Anexar Documento"**
3. Selecione o arquivo (PDF, imagem, etc.)
4. Adicione uma descrição (opcional)
5. Clique em **"Enviar"**

#### Exportando seu Prontuário

Você pode exportar seu prontuário completo em PDF:

1. Acesse **"Meu Prontuário"**
2. Clique em **"Exportar Prontuário"**
3. O sistema gerará um PDF com todas as informações disponíveis
4. Baixe o arquivo para seu dispositivo

### Gerenciando seu Perfil

#### Editando Dados Pessoais

1. Acesse **"Configurações"** → **"Perfil"** ou `/settings/profile`
2. Edite os campos desejados:
   - Nome
   - Telefone
   - Contato de emergência
   - Histórico médico
   - Alergias
   - Medicamentos em uso
   - Outros dados clínicos
3. Clique em **"Salvar Alterações"**

#### Alterando sua Senha

1. Acesse **"Configurações"** → **"Senha"** ou `/settings/password`
2. Informe sua senha atual
3. Digite a nova senha
4. Confirme a nova senha
5. Clique em **"Alterar Senha"**

#### Excluindo sua Conta

1. Acesse **"Configurações"**
2. Role até a seção **"Excluir Conta"**
3. Leia as informações sobre a exclusão
4. Confirme que deseja excluir sua conta
5. Digite sua senha para confirmar
6. Clique em **"Excluir Conta"**

**Atenção**: A exclusão da conta é permanente e não pode ser desfeita. Seu histórico médico será mantido conforme a legislação vigente (LGPD).

---

## Manual para Médicos

### Dashboard do Médico

Após fazer login, você será direcionado para o dashboard médico, onde pode:

- Ver suas próximas consultas
- Acessar sua agenda
- Visualizar pacientes atendidos
- Gerenciar seu perfil profissional

### Configurando sua Agenda

#### Locais de Atendimento

Você pode cadastrar múltiplos locais de atendimento:

1. Acesse **"Agenda"** → **"Locais de Atendimento"** ou `/doctor/schedule`
2. Clique em **"Adicionar Local"**
3. Preencha:
   - Nome do local (ex: "Consultório Principal", "Teleconsulta")
   - Tipo (Teleconsulta, Consultório, Hospital, Clínica)
   - Endereço (opcional)
   - Descrição (opcional)
4. Clique em **"Salvar"**

#### Configurando Disponibilidade

Você pode configurar sua disponibilidade de duas formas:

##### Slots Recorrentes (Semanais)

Configure horários que se repetem toda semana:

1. Acesse **"Agenda"** → **"Disponibilidade"**
2. Clique em **"Adicionar Slot Recorrente"**
3. Configure:
   - Dia da semana (segunda, terça, etc.)
   - Horário de início
   - Horário de término
   - Local de atendimento
   - Duração das consultas (padrão: 30 minutos)
4. Clique em **"Salvar"**

**Exemplo**: Toda segunda-feira, das 8h às 12h, no consultório.

##### Slots Específicos (Datas)

Configure horários para datas específicas:

1. Acesse **"Agenda"** → **"Disponibilidade"**
2. Clique em **"Adicionar Slot Específico"**
3. Configure:
   - Data específica
   - Horário de início
   - Horário de término
   - Local de atendimento
   - Duração das consultas
4. Clique em **"Salvar"**

**Exemplo**: 15 de janeiro de 2025, das 14h às 18h, para teleconsulta.

#### Bloqueando Datas

Para bloquear datas específicas (feriados, férias, etc.):

1. Acesse **"Agenda"** → **"Datas Bloqueadas"**
2. Clique em **"Bloquear Data"**
3. Selecione a data
4. Adicione uma descrição (opcional, ex: "Feriado", "Férias")
5. Clique em **"Salvar"**

**Observação**: Datas bloqueadas não aparecerão como disponíveis para agendamento.

### Gerenciando Consultas

#### Visualizando suas Consultas

1. Acesse **"Consultas"** ou `/doctor/consultations`
2. Você verá:
   - **Próximas consultas**: Consultas agendadas e em andamento
   - **Histórico**: Consultas finalizadas, canceladas ou não comparecidas

#### Detalhes da Consulta

Ao clicar em uma consulta, você verá:

- Informações do paciente
- Data, horário e local
- Status da consulta
- Histórico médico do paciente (consultas anteriores)
- Prontuário do paciente (quando consulta estiver em andamento)

#### Iniciando uma Consulta

1. Acesse os detalhes da consulta
2. Clique em **"Iniciar Consulta"**
3. O sistema:
   - Atualizará o status para "em andamento"
   - Criará a sala de videoconferência
   - Permitirá acesso ao prontuário do paciente
4. O paciente receberá uma notificação para entrar na chamada

**Observação**: Você só pode iniciar consultas que estão com status "agendada" ou "reagendada", e dentro da janela de tempo permitida (geralmente 10 minutos antes do horário agendado).

#### Durante a Consulta

##### Participando da Videoconferência

1. **Permitir acesso à câmera e microfone**:
   - Quando solicitado pelo navegador, clique em **"Permitir"**

2. **Controles da videoconferência**:
   - Ligar/desligar microfone
   - Ligar/desligar câmera
   - Ajustar volume
   - Compartilhar tela (quando disponível)

##### Acessando e Editando o Prontuário

Durante a consulta, você pode acessar e editar o prontuário completo do paciente:

1. Clique em **"Abrir Prontuário"** (ou acesse via sidebar)
2. Você poderá registrar:
   - **Queixa Principal**: Motivo da consulta
   - **Anamnese**: Histórico e sintomas relatados
   - **Diagnóstico**: Com código CID-10
   - **Prescrições**: Medicamentos e instruções
   - **Exames**: Solicitação de exames
   - **Anotações Clínicas**: Notas públicas ou privadas
   - **Sinais Vitais**: Pressão, temperatura, etc.
   - **Atestados**: Emissão de atestados médicos
   - **Documentos**: Anexar documentos ao prontuário

3. **Salvando rascunho**:
   - Você pode salvar o progresso sem finalizar a consulta
   - Clique em **"Salvar Rascunho"** a qualquer momento

##### Registrando Diagnóstico

1. No prontuário, acesse **"Diagnósticos"**
2. Clique em **"Adicionar Diagnóstico"**
3. Preencha:
   - Código CID-10 (ou busque na lista)
   - Descrição do diagnóstico
   - Observações (opcional)
4. Clique em **"Salvar"**

##### Emitindo Prescrição

1. No prontuário, acesse **"Prescrições"**
2. Clique em **"Nova Prescrição"**
3. Adicione os medicamentos:
   - Nome do medicamento
   - Dosagem
   - Frequência
   - Duração do tratamento
   - Instruções de uso
4. Clique em **"Salvar Prescrição"**

**Observação**: Apenas médicos com CRM válido podem emitir prescrições.

##### Solicitando Exames

1. No prontuário, acesse **"Exames"**
2. Clique em **"Solicitar Exame"**
3. Preencha:
   - Tipo de exame (laboratorial, imagem, outros)
   - Descrição
   - Instruções para o paciente (opcional)
4. Clique em **"Salvar"**

##### Registrando Sinais Vitais

1. No prontuário, acesse **"Sinais Vitais"**
2. Clique em **"Registrar Sinais Vitais"**
3. Preencha os valores:
   - Pressão arterial
   - Temperatura
   - Frequência cardíaca
   - Frequência respiratória
   - Peso
   - Altura
   - Outros (conforme necessário)
4. Clique em **"Salvar"**

##### Emitindo Atestado

1. No prontuário, acesse **"Atestados"**
2. Clique em **"Emitir Atestado"**
3. Preencha:
   - Tipo de atestado
   - Período de afastamento (se aplicável)
   - Descrição
4. Clique em **"Emitir"**

O sistema gerará um atestado com código de verificação único.

##### Adicionando Anotações Clínicas

1. No prontuário, acesse **"Anotações"**
2. Clique em **"Nova Anotação"**
3. Preencha:
   - Título
   - Conteúdo
   - Visibilidade (pública ou privada)
   - **Pública**: Visível para o paciente
   - **Privada**: Apenas para você
4. Clique em **"Salvar"**

#### Finalizando uma Consulta

1. Após concluir a consulta e registrar todos os dados necessários:
2. Clique em **"Finalizar Consulta"**
3. O sistema:
   - Atualizará o status para "finalizada"
   - Bloqueará a edição de dados críticos (diagnóstico, prescrições)
   - Gerará PDF da consulta (opcional)
   - Expirará a sala de videoconferência
   - Enviará notificações ao paciente

**Importante**: Após finalizar, você só poderá adicionar complementos ao prontuário. Dados principais não poderão ser alterados.

#### Adicionando Complementos

Após finalizar uma consulta, você pode adicionar complementos:

1. Acesse os detalhes da consulta finalizada
2. Clique em **"Adicionar Complemento"**
3. Adicione:
   - Comentários adicionais
   - Anexos de documentos
   - Correções com justificativa (registradas em auditoria)
4. Clique em **"Salvar"**

### Visualizando Prontuários de Pacientes

#### Acessando Prontuário Completo

1. Acesse **"Pacientes"** ou `/doctor/patients`
2. Selecione o paciente desejado
3. Clique em **"Ver Prontuário"** ou `/doctor/patients/{patient}/medical-record`
4. Você verá:
   - Histórico completo de consultas
   - Todos os diagnósticos registrados
   - Prescrições (ativas e expiradas)
   - Exames (solicitados e resultados)
   - Anotações clínicas (públicas e privadas)
   - Atestados emitidos
   - Sinais vitais históricos
   - Documentos anexados

#### Exportando Prontuário

Você pode exportar o prontuário completo de um paciente em PDF:

1. Acesse o prontuário do paciente
2. Clique em **"Exportar Prontuário"**
3. O sistema gerará um PDF completo
4. Baixe o arquivo

#### Gerando PDF de Consulta

Você pode gerar um PDF específico de uma consulta:

1. Acesse os detalhes da consulta
2. Clique em **"Gerar PDF da Consulta"**
3. O PDF incluirá:
   - Dados da consulta
   - Prontuário registrado na consulta
   - Prescrições emitidas
   - Exames solicitados
   - Atestados emitidos

### Gerenciando Timeline Profissional

Você pode adicionar eventos à sua timeline profissional (formação, cursos, certificados):

1. Acesse **"Perfil"** → **"Timeline Profissional"` ou `/api/timeline-events`
2. Clique em **"Adicionar Evento"**
3. Selecione o tipo:
   - **Educação**: Formação acadêmica (fundamental, médio, graduação, pós)
   - **Curso**: Cursos realizados
   - **Certificado**: Certificações profissionais
   - **Projeto**: Projetos profissionais
4. Preencha:
   - Título
   - Descrição
   - Data de início e término (se aplicável)
   - Instituição/Organização
   - Visibilidade (público ou privado)
   - Ordem de exibição
5. Anexe certificados ou documentos (opcional)
6. Clique em **"Salvar"**

**Observação**: Eventos marcados como públicos aparecerão no seu perfil para pacientes.

### Gerenciando seu Perfil

#### Editando Dados Profissionais

1. Acesse **"Configurações"** → **"Perfil"** ou `/settings/profile`
2. Edite os campos desejados:
   - Nome
   - CRM (se necessário atualizar)
   - Biografia
   - Especializações (até 5 especializações)
   - Telefone
   - Outros dados profissionais
3. Clique em **"Salvar Alterações"**

#### Alterando sua Senha

1. Acesse **"Configurações"** → **"Senha"** ou `/settings/password`
2. Informe sua senha atual
3. Digite a nova senha
4. Confirme a nova senha
5. Clique em **"Alterar Senha"**

---

## Funcionalidades Compartilhadas

### Notificações

A plataforma envia notificações para:

- Confirmação de agendamento
- Lembrete de consulta (antes do horário)
- Início de consulta
- Cancelamento ou reagendamento
- Prescrições emitidas
- Exames solicitados
- Atestados emitidos
- Documentos anexados

As notificações aparecem no painel da plataforma e podem ser enviadas por e-mail.

### Segurança e Privacidade

#### Proteção de Dados

- Todas as comunicações são criptografadas (HTTPS)
- Senhas são armazenadas de forma segura (criptografia bcrypt)
- Dados sensíveis são protegidos conforme LGPD
- Acesso aos prontuários é controlado e auditado

#### Auditoria

Todas as ações em prontuários médicos são registradas em logs de auditoria, incluindo:

- Quem acessou
- Quando acessou
- O que foi modificado
- IP e informações do dispositivo

Esses logs não podem ser excluídos e garantem rastreabilidade completa.

### Exportação de Dados

#### Pacientes

- Exportar próprio prontuário em PDF
- Visualizar histórico completo de consultas

#### Médicos

- Exportar prontuário completo de pacientes atendidos
- Gerar PDF de consultas específicas
- Visualizar histórico de atendimentos

---

## Dúvidas Frequentes

### Sobre Agendamentos

**P: Posso agendar consultas para o mesmo dia?**
R: Sim, desde que haja disponibilidade do médico e o horário esteja dentro da janela permitida.

**P: Quantas consultas posso agendar por vez?**
R: Não há limite de consultas agendadas. Você pode agendar quantas consultas desejar.

**P: O que acontece se eu não comparecer à consulta?**
R: O médico pode marcar a consulta como "não compareceu" (no-show). Consulte a política de cancelamento da plataforma.

### Sobre Videoconferências

**P: Preciso instalar algum software para a videoconferência?**
R: Não. A videoconferência funciona diretamente no navegador, sem necessidade de instalação.

**P: O que fazer se a videoconferência não estiver funcionando?**
R: Verifique:
- Sua conexão com a internet
- Se permitiu acesso à câmera e microfone
- Se está usando um navegador atualizado
- Tente recarregar a página

**P: A consulta é gravada?**
R: Por padrão, as consultas não são gravadas. O médico pode optar por gravar (quando disponível), com seu consentimento.

### Sobre Prontuários

**P: Quem pode ver meu prontuário?**
R: Apenas você e os médicos que te atenderam podem acessar seu prontuário. Médicos que não te atenderam não têm acesso.

**P: Posso editar meu prontuário?**
R: Não. O prontuário é editado apenas pelos médicos durante as consultas. Você pode anexar documentos e visualizar informações.

**P: Meus dados são seguros?**
R: Sim. A plataforma segue todas as normas de segurança e privacidade (LGPD). Dados são criptografados e o acesso é auditado.

### Sobre Prescrições

**P: As prescrições são válidas em farmácias?**
R: Sim. As prescrições digitais emitidas por médicos com CRM válido são válidas e podem ser apresentadas em farmácias.

**P: Como sei se uma prescrição ainda está válida?**
R: No seu prontuário, você verá prescrições "ativas" (válidas) e "expiradas" (histórico). Cada prescrição mostra sua data de validade.

### Sobre Pagamentos

**P: Como funciona o pagamento?**
R: O sistema de pagamentos está em desenvolvimento. Consulte a plataforma para informações atualizadas sobre métodos de pagamento.

### Problemas Técnicos

**P: Não consigo fazer login. O que fazer?**
R: Verifique:
- Se está usando o e-mail correto
- Se a senha está correta
- Se sua conta está ativa
- Tente recuperar sua senha se necessário

**P: A página não carrega. O que fazer?**
R: Verifique:
- Sua conexão com a internet
- Se está usando um navegador atualizado
- Tente limpar o cache do navegador
- Tente em outro navegador

---

## Suporte

### Como Obter Ajuda

Se você precisar de ajuda:

1. **Consulte este manual**: A maioria das dúvidas está respondida aqui
2. **Central de Ajuda**: Acesse a seção de ajuda na plataforma
3. **Contato**: Entre em contato com o suporte através dos canais disponíveis

### Informações de Contato

Para suporte técnico ou dúvidas:

- **E-mail**: [e-mail de suporte]
- **Telefone**: [telefone de suporte]
- **Horário de Atendimento**: [horário de atendimento]

### Reportar Problemas

Se você encontrar um problema na plataforma:

1. Tente descrever o problema com detalhes
2. Informe:
   - O que você estava fazendo quando o problema ocorreu
   - Mensagens de erro (se houver)
   - Navegador e versão utilizados
   - Dispositivo utilizado (computador, tablet, celular)
3. Entre em contato com o suporte

---

## Glossário

- **Agendamento**: Marcação de uma consulta com data e horário específicos
- **CID-10**: Classificação Internacional de Doenças, 10ª revisão
- **CRM**: Conselho Regional de Medicina - registro profissional do médico
- **LGPD**: Lei Geral de Proteção de Dados
- **Prontuário**: Registro completo do histórico médico de um paciente
- **Prescrição Digital**: Prescrição médica emitida eletronicamente
- **Teleconsulta**: Consulta médica realizada remotamente por videoconferência
- **Videoconferência**: Comunicação em tempo real por vídeo e áudio

---

*Última atualização: Janeiro 2025*
*Versão: 1.0*


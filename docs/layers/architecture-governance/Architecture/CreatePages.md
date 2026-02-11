# Mapeamento de Telas e Fluxos de Usuário - Plataforma de Telemedicina

Este documento descreve todas as telas planejadas para a plataforma, detalhando suas funcionalidades e mapeando os fluxos de usuário para os perfis de Paciente e Médico.

## 1. Listagem e Detalhamento de Telas

### Telas do Médico

#### 1.1. Painel Principal (Dashboard)
- **Visualização Rápida:** Exibição clara das consultas marcadas para o dia, com nome do paciente e horário
- **Métricas de Desempenho:** Gráficos simples mostrando o número de consultas realizadas na semana/mês
- **Acessos Rápidos:** Botões de destaque para as ações mais comuns, como "Gerenciar Agenda" e "Ver Histórico Completo"
- **Área de Notificações:** Alertas sobre novos agendamentos, cancelamentos de última hora ou mensagens de pacientes

#### 1.2. Gestão de Agenda
- **Calendário Interativo:** Interface de calendário (semanal/mensal) onde o médico pode clicar para adicionar, editar ou remover blocos de horários disponíveis
- **Configuração de Padrões:** Opção para definir horários recorrentes (ex: "toda segunda-feira, das 9h às 18h")
- **Bloqueio de Horários:** Funcionalidade para marcar datas ou períodos específicos como indisponíveis para compromissos pessoais ou férias
- **Visão de Ocupação:** Indicação visual clara de quais horários já foram agendados

#### 1.3. Histórico de Consultas
- **Listagem Completa:** Tabela ou lista com todas as consultas, mostrando paciente, data, hora e status (Agendada, Concluída, Cancelada, Não Compareceu)
- **Filtros Avançados:** Ferramentas para filtrar a lista por período, por nome do paciente ou pelo status da consulta
- **Busca Rápida:** Campo de pesquisa para encontrar um paciente ou consulta específica rapidamente
- **Acesso aos Detalhes:** Link em cada item da lista para abrir a tela de "Detalhes da Consulta"

#### 1.4. Detalhes da Consulta
- **Informações do Paciente:** Cabeçalho com nome completo, idade, e contato do paciente
- **Painel de Ações:** Botões para "Iniciar Chamada de Vídeo", "Finalizar Atendimento", "Cancelar Consulta" e "Marcar Não Comparecimento"
- **Prontuário Rápido:** Acesso a um resumo das informações clínicas mais importantes do paciente
- **Área de Anotações:** Espaço para o médico fazer anotações privadas durante e após a consulta
- **Link para Prescrição:** Atalho direto para a tela de "Emissão de Documentos" pré-preenchida com os dados do paciente

#### 1.5. Emissão de Documentos
- **Formulário Inteligente:** Campos estruturados para criar prescrições, atestados e pedidos de exames
- **Busca de Medicamentos:** Base de dados de medicamentos para facilitar a adição, com preenchimento automático de dosagem e via de administração
- **Assinatura Digital:** Integração para validar o documento com a assinatura digital do profissional
- **Envio Seguro:** Funcionalidade para enviar o documento gerado diretamente para o portal do paciente com segurança e confirmação de recebimento

### Telas do Paciente

#### 1.6. Painel Principal (Dashboard)
- **Próxima Consulta:** Destaque principal para a próxima consulta agendada, com nome do médico, data, hora e um botão para "Acessar Sala de Consulta"
- **Lembretes Importantes:** Avisos sobre a necessidade de atualizar informações de saúde ou visualizar novos documentos
- **Histórico Recente:** Lista das últimas 3-5 consultas realizadas, com acesso rápido aos documentos relacionados
- **Botão de Ação Principal:** Atalho bem visível para "Buscar Profissionais e Agendar"

#### 1.7. Busca de Profissionais
- **Filtros de Pesquisa:** Campos para buscar por especialidade, nome do médico, ou cidade
- **Perfil do Médico:** Resultados exibindo foto, nome, especialidade e avaliação de cada profissional, com um link para um perfil completo
- **Visualização de Agenda:** Ao selecionar um médico, exibir seu calendário com os horários disponíveis de forma clara e intuitiva

#### 1.8. Agendamento de Consulta
- **Seleção de Horário:** Interface simples para o paciente clicar no dia e horário desejado na agenda do médico
- **Confirmação de Dados:** Etapa para o paciente confirmar seus dados e, se necessário, inserir o motivo da consulta
- **Integração com Pagamento (Opcional):** Se aplicável, fluxo para realizar o pagamento da consulta
- **Confirmação Final:** Tela de sucesso do agendamento, com envio automático de e-mail e/ou SMS de confirmação

#### 1.9. Histórico de Consultas
- **Linha do Tempo de Saúde:** Visualização cronológica de todas as consultas passadas e futuras
- **Acesso a Detalhes:** Opção para expandir cada consulta e ver detalhes, como o médico que atendeu e os documentos emitidos
- **Filtros Simples:** Opção para filtrar por médico ou por período

#### 1.10. Visualização de Documentos
- **Lista Organizada:** Relação de todos os documentos recebidos (prescrições, atestados), com data e nome do médico que emitiu
- **Visualizador Integrado:** Ferramenta para abrir e ler os documentos em PDF diretamente no navegador, sem precisar baixar
- **Opções de Download e Impressão:** Botões claros para salvar o documento no dispositivo ou imprimir

#### 1.11. Gerenciamento de Saúde
- **Formulário Guiado:** Seções bem definidas para o paciente preencher informações sobre alergias, medicamentos de uso contínuo, condições crônicas, cirurgias prévias e histórico familiar
- **Facilidade de Edição:** Permitir que o paciente atualize essas informações a qualquer momento de forma simples

### Telas Comuns (Acessíveis por ambos)

#### 1.12. Sala de Consulta Virtual
- **Interface Limpa:** Foco principal no vídeo, com os controles posicionados de forma a não atrapalhar
- **Controles Essenciais:** Botões visíveis para ativar/desativar microfone e câmera, e para encerrar a chamada
- **Chat de Apoio:** Uma janela de chat de texto para comunicação caso o áudio falhe ou para compartilhar links
- **Indicador de Qualidade:** Um ícone que mostra a qualidade da conexão de internet para ambos os participantes

#### 1.13. Configurações de Perfil
- **Edição de Dados Pessoais:** Campos para alterar nome, e-mail, telefone e senha
- **Informações Específicas:** Seção para o médico atualizar dados profissionais (CRM, especialidade) e para o paciente atualizar dados de contato e convênio
- **Preferências de Notificação:** Opções para o usuário escolher como deseja receber lembretes e notificações (e-mail, push no app, etc.)

#### 1.14. Configurações de Segurança
- **Autenticação de Dois Fatores:** Configuração de 2FA via SMS, e-mail ou aplicativo autenticador
- **Histórico de Login:** Visualização de acessos recentes, dispositivos conectados e localizações
- **Sessões Ativas:** Gerenciamento de sessões ativas, opção para desconectar dispositivos específicos
- **Alteração de Senha:** Formulário seguro para troca de senha com validação de senha atual
- **Backup de Códigos:** Códigos de recuperação para 2FA e acesso de emergência

#### 1.15. Configurações de Conta (Campos Adicionais)
- **Dados de Contato de Emergência:** Campos para contato de emergência e telefone de emergência
- **Histórico Médico Detalhado:** Seção expandida para histórico médico completo, cirurgias e condições crônicas
- **Informações de Alergias:** Gestão detalhada de alergias, medicamentos e reações adversas
- **Medicamentos em Uso:** Lista de medicamentos atuais, dosagens e horários de administração
- **Tipo Sanguíneo:** Campo para tipo sanguíneo e informações de compatibilidade
- **Dados Físicos:** Altura, peso e IMC com histórico de variações
- **Informações de Seguro:** Dados do convênio médico, número da carteirinha e validade
- **Consentimento de Telemedicina:** Termos de consentimento e preferências de gravação

#### 1.16. Central de Ajuda e FAQ
- **Perguntas Frequentes:** Lista organizada de perguntas e respostas comuns
- **Categorias de Ajuda:** Seções temáticas (Agendamento, Videoconferência, Documentos, Pagamentos)
- **Busca na Ajuda:** Campo de pesquisa para encontrar tópicos específicos
- **Tutoriais em Vídeo:** Guias visuais para funcionalidades principais
- **Contato com Suporte:** Formulário para reportar problemas ou solicitar ajuda
- **Status do Sistema:** Indicadores de status dos serviços e manutenções programadas

---

## 2. Mapeamento de Fluxo de Usuário

### Fluxo do Paciente

#### 2.1. Fluxo de Agendamento de Primeira Consulta
1. **Entrada:** Página Inicial ou **Painel do Paciente**
2. **Busca:** O paciente acessa a tela de **Busca de Profissionais**, usa os filtros e encontra uma lista de médicos
3. **Escolha:** Ele clica no perfil de um médico para ver detalhes e sua agenda
4. **Agendamento:** Na tela de **Agendamento de Consulta**, ele seleciona um horário, confirma seus dados e finaliza o processo
5. **Confirmação:** A nova consulta aparece em seu **Painel Principal** e uma notificação é enviada
6. **Saída:** Paciente com uma consulta agendada

#### 2.2. Fluxo de Realização da Consulta
1. **Entrada:** **Painel do Paciente**, próximo ao horário da consulta
2. **Acesso à Sala:** O paciente clica no botão "Entrar na Sala Virtual" em seu painel
3. **Consulta:** Ele entra na **Sala de Consulta Virtual**, aguarda o médico e realiza a teleconsulta
4. **Pós-Consulta:** Ao final, ele recebe notificações sobre novos documentos
5. **Acesso aos Documentos:** O paciente navega para a **Visualização de Documentos** para baixar prescrições ou atestados
6. **Saída:** Consulta realizada e documentos recebidos

#### 2.3. Fluxo de Gestão da Conta
1. **Entrada:** Menu principal da plataforma
2. **Atualização de Saúde:** O paciente acessa o **Gerenciamento de Saúde** para atualizar seu histórico clínico
3. **Atualização de Perfil:** Ele vai para **Configurações de Perfil** para alterar dados de contato ou senha
4. **Saída:** Informações do paciente atualizadas

#### 2.4. Fluxo de Configuração de Segurança
1. **Entrada:** **Configurações de Perfil** ou menu de segurança
2. **Configuração 2FA:** O usuário acessa **Configurações de Segurança** para ativar autenticação de dois fatores
3. **Validação:** Sistema solicita confirmação via SMS/e-mail para ativar 2FA
4. **Backup:** Usuário salva códigos de recuperação em local seguro
5. **Histórico:** Visualiza **Histórico de Login** para verificar acessos recentes
6. **Saída:** Conta com segurança reforçada e 2FA ativado

#### 2.5. Fluxo de Preenchimento de Dados Adicionais
1. **Entrada:** **Configurações de Conta** ou notificação de dados incompletos
2. **Contato de Emergência:** Preenche dados de contato de emergência obrigatórios
3. **Histórico Médico:** Adiciona informações detalhadas sobre histórico médico e cirurgias
4. **Alergias e Medicamentos:** Registra alergias conhecidas e medicamentos em uso
5. **Dados Físicos:** Informa tipo sanguíneo, altura, peso e dados do convênio
6. **Consentimento:** Aceita termos de telemedicina e preferências de gravação
7. **Saída:** Perfil completo com todos os dados necessários preenchidos

#### 2.6. Fluxo de Busca de Ajuda
1. **Entrada:** Menu de ajuda ou ícone de suporte
2. **Navegação:** Acessa **Central de Ajuda e FAQ** para buscar solução
3. **Busca:** Utiliza campo de pesquisa ou navega pelas categorias
4. **Solução:** Encontra resposta na FAQ ou tutorial em vídeo
5. **Contato:** Se necessário, preenche formulário de **Contato com Suporte**
6. **Saída:** Problema resolvido ou suporte acionado

### Fluxo do Médico

#### 2.7. Fluxo de Configuração da Agenda
1. **Entrada:** **Painel Principal do Médico**
2. **Acesso à Agenda:** O médico navega para a **Gestão de Agenda**
3. **Definição de Horários:** Ele usa o calendário para definir seus horários de trabalho ou bloquear datas específicas
4. **Publicação:** Ao salvar, os horários ficam disponíveis para os pacientes
5. **Saída:** Agenda do médico configurada

#### 2.8. Fluxo de Atendimento de um Paciente
1. **Entrada:** **Painel Principal do Médico** no dia da consulta
2. **Identificação:** O painel exibe a lista de pacientes do dia. O médico seleciona o próximo
3. **Revisão:** Ele abre os **Detalhes da Consulta** para revisar o prontuário do paciente
4. **Início da Consulta:** No horário, ele clica em "Iniciar Chamada de Vídeo" e acessa a **Sala de Consulta Virtual**
5. **Finalização:** Após o atendimento, ele encerra a chamada e marca a consulta como "Concluída"
6. **Saída:** Paciente atendido

#### 2.9. Fluxo de Emissão de Documentos Pós-Consulta
1. **Entrada:** Tela de **Detalhes da Consulta** após o atendimento
2. **Acesso à Emissão:** O médico clica em "Emitir Prescrição/Documento"
3. **Criação:** Na tela de **Emissão de Documentos**, ele preenche o formulário, busca medicamentos e assina digitalmente
4. **Envio:** Com um clique, o documento é enviado de forma segura ao paciente
5. **Verificação:** O médico pode confirmar o envio no **Histórico de Consultas**
6. **Saída:** Documentos gerados e entregues ao paciente

#### 2.10. Fluxo de Configuração de Segurança (Médico)
1. **Entrada:** **Configurações de Perfil** ou menu de segurança
2. **Configuração 2FA:** O médico acessa **Configurações de Segurança** para ativar autenticação de dois fatores
3. **Validação:** Sistema solicita confirmação via SMS/e-mail para ativar 2FA
4. **Backup:** Médico salva códigos de recuperação em local seguro
5. **Histórico:** Visualiza **Histórico de Login** para verificar acessos recentes
6. **Saída:** Conta médica com segurança reforçada e 2FA ativado

#### 2.11. Fluxo de Preenchimento de Dados Profissionais
1. **Entrada:** **Configurações de Conta** ou notificação de dados incompletos
2. **Dados Profissionais:** Completa informações do CRM, número da licença e data de validade
3. **Biografia:** Adiciona biografia profissional e especialidades
4. **Horários de Atendimento:** Define disponibilidade padrão e valores de consulta
5. **Consentimento:** Aceita termos de telemedicina e preferências de gravação
6. **Saída:** Perfil médico completo com todos os dados profissionais preenchidos
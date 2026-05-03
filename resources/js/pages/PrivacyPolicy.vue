<script setup lang="ts">
import { login, tcle } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

const emailContact = 'audririan1@gmail.com';
const lastUpdate = 'Maio de 2026';
</script>

<template>
    <Head title="Política de Privacidade" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-8 text-center">
                <Link :href="login()" class="mb-6 inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para login
                </Link>
                <h1 class="mb-2 text-4xl font-bold text-gray-900">Política de Privacidade</h1>
                <p class="text-lg text-gray-600">Projeto Telemedicina Para Todos</p>
                <p class="mt-2 text-sm text-gray-500">Última atualização: {{ lastUpdate }}</p>
            </div>

            <!-- Content Card -->
            <div class="space-y-8 rounded-lg bg-white p-8 shadow-lg md:p-12">
                <!-- Seção 1: Introdução -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">1. Introdução</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Bem-vindo à Política de Privacidade do Projeto Telemedicina Para Todos ("nós", "nosso site").
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Este documento explica como tratamos as informações pessoais de nossos visitantes e usuários, em conformidade com a Lei nº
                        13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD).
                    </p>
                    <div class="mt-4 border-l-4 border-blue-500 bg-blue-50 p-4">
                        <p class="mb-2 leading-relaxed font-semibold text-gray-700">
                            <strong>📋 Conformidade com a LGPD:</strong>
                        </p>
                        <p class="leading-relaxed text-gray-700">
                            Esta Política está em conformidade com a
                            <strong>Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD)</strong>, que garante os direitos de
                            privacidade e proteção de dados dos usuários no Brasil. A LGPD estabelece regras claras sobre como dados pessoais devem
                            ser coletados, armazenados, tratados e compartilhados, assegurando transparência e segurança no tratamento de informações
                            pessoais.
                        </p>
                    </div>
                </section>

                <!-- Seção 1.1: Sobre a LGPD -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">2. Sobre a Lei Geral de Proteção de Dados (LGPD)</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        A <strong>Lei Geral de Proteção de Dados Pessoais (LGPD)</strong> — Lei nº 13.709/2018 — é a legislação brasileira que regula
                        o tratamento de dados pessoais, tanto no ambiente físico quanto digital. Esta lei tem como objetivo proteger os direitos
                        fundamentais de liberdade e privacidade, além de garantir o livre desenvolvimento da personalidade da pessoa natural.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Princípios da LGPD que seguimos:</strong>
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Finalidade:</strong> Tratamos dados apenas para finalidades específicas e informadas</li>
                        <li><strong>Adequação:</strong> Os dados são compatíveis com as finalidades informadas</li>
                        <li><strong>Necessidade:</strong> Coletamos apenas os dados necessários para as finalidades</li>
                        <li><strong>Transparência:</strong> Informamos claramente sobre o tratamento de dados</li>
                        <li><strong>Segurança:</strong> Adotamos medidas para proteger os dados</li>
                        <li><strong>Prevenção:</strong> Tomamos medidas para prevenir danos</li>
                        <li><strong>Não discriminação:</strong> Não realizamos tratamento para fins discriminatórios</li>
                        <li><strong>Responsabilização:</strong> Demonstramos conformidade com a LGPD</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Ao utilizar nosso site, você pode ter a certeza de que seus dados pessoais são tratados de acordo com todos os princípios e
                        requisitos estabelecidos pela LGPD, garantindo sua privacidade e proteção.
                    </p>
                </section>

                <!-- Seção 3: Quais Dados Coletamos -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">3. Quais Dados Coletamos</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">Podemos coletar as seguintes informações quando você utiliza nosso site:</p>

                    <div class="space-y-4">
                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Dados fornecidos voluntariamente pelo usuário:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li><strong>Dados de identificação:</strong> Nome completo, e-mail, data de nascimento</li>
                                <li><strong>Dados de perfil:</strong> Gênero, telefone, informações de perfil médico (apenas para testes)</li>
                                <li><strong>Dados de autenticação:</strong> Senha (criptografada), tokens de sessão</li>
                                <li><strong>Dados de comunicação:</strong> Mensagens enviadas através do site, feedback, suporte</li>
                                <li>
                                    <strong>Dados de formulários:</strong> Informações preenchidas em formulários de registro, consulta ou contato
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Dados coletados automaticamente:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li><strong>Dados técnicos:</strong> Endereço IP, tipo de navegador, sistema operacional, resolução de tela</li>
                                <li>
                                    <strong>Dados de navegação:</strong> Páginas visitadas, tempo de permanência, links clicados, origem do acesso
                                </li>
                                <li>
                                    <strong>Dados de dispositivo:</strong> Tipo de dispositivo (desktop, mobile, tablet), identificador do dispositivo
                                </li>
                                <li><strong>Dados de uso:</strong> Horários de acesso, frequência de uso, funcionalidades utilizadas</li>
                                <li>
                                    <strong>Cookies e tecnologias similares:</strong> Dados armazenados em cookies, local storage, session storage
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Dados de terceiros (quando aplicável):</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li>Dados fornecidos por serviços de autenticação de terceiros (se houver integração)</li>
                                <li>Dados de serviços de análise (anônimos e agregados)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4 border-l-4 border-blue-500 bg-blue-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            <strong>Dados sensíveis de saúde</strong> (prontuário, prescrições, diagnósticos, exames, sinais vitais) são tratados com
                            base no art. 11 da LGPD e nas normas do CFM, com acesso restrito ao paciente titular e ao médico responsável pelo
                            atendimento.
                        </p>
                    </div>
                </section>

                <!-- Seção 4: Finalidade do Uso dos Dados -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">4. Finalidade do Uso dos Dados</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">Utilizamos os dados coletados exclusivamente para as seguintes finalidades:</p>

                    <div class="space-y-3">
                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Finalidades principais:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li>
                                    <strong>Funcionamento do serviço:</strong> Permitir o funcionamento básico do site e das funcionalidades de teste
                                    e demonstração
                                </li>
                                <li><strong>Autenticação e segurança:</strong> Gerenciar contas de usuários, autenticação e prevenção de fraudes</li>
                                <li>
                                    <strong>Comunicação:</strong> Entrar em contato quando solicitado (ex: formulários de feedback, suporte técnico)
                                </li>
                                <li>
                                    <strong>Melhoria do serviço:</strong> Analisar o uso do site para melhorar a experiência do usuário e corrigir
                                    falhas
                                </li>
                                <li><strong>Desenvolvimento:</strong> Testar e desenvolver novas funcionalidades</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Finalidades secundárias:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li><strong>Análise estatística:</strong> Gerar estatísticas anônimas sobre o uso do site</li>
                                <li><strong>Segurança:</strong> Detectar e prevenir atividades fraudulentas ou ilegais</li>
                                <li><strong>Cumprimento legal:</strong> Atender obrigações legais e regulamentares</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4 border-l-4 border-green-400 bg-green-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            <strong>Garantia:</strong> Não utilizamos os dados para fins comerciais, publicitários ou de compartilhamento com
                            terceiros para fins comerciais. Todos os dados são utilizados exclusivamente para a prestação dos serviços de telemedicina
                            e cumprimento de obrigações legais (LGPD, CFM).
                        </p>
                    </div>
                </section>

                <!-- Seção 5: Armazenamento e Segurança dos Dados -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">5. Armazenamento e Segurança dos Dados</h2>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Local de Armazenamento:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Os dados coletados são armazenados de forma segura em serviços de hospedagem confiáveis, que podem estar localizados no Brasil
                        ou em outros países. Ao utilizar nosso site, você consente com a transferência de seus dados para esses locais.
                    </p>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Medidas de Segurança:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Empregamos medidas técnicas e organizacionais razoáveis para proteger as informações contra acesso, uso, alteração ou
                        divulgação não autorizada, incluindo:
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Criptografia de dados sensíveis (como senhas)</li>
                        <li>Uso de protocolos seguros (HTTPS/SSL)</li>
                        <li>Controles de acesso e autenticação</li>
                        <li>Monitoramento regular de segurança</li>
                        <li>Backups regulares dos dados</li>
                        <li>Atualizações de segurança do sistema</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Período de Retenção:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Mantemos seus dados pessoais apenas pelo tempo necessário para cumprir as finalidades descritas nesta política, exceto quando
                        a retenção for exigida ou permitida por lei.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Dados de conta: enquanto sua conta estiver ativa e por um período razoável após o encerramento</li>
                        <li>Dados de uso: geralmente por até 24 meses</li>
                        <li>Dados de logs: por períodos menores, conforme necessário para segurança</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Exclusão de Dados:</h3>
                    <p class="leading-relaxed text-gray-700">
                        O usuário pode solicitar, a qualquer momento, a exclusão total de seus dados pessoais de nossos registros. Após a solicitação,
                        excluiremos os dados dentro de um prazo razoável, exceto quando a retenção for necessária por obrigações legais.
                    </p>
                </section>

                <!-- Seção 6: Base Legal para Tratamento -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">6. Base Legal para Tratamento</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Conforme estabelecido pela <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>, o tratamento de dados
                        pessoais realizado neste site se baseia principalmente no <strong>consentimento do titular</strong> (art. 7º, inciso I, da
                        LGPD).
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Bases legais que utilizamos (art. 7º da LGPD):</strong>
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>Consentimento do titular (inciso I):</strong> Você consente expressamente com o tratamento de seus dados ao
                            utilizar nosso site
                        </li>
                        <li><strong>Execução de contrato (inciso V):</strong> Para cumprir obrigações decorrentes do uso do serviço</li>
                        <li><strong>Legítimo interesse (inciso IX):</strong> Para melhorar nossos serviços e garantir a segurança do site</li>
                        <li><strong>Cumprimento de obrigação legal (inciso II):</strong> Quando exigido por lei ou ordem judicial</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        <strong>Revogação do Consentimento:</strong> O usuário pode retirar o consentimento a qualquer momento, bastando solicitar via
                        e-mail de contato. A revogação do consentimento não afeta a legalidade do tratamento realizado com base no consentimento
                        anterior à revogação.
                    </p>
                </section>

                <!-- Seção 7: Consentimento para Telemedicina -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">7. Consentimento para Telemedicina</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Em conformidade com a <strong>Resolução CFM nº 2.314/2022</strong>, Art. 4 e 5, toda teleconsulta requer o consentimento livre
                        e esclarecido do paciente antes do início do atendimento.
                    </p>
                    <div class="mb-4 border-l-4 border-blue-500 bg-blue-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            Antes de agendar uma consulta, você deverá ler e aceitar o
                            <Link :href="tcle().url" class="font-medium text-primary underline underline-offset-2 hover:text-primary/80">
                                Termo de Consentimento Livre e Esclarecido (TCLE)
                            </Link>
                            para Telemedicina. O aceite é registrado com data, hora e versão do termo.
                        </p>
                    </div>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Tipos de consentimento gerenciados pela plataforma:</strong>
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Telemedicina:</strong> Consentimento para realização de teleconsultas</li>
                        <li><strong>Gravação de consultas:</strong> Opcional — consentimento específico e separado</li>
                        <li><strong>Compartilhamento com laboratórios:</strong> Para integração de resultados de exames</li>
                        <li><strong>Envio à RNDS:</strong> Para notificação ao sistema nacional de saúde (quando aplicável)</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Você pode gerenciar e revogar seus consentimentos a qualquer momento em
                        <strong>Configurações → Privacidade → Gerenciar Consentimentos</strong>.
                    </p>
                </section>

                <!-- Seção 8: Prontuário Eletrônico -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">8. Prontuário Eletrônico</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        O prontuário eletrônico é regido pela <strong>Resolução CFM nº 1.821/2007</strong> e pela LGPD.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>Imutabilidade:</strong> Registros do prontuário não podem ser alterados após criação — apenas novos registros
                            podem ser adicionados (CFM Res. 1.821/2007)
                        </li>
                        <li>
                            <strong>Retenção:</strong> Prontuários são mantidos por <strong>20 anos</strong> a partir da última consulta (CFM Res.
                            1.821/2007, art. 8)
                        </li>
                        <li>
                            <strong>Acesso:</strong> Restrito ao paciente titular, médico responsável pelo atendimento e responsável legal de menor de
                            idade
                        </li>
                        <li>
                            <strong>Auditoria:</strong> Todo acesso ao prontuário é registrado com data, hora, usuário e IP (trilha de auditoria LGPD)
                        </li>
                        <li>
                            <strong>Exportação:</strong> O paciente pode exportar seu prontuário em PDF a qualquer momento; documentos assinados
                            digitalmente com ICP-Brasil têm validade legal plena
                        </li>
                    </ul>
                </section>

                <!-- Seção 9: Gravação de Consultas -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">9. Gravação de Consultas</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        A gravação de teleconsultas é <strong>opcional</strong> e requer consentimento específico e separado do consentimento geral de
                        telemedicina.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Nenhuma consulta é gravada sem consentimento explícito do paciente</li>
                        <li>Gravações são armazenadas com criptografia em ambiente seguro</li>
                        <li>Acesso restrito ao paciente e ao médico responsável pelo atendimento</li>
                        <li>Retenção: até <strong>5 anos</strong> após a data da consulta</li>
                        <li>O consentimento para gravação pode ser revogado a qualquer momento em <strong>Configurações → Privacidade</strong></li>
                        <li>A revogação não afeta gravações já realizadas com consentimento anterior</li>
                    </ul>
                </section>

                <!-- Seção 10: Documentos Médicos Digitais -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">10. Documentos Médicos Digitais</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Prescrições, atestados e laudos emitidos pela plataforma são documentos médicos digitais regidos pela
                        <strong>Resolução CFM nº 2.314/2022, Art. 8</strong>.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>Validade legal plena:</strong> Documentos assinados digitalmente com certificado ICP-Brasil têm validade jurídica
                            equivalente ao documento físico
                        </li>
                        <li>
                            <strong>Verificação pública:</strong> Autenticidade verificável em <strong>/verify/{código}</strong> impresso no documento
                        </li>
                        <li>
                            <strong>Documentos sem ICP-Brasil:</strong> Têm validade apenas para uso interno da plataforma até a implementação da
                            assinatura digital
                        </li>
                        <li><strong>Retenção:</strong> Documentos médicos são mantidos pelo mesmo prazo do prontuário (20 anos)</li>
                    </ul>
                    <div class="mt-4 border-l-4 border-blue-500 bg-blue-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            A implementação da assinatura digital ICP-Brasil está em andamento. Documentos emitidos antes da ativação do certificado
                            digital serão retroativamente assinados quando disponível.
                        </p>
                    </div>
                </section>

                <!-- Seção 11: Protocolo de Emergências -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">11. Protocolo de Emergências</h2>
                    <div class="mb-4 border-l-4 border-red-500 bg-red-50 p-4">
                        <p class="mb-2 leading-relaxed font-semibold text-gray-700">Em caso de emergência médica:</p>
                        <ul class="list-none space-y-1 text-gray-700">
                            <li><strong>SAMU: 192</strong> — Serviço de Atendimento Móvel de Urgência</li>
                            <li><strong>Bombeiros: 193</strong> — Resgate e emergências</li>
                            <li><strong>CVV: 188</strong> — Centro de Valorização da Vida (24h)</li>
                        </ul>
                    </div>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Esta plataforma <strong>não substitui</strong> atendimento de emergência presencial</li>
                        <li>Em situações de risco de vida, ligue imediatamente para o SAMU (192)</li>
                        <li>
                            O médico tem obrigação ética de encaminhar o paciente ao serviço de emergência presencial quando necessário (CFM Res.
                            2.217/2018)
                        </li>
                        <li>Teleconsultas não são adequadas para diagnóstico e tratamento de emergências</li>
                    </ul>
                </section>

                <!-- Seção 12: Responsabilidades do Médico -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">12. Responsabilidades do Médico</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Os médicos cadastrados na plataforma assumem as seguintes responsabilidades perante o CFM e a legislação vigente:
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>CRM ativo e regular:</strong> Registro no Conselho Regional de Medicina da UF de atuação obrigatório e em situação
                            regular
                        </li>
                        <li>
                            <strong>Responsabilidade clínica:</strong> Responsabilidade integral pelo diagnóstico, prescrição e conduta clínica
                            adotada na teleconsulta
                        </li>
                        <li>
                            <strong>Sigilo médico:</strong> Dever absoluto de sigilo sobre informações do paciente (CFM Res. 2.217/2018, art. 73-79)
                        </li>
                        <li><strong>Prontuário:</strong> Obrigação de manter o prontuário eletrônico atualizado e completo após cada atendimento</li>
                        <li><strong>Emergências:</strong> Obrigação ética de encaminhar ao serviço presencial em casos de urgência ou emergência</li>
                        <li>
                            <strong>Limites da telemedicina:</strong> Responsabilidade por identificar situações que exigem exame físico presencial
                        </li>
                    </ul>
                </section>

                <!-- Seção 13: Uso de Cookies e Tecnologias Similares -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">13. Uso de Cookies e Tecnologias Similares</h2>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">O que são Cookies?</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Cookies são pequenos arquivos de texto armazenados em seu dispositivo quando você visita um site. Eles ajudam o site a lembrar
                        suas preferências e melhorar sua experiência de navegação.
                    </p>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Tipos de Cookies que Utilizamos:</h3>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Cookies essenciais:</strong> Necessários para o funcionamento básico do site (autenticação, segurança)</li>
                        <li><strong>Cookies de funcionalidade:</strong> Permitem que o site lembre suas preferências e escolhas</li>
                        <li><strong>Cookies de análise:</strong> Ajudam a entender como os usuários interagem com o site (dados anônimos)</li>
                        <li><strong>Cookies de sessão:</strong> Temporários, deletados quando você fecha o navegador</li>
                        <li><strong>Cookies persistentes:</strong> Permanecem no dispositivo por um período determinado</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Outras Tecnologias:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">Além de cookies, podemos utilizar tecnologias similares como:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Local Storage e Session Storage (armazenamento local do navegador)</li>
                        <li>Pixel tags ou web beacons (imagens invisíveis para rastreamento)</li>
                        <li>Fingerprinting do navegador (identificação através de características do dispositivo)</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Gerenciamento de Cookies:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Você pode configurar seu navegador para recusar todos os cookies, ser notificado quando um cookie for enviado ou aceitar
                        apenas cookies de determinados sites.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Importante:</strong> O bloqueio ou desativação de cookies pode afetar o funcionamento de algumas partes do site,
                        incluindo a capacidade de fazer login ou utilizar certas funcionalidades.
                    </p>
                    <p class="leading-relaxed text-gray-700">
                        Para mais informações sobre como gerenciar cookies, consulte as configurações do seu navegador:
                    </p>
                    <ul class="mt-2 ml-4 list-inside list-disc space-y-1 leading-relaxed text-gray-700">
                        <li>Google Chrome: Configurações → Privacidade e segurança → Cookies</li>
                        <li>Mozilla Firefox: Opções → Privacidade e segurança → Cookies e dados do site</li>
                        <li>Safari: Preferências → Privacidade → Gerenciar dados do site</li>
                        <li>Microsoft Edge: Configurações → Cookies e permissões do site</li>
                    </ul>
                </section>

                <!-- Seção 14: Compartilhamento de Dados -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">14. Compartilhamento de Dados</h2>

                    <div class="mb-4 border-l-4 border-green-400 bg-green-50 p-4">
                        <p class="leading-relaxed font-semibold text-gray-700">
                            <strong
                                >✅ NÃO compartilhamos, vendemos, alugamos ou comercializamos informações pessoais identificáveis com terceiros para
                                fins comerciais.</strong
                            >
                        </p>
                    </div>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Prestadores de Serviços:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Podemos compartilhar dados com prestadores de serviços terceirizados que nos ajudam a operar o site, sempre sob nossa
                        supervisão e em conformidade com a LGPD:
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Serviços de hospedagem:</strong> Para armazenar e processar dados do site</li>
                        <li><strong>Serviços de análise:</strong> Para entender o uso do site (dados geralmente anonimizados)</li>
                        <li><strong>Serviços de backup:</strong> Para manter cópias de segurança dos dados</li>
                        <li><strong>Serviços de segurança:</strong> Para proteger o site contra ameaças</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Requisitos Legais:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">Podemos divulgar dados pessoais quando necessário para:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Cumprir obrigações legais ou processos judiciais</li>
                        <li>Proteger nossos direitos, propriedade ou segurança</li>
                        <li>Proteger os direitos, propriedade ou segurança de outros usuários</li>
                        <li>Prevenir atividades fraudulentas ou ilegais</li>
                        <li>Responder a solicitações de autoridades governamentais</li>
                    </ul>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Transferência de Dados:</h3>
                    <p class="leading-relaxed text-gray-700">
                        Em caso de fusão, aquisição ou venda de ativos, seus dados pessoais podem ser transferidos como parte da transação, mas
                        continuarão sendo protegidos de acordo com esta política.
                    </p>
                </section>

                <!-- Seção 15: Direitos do Usuário (Titular dos Dados) -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">15. Direitos do Usuário (Titular dos Dados)</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Nos termos da <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>, especificamente no artigo 18, você,
                        como titular dos dados pessoais, tem os seguintes direitos garantidos por lei:
                    </p>

                    <div class="space-y-3">
                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Direitos Fundamentais:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li><strong>Confirmação e acesso:</strong> Confirmar a existência de tratamento e acessar seus dados pessoais</li>
                                <li><strong>Correção:</strong> Corrigir dados incompletos, inexatos ou desatualizados</li>
                                <li>
                                    <strong>Anonimização, bloqueio ou eliminação:</strong> Solicitar a anonimização, bloqueio ou eliminação de dados
                                    desnecessários, excessivos ou tratados em desconformidade com a LGPD
                                </li>
                                <li>
                                    <strong>Portabilidade:</strong> Solicitar a portabilidade dos dados para outro prestador de serviço (quando
                                    aplicável)
                                </li>
                                <li><strong>Eliminação:</strong> Solicitar a eliminação dos dados pessoais tratados com seu consentimento</li>
                                <li><strong>Revogação do consentimento:</strong> Revogar o consentimento a qualquer momento</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800">Direitos Adicionais:</h3>
                            <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                                <li>
                                    <strong>Informação:</strong> Obter informações sobre as entidades públicas e privadas com as quais compartilhamos
                                    dados
                                </li>
                                <li><strong>Oposição:</strong> Opor-se ao tratamento de dados em certas circunstâncias</li>
                                <li>
                                    <strong>Revisão de decisões:</strong> Solicitar revisão de decisões tomadas unicamente com base em tratamento
                                    automatizado
                                </li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="mt-4 mb-2 text-lg font-semibold text-gray-800">Como Exercer Seus Direitos:</h3>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Para exercer qualquer um desses direitos, envie uma solicitação por e-mail para
                        <a :href="`mailto:${emailContact}`" class="font-medium text-primary underline underline-offset-2 hover:text-primary/80">
                            {{ emailContact }} </a
                        >, incluindo:
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Seu nome completo e e-mail cadastrado</li>
                        <li>Descrição clara do direito que deseja exercer</li>
                        <li>Identificação do pedido (quando aplicável)</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Responderemos sua solicitação em até 15 (quinze) dias, podendo ser prorrogado por mais 15 dias mediante justificativa. Em
                        alguns casos, podemos solicitar informações adicionais para verificar sua identidade e proteger seus dados.
                    </p>
                </section>

                <!-- Seção 16: Proteção de Dados de Menores -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">16. Proteção de Dados de Menores</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Nosso site não é destinado a menores de 18 anos. Não coletamos intencionalmente dados pessoais de menores de idade.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Se você é menor de 18 anos, deve ter o consentimento de um responsável legal (pais ou tutores) antes de utilizar este site ou
                        fornecer quaisquer dados pessoais.
                    </p>
                    <p class="leading-relaxed text-gray-700">
                        Se tomarmos conhecimento de que coletamos dados de um menor sem o consentimento adequado, tomaremos medidas para excluir essas
                        informações imediatamente.
                    </p>
                </section>

                <!-- Seção 17: Transferência Internacional de Dados -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">17. Transferência Internacional de Dados</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Seus dados pessoais podem ser transferidos e armazenados em servidores localizados fora do Brasil, onde as leis de proteção de
                        dados podem ser diferentes.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">Ao utilizar nosso site, você consente com essa transferência. Garantimos que:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Os prestadores de serviços terceirizados seguem padrões adequados de proteção de dados</li>
                        <li>Mantemos salvaguardas apropriadas para proteger seus dados</li>
                        <li>Respeitamos os requisitos da LGPD mesmo quando os dados estão em outros países</li>
                    </ul>
                </section>

                <!-- Seção 18: Alterações na Política -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">18. Alterações na Política</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Podemos atualizar esta Política de Privacidade periodicamente para refletir mudanças em nossas práticas, serviços ou
                        requisitos legais.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">Quando fizermos alterações significativas, notificaremos você por:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Publicação de uma notificação no site</li>
                        <li>Envio de um e-mail para o endereço cadastrado (para alterações importantes)</li>
                        <li>Atualização da data de "Última atualização" no início desta política</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        A versão mais recente estará sempre disponível nesta página. Recomendamos que você revise esta política periodicamente para se
                        manter informado sobre como protegemos seus dados.
                    </p>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        O uso continuado do site após alterações constitui aceitação da política revisada. Se você não concordar com as alterações,
                        deve descontinuar o uso do site e pode solicitar a exclusão de seus dados.
                    </p>
                </section>

                <!-- Seção 19: Links para Outros Sites -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">19. Links para Outros Sites</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Nosso site pode conter links para sites de terceiros que não são operados por nós. Esta Política de Privacidade não se aplica
                        a sites de terceiros.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Não temos controle sobre e não assumimos responsabilidade pelo conteúdo, políticas de privacidade ou práticas de sites de
                        terceiros.
                    </p>
                    <p class="leading-relaxed text-gray-700">
                        Recomendamos fortemente que você revise as políticas de privacidade de qualquer site de terceiros que visitar.
                    </p>
                </section>

                <!-- Seção 20: Segurança e Notificação de Violações -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">20. Segurança e Notificação de Violações</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Conforme exigido pela <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>, implementamos medidas de
                        segurança técnicas e organizacionais adequadas para proteger seus dados pessoais contra acesso não autorizado, alteração,
                        divulgação ou destruição.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        No entanto, nenhum método de transmissão pela Internet ou método de armazenamento eletrônico é 100% seguro. Embora nos
                        esforcemos para usar meios comercialmente aceitáveis para proteger seus dados, não podemos garantir segurança absoluta.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Notificação de Violações (art. 48 da LGPD):</strong> Em caso de violação de dados pessoais que possa acarretar risco
                        ou dano relevante aos titulares, notificaremos:
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Você (titular dos dados):</strong> Notificaremos sobre o incidente e as medidas adotadas, em prazo razoável</li>
                        <li>
                            <strong>Autoridade Nacional de Proteção de Dados (ANPD):</strong> Comunicaremos o incidente dentro do prazo estabelecido
                            pela LGPD
                        </li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        A notificação incluirá a natureza dos dados afetados, informações sobre os titulares envolvidos, indicação das medidas
                        técnicas e de segurança utilizadas para proteger os dados, os riscos relacionados ao incidente e as medidas que foram ou serão
                        adotadas.
                    </p>
                </section>

                <!-- Seção 21: Contato / Encarregado LGPD -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">21. Contato / Encarregado LGPD</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Se você tiver dúvidas, solicitações, reclamações ou desejar exercer seus direitos relacionados à privacidade e proteção de
                        dados, entre em contato com o responsável pelo tratamento de dados:
                    </p>
                    <div class="mt-4 space-y-2 rounded-lg bg-gray-50 p-4">
                        <p class="leading-relaxed text-gray-700"><strong>Responsável pelo Tratamento de Dados:</strong> Audri Rian</p>
                        <p class="leading-relaxed text-gray-700">
                            <strong>E-mail:</strong>
                            <a :href="`mailto:${emailContact}`" class="font-medium text-primary underline underline-offset-2 hover:text-primary/80">
                                {{ emailContact }}
                            </a>
                        </p>
                        <p class="leading-relaxed text-gray-700"><strong>Prazo de Resposta:</strong> Tentaremos responder em até 15 dias úteis.</p>
                        <p class="mt-4 leading-relaxed text-gray-700">
                            <strong>Autoridade Nacional de Proteção de Dados (ANPD):</strong><br />
                            Em caso de insatisfação com o tratamento de seus dados, você também pode entrar em contato com a ANPD através do site
                            <a
                                href="https://www.gov.br/anpd/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary underline underline-offset-2 hover:text-primary/80"
                            >
                                www.gov.br/anpd </a
                            >.
                        </p>
                    </div>
                </section>

                <!-- Footer da página -->
                <div class="mt-8 border-t border-gray-200 pt-8">
                    <p class="text-center text-sm text-gray-500">
                        Ao utilizar este site, você confirma que leu, compreendeu e concorda com esta Política de Privacidade.
                    </p>
                </div>
            </div>

            <!-- Botão de volta -->
            <div class="mt-8 text-center">
                <Link
                    :href="login()"
                    class="inline-flex items-center rounded-lg bg-primary px-6 py-3 font-medium text-black transition-colors duration-200 hover:bg-primary/90"
                >
                    Voltar para Login
                </Link>
            </div>
        </div>
    </div>
</template>

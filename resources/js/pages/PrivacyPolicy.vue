<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';

const emailContact = 'audririan1@gmail.com';
const lastUpdate = 'Janeiro de 2026';
</script>

<template>

    <Head title="Política de Privacidade" />

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8 text-center">
                <Link :href="login()" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-6">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para login
                </Link>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Política de Privacidade</h1>
                <p class="text-lg text-gray-600">Projeto Telemedicina Para Todos</p>
                <p class="text-sm text-gray-500 mt-2">Última atualização: {{ lastUpdate }}</p>
            </div>

            <!-- Content Card -->
            <div class="bg-white rounded-lg shadow-lg p-8 md:p-12 space-y-8">

                <!-- Seção 1: Introdução -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Introdução</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Bem-vindo à Política de Privacidade do Projeto Telemedicina Para Todos ("nós", "nosso site").
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Este documento explica como tratamos as informações pessoais de nossos visitantes e usuários, em
                        conformidade com a Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD).
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-4">
                        <p class="text-gray-700 leading-relaxed font-semibold mb-2">
                            <strong>📋 Conformidade com a LGPD:</strong>
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            Esta Política está em conformidade com a <strong>Lei nº 13.709/2018 — Lei Geral de Proteção
                                de Dados Pessoais (LGPD)</strong>, que garante os direitos de privacidade e proteção de
                            dados dos usuários no Brasil. A LGPD estabelece regras claras sobre como dados pessoais
                            devem ser coletados, armazenados, tratados e compartilhados, assegurando transparência e
                            segurança no tratamento de informações pessoais.
                        </p>
                    </div>
                </section>

                <!-- Seção 1.1: Sobre a LGPD -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Sobre a Lei Geral de Proteção de Dados (LGPD)
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        A <strong>Lei Geral de Proteção de Dados Pessoais (LGPD)</strong> — Lei nº 13.709/2018 — é a
                        legislação brasileira que regula o tratamento de dados pessoais, tanto no ambiente físico quanto
                        digital. Esta lei tem como objetivo proteger os direitos fundamentais de liberdade e
                        privacidade, além de garantir o livre desenvolvimento da personalidade da pessoa natural.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Princípios da LGPD que seguimos:</strong>
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Finalidade:</strong> Tratamos dados apenas para finalidades específicas e informadas
                        </li>
                        <li><strong>Adequação:</strong> Os dados são compatíveis com as finalidades informadas</li>
                        <li><strong>Necessidade:</strong> Coletamos apenas os dados necessários para as finalidades</li>
                        <li><strong>Transparência:</strong> Informamos claramente sobre o tratamento de dados</li>
                        <li><strong>Segurança:</strong> Adotamos medidas para proteger os dados</li>
                        <li><strong>Prevenção:</strong> Tomamos medidas para prevenir danos</li>
                        <li><strong>Não discriminação:</strong> Não realizamos tratamento para fins discriminatórios
                        </li>
                        <li><strong>Responsabilização:</strong> Demonstramos conformidade com a LGPD</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Ao utilizar nosso site, você pode ter a certeza de que seus dados pessoais são tratados de
                        acordo com todos os princípios e requisitos estabelecidos pela LGPD, garantindo sua privacidade
                        e proteção.
                    </p>
                </section>

                <!-- Seção 3: Quais Dados Coletamos -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Quais Dados Coletamos</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Podemos coletar as seguintes informações quando você utiliza nosso site:
                    </p>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Dados fornecidos voluntariamente pelo
                                usuário:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Dados de identificação:</strong> Nome completo, e-mail, data de nascimento
                                </li>
                                <li><strong>Dados de perfil:</strong> Gênero, telefone, informações de perfil médico
                                    (apenas para testes)</li>
                                <li><strong>Dados de autenticação:</strong> Senha (criptografada), tokens de sessão</li>
                                <li><strong>Dados de comunicação:</strong> Mensagens enviadas através do site, feedback,
                                    suporte</li>
                                <li><strong>Dados de formulários:</strong> Informações preenchidas em formulários de
                                    registro, consulta ou contato</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Dados coletados automaticamente:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Dados técnicos:</strong> Endereço IP, tipo de navegador, sistema
                                    operacional, resolução de tela</li>
                                <li><strong>Dados de navegação:</strong> Páginas visitadas, tempo de permanência, links
                                    clicados, origem do acesso</li>
                                <li><strong>Dados de dispositivo:</strong> Tipo de dispositivo (desktop, mobile,
                                    tablet), identificador do dispositivo</li>
                                <li><strong>Dados de uso:</strong> Horários de acesso, frequência de uso,
                                    funcionalidades utilizadas</li>
                                <li><strong>Cookies e tecnologias similares:</strong> Dados armazenados em cookies,
                                    local storage, session storage</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Dados de terceiros (quando aplicável):
                            </h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li>Dados fornecidos por serviços de autenticação de terceiros (se houver integração)
                                </li>
                                <li>Dados de serviços de análise (anônimos e agregados)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-4">
                        <p class="text-gray-700 leading-relaxed">
                            <strong>⚠️ Importante:</strong> Nenhum dado sensível (como CPF, RG, dados médicos reais,
                            informações bancárias ou de cartão de crédito) é solicitado intencionalmente. Este é um
                            projeto experimental e não coletamos informações médicas reais.
                        </p>
                    </div>
                </section>

                <!-- Seção 4: Finalidade do Uso dos Dados -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Finalidade do Uso dos Dados</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Utilizamos os dados coletados exclusivamente para as seguintes finalidades:
                    </p>

                    <div class="space-y-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Finalidades principais:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Funcionamento do serviço:</strong> Permitir o funcionamento básico do site e
                                    das funcionalidades de teste e demonstração</li>
                                <li><strong>Autenticação e segurança:</strong> Gerenciar contas de usuários,
                                    autenticação e prevenção de fraudes</li>
                                <li><strong>Comunicação:</strong> Entrar em contato quando solicitado (ex: formulários
                                    de feedback, suporte técnico)</li>
                                <li><strong>Melhoria do serviço:</strong> Analisar o uso do site para melhorar a
                                    experiência do usuário e corrigir falhas</li>
                                <li><strong>Desenvolvimento:</strong> Testar e desenvolver novas funcionalidades</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Finalidades secundárias:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Análise estatística:</strong> Gerar estatísticas anônimas sobre o uso do
                                    site</li>
                                <li><strong>Segurança:</strong> Detectar e prevenir atividades fraudulentas ou ilegais
                                </li>
                                <li><strong>Cumprimento legal:</strong> Atender obrigações legais e regulamentares</li>
                            </ul>
                        </div>
                    </div>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4">
                        <p class="text-gray-700 leading-relaxed">
                            <strong>✅ Garantia:</strong> Não utilizamos os dados para fins comerciais, publicitários, de
                            marketing direto ou de compartilhamento com terceiros para fins comerciais. Todos os dados
                            são utilizados exclusivamente para o funcionamento e melhoria deste projeto experimental.
                        </p>
                    </div>
                </section>

                <!-- Seção 5: Armazenamento e Segurança dos Dados -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Armazenamento e Segurança dos Dados</h2>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Local de Armazenamento:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Os dados coletados são armazenados de forma segura em serviços de hospedagem confiáveis, que
                        podem estar localizados no Brasil ou em outros países. Ao utilizar nosso site, você consente com
                        a transferência de seus dados para esses locais.
                    </p>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Medidas de Segurança:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Empregamos medidas técnicas e organizacionais razoáveis para proteger as informações contra
                        acesso, uso, alteração ou divulgação não autorizada, incluindo:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Criptografia de dados sensíveis (como senhas)</li>
                        <li>Uso de protocolos seguros (HTTPS/SSL)</li>
                        <li>Controles de acesso e autenticação</li>
                        <li>Monitoramento regular de segurança</li>
                        <li>Backups regulares dos dados</li>
                        <li>Atualizações de segurança do sistema</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Período de Retenção:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Mantemos seus dados pessoais apenas pelo tempo necessário para cumprir as finalidades descritas
                        nesta política, exceto quando a retenção for exigida ou permitida por lei.
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Dados de conta: enquanto sua conta estiver ativa e por um período razoável após o
                            encerramento</li>
                        <li>Dados de uso: geralmente por até 24 meses</li>
                        <li>Dados de logs: por períodos menores, conforme necessário para segurança</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Exclusão de Dados:</h3>
                    <p class="text-gray-700 leading-relaxed">
                        O usuário pode solicitar, a qualquer momento, a exclusão total de seus dados pessoais de nossos
                        registros. Após a solicitação, excluiremos os dados dentro de um prazo razoável, exceto quando a
                        retenção for necessária por obrigações legais.
                    </p>
                </section>

                <!-- Seção 6: Base Legal para Tratamento -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Base Legal para Tratamento</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Conforme estabelecido pela <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº
                            13.709/2018)</strong>, o tratamento de dados pessoais realizado neste site se baseia
                        principalmente no <strong>consentimento do titular</strong> (art. 7º, inciso I, da LGPD).
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Bases legais que utilizamos (art. 7º da LGPD):</strong>
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Consentimento do titular (inciso I):</strong> Você consente expressamente com o
                            tratamento de seus dados ao utilizar nosso site</li>
                        <li><strong>Execução de contrato (inciso V):</strong> Para cumprir obrigações decorrentes do uso
                            do serviço</li>
                        <li><strong>Legítimo interesse (inciso IX):</strong> Para melhorar nossos serviços e garantir a
                            segurança do site</li>
                        <li><strong>Cumprimento de obrigação legal (inciso II):</strong> Quando exigido por lei ou ordem
                            judicial</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        <strong>Revogação do Consentimento:</strong> O usuário pode retirar o consentimento a qualquer
                        momento, bastando solicitar via e-mail de contato. A revogação do consentimento não afeta a
                        legalidade do tratamento realizado com base no consentimento anterior à revogação.
                    </p>
                </section>

                <!-- Seção 7: Uso de Cookies e Tecnologias Similares -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Uso de Cookies e Tecnologias Similares</h2>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">O que são Cookies?</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Cookies são pequenos arquivos de texto armazenados em seu dispositivo quando você visita um
                        site. Eles ajudam o site a lembrar suas preferências e melhorar sua experiência de navegação.
                    </p>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Tipos de Cookies que Utilizamos:</h3>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Cookies essenciais:</strong> Necessários para o funcionamento básico do site
                            (autenticação, segurança)</li>
                        <li><strong>Cookies de funcionalidade:</strong> Permitem que o site lembre suas preferências e
                            escolhas</li>
                        <li><strong>Cookies de análise:</strong> Ajudam a entender como os usuários interagem com o site
                            (dados anônimos)</li>
                        <li><strong>Cookies de sessão:</strong> Temporários, deletados quando você fecha o navegador
                        </li>
                        <li><strong>Cookies persistentes:</strong> Permanecem no dispositivo por um período determinado
                        </li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Outras Tecnologias:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Além de cookies, podemos utilizar tecnologias similares como:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Local Storage e Session Storage (armazenamento local do navegador)</li>
                        <li>Pixel tags ou web beacons (imagens invisíveis para rastreamento)</li>
                        <li>Fingerprinting do navegador (identificação através de características do dispositivo)</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Gerenciamento de Cookies:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Você pode configurar seu navegador para recusar todos os cookies, ser notificado quando um
                        cookie for enviado ou aceitar apenas cookies de determinados sites.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Importante:</strong> O bloqueio ou desativação de cookies pode afetar o funcionamento de
                        algumas partes do site, incluindo a capacidade de fazer login ou utilizar certas
                        funcionalidades.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Para mais informações sobre como gerenciar cookies, consulte as configurações do seu navegador:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-1 ml-4 mt-2">
                        <li>Google Chrome: Configurações → Privacidade e segurança → Cookies</li>
                        <li>Mozilla Firefox: Opções → Privacidade e segurança → Cookies e dados do site</li>
                        <li>Safari: Preferências → Privacidade → Gerenciar dados do site</li>
                        <li>Microsoft Edge: Configurações → Cookies e permissões do site</li>
                    </ul>
                </section>

                <!-- Seção 8: Compartilhamento de Dados -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Compartilhamento de Dados</h2>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                        <p class="text-gray-700 leading-relaxed font-semibold">
                            <strong>✅ NÃO compartilhamos, vendemos, alugamos ou comercializamos informações pessoais
                                identificáveis com terceiros para fins comerciais.</strong>
                        </p>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Prestadores de Serviços:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Podemos compartilhar dados com prestadores de serviços terceirizados que nos ajudam a operar o
                        site, sempre sob nossa supervisão e em conformidade com a LGPD:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Serviços de hospedagem:</strong> Para armazenar e processar dados do site</li>
                        <li><strong>Serviços de análise:</strong> Para entender o uso do site (dados geralmente
                            anonimizados)</li>
                        <li><strong>Serviços de backup:</strong> Para manter cópias de segurança dos dados</li>
                        <li><strong>Serviços de segurança:</strong> Para proteger o site contra ameaças</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Requisitos Legais:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Podemos divulgar dados pessoais quando necessário para:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Cumprir obrigações legais ou processos judiciais</li>
                        <li>Proteger nossos direitos, propriedade ou segurança</li>
                        <li>Proteger os direitos, propriedade ou segurança de outros usuários</li>
                        <li>Prevenir atividades fraudulentas ou ilegais</li>
                        <li>Responder a solicitações de autoridades governamentais</li>
                    </ul>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Transferência de Dados:</h3>
                    <p class="text-gray-700 leading-relaxed">
                        Em caso de fusão, aquisição ou venda de ativos, seus dados pessoais podem ser transferidos como
                        parte da transação, mas continuarão sendo protegidos de acordo com esta política.
                    </p>
                </section>

                <!-- Seção 9: Direitos do Usuário (Titular dos Dados) -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Direitos do Usuário (Titular dos Dados)</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Nos termos da <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>,
                        especificamente no artigo 18, você, como titular dos dados pessoais, tem os seguintes direitos
                        garantidos por lei:
                    </p>

                    <div class="space-y-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Direitos Fundamentais:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Confirmação e acesso:</strong> Confirmar a existência de tratamento e
                                    acessar seus dados pessoais</li>
                                <li><strong>Correção:</strong> Corrigir dados incompletos, inexatos ou desatualizados
                                </li>
                                <li><strong>Anonimização, bloqueio ou eliminação:</strong> Solicitar a anonimização,
                                    bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em
                                    desconformidade com a LGPD</li>
                                <li><strong>Portabilidade:</strong> Solicitar a portabilidade dos dados para outro
                                    prestador de serviço (quando aplicável)</li>
                                <li><strong>Eliminação:</strong> Solicitar a eliminação dos dados pessoais tratados com
                                    seu consentimento</li>
                                <li><strong>Revogação do consentimento:</strong> Revogar o consentimento a qualquer
                                    momento</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Direitos Adicionais:</h3>
                            <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                                <li><strong>Informação:</strong> Obter informações sobre as entidades públicas e
                                    privadas com as quais compartilhamos dados</li>
                                <li><strong>Oposição:</strong> Opor-se ao tratamento de dados em certas circunstâncias
                                </li>
                                <li><strong>Revisão de decisões:</strong> Solicitar revisão de decisões tomadas
                                    unicamente com base em tratamento automatizado</li>
                            </ul>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-800 mb-2 mt-4">Como Exercer Seus Direitos:</h3>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Para exercer qualquer um desses direitos, envie uma solicitação por e-mail para
                        <a :href="`mailto:${emailContact}`"
                            class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                            {{ emailContact }}
                        </a>, incluindo:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Seu nome completo e e-mail cadastrado</li>
                        <li>Descrição clara do direito que deseja exercer</li>
                        <li>Identificação do pedido (quando aplicável)</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Responderemos sua solicitação em até 15 (quinze) dias, podendo ser prorrogado por mais 15 dias
                        mediante justificativa. Em alguns casos, podemos solicitar informações adicionais para verificar
                        sua identidade e proteger seus dados.
                    </p>
                </section>

                <!-- Seção 10: Proteção de Dados de Menores -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Proteção de Dados de Menores</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Nosso site não é destinado a menores de 18 anos. Não coletamos intencionalmente dados pessoais
                        de menores de idade.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Se você é menor de 18 anos, deve ter o consentimento de um responsável legal (pais ou tutores)
                        antes de utilizar este site ou fornecer quaisquer dados pessoais.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Se tomarmos conhecimento de que coletamos dados de um menor sem o consentimento adequado,
                        tomaremos medidas para excluir essas informações imediatamente.
                    </p>
                </section>

                <!-- Seção 11: Transferência Internacional de Dados -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Transferência Internacional de Dados</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Seus dados pessoais podem ser transferidos e armazenados em servidores localizados fora do
                        Brasil, onde as leis de proteção de dados podem ser diferentes.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Ao utilizar nosso site, você consente com essa transferência. Garantimos que:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Os prestadores de serviços terceirizados seguem padrões adequados de proteção de dados</li>
                        <li>Mantemos salvaguardas apropriadas para proteger seus dados</li>
                        <li>Respeitamos os requisitos da LGPD mesmo quando os dados estão em outros países</li>
                    </ul>
                </section>

                <!-- Seção 12: Alterações na Política -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Alterações na Política</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Podemos atualizar esta Política de Privacidade periodicamente para refletir mudanças em nossas
                        práticas, serviços ou requisitos legais.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Quando fizermos alterações significativas, notificaremos você por:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Publicação de uma notificação no site</li>
                        <li>Envio de um e-mail para o endereço cadastrado (para alterações importantes)</li>
                        <li>Atualização da data de "Última atualização" no início desta política</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        A versão mais recente estará sempre disponível nesta página. Recomendamos que você revise esta
                        política periodicamente para se manter informado sobre como protegemos seus dados.
                    </p>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        O uso continuado do site após alterações constitui aceitação da política revisada. Se você não
                        concordar com as alterações, deve descontinuar o uso do site e pode solicitar a exclusão de seus
                        dados.
                    </p>
                </section>

                <!-- Seção 13: Links para Outros Sites -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Links para Outros Sites</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Nosso site pode conter links para sites de terceiros que não são operados por nós. Esta Política
                        de Privacidade não se aplica a sites de terceiros.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Não temos controle sobre e não assumimos responsabilidade pelo conteúdo, políticas de
                        privacidade ou práticas de sites de terceiros.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Recomendamos fortemente que você revise as políticas de privacidade de qualquer site de
                        terceiros que visitar.
                    </p>
                </section>

                <!-- Seção 14: Segurança e Notificação de Violações -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">14. Segurança e Notificação de Violações</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Conforme exigido pela <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº
                            13.709/2018)</strong>, implementamos medidas de segurança técnicas e organizacionais
                        adequadas para proteger seus dados pessoais contra acesso não autorizado, alteração, divulgação
                        ou destruição.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        No entanto, nenhum método de transmissão pela Internet ou método de armazenamento eletrônico é
                        100% seguro. Embora nos esforcemos para usar meios comercialmente aceitáveis para proteger seus
                        dados, não podemos garantir segurança absoluta.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Notificação de Violações (art. 48 da LGPD):</strong> Em caso de violação de dados
                        pessoais que possa acarretar risco ou dano relevante aos titulares, notificaremos:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Você (titular dos dados):</strong> Notificaremos sobre o incidente e as medidas
                            adotadas, em prazo razoável</li>
                        <li><strong>Autoridade Nacional de Proteção de Dados (ANPD):</strong> Comunicaremos o incidente
                            dentro do prazo estabelecido pela LGPD</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        A notificação incluirá a natureza dos dados afetados, informações sobre os titulares envolvidos,
                        indicação das medidas técnicas e de segurança utilizadas para proteger os dados, os riscos
                        relacionados ao incidente e as medidas que foram ou serão adotadas.
                    </p>
                </section>

                <!-- Seção 15: Contato / Encarregado LGPD -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">15. Contato / Encarregado LGPD</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Se você tiver dúvidas, solicitações, reclamações ou desejar exercer seus direitos relacionados à
                        privacidade e proteção de dados, entre em contato com o responsável pelo tratamento de dados:
                    </p>
                    <div class="bg-gray-50 rounded-lg p-4 mt-4 space-y-2">
                        <p class="text-gray-700 leading-relaxed">
                            <strong>Responsável pelo Tratamento de Dados:</strong> Audri Rian
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            <strong>E-mail:</strong>
                            <a :href="`mailto:${emailContact}`"
                                class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                                {{ emailContact }}
                            </a>
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            <strong>Prazo de Resposta:</strong> Tentaremos responder em até 15 dias úteis.
                        </p>
                        <p class="text-gray-700 leading-relaxed mt-4">
                            <strong>Autoridade Nacional de Proteção de Dados (ANPD):</strong><br>
                            Em caso de insatisfação com o tratamento de seus dados, você também pode entrar em contato
                            com a ANPD através do site
                            <a href="https://www.gov.br/anpd/" target="_blank" rel="noopener noreferrer"
                                class="text-primary hover:text-primary/80 underline underline-offset-2">
                                www.gov.br/anpd
                            </a>.
                        </p>
                    </div>
                </section>

                <!-- Footer da página -->
                <div class="border-t border-gray-200 pt-8 mt-8">
                    <p class="text-sm text-gray-500 text-center">
                        Ao utilizar este site, você confirma que leu, compreendeu e concorda com esta Política de
                        Privacidade.
                    </p>
                </div>
            </div>

            <!-- Botão de volta -->
            <div class="mt-8 text-center">
                <Link :href="login()"
                    class="inline-flex items-center px-6 py-3 bg-primary text-black font-medium rounded-lg hover:bg-primary/90 transition-colors duration-200">
                    Voltar para Login
                </Link>
            </div>
        </div>
    </div>
</template>

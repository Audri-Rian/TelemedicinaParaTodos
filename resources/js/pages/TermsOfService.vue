<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';

const emailContact = 'audririan1@gmail.com';
</script>

<template>

    <Head title="Termos de Serviço" />

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
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Termos de Serviço</h1>
                <p class="text-lg text-gray-600">Projeto Telemedicina Para Todos</p>
                <p class="text-sm text-gray-500 mt-2">Última atualização: {{ new Date().toLocaleDateString('pt-BR') }}
                </p>
            </div>

            <!-- Content Card -->
            <div class="bg-white rounded-lg shadow-lg p-8 md:p-12 space-y-8">

                <!-- Seção 1: Introdução -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Introdução</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Bem-vindo ao Projeto Telemedicina Para Todos ("nós", "nosso" ou "site").
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Estes Termos de Serviço regulam o uso deste site experimental. Ao acessar ou utilizar este
                        projeto, você concorda integralmente com os termos aqui descritos.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        O tratamento de dados pessoais realizado através deste site é feito em conformidade com a
                        <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>, conforme detalhado
                        em nossa
                        <Link href="/privacy"
                            class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                            Política de Privacidade
                        </Link>.
                    </p>
                </section>

                <!-- Seção 2: Objetivo e Natureza do Site -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Objetivo e Natureza do Site</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Este site é um projeto pessoal em desenvolvimento, criado para fins de demonstração e testes.
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Natureza do site: projeto pessoal, sem fins comerciais.</li>
                        <li>Possíveis falhas ou interrupções podem ocorrer durante o uso.</li>
                        <li>O serviço pode mudar ou ser encerrado a qualquer momento, sem aviso prévio.</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Não há garantias de funcionamento contínuo, disponibilidade ou suporte. O autor pode alterar,
                        suspender ou encerrar o site a qualquer momento, sem aviso prévio.
                    </p>
                </section>

                <!-- Seção 3: Contas de Usuário -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Contas de Usuário</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Para utilizar determinadas funcionalidades do site, você pode precisar criar uma conta. Ao criar
                        uma conta, você concorda em:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Fornecer informações precisas, completas e atualizadas</li>
                        <li>Manter a segurança de sua senha e informações de conta</li>
                        <li>Notificar-nos imediatamente sobre qualquer uso não autorizado de sua conta</li>
                        <li>Ser responsável por todas as atividades que ocorrem sob sua conta</li>
                        <li>Ter pelo menos 18 anos de idade ou ter consentimento de um responsável legal</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Reservamo-nos o direito de suspender ou encerrar contas que violem estes termos ou sejam usadas
                        de forma inadequada.
                    </p>
                </section>

                <!-- Seção 4: Responsabilidades do Usuário -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Responsabilidades do Usuário</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Ao utilizar este site, você se compromete a:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Fornecer informações verdadeiras, precisas e atualizadas</li>
                        <li>Não inserir dados falsos ou de terceiros sem consentimento expresso</li>
                        <li>Não usar identidades falsas ou se passar por outra pessoa</li>
                        <li>Não utilizar sistemas automatizados (bots, scripts, crawlers) sem autorização prévia</li>
                        <li>Respeitar os direitos de propriedade intelectual de terceiros</li>
                        <li>Não interferir ou interromper o funcionamento do site</li>
                        <li>Não tentar acessar áreas restritas do sistema</li>
                        <li>Manter a confidencialidade de suas credenciais de acesso</li>
                    </ul>
                </section>

                <!-- Seção 5: Serviços de Telemedicina -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Serviços de Telemedicina</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Aviso Importante:</strong> Este site é um projeto experimental e de demonstração. Os
                        serviços de telemedicina oferecidos são apenas para fins de teste e não substituem consultas
                        médicas reais.
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Não oferecemos diagnósticos, prescrições ou tratamentos médicos reais</li>
                        <li>As consultas virtuais são simuladas e não têm valor médico real</li>
                        <li>Para emergências médicas, sempre procure atendimento presencial imediato</li>
                        <li>Este serviço não está regulamentado como serviço de saúde real</li>
                        <li>Não nos responsabilizamos por decisões médicas baseadas no uso deste site experimental</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Em caso de necessidade médica real, consulte sempre um profissional de saúde qualificado
                        presencialmente.
                    </p>
                </section>

                <!-- Seção 6: Privacidade e Proteção de Dados -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Privacidade e Proteção de Dados</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        As informações pessoais fornecidas são tratadas de acordo com nossa
                        <Link href="/privacy"
                            class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                            Política de Privacidade
                        </Link>, que faz parte integrante destes Termos de Serviço.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <p class="text-gray-700 leading-relaxed font-semibold mb-2">
                            <strong>📋 Conformidade com a LGPD:</strong>
                        </p>
                        <p class="text-gray-700 leading-relaxed">
                            O tratamento de dados pessoais dos usuários será realizado em conformidade com a <strong>Lei
                                Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>, conforme descrito em
                            nossa Política de Privacidade. Assim, o usuário sabe que os seus dados são tratados conforme
                            a lei brasileira de proteção de dados.
                        </p>
                    </div>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Respeitamos sua privacidade e protegemos seus dados pessoais de acordo com a LGPD</li>
                        <li>Nenhum dado é vendido, alugado ou compartilhado com terceiros para fins comerciais</li>
                        <li>O uso deste site implica consentimento para tratamento de dados conforme a Lei nº
                            13.709/2018 (LGPD)</li>
                        <li>Você tem direitos garantidos pela LGPD, incluindo acesso, correção, exclusão e portabilidade
                            de dados</li>
                        <li>Você pode solicitar acesso, correção ou exclusão de seus dados a qualquer momento</li>
                        <li>Utilizamos medidas de segurança técnicas e organizacionais adequadas para proteger suas
                            informações</li>
                        <li>Em caso de violação de dados, notificaremos você e a ANPD conforme exigido pela LGPD</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Para mais informações sobre como tratamos seus dados em conformidade com a LGPD, consulte nossa
                        <Link href="/privacy"
                            class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                            Política de Privacidade completa
                        </Link>.
                    </p>
                </section>

                <!-- Seção 7: Uso Proibido -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Uso Proibido</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        É estritamente proibido utilizar este site para:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Qualquer atividade ilegal ou não autorizada</li>
                        <li>Realizar tentativas de invasão, hacking, exploração de vulnerabilidades ou ataques</li>
                        <li>Copiar, reproduzir ou fazer engenharia reversa do código-fonte</li>
                        <li>Enviar spam, vírus, malware ou qualquer código malicioso</li>
                        <li>Interferir no funcionamento normal do site ou dos serviços</li>
                        <li>Usar o site para fins comerciais sem autorização</li>
                        <li>Violar direitos de propriedade intelectual de terceiros</li>
                        <li>Coletar dados de outros usuários sem autorização</li>
                        <li>Fazer scraping ou mineração de dados automatizada</li>
                        <li>Simular consultas médicas reais ou oferecer serviços médicos reais</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        A violação destas regras pode resultar em encerramento imediato da conta e possíveis medidas
                        legais.
                    </p>
                </section>

                <!-- Seção 8: Propriedade Intelectual -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Propriedade Intelectual</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Todo o conteúdo disponível neste site — incluindo, mas não se limitando a textos, código-fonte,
                        imagens, logotipos, design, gráficos, ícones, áudios, vídeos, software e compilações de dados —
                        é de propriedade exclusiva de Audri Rian ou de seus licenciadores.
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>É vedada a reprodução, distribuição, modificação ou criação de obras derivadas sem
                            autorização prévia e por escrito</li>
                        <li>Marcações comerciais, nomes comerciais e logotipos são propriedade de seus respectivos
                            proprietários</li>
                        <li>O conteúdo gerado por usuários permanece propriedade do usuário, mas concede licença de uso
                            ao site</li>
                        <li>Qualquer uso não autorizado pode resultar em violação de direitos autorais, marcas
                            registradas ou outras leis</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Se você acredita que seu trabalho foi usado de forma que constitua violação de direitos
                        autorais, entre em contato conosco imediatamente.
                    </p>
                </section>

                <!-- Seção 9: Limitação de Responsabilidade -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Limitação de Responsabilidade</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        <strong>Este site é oferecido "no estado em que se encontra" (AS IS), sem garantias expressas ou
                            implícitas de qualquer tipo.</strong>
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Na medida máxima permitida por lei, o autor não se responsabiliza por:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Danos diretos, indiretos, acidentais, especiais ou consequentes decorrentes do uso ou
                            incapacidade de usar o site</li>
                        <li>Perda de dados, informações ou conteúdo</li>
                        <li>Interrupções, erros, bugs ou falhas no funcionamento</li>
                        <li>Decisões tomadas com base em informações do site</li>
                        <li>Problemas de segurança ou acesso não autorizado</li>
                        <li>Danos resultantes de vírus ou outros componentes maliciosos</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Como este é um projeto experimental, não há garantia de disponibilidade contínua, correção de
                        erros ou suporte técnico. O uso do site é por sua conta e risco.
                    </p>
                </section>

                <!-- Seção 10: Disponibilidade do Serviço -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Disponibilidade do Serviço</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Fazemos esforços para manter o site disponível, mas não garantimos acesso ininterrupto ou livre
                        de erros.
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>O site pode estar temporariamente indisponível devido a manutenção, atualizações ou
                            problemas técnicos</li>
                        <li>Não nos responsabilizamos por interrupções no serviço</li>
                        <li>Podemos modificar, suspender ou descontinuar qualquer parte do serviço a qualquer momento
                        </li>
                        <li>Reservamo-nos o direito de limitar o acesso ao site para determinados usuários ou regiões
                        </li>
                    </ul>
                </section>

                <!-- Seção 11: Cancelamento e Encerramento -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Cancelamento e Encerramento</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Você pode encerrar sua conta a qualquer momento através das configurações da sua conta ou
                        entrando em contato conosco.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Reservamo-nos o direito de suspender ou encerrar sua conta, com ou sem aviso prévio, se:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li>Você violar estes Termos de Serviço</li>
                        <li>Você usar o site de forma fraudulenta ou ilegal</li>
                        <li>Requisitos legais ou regulamentares exigirem</li>
                        <li>Decidirmos descontinuar o serviço</li>
                    </ul>
                    <p class="text-gray-700 leading-relaxed mt-4">
                        Após o encerramento, você pode solicitar a exclusão de seus dados pessoais conforme nossa
                        Política de Privacidade.
                    </p>
                </section>

                <!-- Seção 12: Links Externos -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">12. Links Externos</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        O site pode conter links para sites de terceiros que não são controlados por nós.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Não nos responsabilizamos pelo conteúdo, políticas de privacidade ou práticas de sites de
                        terceiros. O acesso a esses links é por sua conta e risco.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Recomendamos que você leia os termos de serviço e políticas de privacidade de qualquer site de
                        terceiros que visitar.
                    </p>
                </section>

                <!-- Seção 13: Modificações dos Termos -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">13. Modificações dos Termos</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Reservamo-nos o direito de modificar estes Termos de Serviço a qualquer momento. Alterações
                        significativas serão comunicadas através do site ou por e-mail.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        É sua responsabilidade revisar periodicamente estes termos. O uso continuado do site após
                        alterações constitui aceitação dos novos termos.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Se você não concordar com as alterações, deve descontinuar o uso do site e pode solicitar o
                        encerramento de sua conta.
                    </p>
                </section>

                <!-- Seção 14: Lei Aplicável e Jurisdição -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">14. Lei Aplicável e Jurisdição</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Estes Termos de Serviço são regidos pelas leis da República Federativa do Brasil.
                    </p>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Qualquer disputa relacionada a estes termos será resolvida preferencialmente por meio de
                        negociação amigável. Se não for possível, a disputa será submetida ao foro da comarca do
                        domicílio do responsável pelo site.
                    </p>
                    <p class="text-gray-700 leading-relaxed">
                        Em caso de litígio, ambas as partes se comprometem a buscar resolução através de mediação antes
                        de recorrer à justiça.
                    </p>
                </section>

                <!-- Seção 15: Disposições Gerais -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">15. Disposições Gerais</h2>
                    <ul class="list-disc list-inside text-gray-700 leading-relaxed space-y-2 ml-4">
                        <li><strong>Integralidade:</strong> Estes termos, juntamente com a Política de Privacidade,
                            constituem o acordo integral entre você e nós</li>
                        <li><strong>Renúncia:</strong> A falha em exercer qualquer direito não constitui renúncia a tal
                            direito</li>
                        <li><strong>Divisibilidade:</strong> Se qualquer disposição for considerada inválida, as demais
                            disposições permanecerão em vigor</li>
                        <li><strong>Cessão:</strong> Você não pode transferir seus direitos ou obrigações sem nosso
                            consentimento prévio por escrito</li>
                        <li><strong>Idioma:</strong> Em caso de divergência entre versões em diferentes idiomas, a
                            versão em português prevalecerá</li>
                    </ul>
                </section>

                <!-- Seção 16: Contato -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">16. Contato</h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Em caso de dúvidas, solicitações ou reclamações sobre estes Termos de Serviço, entre em contato:
                    </p>
                    <div class="bg-gray-50 rounded-lg p-4 mt-4">
                        <p class="text-gray-700 leading-relaxed">
                            <strong>Responsável:</strong> Audri Rian
                        </p>
                        <p class="text-gray-700 leading-relaxed mt-2">
                            <strong>E-mail:</strong>
                            <a :href="`mailto:${emailContact}`"
                                class="text-primary hover:text-primary/80 underline underline-offset-2 font-medium">
                                {{ emailContact }}
                            </a>
                        </p>
                        <p class="text-gray-700 leading-relaxed mt-2">
                            <strong>Prazo de Resposta:</strong> Tentaremos responder em até 5 dias úteis.
                        </p>
                    </div>
                </section>

                <!-- Footer da página -->
                <div class="border-t border-gray-200 pt-8 mt-8">
                    <p class="text-sm text-gray-500 text-center">
                        Ao utilizar este site, você confirma que leu, compreendeu e concorda com estes Termos de
                        Serviço.
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

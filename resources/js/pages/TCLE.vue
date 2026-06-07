<script setup lang="ts">
import { login, privacy } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

const version = 'v1.0';
const lastUpdate = 'Maio de 2026';
</script>

<template>
    <Head title="Termo de Consentimento Livre e Esclarecido" />

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
                <h1 class="mb-2 text-4xl font-bold text-gray-900">Termo de Consentimento Livre e Esclarecido</h1>
                <p class="text-lg text-gray-600">Telemedicina Para Todos</p>
                <p class="mt-1 text-sm text-gray-500">CFM Resolução 2.314/2022 — Art. 4 e 5</p>
                <p class="mt-1 text-sm text-gray-500">Versão {{ version }} — Última atualização: {{ lastUpdate }}</p>
            </div>

            <!-- Content Card -->
            <div class="space-y-8 rounded-lg bg-white p-8 shadow-lg md:p-12">
                <!-- Seção 1: O que é Telemedicina -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">1. O que é Telemedicina</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Telemedicina é o exercício da medicina mediado por tecnologias de comunicação, conforme definido pela
                        <strong>Resolução CFM nº 2.314/2022</strong>. Permite a realização de consultas, diagnósticos e acompanhamentos à distância,
                        com a mesma responsabilidade ética e legal do atendimento presencial.
                    </p>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        <strong>Modalidades disponíveis nesta plataforma:</strong>
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>Teleconsulta:</strong> Consulta médica realizada remotamente por videoconferência</li>
                        <li><strong>Telediagnóstico:</strong> Análise e interpretação de exames à distância por especialista</li>
                        <li><strong>Telemonitoramento:</strong> Acompanhamento remoto de sinais vitais e evolução clínica</li>
                    </ul>
                </section>

                <!-- Seção 2: Benefícios e Limitações -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">2. Benefícios e Limitações</h2>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Benefícios:</h3>
                    <ul class="mb-4 ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Acesso a especialistas sem necessidade de deslocamento</li>
                        <li>Continuidade do cuidado médico com comodidade e segurança</li>
                        <li>Redução do tempo de espera para consultas</li>
                        <li>Atendimento em regiões com menor disponibilidade de especialistas</li>
                        <li>Registro digital completo do histórico de atendimentos</li>
                    </ul>

                    <h3 class="mb-2 text-lg font-semibold text-gray-800">Limitações:</h3>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>O exame físico é limitado — algumas condições exigem avaliação presencial</li>
                        <li>Emergências médicas requerem atendimento presencial imediato</li>
                        <li>A qualidade da consulta depende da conexão de internet de ambas as partes</li>
                        <li>Determinados procedimentos e exames não podem ser realizados remotamente</li>
                    </ul>
                </section>

                <!-- Seção 3: Seus Direitos como Paciente -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">3. Seus Direitos como Paciente</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">Como paciente desta plataforma, você tem os seguintes direitos garantidos:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Recusar a teleconsulta e optar por atendimento presencial a qualquer momento, sem prejuízo ao seu cuidado</li>
                        <li>Ser informado sobre o médico (nome completo, CRM, UF e especialidade) antes do início da consulta</li>
                        <li>Ter sigilo médico absoluto garantido (CFM Res. 2.217/2018, art. 73-79)</li>
                        <li>Acessar seu prontuário eletrônico completo a qualquer momento</li>
                        <li>Solicitar cópia de todos os documentos emitidos (prescrições, atestados, laudos)</li>
                        <li>Revogar qualquer consentimento a qualquer momento via Configurações → Privacidade</li>
                        <li>Receber encaminhamento ao serviço de emergência presencial quando necessário</li>
                        <li>Ser tratado com dignidade, respeito e sem discriminação</li>
                    </ul>
                </section>

                <!-- Seção 4: Responsabilidades do Médico -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">4. Responsabilidades do Médico</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">O médico que realiza o atendimento assume as seguintes responsabilidades:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li><strong>CRM ativo e regular</strong> na especialidade praticada e na UF de atuação</li>
                        <li><strong>Responsabilidade integral</strong> pelo diagnóstico, prescrição e conduta clínica adotada</li>
                        <li><strong>Sigilo médico absoluto</strong> sobre todas as informações do paciente (CFM Res. 2.217/2018)</li>
                        <li><strong>Manutenção do prontuário</strong> eletrônico atualizado e completo após cada atendimento</li>
                        <li><strong>Encaminhamento imediato</strong> ao serviço presencial em casos de urgência ou emergência</li>
                        <li><strong>Identificação dos limites</strong> da telemedicina e indicação de atendimento presencial quando necessário</li>
                    </ul>
                </section>

                <!-- Seção 5: Privacidade e Segurança -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">5. Privacidade e Segurança</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Seus dados de saúde são tratados com o mais alto nível de proteção, em conformidade com a
                        <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong> e as normas do CFM.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Consultas transmitidas com criptografia durante a sessão</li>
                        <li>Dados de saúde armazenados em ambiente seguro com acesso restrito</li>
                        <li>Acesso ao prontuário restrito ao paciente titular e ao médico responsável pelo atendimento</li>
                        <li>Registro de auditoria de todos os acessos ao prontuário (data, hora, usuário)</li>
                        <li>Prontuário mantido por 20 anos conforme exigência do CFM (Res. 1.821/2007)</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Para informações completas sobre o tratamento de dados, consulte nossa
                        <Link :href="privacy().url" class="font-medium text-primary underline underline-offset-2 hover:text-primary/80">
                            Política de Privacidade </Link
                        >.
                    </p>
                </section>

                <!-- Seção 6: Gravação de Consultas -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">6. Gravação de Consultas</h2>
                    <div class="mb-4 border-l-4 border-blue-500 bg-blue-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            A gravação de consultas é <strong>opcional</strong> e requer consentimento específico e separado. Nenhuma consulta é
                            gravada sem sua autorização explícita.
                        </p>
                    </div>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>O consentimento para gravação é solicitado separadamente antes de cada consulta</li>
                        <li>Gravações são armazenadas com criptografia em ambiente seguro</li>
                        <li>Acesso restrito ao paciente e ao médico responsável pelo atendimento</li>
                        <li>Retenção por até 5 anos após a data da consulta</li>
                        <li>O consentimento pode ser revogado a qualquer momento em <strong>Configurações → Privacidade</strong></li>
                        <li>A revogação não afeta gravações já realizadas com consentimento anterior</li>
                    </ul>
                </section>

                <!-- Seção 7: Validade dos Documentos Médicos -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">7. Validade dos Documentos Médicos</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">
                        Prescrições, atestados e laudos emitidos pela plataforma são regidos pela
                        <strong>Resolução CFM nº 2.314/2022, Art. 8</strong>.
                    </p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>Validade legal plena:</strong> Documentos assinados digitalmente com certificado ICP-Brasil têm validade jurídica
                            equivalente ao documento físico
                        </li>
                        <li><strong>Verificação de autenticidade:</strong> Disponível em <strong>/verify/{código}</strong> impresso no documento</li>
                        <li>
                            <strong>Documentos sem ICP-Brasil:</strong> Têm validade apenas para uso interno da plataforma até a implementação da
                            assinatura digital
                        </li>
                        <li><strong>Retenção:</strong> Documentos médicos são mantidos pelo mesmo prazo do prontuário (20 anos)</li>
                    </ul>
                </section>

                <!-- Seção 8: Emergências -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">8. Emergências</h2>
                    <div class="mb-4 border-l-4 border-red-500 bg-red-50 p-4">
                        <p class="mb-3 leading-relaxed font-semibold text-gray-700">Em caso de emergência médica, ligue imediatamente:</p>
                        <ul class="list-none space-y-2 text-gray-800">
                            <li class="text-lg"><strong>SAMU: 192</strong> — Serviço de Atendimento Móvel de Urgência</li>
                            <li class="text-lg"><strong>Bombeiros: 193</strong> — Resgate e emergências</li>
                            <li class="text-lg"><strong>CVV: 188</strong> — Centro de Valorização da Vida (24h)</li>
                        </ul>
                    </div>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>Esta plataforma <strong>não substitui</strong> atendimento de emergência presencial</li>
                        <li>Em situações de risco de vida, ligue imediatamente para o SAMU (192) antes de qualquer outra ação</li>
                        <li>
                            O médico tem obrigação ética de encaminhar o paciente ao serviço de emergência presencial quando necessário (CFM Res.
                            2.217/2018)
                        </li>
                        <li>Teleconsultas não são adequadas para diagnóstico e tratamento de emergências médicas</li>
                    </ul>
                </section>

                <!-- Seção 9: Consentimento -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">9. Consentimento</h2>
                    <div class="mb-4 border-l-4 border-green-400 bg-green-50 p-4">
                        <p class="leading-relaxed text-gray-700">
                            Ao agendar uma teleconsulta, você declara ter lido, compreendido e concordado com este Termo de Consentimento Livre e
                            Esclarecido (versão <strong>{{ version }}</strong
                            >).
                        </p>
                    </div>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>O consentimento é registrado com data, hora e versão do termo aceita</li>
                        <li>O registro de consentimento é armazenado de forma segura e auditável</li>
                        <li>Você pode revogar o consentimento a qualquer momento — consultas futuras não serão possíveis sem novo consentimento</li>
                        <li>A revogação não afeta atendimentos já realizados</li>
                        <li>Menores de 18 anos requerem consentimento do responsável legal</li>
                    </ul>
                </section>

                <!-- Seção 10: Contato e Revogação -->
                <section>
                    <h2 class="mb-4 text-2xl font-bold text-gray-900">10. Contato e Revogação</h2>
                    <p class="mb-4 leading-relaxed text-gray-700">Para exercer seus direitos ou revogar consentimentos:</p>
                    <ul class="ml-4 list-inside list-disc space-y-2 leading-relaxed text-gray-700">
                        <li>
                            <strong>Revogar consentimentos:</strong> Acesse <strong>Configurações → Privacidade → Gerenciar Consentimentos</strong>
                        </li>
                        <li><strong>Dúvidas e solicitações:</strong> audririan1@gmail.com</li>
                        <li><strong>Prazo de resposta:</strong> Até 15 dias úteis</li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-gray-700">
                        Para informações completas sobre privacidade e proteção de dados, consulte nossa
                        <Link :href="privacy().url" class="font-medium text-primary underline underline-offset-2 hover:text-primary/80">
                            Política de Privacidade </Link
                        >.
                    </p>
                </section>

                <!-- Footer da página -->
                <div class="mt-8 border-t border-gray-200 pt-8">
                    <p class="text-center text-sm text-gray-500">
                        Versão {{ version }} — Última atualização: {{ lastUpdate }} — CFM Resolução 2.314/2022
                    </p>
                    <p class="mt-2 text-center text-sm text-gray-500">Ao agendar uma teleconsulta, você confirma que leu e compreendeu este termo.</p>
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

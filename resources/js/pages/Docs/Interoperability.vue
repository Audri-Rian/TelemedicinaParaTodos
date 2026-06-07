<script setup lang="ts">
import BlankPageLayout from '@/layouts/BlankPageLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Lightbulb, ShieldPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type SnippetLang = 'curl' | 'python' | 'php';

const activeSnippet = ref<SnippetLang>('curl');
const copied = ref(false);

const codeSnippets: Record<SnippetLang, string> = {
    curl: `curl -X GET \\
'https://api.telemedicinaparatodos.com/v2/ServiceRequest/1' \\
-H 'Authorization: Bearer YOUR_API_KEY' \\
-H 'Accept: application/fhir+json'`,
    python: `import requests

url = "https://api.telemedicinaparatodos.com/v2/ServiceRequest/1"
headers = {
    "Authorization": "Bearer YOUR_API_KEY",
    "Accept": "application/fhir+json",
}

response = requests.get(url, headers=headers, timeout=30)
print(response.json())`,
    php: `<?php

$client = new \\GuzzleHttp\\Client();
$response = $client->get(
    'https://api.telemedicinaparatodos.com/v2/ServiceRequest/1',
    [
        'headers' => [
            'Authorization' => 'Bearer YOUR_API_KEY',
            'Accept' => 'application/fhir+json',
        ],
        'timeout' => 30,
    ]
);

echo $response->getBody();`,
};

const activeSnippetCode = computed(() => codeSnippets[activeSnippet.value]);

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(activeSnippetCode.value);
    } catch {
        const el = document.createElement('textarea');
        el.value = activeSnippetCode.value;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    }

    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 1500);
};
</script>

<template>
    <Head title="Documentacao de Interoperabilidade" />

    <BlankPageLayout>
        <section class="min-h-screen w-full space-y-10 bg-[#f7fafb]">
            <section id="introduction" class="rounded-xl border border-border/60 bg-background p-5 md:p-6">
                <div class="grid gap-8 xl:grid-cols-[1.35fr_1fr]">
                    <div class="space-y-7">
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="rounded bg-emerald-100 px-2 py-1 text-[10px] font-bold tracking-wider text-emerald-700 uppercase">
                                    Academic v2
                                </span>
                                <span class="text-xs font-medium text-muted-foreground">Last updated June 2024</span>
                            </div>

                            <h1 class="max-w-3xl text-2xl leading-tight font-bold tracking-tight text-foreground lg:text-3xl">
                                Visao Geral da API de Interoperabilidade
                            </h1>

                            <p class="max-w-3xl text-sm leading-relaxed text-slate-600">
                                Nossa API fornece uma interface programatica robusta para curadoria e gerenciamento de dados clinicos, seguindo
                                estritamente os padroes
                                <span class="font-semibold text-emerald-700">HL7 FHIR R4</span>. Desenvolvida para instituicoes academicas, clinicas e
                                centros de pesquisa que exigem alta fidelidade semantica.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h2 class="text-2xl font-bold tracking-tight text-slate-800">O padrao FHIR</h2>
                            <p class="max-w-3xl text-base leading-relaxed text-slate-500">
                                O Telemedicina Para Todos API expoe recursos FHIR R4 para garantir interoperabilidade universal. Cada endpoint e
                                mapeado para um recurso especifico, permitindo consultas padronizadas para diagnosticos, medicamentos e observacoes.
                            </p>
                        </div>

                        <article class="rounded-xl border border-emerald-100 bg-slate-50 p-5">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <ShieldPlus class="size-5" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-800">Mapeamento de Terminologia</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                        Todos os dados sao automaticamente codificados com LOINC para observacoes, SNOMED CT para diagnosticos e
                                        RxNorm para prescricoes medicas.
                                    </p>
                                </div>
                            </div>
                        </article>

                        <div class="space-y-4 pt-3">
                            <div class="flex items-center gap-3">
                                <span class="rounded-md bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">GET</span>
                                <code class="rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold text-emerald-700">/v2/ServiceRequest/{id}</code>
                            </div>
                            <h3 class="text-2xl font-bold tracking-tight text-slate-800">Consultar Pedidos de Exame</h3>
                            <p class="max-w-3xl text-base leading-relaxed text-slate-500">
                                Recupere detalhes completos de uma requisicao de servico clinico. Este endpoint retorna o recurso ServiceRequest
                                completo, incluindo referencias para o paciente (Subject) e o profissional solicitante (Requester).
                            </p>
                        </div>

                        <div class="space-y-8 pt-4">
                            <section class="space-y-4">
                                <h4 class="text-sm font-bold tracking-[0.2em] text-slate-500 uppercase">Parametros de Path</h4>
                                <div class="rounded-xl border border-slate-200 bg-white">
                                    <div class="grid grid-cols-[1.1fr_1fr] gap-4 border-b border-slate-200 px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="text-2xl leading-none font-bold text-slate-800">id</span>
                                            <span class="text-xs font-semibold text-slate-500">string • Required</span>
                                        </div>
                                        <p class="text-base font-semibold text-slate-500">O identificador unico do recurso FHIR.</p>
                                    </div>
                                </div>
                            </section>

                            <section class="space-y-4">
                                <h4 class="text-sm font-bold tracking-[0.2em] text-slate-500 uppercase">Status Codes</h4>
                                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <div class="grid grid-cols-[0.9fr_2fr] bg-slate-50 px-4 py-3">
                                        <p class="text-sm font-semibold text-slate-500">Code</p>
                                        <p class="text-sm font-semibold text-slate-500">Description</p>
                                    </div>

                                    <div class="grid grid-cols-[0.9fr_2fr] border-t border-slate-200 px-4 py-4">
                                        <p class="text-xl font-bold text-emerald-700">200 OK</p>
                                        <p class="text-base font-semibold text-slate-500">A requisicao foi bem sucedida e o recurso foi retornado.</p>
                                    </div>

                                    <div class="grid grid-cols-[0.9fr_2fr] border-t border-slate-200 px-4 py-4">
                                        <p class="text-xl font-bold text-rose-500">401 Unauthorized</p>
                                        <p class="text-base font-semibold text-slate-500">A chave de API fornecida e invalida ou expirou.</p>
                                    </div>

                                    <div class="grid grid-cols-[0.9fr_2fr] border-t border-slate-200 px-4 py-4">
                                        <p class="text-xl font-bold text-rose-500">404 Not Found</p>
                                        <p class="text-base font-semibold text-slate-500">O ID do recurso solicitado nao existe no sistema.</p>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <article class="overflow-hidden rounded-xl bg-[#101827] shadow-lg">
                            <header class="flex items-center justify-between border-b border-white/10 bg-black/25 px-4 py-3">
                                <div class="flex items-center gap-4 text-xs font-semibold">
                                    <button
                                        type="button"
                                        class="cursor-pointer transition-colors"
                                        :class="activeSnippet === 'curl' ? 'text-cyan-400' : 'text-slate-400 hover:text-slate-200'"
                                        @click="activeSnippet = 'curl'"
                                    >
                                        cURL
                                    </button>
                                    <button
                                        type="button"
                                        class="cursor-pointer transition-colors"
                                        :class="activeSnippet === 'python' ? 'text-cyan-400' : 'text-slate-400 hover:text-slate-200'"
                                        @click="activeSnippet = 'python'"
                                    >
                                        Python
                                    </button>
                                    <button
                                        type="button"
                                        class="cursor-pointer transition-colors"
                                        :class="activeSnippet === 'php' ? 'text-cyan-400' : 'text-slate-400 hover:text-slate-200'"
                                        @click="activeSnippet = 'php'"
                                    >
                                        PHP
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase transition-colors hover:text-slate-200"
                                    @click="copyCode"
                                >
                                    {{ copied ? 'Copied' : 'Copy' }}
                                </button>
                            </header>
                            <pre class="overflow-x-auto px-4 py-4 text-[13px] leading-6 text-slate-100"><code>{{ activeSnippetCode }}</code></pre>
                        </article>

                        <article class="overflow-hidden rounded-xl bg-[#101827] shadow-lg">
                            <header class="flex items-center justify-between border-b border-white/10 bg-black/25 px-4 py-3">
                                <span class="text-[11px] font-semibold tracking-wide text-slate-400 uppercase">Response Body</span>
                                <span class="text-[11px] font-semibold tracking-wide text-emerald-400 uppercase">200 OK</span>
                            </header>
                            <pre class="overflow-x-auto px-4 py-4 text-[13px] leading-6 text-slate-100"><code>{
  "resourceType": "ServiceRequest",
  "id": "sr-9921",
  "status": "active",
  "intent": "order",
  "category": [
    {
      "coding": [
        {
          "system": "http://snomed.info/sct",
          "code": "108252007",
          "display": "Laboratory procedure"
        }
      ]
    }
  ],
  "subject": {
    "reference": "Patient/771"
  }
}</code></pre>
                        </article>

                        <article class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 text-sky-700">
                                    <Lightbulb class="size-5" />
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Dica do Curador</p>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-600">
                                        Sempre inclua o header <code>Accept: application/fhir+json</code> para garantir resposta semantica compativel
                                        com FHIR R4.
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <footer class="border-t border-border/70 bg-[#f7fafb]">
                <div class="w-full px-8 py-10">
                    <div class="grid gap-10 md:grid-cols-3">
                        <section class="space-y-3">
                            <h3 class="text-2xl font-bold tracking-tight text-teal-700">Telemedicina API</h3>
                            <p class="max-w-md text-base leading-relaxed text-slate-500">
                                Elevando os padroes de interoperabilidade medica atraves de engenharia precisa e curadoria editorial de dados.
                            </p>
                        </section>

                        <section class="space-y-3">
                            <h4 class="text-xs font-bold tracking-[0.2em] text-slate-500 uppercase">Proximos passos</h4>
                            <ul class="space-y-2 text-base font-semibold text-teal-700">
                                <li><a href="#" class="hover:underline">Fluxo de Autenticacao OAuth2</a></li>
                                <li><a href="#" class="hover:underline">Webhooks de Resultado</a></li>
                                <li><a href="#" class="hover:underline">Limites de Rate Limit</a></li>
                            </ul>
                        </section>

                        <section class="space-y-3">
                            <h4 class="text-xs font-bold tracking-[0.2em] text-slate-500 uppercase">Suporte</h4>
                            <ul class="space-y-2 text-base font-semibold text-teal-700">
                                <li><a href="#" class="hover:underline">Stack Overflow (Tag: TelemedicinaAPI)</a></li>
                                <li><a href="#" class="hover:underline">Status do Servidor</a></li>
                                <li><a href="#" class="hover:underline">Portal do Desenvolvedor</a></li>
                            </ul>
                        </section>
                    </div>

                    <div class="mt-10 border-t border-border/70 pt-6">
                        <p class="text-xs font-semibold tracking-[0.2em] text-slate-400 uppercase">
                            © 2026 Telemedicina Para Todos API • HL7, FHIR e FLAME sao marcas registradas da Health Level Seven International.
                        </p>
                    </div>
                </div>
            </footer>
        </section>
    </BlankPageLayout>
</template>

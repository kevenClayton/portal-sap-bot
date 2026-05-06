<template>
  <div class="space-y-8">
    <PageHeader
      title="Configuração"
      eyebrow="Bot & regras"
      description="Use as secções ao lado (ou as abas no telemóvel): cada grupo tem o seu botão de guardar. Agendamento, alertas, portal, cidades para aceite de cargas, regras e filtros globais."
    />

    <div v-if="!bot" class="ui-card p-10 text-center">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
        <svg class="h-7 w-7 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z"
          />
        </svg>
      </div>
      <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nenhum bot configurado ainda.</p>
      <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Crie um registo para passar a editar parâmetros e regras.</p>
      <button type="button" class="ui-btn-primary mt-6" @click="criarBot">Criar bot</button>
    </div>

    <template v-else>
      <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
        <!-- Mobile: abas em linha -->
        <div class="lg:hidden">
          <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Secção</p>
          <nav
            class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-2 [scrollbar-width:thin]"
            aria-label="Secções da configuração"
          >
            <button
              v-for="aba in abasConfig"
              :key="aba.id"
              type="button"
              class="shrink-0 rounded-xl border px-3.5 py-2 text-sm font-semibold transition-colors"
              :class="
                abaAtiva === aba.id
                  ? 'border-sky-500/40 bg-sky-500/10 text-sky-900 dark:border-sky-400/30 dark:bg-sky-500/15 dark:text-sky-100'
                  : 'border-zinc-200/80 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-300 dark:hover:bg-zinc-800'
              "
              :aria-current="abaAtiva === aba.id ? 'true' : undefined"
              @click="abaAtiva = aba.id"
            >
              {{ aba.rotuloCurto }}
            </button>
          </nav>
        </div>

        <!-- Desktop: navegação lateral -->
        <nav
          class="hidden shrink-0 lg:block lg:w-56 xl:w-60"
          aria-label="Secções da configuração"
        >
          <div class="ui-card sticky top-4 space-y-0.5 p-2">
            <p class="px-3 pb-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
              Secções
            </p>
            <button
              v-for="aba in abasConfig"
              :key="'side-' + aba.id"
              type="button"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-colors"
              :class="
                abaAtiva === aba.id
                  ? 'bg-sky-500/12 text-sky-950 shadow-sm dark:bg-sky-500/20 dark:text-sky-50'
                  : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800/80'
              "
              :aria-current="abaAtiva === aba.id ? 'page' : undefined"
              @click="abaAtiva = aba.id"
            >
              <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold tabular-nums"
                :class="
                  abaAtiva === aba.id
                    ? 'bg-sky-600 text-white dark:bg-sky-500'
                    : 'bg-zinc-200/80 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'
                "
              >
                {{ aba.indice }}
              </span>
              <span class="min-w-0 leading-snug">{{ aba.rotulo }}</span>
            </button>
          </div>
        </nav>

        <div class="min-w-0 flex-1 space-y-6">
      <div v-show="abaAtiva === 'agendamento'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <h3 class="ui-section-title">
          <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-500/15 text-sky-700 dark:text-sky-300">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </span>
          Agendamento do bot
        </h3>
        <p class="ui-section-desc mb-6 max-w-xl">Intervalo entre ciclos e, se quiser, horário em que o bot pode executar.</p>
        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="ui-label">Intervalo (segundos)</label>
            <input v-model.number="formBot.intervalo" type="number" min="10" max="3600" class="ui-input" />
          </div>
          <div>
            <label class="ui-label">Horário início</label>
            <input v-model="formBot.horario_inicio" type="time" class="ui-input" />
          </div>
          <div>
            <label class="ui-label">Horário fim</label>
            <input v-model="formBot.horario_fim" type="time" class="ui-input" />
          </div>
        </div>
        <button type="button" class="ui-btn-muted mt-6" @click="salvarBot">Salvar agendamento</button>
      </div>

      <div v-show="abaAtiva === 'notificacoes'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <h3 class="ui-section-title">
          <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/15 text-violet-700 dark:text-violet-300">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
              />
            </svg>
          </span>
          Notificações e modo teste
        </h3>
        <p class="ui-section-desc mb-6 max-w-2xl">
          E-mails e WhatsApp quando uma carga for aceite (ou em modo teste). Deixe em branco para desativar. O WhatsApp depende da configuração feita pelo administrador do sistema.
        </p>
        <div class="grid gap-5 lg:grid-cols-2">
          <div class="lg:col-span-2">
            <label class="ui-label">E-mails (um por linha ou separados por vírgula)</label>
            <textarea
              v-model="formParam.emails_text"
              rows="3"
              class="ui-textarea"
              placeholder="operador@empresa.com&#10;supervisor@empresa.com"
            />
          </div>
          <div class="lg:col-span-2">
            <label class="ui-label">WhatsApp — números (E.164, ex. 5511999998888)</label>
            <textarea
              v-model="formParam.whatsapp_text"
              rows="3"
              class="ui-textarea"
              placeholder="5511999998888&#10;5511888887777"
            />
          </div>
          <div
            class="flex items-start gap-3 rounded-xl border border-violet-200/80 bg-violet-50/50 p-4 dark:border-violet-900/40 dark:bg-violet-950/20 lg:col-span-2"
          >
            <input
              id="modo_teste"
              v-model="formParam.modo_teste"
              type="checkbox"
              class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30"
            />
            <label for="modo_teste" class="text-sm leading-snug text-zinc-700 dark:text-zinc-300">
              <span class="font-semibold text-violet-900 dark:text-violet-200">Modo teste</span>
              — não envia aceite ao SAP; apenas registro e notificações (e-mail / WhatsApp se configurados).
            </label>
          </div>
        </div>
        <button type="button" class="ui-btn-primary mt-6 inline-flex items-center gap-2 px-6" @click="salvarParametro">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          Salvar parâmetros
        </button>
      </div>

      <div v-show="abaAtiva === 'portal'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <h3 class="ui-section-title">
          <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H4.5v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"
              />
            </svg>
          </span>
          Acesso ao portal
        </h3>
        <p class="ui-section-desc mb-6 max-w-2xl">
          Utilizador e palavra-passe com que o bot inicia sessão no portal de logística. Se não preencher, serão usadas as credenciais já definidas na instalação do ambiente.
        </p>
        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label class="ui-label">Utilizador</label>
            <input
              v-model="formParam.portal_usuario"
              type="text"
              autocomplete="username"
              class="ui-input"
              placeholder="Opcional"
            />
          </div>
          <div>
            <label class="ui-label">Palavra-passe</label>
            <input
              v-model="formParam.portal_senha"
              type="password"
              autocomplete="new-password"
              class="ui-input"
              placeholder="Deixe em branco para manter a atual"
            />
            <p v-if="formParam.portal_senha_definida" class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
              Já existe uma palavra-passe guardada. Preencha de novo apenas se quiser alterá-la.
            </p>
          </div>
        </div>
        <button type="button" class="ui-btn-primary mt-6 inline-flex items-center gap-2 px-6" @click="salvarParametro">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          Salvar parâmetros
        </button>
      </div>

      <div v-show="abaAtiva === 'cidades'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <h3 class="ui-section-title">
          <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-800 dark:text-emerald-300">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
              />
            </svg>
          </span>
          Cidades permitidas para aceite
        </h3>
        <p class="ui-section-desc mb-3 max-w-2xl">
          Estas listas dizem <span class="font-semibold text-zinc-800 dark:text-zinc-100">para que cidades o bot pode aceitar cargas</span>: só entram no fluxo de aceite as cargas cuja origem e destino respeitam o que configurar aqui (quando a lista desse lado tiver linhas).
        </p>
        <div
          class="mb-6 rounded-xl border border-sky-200/80 bg-sky-50/70 px-4 py-3 text-sm leading-relaxed text-sky-950 dark:border-sky-900/50 dark:bg-sky-950/25 dark:text-sky-100"
        >
          <span class="font-semibold">Como interpretar:</span>
          origem = cidade de recolha da carga; destino = cidade de entrega.
          Se deixar uma lista vazia, o bot <span class="font-medium">não filtra</span> cargas por esse lado (aceita qualquer cidade nesse eixo).
          Vista em planilha: cada linha é uma cidade permitida.
        </div>
        <div class="grid gap-8 lg:grid-cols-2">
          <!-- Planilha: origens -->
          <div class="overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
            <div
              class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/70 bg-emerald-50/90 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/35"
            >
              <div class="min-w-0">
                <span class="block text-sm font-semibold text-emerald-950 dark:text-emerald-100">Origens permitidas</span>
                <span class="mt-0.5 block text-[11px] font-normal leading-snug text-emerald-800/90 dark:text-emerald-200/90">
                  Cidades de origem em que o aceite de cargas é permitido
                </span>
              </div>
              <span
                class="rounded-md border border-emerald-200/80 bg-white/90 px-2.5 py-1 text-xs font-semibold tabular-nums text-emerald-900 dark:border-emerald-800 dark:bg-zinc-900/80 dark:text-emerald-200"
              >
                {{ formParam.cidades_origem_list.length }} linha(s)
              </span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full min-w-[320px] text-left text-sm">
                <thead>
                  <tr class="border-b border-zinc-200 bg-zinc-50/95 dark:border-zinc-700 dark:bg-zinc-800/90">
                    <th class="w-14 px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      N.º
                    </th>
                    <th class="px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Cidade (origem da carga)
                    </th>
                    <th class="w-24 px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Ação
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/90">
                  <tr
                    v-for="(cidadeOrigem, indiceOrigem) in formParam.cidades_origem_list"
                    :key="'o-' + indiceOrigem + cidadeOrigem"
                    class="bg-white transition-colors hover:bg-emerald-50/35 dark:bg-transparent dark:hover:bg-emerald-950/15"
                  >
                    <td class="px-3 py-2.5 tabular-nums text-zinc-500 dark:text-zinc-400">
                      {{ indiceOrigem + 1 }}
                    </td>
                    <td class="px-3 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">
                      {{ cidadeOrigem }}
                    </td>
                    <td class="px-3 py-2.5 text-right">
                      <button
                        type="button"
                        class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline dark:text-red-400 dark:hover:text-red-300"
                        @click="removerCidadeOrigem(indiceOrigem)"
                      >
                        Remover
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!formParam.cidades_origem_list.length">
                    <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                      Nenhuma linha — o aceite <span class="font-medium text-zinc-700 dark:text-zinc-300">não exige</span> cidade de origem específica.
                    </td>
                  </tr>
                </tbody>
                <tfoot class="border-t border-zinc-200 bg-zinc-50/70 dark:border-zinc-700 dark:bg-zinc-800/40">
                  <tr>
                    <td colspan="3" class="p-3">
                      <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        Nova linha
                      </p>
                      <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <input
                          v-model="inputOrigem"
                          type="text"
                          class="ui-input min-w-0 flex-1 font-mono text-sm"
                          placeholder="Nome da cidade — Enter para adicionar"
                          @keydown.enter.prevent="adicionarCidadeOrigem"
                        />
                        <button type="button" class="ui-btn-primary shrink-0 px-4 sm:w-auto" @click="adicionarCidadeOrigem">
                          Adicionar linha
                        </button>
                      </div>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <details
              class="group border-t border-zinc-200 bg-zinc-50/40 open:bg-white dark:border-zinc-700 dark:bg-zinc-900/30 dark:open:bg-zinc-900/60"
            >
              <summary
                class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-emerald-900 marker:text-emerald-700 dark:text-emerald-200 dark:marker:text-emerald-400"
              >
                Colar várias cidades (importação em massa)
              </summary>
              <div class="space-y-2 border-t border-zinc-200/80 px-4 pb-4 pt-3 dark:border-zinc-700/80">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                  Uma cidade por linha, ou separadas por vírgula ou ponto e vírgula. Duplicatas são ignoradas.
                </p>
                <textarea
                  v-model="bulkOrigem"
                  rows="5"
                  class="ui-input w-full resize-y font-mono text-sm"
                  placeholder="São Paulo&#10;Guarulhos&#10;Campinas"
                />
                <button type="button" class="ui-btn-secondary w-full sm:w-auto" @click="adicionarCidadesOrigemEmMassa">
                  Inserir todas como linhas de origem
                </button>
              </div>
            </details>
          </div>

          <!-- Planilha: destinos -->
          <div class="overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
            <div
              class="flex flex-wrap items-center justify-between gap-2 border-b border-sky-200/70 bg-sky-50/90 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/35"
            >
              <div class="min-w-0">
                <span class="block text-sm font-semibold text-sky-950 dark:text-sky-100">Destinos permitidos</span>
                <span class="mt-0.5 block text-[11px] font-normal leading-snug text-sky-900/90 dark:text-sky-200/90">
                  Cidades de destino em que o aceite de cargas é permitido
                </span>
              </div>
              <span
                class="rounded-md border border-sky-200/80 bg-white/90 px-2.5 py-1 text-xs font-semibold tabular-nums text-sky-950 dark:border-sky-800 dark:bg-zinc-900/80 dark:text-sky-100"
              >
                {{ formParam.cidades_destino_list.length }} linha(s)
              </span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full min-w-[320px] text-left text-sm">
                <thead>
                  <tr class="border-b border-zinc-200 bg-zinc-50/95 dark:border-zinc-700 dark:bg-zinc-800/90">
                    <th class="w-14 px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      N.º
                    </th>
                    <th class="px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Cidade (destino da carga)
                    </th>
                    <th class="w-24 px-3 py-2.5 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Ação
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/90">
                  <tr
                    v-for="(cidadeDestino, indiceDestino) in formParam.cidades_destino_list"
                    :key="'d-' + indiceDestino + cidadeDestino"
                    class="bg-white transition-colors hover:bg-sky-50/40 dark:bg-transparent dark:hover:bg-sky-950/20"
                  >
                    <td class="px-3 py-2.5 tabular-nums text-zinc-500 dark:text-zinc-400">
                      {{ indiceDestino + 1 }}
                    </td>
                    <td class="px-3 py-2.5 font-medium text-zinc-800 dark:text-zinc-100">
                      {{ cidadeDestino }}
                    </td>
                    <td class="px-3 py-2.5 text-right">
                      <button
                        type="button"
                        class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline dark:text-red-400 dark:hover:text-red-300"
                        @click="removerCidadeDestino(indiceDestino)"
                      >
                        Remover
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!formParam.cidades_destino_list.length">
                    <td colspan="3" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                      Nenhuma linha — o aceite <span class="font-medium text-zinc-700 dark:text-zinc-300">não exige</span> cidade de destino específica.
                    </td>
                  </tr>
                </tbody>
                <tfoot class="border-t border-zinc-200 bg-zinc-50/70 dark:border-zinc-700 dark:bg-zinc-800/40">
                  <tr>
                    <td colspan="3" class="p-3">
                      <p class="mb-2 text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        Nova linha
                      </p>
                      <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <input
                          v-model="inputDestino"
                          type="text"
                          class="ui-input min-w-0 flex-1 font-mono text-sm"
                          placeholder="Nome da cidade — Enter para adicionar"
                          @keydown.enter.prevent="adicionarCidadeDestino"
                        />
                        <button
                          type="button"
                          class="shrink-0 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-sky-500 sm:w-auto"
                          @click="adicionarCidadeDestino"
                        >
                          Adicionar linha
                        </button>
                      </div>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <details
              class="group border-t border-zinc-200 bg-zinc-50/40 open:bg-white dark:border-zinc-700 dark:bg-zinc-900/30 dark:open:bg-zinc-900/60"
            >
              <summary
                class="cursor-pointer select-none px-4 py-3 text-sm font-semibold text-sky-950 marker:text-sky-700 dark:text-sky-100 dark:marker:text-sky-400"
              >
                Colar várias cidades (importação em massa)
              </summary>
              <div class="space-y-2 border-t border-zinc-200/80 px-4 pb-4 pt-3 dark:border-zinc-700/80">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                  Uma cidade por linha, ou separadas por vírgula ou ponto e vírgula. Duplicatas são ignoradas.
                </p>
                <textarea
                  v-model="bulkDestino"
                  rows="5"
                  class="ui-input w-full resize-y font-mono text-sm"
                  placeholder="Bauru&#10;Ribeirão Preto&#10;Sorocaba"
                />
                <button
                  type="button"
                  class="w-full rounded-xl border border-sky-300 bg-white px-4 py-2.5 text-sm font-semibold text-sky-900 shadow-sm transition-all hover:bg-sky-50 dark:border-sky-700 dark:bg-zinc-800 dark:text-sky-100 dark:hover:bg-sky-950/50 sm:w-auto"
                  @click="adicionarCidadesDestinoEmMassa"
                >
                  Inserir todas como linhas de destino
                </button>
              </div>
            </details>
          </div>
        </div>
        <button type="button" class="ui-btn-primary mt-6 inline-flex items-center gap-2 px-6" @click="salvarParametro">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          Salvar parâmetros
        </button>
      </div>

      <div v-show="abaAtiva === 'regras'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <h3 class="ui-section-title">
              <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-500/15 text-teal-800 dark:text-teal-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655-5.653a2.548 2.548 0 010-3.586L11.42 15.17z"
                  />
                </svg>
              </span>
              Regras por cidade
            </h3>
            <p class="ui-section-desc mt-2 max-w-2xl">
              Vista em planilha por regra: limite de peso e valor aplicam-se às cidades marcadas na coluna «Na regra». As cidades vêm da secção «Cidades permitidas para aceite». Várias regras podem aplicar-se à mesma carga — todas devem passar.
            </p>
          </div>
          <button type="button" class="ui-btn-secondary shrink-0" @click="adicionarRegra">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nova regra
          </button>
        </div>

        <div class="mt-6 space-y-6">
          <div
            v-for="(r, idx) in formParam.regras"
            :key="r._key"
            class="overflow-hidden rounded-xl border border-zinc-200/90 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50"
          >
            <div
              class="flex flex-wrap items-center justify-between gap-2 border-b border-teal-200/70 bg-teal-50/90 px-4 py-3 dark:border-teal-900/40 dark:bg-teal-950/30"
            >
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-teal-950 dark:text-teal-100">Regra {{ idx + 1 }}</span>
                <span
                  class="rounded-md border border-teal-200/80 bg-white/90 px-2 py-0.5 text-xs font-semibold tabular-nums text-teal-900 dark:border-teal-800 dark:bg-zinc-900/80 dark:text-teal-200"
                >
                  {{ cidadesSelecionadasPermitidasNaRegra(r).length }} cidade(s) na regra
                </span>
              </div>
              <button
                type="button"
                class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline dark:text-red-400"
                @click="removerRegra(idx)"
              >
                Remover regra
              </button>
            </div>

            <!-- Parâmetros numéricos: uma linha tipo planilha -->
            <div class="overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
              <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                  <tr class="border-b border-zinc-200 bg-zinc-50/95 dark:border-zinc-700 dark:bg-zinc-800/90">
                    <th class="min-w-[10rem] px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Aplica à
                    </th>
                    <th class="min-w-[6.5rem] px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Peso mín. (t)
                    </th>
                    <th class="min-w-[6.5rem] px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Peso máx. (t)
                    </th>
                    <th class="min-w-[7.5rem] px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Valor carga mín.
                    </th>
                    <th class="min-w-[7.5rem] px-3 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Valor carga máx.
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="bg-white dark:bg-transparent">
                    <td class="px-3 py-2.5 align-middle">
                      <select v-model="r.aplica_a" class="ui-select w-full" @change="aoMudarAplicaARegra(r)">
                        <option value="origem">Cidade de origem</option>
                        <option value="destino">Cidade de destino</option>
                      </select>
                    </td>
                    <td class="px-3 py-2.5 align-middle">
                      <input
                        v-model.number="r.peso_min_ton"
                        type="number"
                        step="0.01"
                        min="0"
                        class="ui-input w-full tabular-nums"
                        placeholder="—"
                      />
                    </td>
                    <td class="px-3 py-2.5 align-middle">
                      <input
                        v-model.number="r.peso_max_ton"
                        type="number"
                        step="0.01"
                        min="0"
                        class="ui-input w-full tabular-nums"
                        placeholder="—"
                      />
                    </td>
                    <td class="px-3 py-2.5 align-middle">
                      <input
                        v-model.number="r.valor_carga_min"
                        type="number"
                        step="0.01"
                        min="0"
                        class="ui-input w-full tabular-nums"
                        placeholder="Ex.: 400000"
                      />
                    </td>
                    <td class="px-3 py-2.5 align-middle">
                      <input
                        v-model.number="r.valor_carga_max"
                        type="number"
                        step="0.01"
                        min="0"
                        class="ui-input w-full tabular-nums"
                        placeholder="—"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Cidades: planilha com filtro -->
            <template v-if="cidadesDisponiveisParaRegra(r.aplica_a).length">
              <div
                class="flex flex-col gap-3 border-b border-zinc-200 bg-zinc-50/50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/30 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
              >
                <div class="relative min-w-0 flex-1 sm:max-w-md">
                  <svg
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                    />
                  </svg>
                  <input
                    v-model="r.busca_cidades"
                    type="search"
                    class="ui-input w-full pl-9"
                    placeholder="Filtrar linhas pelo nome da cidade…"
                    autocomplete="off"
                    :aria-label="'Filtrar cidades da regra ' + (idx + 1)"
                  />
                </div>
                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-800 shadow-sm hover:bg-emerald-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-emerald-300 dark:hover:bg-emerald-950/30"
                    @click="selecionarTodasCidadesRegra(r)"
                  >
                    Marcar todas
                  </button>
                  <button
                    v-if="normalizarBuscaCidade(r.busca_cidades) && cidadesRegraFiltradas(r).length"
                    type="button"
                    class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-teal-800 shadow-sm hover:bg-teal-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-teal-300 dark:hover:bg-teal-950/30"
                    @click="marcarCidadesFiltradasRegra(r)"
                  >
                    Marcar só filtradas
                  </button>
                  <button
                    type="button"
                    class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    @click="limparCidadesRegra(r)"
                  >
                    Limpar marcações
                  </button>
                </div>
              </div>

              <div v-if="cidadesRegraFiltradas(r).length" class="max-h-72 overflow-auto">
                <table class="w-full min-w-[360px] text-left text-sm">
                  <thead class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                      <th class="w-12 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        N.º
                      </th>
                      <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Cidade
                      </th>
                      <th class="w-28 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Na regra
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/90">
                    <tr
                      v-for="(nomeCidade, indiceFiltrado) in cidadesRegraFiltradas(r)"
                      :key="r._key + '-c-' + nomeCidade"
                      class="transition-colors"
                      :class="
                        regraTemCidade(r, nomeCidade)
                          ? 'bg-teal-50/70 dark:bg-teal-950/25'
                          : 'bg-white hover:bg-zinc-50 dark:bg-transparent dark:hover:bg-zinc-800/40'
                      "
                    >
                      <td class="px-3 py-2 tabular-nums text-zinc-500 dark:text-zinc-400">
                        {{ indiceFiltrado + 1 }}
                      </td>
                      <td class="px-3 py-2 font-medium text-zinc-800 dark:text-zinc-100">
                        {{ nomeCidade }}
                      </td>
                      <td class="px-3 py-2 text-center align-middle">
                        <input
                          type="checkbox"
                          class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30"
                          :checked="regraTemCidade(r, nomeCidade)"
                          @change="toggleCidadeRegra(r, nomeCidade, $event.target.checked)"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="border-b border-zinc-100 px-4 py-10 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                Nenhuma linha corresponde ao filtro. Limpe a caixa de busca ou ajuste o texto.
              </p>
              <div
                class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-zinc-200 bg-zinc-50/60 px-3 py-2 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400"
              >
                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ cidadesSelecionadasPermitidasNaRegra(r).length }}</span>
                <span>com «Na regra» ativado</span>
                <template v-if="normalizarBuscaCidade(r.busca_cidades)">
                  <span class="text-zinc-400">·</span>
                  <span>a mostrar</span>
                  <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ cidadesRegraFiltradas(r).length }}</span>
                  <span>de {{ cidadesDisponiveisParaRegra(r.aplica_a).length }} linhas</span>
                </template>
              </div>
            </template>
            <p
              v-else
              class="border-b border-amber-200/80 bg-amber-50/80 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/35 dark:text-amber-100"
            >
              Nenhuma cidade na lista de aceite. Na secção «Cidades permitidas para aceite», adicione linhas em origens (regra por origem) ou destinos (regra por destino).
            </p>

            <div v-if="cidadesOrfasNaRegra(r).length" class="border-t border-amber-200/60 bg-amber-50/40 dark:border-amber-900/40 dark:bg-amber-950/20">
              <p class="px-4 pt-3 text-xs font-medium text-amber-950 dark:text-amber-100">
                Cidades guardadas na regra que já não existem na planilha de cidades permitidas para aceite:
              </p>
              <div class="overflow-x-auto px-2 pb-3">
                <table class="mt-2 w-full min-w-[280px] text-left text-sm">
                  <thead>
                    <tr class="border-b border-amber-200/70 bg-amber-100/50 dark:border-amber-900/50 dark:bg-amber-950/40">
                      <th class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-200">
                        Cidade
                      </th>
                      <th class="w-24 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-amber-900 dark:text-amber-200">
                        Ação
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-amber-100 dark:divide-amber-900/30">
                    <tr
                      v-for="nomeCidadeOrfa in cidadesOrfasNaRegra(r)"
                      :key="'orf-' + r._key + '-' + nomeCidadeOrfa"
                      class="bg-white/80 dark:bg-zinc-900/30"
                    >
                      <td class="px-3 py-2 font-medium text-amber-950 dark:text-amber-50">
                        {{ nomeCidadeOrfa }}
                      </td>
                      <td class="px-3 py-2 text-right">
                        <button
                          type="button"
                          class="text-xs font-semibold text-red-600 hover:underline dark:text-red-400"
                          @click="toggleCidadeRegra(r, nomeCidadeOrfa, false)"
                        >
                          Remover da regra
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <p
              v-if="cidadesDisponiveisParaRegra(r.aplica_a).length && !r.cidades_selecionadas?.length"
              class="border-t border-zinc-200 px-4 py-2 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-400"
            >
              Marque ao menos uma cidade na coluna «Na regra» para esta regra ser guardada.
            </p>
          </div>

          <div
            v-if="!formParam.regras.length"
            class="overflow-hidden rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 dark:border-zinc-600 dark:bg-zinc-900/25"
          >
            <div class="overflow-x-auto">
              <table class="w-full min-w-[320px] text-left text-sm opacity-60">
                <thead>
                  <tr class="border-b border-zinc-200 bg-zinc-100/80 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <th class="px-4 py-2 text-xs font-semibold uppercase text-zinc-500">N.º</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase text-zinc-500">Cidade</th>
                    <th class="px-4 py-2 text-xs font-semibold uppercase text-zinc-500">Na regra</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="3" class="px-4 py-12 text-center">
                      <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Nenhuma regra extra ainda.</p>
                      <p class="mt-1 text-xs text-zinc-500">
                        Clique em «Nova regra» e preencha a planilha (pesos, valores e cidades).
                      </p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <button type="button" class="ui-btn-primary mt-6 inline-flex items-center gap-2 px-6" @click="salvarParametro">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          Salvar parâmetros
        </button>
      </div>

      <div v-show="abaAtiva === 'filtros'" class="ui-card ui-card-interactive p-6 sm:p-7">
        <h3 class="ui-section-title">
          <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-zinc-800/10 text-zinc-800 dark:bg-white/10 dark:text-zinc-200">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"
              />
            </svg>
          </span>
          Filtros globais
        </h3>
        <p class="ui-section-desc mb-6 max-w-2xl">
          Opcionais para todas as cargas que já passaram nas cidades e regras. Peso no SAP costuma vir em kg.
        </p>
        <div class="grid gap-5 lg:grid-cols-2">
          <div>
            <label class="ui-label">Peso mínimo (kg)</label>
            <input
              v-model.number="formParam.peso_min"
              type="number"
              step="0.01"
              class="ui-input"
            />
          </div>
          <div>
            <label class="ui-label">Peso máximo (kg)</label>
            <input
              v-model.number="formParam.peso_max"
              type="number"
              step="0.01"
              class="ui-input"
            />
          </div>
          <div>
            <label class="ui-label">Distância máxima</label>
            <input
              v-model.number="formParam.distancia_max"
              type="number"
              step="0.01"
              class="ui-input"
            />
          </div>
          <div>
            <label class="ui-label">Tipo veículo</label>
            <select v-model="formParam.tipo_veiculo" class="ui-select">
              <option value="">—</option>
              <option value="ZZTRUCK">ZZTRUCK</option>
              <option value="ZZBITRUCK">ZZBITRUCK</option>
              <option value="ZZCARRETA">ZZCARRETA</option>
              <option value="ZZRODOTREM">ZZRODOTREM</option>
            </select>
          </div>
          <div>
            <label class="ui-label">Intervalo busca (s)</label>
            <input
              v-model.number="formParam.intervalo_busca"
              type="number"
              min="10"
              class="ui-input"
            />
          </div>
        </div>
        <button type="button" class="ui-btn-primary mt-6 inline-flex items-center gap-2 px-6" @click="salvarParametro">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
          Salvar parâmetros
        </button>
      </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const bot = ref(null);
const formBot = ref({ intervalo: 60, horario_inicio: '', horario_fim: '' });
const inputOrigem = ref('');
const inputDestino = ref('');
const bulkOrigem = ref('');
const bulkDestino = ref('');
let regraKey = 0;

function novaRegraVazia() {
  regraKey += 1;
  return {
    _key: `r-${regraKey}-${Date.now()}`,
    aplica_a: 'origem',
    cidades_selecionadas: [],
    busca_cidades: '',
    peso_min_ton: null,
    peso_max_ton: null,
    valor_carga_min: null,
    valor_carga_max: null,
  };
}

function defaultFormParam() {
  return {
    peso_min: null,
    peso_max: null,
    distancia_max: null,
    tipo_veiculo: '',
    intervalo_busca: 60,
    modo_teste: false,
    emails_text: '',
    whatsapp_text: '',
    portal_usuario: '',
    portal_senha: '',
    portal_senha_definida: false,
    cidades_origem_list: [],
    cidades_destino_list: [],
    regras: [],
  };
}

const formParam = ref(defaultFormParam());

const abaAtiva = ref('agendamento');

const abasConfig = [
  { id: 'agendamento', rotulo: 'Agendamento', rotuloCurto: 'Agenda', indice: '1' },
  { id: 'notificacoes', rotulo: 'Notificações', rotuloCurto: 'Alertas', indice: '2' },
  { id: 'portal', rotulo: 'Portal', rotuloCurto: 'Portal', indice: '3' },
  { id: 'cidades', rotulo: 'Cidades (aceite)', rotuloCurto: 'Cidades', indice: '4' },
  { id: 'regras', rotulo: 'Regras', rotuloCurto: 'Regras', indice: '5' },
  { id: 'filtros', rotulo: 'Filtros globais', rotuloCurto: 'Filtros', indice: '6' },
];

function cidadesDisponiveisParaRegra(aplicaA) {
  const fp = formParam.value;
  if (aplicaA === 'destino') {
    return [...(fp.cidades_destino_list || [])];
  }
  return [...(fp.cidades_origem_list || [])];
}

function normalizarBuscaCidade(s) {
  return String(s || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function cidadesRegraFiltradas(r) {
  const todas = cidadesDisponiveisParaRegra(r.aplica_a);
  const q = normalizarBuscaCidade(r.busca_cidades);
  if (!q) {
    return todas;
  }
  return todas.filter((c) => normalizarBuscaCidade(c).includes(q));
}

function cidadesSelecionadasPermitidasNaRegra(r) {
  const permitidas = new Set(cidadesDisponiveisParaRegra(r.aplica_a));
  const sel = Array.isArray(r.cidades_selecionadas) ? r.cidades_selecionadas : [];
  return sel.filter((c) => permitidas.has(c));
}

function marcarCidadesFiltradasRegra(r) {
  if (!Array.isArray(r.cidades_selecionadas)) {
    r.cidades_selecionadas = [];
  }
  for (const c of cidadesRegraFiltradas(r)) {
    if (!r.cidades_selecionadas.includes(c)) {
      r.cidades_selecionadas.push(c);
    }
  }
}

function regraTemCidade(r, cidade) {
  return Array.isArray(r.cidades_selecionadas) && r.cidades_selecionadas.includes(cidade);
}

function toggleCidadeRegra(r, cidade, checked) {
  if (!Array.isArray(r.cidades_selecionadas)) {
    r.cidades_selecionadas = [];
  }
  if (checked) {
    if (!r.cidades_selecionadas.includes(cidade)) {
      r.cidades_selecionadas.push(cidade);
    }
  } else {
    r.cidades_selecionadas = r.cidades_selecionadas.filter((c) => c !== cidade);
  }
}

function selecionarTodasCidadesRegra(r) {
  r.cidades_selecionadas = [...cidadesDisponiveisParaRegra(r.aplica_a)];
}

function limparCidadesRegra(r) {
  r.cidades_selecionadas = [];
}

function aoMudarAplicaARegra(r) {
  r.busca_cidades = '';
  const permitidas = new Set(cidadesDisponiveisParaRegra(r.aplica_a));
  if (!Array.isArray(r.cidades_selecionadas)) {
    r.cidades_selecionadas = [];
    return;
  }
  r.cidades_selecionadas = r.cidades_selecionadas.filter((c) => permitidas.has(c));
}

function cidadesOrfasNaRegra(r) {
  const permitidas = new Set(cidadesDisponiveisParaRegra(r.aplica_a));
  return (r.cidades_selecionadas || []).filter((c) => !permitidas.has(c));
}

function normNumApi(v) {
  if (v == null || v === '') {
    return '';
  }
  const n = Number(v);
  return Number.isNaN(n) ? String(v) : String(n);
}

function chaveGrupoRegraApi(r) {
  return [
    r.aplica_a || 'origem',
    normNumApi(r.peso_min_kg),
    normNumApi(r.peso_max_kg),
    normNumApi(r.valor_carga_min),
    normNumApi(r.valor_carga_max),
  ].join('\0');
}

function agruparRegrasDaApi(regrasApi) {
  if (!Array.isArray(regrasApi) || !regrasApi.length) {
    return [];
  }
  const map = new Map();
  for (const r of regrasApi) {
    const k = chaveGrupoRegraApi(r);
    if (!map.has(k)) {
      map.set(k, { row: r, cidades: [] });
    }
    const g = map.get(k);
    const city = (r.cidade ?? '').trim();
    if (city && !g.cidades.includes(city)) {
      g.cidades.push(city);
    }
  }
  return [...map.values()].map(({ row, cidades }) => {
    regraKey += 1;
    const pmn =
      row.peso_min_kg != null && row.peso_min_kg !== '' ? Number(row.peso_min_kg) / 1000 : null;
    const pmx =
      row.peso_max_kg != null && row.peso_max_kg !== '' ? Number(row.peso_max_kg) / 1000 : null;
    return {
      _key: `r-${regraKey}-${row.id || Math.random()}`,
      aplica_a: row.aplica_a || 'origem',
      cidades_selecionadas: [...cidades],
      busca_cidades: '',
      peso_min_ton: pmn !== null && !Number.isNaN(pmn) ? pmn : null,
      peso_max_ton: pmx !== null && !Number.isNaN(pmx) ? pmx : null,
      valor_carga_min:
        row.valor_carga_min != null && row.valor_carga_min !== ''
          ? Number(row.valor_carga_min)
          : null,
      valor_carga_max:
        row.valor_carga_max != null && row.valor_carga_max !== ''
          ? Number(row.valor_carga_max)
          : null,
    };
  });
}

function textToList(text) {
  if (!text || !String(text).trim()) return [];
  return String(text)
    .split(/[\n,;]+/)
    .map((s) => s.trim())
    .filter(Boolean);
}

function listToText(arr) {
  if (!arr || !Array.isArray(arr)) return '';
  return arr.join('\n');
}

function tonToKg(v) {
  if (v === null || v === undefined || v === '') return null;
  const n = Number(v);
  if (Number.isNaN(n)) return null;
  return n * 1000;
}

function emptyNum(v) {
  if (v === null || v === undefined || v === '') return null;
  const n = Number(v);
  return Number.isNaN(n) ? null : n;
}

function regrasPayload() {
  const out = [];
  for (const r of formParam.value.regras) {
    const cidades = Array.isArray(r.cidades_selecionadas) ? r.cidades_selecionadas : [];
    for (const cidade of cidades) {
      const c = String(cidade).trim();
      if (!c) {
        continue;
      }
      out.push({
        aplica_a: r.aplica_a,
        cidade: c,
        peso_min_kg: tonToKg(r.peso_min_ton),
        peso_max_kg: tonToKg(r.peso_max_ton),
        valor_carga_min: emptyNum(r.valor_carga_min),
        valor_carga_max: emptyNum(r.valor_carga_max),
      });
    }
  }
  return out;
}

function payloadFromForm() {
  const body = {
    origem: null,
    destino: null,
    peso_min: formParam.value.peso_min,
    peso_max: formParam.value.peso_max,
    distancia_max: formParam.value.distancia_max,
    tipo_veiculo: formParam.value.tipo_veiculo || null,
    intervalo_busca: formParam.value.intervalo_busca,
    modo_teste: !!formParam.value.modo_teste,
    emails_notificacao: textToList(formParam.value.emails_text),
    whatsapp_numeros: textToList(formParam.value.whatsapp_text),
    portal_usuario: (formParam.value.portal_usuario || '').trim() || null,
    cidades_origem_aceitas: [...formParam.value.cidades_origem_list],
    cidades_destino_aceitas: [...formParam.value.cidades_destino_list],
    regras: regrasPayload(),
  };
  const pw = (formParam.value.portal_senha || '').trim();
  if (pw) {
    body.portal_senha = pw;
  }
  return body;
}

function fillFormFromParametro(p) {
  const origens = (p.cidades_aceitas || []).filter((x) => x.tipo === 'origem').map((x) => x.cidade);
  const destinos = (p.cidades_aceitas || []).filter((x) => x.tipo === 'destino').map((x) => x.cidade);

  const regras = agruparRegrasDaApi(p.regras_cidades || []);

  formParam.value = {
    ...defaultFormParam(),
    peso_min: p.peso_min ?? null,
    peso_max: p.peso_max ?? null,
    distancia_max: p.distancia_max ?? null,
    tipo_veiculo: p.tipo_veiculo ?? '',
    intervalo_busca: p.intervalo_busca ?? 60,
    modo_teste: !!p.modo_teste,
    emails_text: listToText(p.emails_notificacao),
    whatsapp_text: listToText(p.whatsapp_numeros),
    portal_usuario: p.portal_usuario ?? '',
    portal_senha: '',
    portal_senha_definida: !!p.portal_senha_definida,
    cidades_origem_list: origens,
    cidades_destino_list: destinos,
    regras,
  };
}

function adicionarCidadeOrigem() {
  const s = (inputOrigem.value || '').trim();
  if (!s) return;
  if (!formParam.value.cidades_origem_list.includes(s)) {
    formParam.value.cidades_origem_list.push(s);
  }
  inputOrigem.value = '';
}

function adicionarCidadesOrigemEmMassa() {
  const novas = textToList(bulkOrigem.value);
  if (!novas.length) return;
  const list = formParam.value.cidades_origem_list;
  for (const raw of novas) {
    const s = raw.length > 255 ? raw.slice(0, 255) : raw;
    if (!list.includes(s)) {
      list.push(s);
    }
  }
  bulkOrigem.value = '';
}

function removerCidadeOrigem(i) {
  const removed = formParam.value.cidades_origem_list[i];
  formParam.value.cidades_origem_list.splice(i, 1);
  for (const r of formParam.value.regras) {
    if (r.aplica_a === 'origem' && Array.isArray(r.cidades_selecionadas)) {
      r.cidades_selecionadas = r.cidades_selecionadas.filter((c) => c !== removed);
    }
  }
}

function adicionarCidadeDestino() {
  const s = (inputDestino.value || '').trim();
  if (!s) return;
  if (!formParam.value.cidades_destino_list.includes(s)) {
    formParam.value.cidades_destino_list.push(s);
  }
  inputDestino.value = '';
}

function adicionarCidadesDestinoEmMassa() {
  const novas = textToList(bulkDestino.value);
  if (!novas.length) return;
  const list = formParam.value.cidades_destino_list;
  for (const raw of novas) {
    const s = raw.length > 255 ? raw.slice(0, 255) : raw;
    if (!list.includes(s)) {
      list.push(s);
    }
  }
  bulkDestino.value = '';
}

function removerCidadeDestino(i) {
  const removed = formParam.value.cidades_destino_list[i];
  formParam.value.cidades_destino_list.splice(i, 1);
  for (const r of formParam.value.regras) {
    if (r.aplica_a === 'destino' && Array.isArray(r.cidades_selecionadas)) {
      r.cidades_selecionadas = r.cidades_selecionadas.filter((c) => c !== removed);
    }
  }
}

function adicionarRegra() {
  formParam.value.regras.push(novaRegraVazia());
}

function removerRegra(i) {
  formParam.value.regras.splice(i, 1);
}

async function carregar() {
  try {
    const { data } = await axios.get('/api/bots');
    const b = Array.isArray(data) ? data[0] : data;
    bot.value = b || null;
    if (b) {
      formBot.value = {
        intervalo: b.intervalo ?? 60,
        horario_inicio: b.horario_inicio ?? '',
        horario_fim: b.horario_fim ?? '',
      };
      const params = b.parametros || [];
      const p = params[0];
      if (p) {
        fillFormFromParametro(p);
      } else {
        formParam.value = defaultFormParam();
      }
    }
  } catch {
    bot.value = null;
  }
}

async function criarBot() {
  try {
    const { data } = await axios.post('/api/bots', { status: 'inativo', intervalo: 60 });
    bot.value = data;
    await carregar();
  } catch (e) {
    console.error(e);
  }
}

async function salvarBot() {
  if (!bot.value?.id) return;
  try {
    await axios.put(`/api/bots/${bot.value.id}`, formBot.value);
    await carregar();
  } catch (e) {
    console.error(e);
  }
}

async function salvarParametro() {
  if (!bot.value?.id) return;
  try {
    const body = payloadFromForm();
    const params = bot.value.parametros || [];
    if (params.length) {
      await axios.put(`/api/parametros/${params[0].id}`, body);
    } else {
      await axios.post(`/api/bots/${bot.value.id}/parametros`, body);
    }
    await carregar();
  } catch (e) {
    console.error(e);
  }
}

onMounted(carregar);
</script>

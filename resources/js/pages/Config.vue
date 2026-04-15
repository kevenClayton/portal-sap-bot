<template>
  <div class="space-y-8">
    <PageHeader
      title="Configuração"
      eyebrow="Bot & regras"
      description="Intervalos, notificações, acesso ao portal, cidades permitidas, regras por cidade e filtros globais aplicados automaticamente."
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
      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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

      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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

      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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

      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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
          Cidades permitidas
        </h3>
        <p class="ui-section-desc mb-8 max-w-2xl">
          Listas de origem e destino aceitos. Lista vazia desse lado não filtra por cidade nesse lado.
        </p>
        <div class="grid gap-8 lg:grid-cols-2">
          <div class="rounded-2xl border border-emerald-200/60 bg-gradient-to-b from-emerald-50/50 to-white p-5 dark:border-emerald-900/40 dark:from-emerald-950/20 dark:to-zinc-900/30">
            <label class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Origens aceitas</label>
            <div class="mt-2 flex min-h-[2.5rem] flex-wrap gap-2">
              <span
                v-for="(c, i) in formParam.cidades_origem_list"
                :key="'o-' + i + c"
                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-100"
              >
                {{ c }}
                <button type="button" class="ui-chip-remove text-emerald-800 dark:text-emerald-200" aria-label="Remover" @click="removerCidadeOrigem(i)">
                  ×
                </button>
              </span>
              <span v-if="!formParam.cidades_origem_list.length" class="self-center text-sm text-zinc-400">Nenhuma — não restringe origem</span>
            </div>
            <div class="mt-3 flex gap-2">
              <input
                v-model="inputOrigem"
                type="text"
                class="ui-input min-w-0 flex-1"
                placeholder="Ex.: Guarulhos — Enter"
                @keydown.enter.prevent="adicionarCidadeOrigem"
              />
              <button type="button" class="ui-btn-primary shrink-0 px-4" @click="adicionarCidadeOrigem">Adicionar</button>
            </div>
            <details class="mt-4 rounded-xl border border-emerald-200/50 bg-white/60 p-3 dark:border-emerald-900/30 dark:bg-zinc-900/40">
              <summary class="cursor-pointer select-none text-sm font-medium text-emerald-900 dark:text-emerald-200">
                Adicionar várias cidades de uma vez
              </summary>
              <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Cole a lista: uma cidade por linha, ou separadas por vírgula ou ponto e vírgula. Duplicatas na lista atual são ignoradas.
              </p>
              <textarea
                v-model="bulkOrigem"
                rows="5"
                class="ui-input mt-2 w-full resize-y font-mono text-sm"
                placeholder="São Paulo&#10;Guarulhos&#10;Campinas"
              />
              <button type="button" class="ui-btn-secondary mt-2 w-full sm:w-auto" @click="adicionarCidadesOrigemEmMassa">
                Adicionar todas às origens
              </button>
            </details>
          </div>
          <div class="rounded-2xl border border-sky-200/60 bg-gradient-to-b from-sky-50/50 to-white p-5 dark:border-sky-900/40 dark:from-sky-950/20 dark:to-zinc-900/30">
            <label class="text-sm font-semibold text-sky-900 dark:text-sky-200">Destinos aceitos</label>
            <div class="mt-2 flex min-h-[2.5rem] flex-wrap gap-2">
              <span
                v-for="(c, i) in formParam.cidades_destino_list"
                :key="'d-' + i + c"
                class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sm text-sky-950 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-100"
              >
                {{ c }}
                <button type="button" class="ui-chip-remove text-sky-900 dark:text-sky-200" aria-label="Remover" @click="removerCidadeDestino(i)">
                  ×
                </button>
              </span>
              <span v-if="!formParam.cidades_destino_list.length" class="self-center text-sm text-zinc-400">Nenhuma — não restringe destino</span>
            </div>
            <div class="mt-3 flex gap-2">
              <input
                v-model="inputDestino"
                type="text"
                class="ui-input min-w-0 flex-1"
                placeholder="Ex.: Bauru — Enter"
                @keydown.enter.prevent="adicionarCidadeDestino"
              />
              <button type="button" class="shrink-0 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-sky-500" @click="adicionarCidadeDestino">
                Adicionar
              </button>
            </div>
            <details class="mt-4 rounded-xl border border-sky-200/50 bg-white/60 p-3 dark:border-sky-900/30 dark:bg-zinc-900/40">
              <summary class="cursor-pointer select-none text-sm font-medium text-sky-900 dark:text-sky-200">
                Adicionar várias cidades de uma vez
              </summary>
              <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Cole a lista: uma cidade por linha, ou separadas por vírgula ou ponto e vírgula. Duplicatas na lista atual são ignoradas.
              </p>
              <textarea
                v-model="bulkDestino"
                rows="5"
                class="ui-input mt-2 w-full resize-y font-mono text-sm"
                placeholder="Bauru&#10;Ribeirão Preto&#10;Sorocaba"
              />
              <button
                type="button"
                class="mt-2 w-full rounded-xl border border-sky-300 bg-white px-4 py-2.5 text-sm font-semibold text-sky-900 shadow-sm transition-all hover:bg-sky-50 dark:border-sky-700 dark:bg-zinc-800 dark:text-sky-100 dark:hover:bg-sky-950/50 sm:w-auto"
                @click="adicionarCidadesDestinoEmMassa"
              >
                Adicionar todas aos destinos
              </button>
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

      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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
              Selecione cidades das listas acima; os mesmos limites valem para todas. Várias regras podem aplicar-se à mesma carga — todas devem passar.
            </p>
          </div>
          <button type="button" class="ui-btn-secondary shrink-0" @click="adicionarRegra">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nova regra
          </button>
        </div>

        <div class="mt-6 space-y-4">
          <div
            v-for="(r, idx) in formParam.regras"
            :key="r._key"
            class="rounded-2xl border border-zinc-200/90 bg-zinc-50/60 p-5 shadow-inner dark:border-zinc-700/80 dark:bg-zinc-950/40"
          >
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200/80 pb-3 dark:border-zinc-700/80">
              <span class="rounded-full bg-zinc-200/80 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                Regra {{ idx + 1 }}
              </span>
              <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400" @click="removerRegra(idx)">
                Remover
              </button>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label class="ui-label">Aplica à</label>
                <select
                  v-model="r.aplica_a"
                  class="ui-select"
                  @change="aoMudarAplicaARegra(r)"
                >
                  <option value="origem">Cidade de origem</option>
                  <option value="destino">Cidade de destino</option>
                </select>
              </div>
              <div class="sm:col-span-2 lg:col-span-3">
                <div class="flex flex-wrap items-end justify-between gap-2">
                  <label class="ui-label">Cidades nesta regra</label>
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      class="text-xs font-medium text-emerald-700 hover:underline dark:text-emerald-400"
                      @click="selecionarTodasCidadesRegra(r)"
                    >
                      Marcar todas
                    </button>
                    <button
                      v-if="normalizarBuscaCidade(r.busca_cidades) && cidadesRegraFiltradas(r).length"
                      type="button"
                      class="text-xs font-medium text-teal-700 hover:underline dark:text-teal-400"
                      @click="marcarCidadesFiltradasRegra(r)"
                    >
                      Marcar resultado da busca
                    </button>
                    <button
                      type="button"
                      class="text-xs font-medium text-zinc-600 hover:underline dark:text-zinc-400"
                      @click="limparCidadesRegra(r)"
                    >
                      Limpar seleção
                    </button>
                  </div>
                </div>
                <div
                  v-if="cidadesDisponiveisParaRegra(r.aplica_a).length"
                  class="mt-2 space-y-3 rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50"
                >
                  <div class="relative">
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
                      placeholder="Buscar cidade pelo nome…"
                      autocomplete="off"
                      :aria-label="'Filtrar cidades da regra ' + (idx + 1)"
                    />
                  </div>
                  <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ cidadesSelecionadasPermitidasNaRegra(r).length }}</span>
                    selecionada(s)
                    <template v-if="normalizarBuscaCidade(r.busca_cidades)">
                      · lista filtrada:
                      <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ cidadesRegraFiltradas(r).length }}</span>
                      de {{ cidadesDisponiveisParaRegra(r.aplica_a).length }}
                    </template>
                  </p>
                  <div
                    v-if="cidadesSelecionadasPermitidasNaRegra(r).length"
                    class="max-h-[5.5rem] overflow-y-auto rounded-lg border border-teal-200/70 bg-teal-50/40 p-2 dark:border-teal-900/50 dark:bg-teal-950/25"
                  >
                    <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-teal-800 dark:text-teal-300">
                      Selecionadas
                    </p>
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="c in cidadesSelecionadasPermitidasNaRegra(r)"
                        :key="'sel-' + r._key + '-' + c"
                        class="inline-flex max-w-full items-center gap-1 rounded-full border border-teal-200 bg-white px-2.5 py-0.5 text-xs font-medium text-teal-900 shadow-sm dark:border-teal-800 dark:bg-zinc-900 dark:text-teal-100"
                      >
                        <span class="truncate" :title="c">{{ c }}</span>
                        <button
                          type="button"
                          class="shrink-0 rounded-full p-0.5 text-teal-600 hover:bg-teal-100 hover:text-red-600 dark:text-teal-400 dark:hover:bg-teal-900/50 dark:hover:text-red-400"
                          aria-label="Remover cidade"
                          @click="toggleCidadeRegra(r, c, false)"
                        >
                          ×
                        </button>
                      </span>
                    </div>
                  </div>
                  <div
                    class="max-h-52 overflow-y-auto rounded-lg border border-zinc-100 dark:border-zinc-700/80 sm:max-h-64"
                  >
                    <ul
                      v-if="cidadesRegraFiltradas(r).length"
                      class="divide-y divide-zinc-100 dark:divide-zinc-800"
                    >
                      <li v-for="c in cidadesRegraFiltradas(r)" :key="r._key + '-c-' + c">
                        <label
                          class="flex cursor-pointer items-center gap-3 px-3 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/70"
                        >
                          <input
                            type="checkbox"
                            class="h-4 w-4 shrink-0 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30"
                            :checked="regraTemCidade(r, c)"
                            @change="toggleCidadeRegra(r, c, $event.target.checked)"
                          />
                          <span class="min-w-0 flex-1 text-sm text-zinc-800 dark:text-zinc-200">{{ c }}</span>
                        </label>
                      </li>
                    </ul>
                    <p
                      v-else
                      class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400"
                    >
                      Nenhuma cidade corresponde à busca. Limpe o filtro ou ajuste o texto.
                    </p>
                  </div>
                </div>
                <p
                  v-else
                  class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                >
                  Nenhuma cidade nesta lista. Adicione cidades em «Origens aceitas» (regra por origem) ou «Destinos aceitos» (regra por destino) acima.
                </p>
                <div
                  v-if="cidadesOrfasNaRegra(r).length"
                  class="mt-2 flex flex-wrap items-center gap-2 rounded-md border border-zinc-200 bg-zinc-100/90 px-3 py-2 text-xs dark:border-zinc-600 dark:bg-zinc-800/60"
                >
                  <span class="text-zinc-600 dark:text-zinc-400">Na regra, mas não aparecem na lista acima (remova ou cadastre de novo na lista):</span>
                  <span
                    v-for="c in cidadesOrfasNaRegra(r)"
                    :key="'orf-' + r._key + '-' + c"
                    class="inline-flex items-center gap-1 rounded-full border border-zinc-300 bg-white px-2 py-0.5 text-zinc-800 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                  >
                    {{ c }}
                    <button
                      type="button"
                      class="text-zinc-500 hover:text-red-600 dark:hover:text-red-400"
                      aria-label="Remover cidade da regra"
                      @click="toggleCidadeRegra(r, c, false)"
                    >
                      ×
                    </button>
                  </span>
                </div>
                <p
                  v-if="cidadesDisponiveisParaRegra(r.aplica_a).length && !r.cidades_selecionadas?.length"
                  class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"
                >
                  Marque ao menos uma cidade para esta regra ser salva.
                </p>
              </div>
              <div>
                <label class="ui-label">Peso mín. (t)</label>
                <input
                  v-model.number="r.peso_min_ton"
                  type="number"
                  step="0.01"
                  min="0"
                  class="ui-input"
                  placeholder="—"
                />
              </div>
              <div>
                <label class="ui-label">Peso máx. (t)</label>
                <input
                  v-model.number="r.peso_max_ton"
                  type="number"
                  step="0.01"
                  min="0"
                  class="ui-input"
                  placeholder="—"
                />
              </div>
              <div>
                <label class="ui-label">Valor carga mín.</label>
                <input
                  v-model.number="r.valor_carga_min"
                  type="number"
                  step="0.01"
                  min="0"
                  class="ui-input"
                  placeholder="Ex.: 400000"
                />
              </div>
              <div>
                <label class="ui-label">Valor carga máx.</label>
                <input
                  v-model.number="r.valor_carga_max"
                  type="number"
                  step="0.01"
                  min="0"
                  class="ui-input"
                  placeholder="—"
                />
              </div>
            </div>
          </div>
          <div
            v-if="!formParam.regras.length"
            class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/50 py-12 text-center dark:border-zinc-600 dark:bg-zinc-900/20"
          >
            <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Nenhuma regra extra ainda.</p>
            <p class="mt-1 px-4 text-xs text-zinc-500 dark:text-zinc-500">
              Use «Nova regra» para valor mínimo em Itatiba, peso máximo em Mauá, etc.
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

      <div class="ui-card ui-card-interactive p-6 sm:p-7">
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

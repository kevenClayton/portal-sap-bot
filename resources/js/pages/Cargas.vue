<template>
  <div>
    <PageHeader
      :title="modoAceitas ? 'Cargas aceitas' : 'Cargas'"
      eyebrow="Histórico"
      :description="
        modoAceitas
          ? 'Cargas capturadas no SAP ou aceites em modo teste. Use os filtros para refinar por datas, texto ou tipo.'
          : 'RFQs processadas pelo worker: filtre por status, datas e busca em RFQ, origem ou destino.'
      "
    />

    <nav
      class="mb-5 flex flex-wrap gap-2 rounded-2xl border border-zinc-200/90 bg-white/90 p-1.5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/60"
      aria-label="Secção de cargas"
    >
      <router-link
        to="/cargas"
        class="rounded-xl px-4 py-2 text-sm font-medium transition-colors"
        :class="
          !modoAceitas
            ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-600'
            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
        "
      >
        Todas as cargas
      </router-link>
      <router-link
        to="/cargas/aceitas"
        class="rounded-xl px-4 py-2 text-sm font-medium transition-colors"
        :class="
          modoAceitas
            ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-600'
            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'
        "
      >
        Cargas aceitas
      </router-link>
    </nav>

    <div
      class="mb-6 space-y-4 rounded-2xl border border-zinc-200/90 bg-white/80 p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50"
    >
      <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-[140px] flex-1">
          <label class="ui-label">Data inicial</label>
          <input v-model="filtrosForm.data_inicio" type="date" class="ui-input w-full" />
        </div>
        <div class="min-w-[140px] flex-1">
          <label class="ui-label">Data final</label>
          <input v-model="filtrosForm.data_fim" type="date" class="ui-input w-full" />
        </div>
        <div class="min-w-[200px] flex-[2]">
          <label class="ui-label">Buscar (RFQ, origem, destino)</label>
          <input
            v-model="filtrosForm.busca"
            type="search"
            class="ui-input w-full"
            placeholder="Ex.: Guarulhos ou número do RFQ"
            autocomplete="off"
            @keydown.enter.prevent="carregar"
          />
        </div>
        <button type="button" class="ui-btn-primary shrink-0 px-5" @click="carregar">Aplicar filtros</button>
      </div>
    </div>

    <div
      class="mb-6 flex flex-wrap items-center gap-2 rounded-2xl border border-zinc-200/90 bg-white/80 p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50"
    >
      <button
        v-for="opt in filtrosAtivos"
        :key="opt.id"
        type="button"
        class="ui-filter-pill"
        :class="filtro === opt.id ? 'ui-filter-pill-active' : 'ui-filter-pill-inactive'"
        @click="filtro = opt.id"
      >
        {{ opt.label }}
      </button>
    </div>

    <div class="ui-card overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
          <thead>
            <tr class="border-b border-zinc-200 bg-zinc-50/90 dark:border-zinc-800 dark:bg-zinc-900/80">
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                ID / RFQ
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Origem
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Destino
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Peso
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Distância
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Status
              </th>
              <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                Data
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
            <tr
              v-for="c in cargas"
              :key="c.id"
              class="bg-white/50 transition-colors hover:bg-emerald-50/40 dark:bg-transparent dark:hover:bg-zinc-800/40"
            >
              <td class="px-5 py-3.5 font-mono text-xs text-zinc-800 dark:text-zinc-200">
                {{ c.rfq_id || c.rfq_uuid || c.id }}
              </td>
              <td class="px-5 py-3.5 text-zinc-700 dark:text-zinc-300">{{ c.origem || '—' }}</td>
              <td class="px-5 py-3.5 text-zinc-700 dark:text-zinc-300">{{ c.destino || '—' }}</td>
              <td class="px-5 py-3.5 tabular-nums text-zinc-600 dark:text-zinc-400">{{ c.peso ?? '—' }}</td>
              <td class="px-5 py-3.5 tabular-nums text-zinc-600 dark:text-zinc-400">{{ c.distancia ?? '—' }}</td>
              <td class="px-5 py-3.5">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                  :class="badgeClass(c.status)"
                >
                  {{ c.status }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-xs text-zinc-500 dark:text-zinc-400">{{ formatarData(c.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="!cargas.length && !loading"
        class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center"
      >
        <div class="rounded-full bg-zinc-100 p-4 dark:bg-zinc-800">
          <svg class="h-10 w-10 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
            />
          </svg>
        </div>
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Nenhuma carga neste filtro.</p>
        <p class="max-w-xs text-xs text-zinc-500">Ajuste datas, busca ou o tipo de listagem acima.</p>
      </div>

      <div v-if="loading" class="flex items-center justify-center gap-2 px-6 py-12 text-sm text-zinc-500">
        <span
          class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"
          aria-hidden="true"
        />
        Carregando…
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const route = useRoute();
const modoAceitas = computed(() => route.name === 'cargas-aceitas');

const cargas = ref([]);
const loading = ref(false);
const filtro = ref('todas');

const filtrosForm = ref({
  data_inicio: '',
  data_fim: '',
  busca: '',
});

const filtrosGeral = [
  { id: 'todas', label: 'Todas' },
  { id: 'capturadas', label: 'Capturadas' },
  { id: 'analisadas', label: 'Analisadas' },
  { id: 'simuladas', label: 'Modo teste' },
];

const filtrosAceitas = [
  { id: 'aceitas_todas', label: 'Todas as aceites' },
  { id: 'aceitas_capturada', label: 'Capturadas no SAP' },
  { id: 'aceitas_simulada', label: 'Só modo teste' },
];

const filtrosAtivos = computed(() => (modoAceitas.value ? filtrosAceitas : filtrosGeral));

function badgeClass(status) {
  const map = {
    capturada: 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300',
    simulada: 'bg-violet-500/15 text-violet-800 dark:text-violet-300',
    analisada: 'bg-zinc-200/80 text-zinc-700 dark:bg-zinc-700/50 dark:text-zinc-300',
    ignorada: 'bg-amber-500/15 text-amber-800 dark:text-amber-300',
    erro: 'bg-red-500/15 text-red-800 dark:text-red-300',
  };
  return map[status] || 'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400';
}

function formatarData(s) {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleString('pt-BR');
}

function buildQueryParams() {
  const params = { per_page: 50 };
  const f = filtrosForm.value;
  if (f.data_inicio) {
    params.data_inicio = f.data_inicio;
  }
  if (f.data_fim) {
    params.data_fim = f.data_fim;
  }
  const q = (f.busca || '').trim();
  if (q) {
    params.busca = q;
  }
  return params;
}

async function carregar() {
  loading.value = true;
  try {
    const params = buildQueryParams();
    let url = '/api/cargas';

    if (modoAceitas.value) {
      url = '/api/cargas/aceitas';
      if (filtro.value === 'aceitas_capturada') {
        params.status = 'capturada';
      } else if (filtro.value === 'aceitas_simulada') {
        params.status = 'simulada';
      }
    } else if (filtro.value === 'capturadas') {
      url = '/api/cargas/capturadas';
    } else if (filtro.value === 'analisadas') {
      url = '/api/cargas/analisadas';
    } else if (filtro.value === 'simuladas') {
      url = '/api/cargas/simuladas';
    }

    const { data } = await axios.get(url, { params });
    cargas.value = data.data ?? data;
  } catch {
    cargas.value = [];
  } finally {
    loading.value = false;
  }
}

watch(
  () => route.name,
  (name) => {
    const next = name === 'cargas-aceitas' ? 'aceitas_todas' : 'todas';
    if (filtro.value !== next) {
      filtro.value = next;
    } else {
      carregar();
    }
  },
  { immediate: true },
);

watch(filtro, () => {
  carregar();
});
</script>

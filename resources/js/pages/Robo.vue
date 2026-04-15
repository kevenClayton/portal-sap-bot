<template>
  <div class="space-y-6">
    <PageHeader
      title="Robô"
      eyebrow="Controlo"
      description="Inicie ou pare o robô e acompanhe a actividade em tempo real."
    />

    <div class="ui-card ui-card-interactive p-6 sm:p-7">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Estado</h2>
          <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Quando está ativo, o robô procura e processa cargas conforme as regras definidas na configuração.
          </p>
          <p class="mt-3">
            <span
              class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold"
              :class="
                dashboard?.bot?.status === 'ativo'
                  ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300'
                  : 'bg-zinc-200/80 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'
              "
            >
              <span
                class="h-2 w-2 rounded-full"
                :class="dashboard?.bot?.status === 'ativo' ? 'animate-pulse bg-emerald-500' : 'bg-zinc-400'"
                aria-hidden="true"
              />
              {{ dashboard?.bot?.status === 'ativo' ? 'Ativo' : 'Inativo' }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="ui-btn-primary"
            :disabled="loading || !dashboard?.bot?.id || dashboard?.bot?.status === 'ativo'"
            @click="iniciar"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
            </svg>
            Iniciar
          </button>
          <button
            type="button"
            class="ui-btn-danger"
            :disabled="loading || !dashboard?.bot?.id || dashboard?.bot?.status !== 'ativo'"
            @click="parar"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.25 6.75v10.5M8.75 6.75v10.5" />
            </svg>
            Parar
          </button>
          <button type="button" class="ui-btn-secondary" :disabled="loading || !dashboard?.bot?.id" @click="reiniciar">
            Reiniciar
          </button>
        </div>
      </div>
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
      >
        <p
          v-if="erro"
          class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
        >
          {{ erro }}
        </p>
      </transition>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-950 shadow-xl ring-1 ring-white/5">
      <div
        class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-800 bg-zinc-900/80 px-4 py-2.5 text-xs text-zinc-400"
      >
        <div class="flex items-center gap-2">
          <span class="font-mono font-medium text-emerald-400/90">●</span>
          <span class="font-medium text-zinc-300">Actividade em tempo real</span>
          <span v-if="polling" class="rounded bg-zinc-800 px-1.5 py-0.5 text-[10px] text-zinc-500">a receber…</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <label class="flex cursor-pointer items-center gap-1.5 text-zinc-500">
            <input v-model="live" type="checkbox" class="rounded border-zinc-600 bg-zinc-800 text-emerald-600" />
            Actualização ao vivo
          </label>
          <button
            type="button"
            class="rounded-lg border border-zinc-700 bg-zinc-800 px-2.5 py-1 text-zinc-300 hover:bg-zinc-700"
            @click="recarregarTerminal"
          >
            Limpar e recarregar
          </button>
        </div>
      </div>
      <div
        ref="terminalEl"
        class="max-h-[min(520px,55vh)] min-h-[280px] overflow-auto px-4 py-3 font-mono text-[13px] leading-relaxed text-zinc-200"
        @scroll="onTerminalScroll"
      >
        <p v-if="terminalErro" class="text-red-400">{{ terminalErro }}</p>
        <template v-else>
          <p v-if="!linhas.length && !terminalLoading" class="text-zinc-500">
            Ainda não há actividade. Inicie o robô e aguarde alguns instantes.
          </p>
          <div v-for="line in linhas" :key="line.id" class="mb-1 break-words border-l-2 border-transparent pl-2 hover:border-zinc-700">
            <span class="select-none text-zinc-600">{{ formatarHora(line.created_at) }}</span>
            <span class="mx-2 select-none" :class="nivelCor(line.nivel)">[{{ line.nivel }}]</span>
            <span
              class="text-sky-400/90"
              :class="{ '!text-zinc-300': line.evento === 'terminal' }"
            >{{ line.evento }}</span>
            <span v-if="line.mensagem" class="text-zinc-300"> — {{ line.mensagem }}</span>
            <span v-if="line.contexto && Object.keys(line.contexto).length" class="mt-0.5 block pl-0 text-[11px] text-zinc-500">
              {{ formatarContexto(line.contexto) }}
            </span>
          </div>
        </template>
        <p v-if="terminalLoading" class="text-zinc-500">A carregar histórico…</p>
      </div>
    </div>
    <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
      A lista actualiza automaticamente enquanto «Actualização ao vivo» estiver ligada.
    </p>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const dashboard = ref(null);
const loading = ref(false);
const erro = ref('');

const terminalEl = ref(null);
const linhas = ref([]);
const terminalLoading = ref(false);
const terminalErro = ref('');
const live = ref(true);
const polling = ref(false);

let idCursor = 0;
let pollTimer = null;
let statusTimer = null;
let stickToBottom = true;

const MAX_LINHAS = 2000;

function paramsTerminal(extra = {}) {
  const id = dashboard.value?.bot?.id;
  return {
    ...extra,
    ...(id != null && id !== '' ? { bot_id: id } : {}),
  };
}

function formatarHora(s) {
  if (!s) return '';
  const d = new Date(s);
  return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

function nivelCor(nivel) {
  if (nivel === 'error') return 'text-red-400';
  if (nivel === 'warning') return 'text-amber-400';
  return 'text-emerald-400/90';
}

function formatarContexto(ctx) {
  try {
    return JSON.stringify(ctx);
  } catch {
    return String(ctx);
  }
}

function scrollTerminalToBottom() {
  nextTick(() => {
    const el = terminalEl.value;
    if (el && stickToBottom) {
      el.scrollTop = el.scrollHeight;
    }
  });
}

function onTerminalScroll() {
  const el = terminalEl.value;
  if (!el) return;
  const gap = el.scrollHeight - el.scrollTop - el.clientHeight;
  stickToBottom = gap < 120;
}

function trimLinhas() {
  if (linhas.value.length <= MAX_LINHAS) return;
  const remove = linhas.value.length - MAX_LINHAS;
  linhas.value = linhas.value.slice(remove);
}

async function carregarDashboard() {
  try {
    const { data } = await axios.get('/api/dashboard');
    dashboard.value = data;
  } catch {
    dashboard.value = null;
  }
}

async function iniciar() {
  if (!dashboard.value?.bot?.id) return;
  loading.value = true;
  erro.value = '';
  try {
    await axios.post(`/api/bots/${dashboard.value.bot.id}/iniciar`);
    await carregarDashboard();
    await recarregarTerminal();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao iniciar';
  } finally {
    loading.value = false;
  }
}

async function parar() {
  if (!dashboard.value?.bot?.id) return;
  loading.value = true;
  erro.value = '';
  try {
    await axios.post(`/api/bots/${dashboard.value.bot.id}/parar`);
    await carregarDashboard();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao parar';
  } finally {
    loading.value = false;
  }
}

async function reiniciar() {
  if (!dashboard.value?.bot?.id) return;
  loading.value = true;
  erro.value = '';
  try {
    await axios.post(`/api/bots/${dashboard.value.bot.id}/reiniciar`);
    await carregarDashboard();
    await recarregarTerminal();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao reiniciar';
  } finally {
    loading.value = false;
  }
}

async function recarregarTerminal() {
  terminalErro.value = '';
  terminalLoading.value = true;
  idCursor = 0;
  stickToBottom = true;
  try {
    const { data } = await axios.get('/api/logs', {
      params: paramsTerminal({ terminal_init: true, limit: 300 }),
    });
    const rows = data.data ?? [];
    linhas.value = rows;
    idCursor = rows.length ? rows[rows.length - 1].id : 0;
    scrollTerminalToBottom();
  } catch (e) {
    terminalErro.value = 'Não foi possível carregar os eventos.';
    linhas.value = [];
  } finally {
    terminalLoading.value = false;
  }
}

async function pollNovasLinhas() {
  if (!live.value) return;
  polling.value = true;
  try {
    if (idCursor < 1) {
      const { data } = await axios.get('/api/logs', {
        params: paramsTerminal({ terminal_init: true, limit: 80 }),
      });
      const rows = data.data ?? [];
      if (rows.length) {
        linhas.value = rows;
        idCursor = rows[rows.length - 1].id;
        trimLinhas();
        scrollTerminalToBottom();
      }
      return;
    }
    const { data } = await axios.get('/api/logs', {
      params: paramsTerminal({ after_id: idCursor }),
    });
    const novas = data.data ?? [];
    if (novas.length) {
      for (const row of novas) {
        if (row.id > idCursor) {
          linhas.value.push(row);
          idCursor = row.id;
        }
      }
      trimLinhas();
      scrollTerminalToBottom();
    }
  } catch {
    /* ignora falhas pontuais de rede */
  } finally {
    polling.value = false;
  }
}

function iniciarTimers() {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    pollNovasLinhas();
  }, 2000);
  if (statusTimer) clearInterval(statusTimer);
  statusTimer = setInterval(() => {
    if (typeof document !== 'undefined' && document.hidden) return;
    carregarDashboard();
  }, 5000);
}

function pararTimers() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
  if (statusTimer) {
    clearInterval(statusTimer);
    statusTimer = null;
  }
}

watch(live, (on) => {
  if (on) {
    iniciarTimers();
  } else {
    pararTimers();
  }
});

onMounted(async () => {
  await carregarDashboard();
  await recarregarTerminal();
  if (live.value) {
    iniciarTimers();
  }
});

onUnmounted(() => {
  pararTimers();
});
</script>

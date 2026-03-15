<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Dashboard</h2>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Status do Bot</p>
        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
          <span :class="dashboard?.bot?.status === 'ativo' ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400'">
            {{ dashboard?.bot?.status === 'ativo' ? 'Ativo' : 'Inativo' }}
          </span>
        </p>
      </div>
      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cargas analisadas hoje</p>
        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ dashboard?.cargas_analisadas_hoje ?? 0 }}</p>
      </div>
      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cargas capturadas hoje</p>
        <p class="mt-2 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ dashboard?.cargas_capturadas_hoje ?? 0 }}</p>
      </div>
      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Taxa de sucesso</p>
        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ dashboard?.taxa_sucesso ?? 0 }}%</p>
      </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
      <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">Controle do Bot</h3>
      <div class="flex flex-wrap gap-3">
        <button
          type="button"
          class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
          :disabled="loading || dashboard?.bot?.status === 'ativo'"
          @click="iniciar"
        >
          Iniciar
        </button>
        <button
          type="button"
          class="rounded-md bg-red-600/80 px-4 py-2 text-sm font-medium text-white hover:bg-red-600 disabled:opacity-50"
          :disabled="loading || dashboard?.bot?.status !== 'ativo'"
          @click="parar"
        >
          Parar
        </button>
        <button
          type="button"
          class="rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-200 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
          :disabled="loading"
          @click="reiniciar"
        >
          Reiniciar
        </button>
      </div>
      <p v-if="erro" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ erro }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const dashboard = ref(null);
const loading = ref(false);
const erro = ref('');

async function carregar() {
  try {
    const { data } = await axios.get('/api/dashboard');
    dashboard.value = data;
  } catch (e) {
    dashboard.value = { cargas_analisadas_hoje: 0, cargas_capturadas_hoje: 0, taxa_sucesso: 0 };
  }
}

async function iniciar() {
  if (!dashboard.value?.bot?.id) return;
  loading.value = true;
  erro.value = '';
  try {
    await axios.post(`/api/bots/${dashboard.value.bot.id}/iniciar`);
    await carregar();
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
    await carregar();
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
    await carregar();
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao reiniciar';
  } finally {
    loading.value = false;
  }
}

onMounted(carregar);
</script>

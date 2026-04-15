<template>
  <div class="space-y-8">
    <PageHeader
      title="Execuções"
      eyebrow="Histórico"
      description="Ciclos do worker: contagens por execução e estado (em andamento, concluída ou erro)."
    />

    <div class="ui-card overflow-hidden shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/80">
          <tr>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">ID</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Início</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Fim</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Analisadas</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Capturadas</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Ignoradas</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Simuladas</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900/30">
          <tr v-for="e in execucoes" :key="e.id">
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ e.id }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ formatarData(e.inicio_execucao) }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ formatarData(e.fim_execucao) }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ e.cargas_analisadas }}</td>
            <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400">{{ e.cargas_capturadas }}</td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ e.cargas_ignoradas }}</td>
            <td class="px-4 py-3 text-violet-600 dark:text-violet-400">{{ e.cargas_simuladas ?? 0 }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': e.status === 'concluida',
                  'bg-amber-500/20 text-amber-600 dark:text-amber-400': e.status === 'em_andamento',
                  'bg-red-500/20 text-red-600 dark:text-red-400': e.status === 'erro',
                }"
              >
                {{ e.status }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!execucoes.length && !loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Nenhuma execução.</div>
      <div v-if="loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Carregando...</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const execucoes = ref([]);
const loading = ref(false);

function formatarData(s) {
  if (!s) return '—';
  return new Date(s).toLocaleString('pt-BR');
}

async function carregar() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/execucoes', { params: { per_page: 50 } });
    execucoes.value = data.data ?? data;
  } catch {
    execucoes.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(carregar);
</script>

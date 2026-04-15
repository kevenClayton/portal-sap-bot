<template>
  <div class="space-y-8">
    <PageHeader
      title="Logs"
      eyebrow="Diagnóstico"
      description="Eventos enviados pelo worker e pela API: erros de auth, cargas aceitas, avisos."
    />

    <div class="ui-card max-h-[70vh] overflow-hidden shadow-sm">
      <div class="overflow-auto">
        <table class="w-full text-left text-sm">
          <thead class="sticky top-0 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
            <tr>
              <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Data</th>
              <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Nível</th>
              <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Evento</th>
              <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Mensagem</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900/30">
            <tr v-for="log in logs" :key="log.id">
              <td class="whitespace-nowrap px-4 py-2 text-zinc-500 dark:text-zinc-400">{{ formatarData(log.created_at) }}</td>
              <td class="px-4 py-2">
                <span
                  class="rounded px-2 py-0.5 text-xs font-medium"
                  :class="{
                    'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': log.nivel === 'info',
                    'bg-amber-500/20 text-amber-600 dark:text-amber-400': log.nivel === 'warning',
                    'bg-red-500/20 text-red-600 dark:text-red-400': log.nivel === 'error',
                  }"
                >
                  {{ log.nivel }}
                </span>
              </td>
              <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ log.evento }}</td>
              <td class="max-w-md truncate px-4 py-2 text-zinc-600 dark:text-zinc-400" :title="log.mensagem">{{ log.mensagem || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!logs.length && !loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Nenhum log.</div>
      <div v-if="loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Carregando...</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const logs = ref([]);
const loading = ref(false);

function formatarData(s) {
  if (!s) return '—';
  return new Date(s).toLocaleString('pt-BR');
}

async function carregar() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/logs', { params: { per_page: 100 } });
    logs.value = data.data ?? data;
  } catch {
    logs.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(carregar);
</script>

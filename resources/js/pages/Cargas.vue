<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Cargas</h2>

    <div class="flex gap-2">
      <button
        type="button"
        class="rounded-md border px-3 py-1.5 text-sm"
        :class="filtro === 'todas' ? 'border-emerald-500 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800'"
        @click="filtro = 'todas'; carregar()"
      >
        Todas
      </button>
      <button
        type="button"
        class="rounded-md border px-3 py-1.5 text-sm"
        :class="filtro === 'capturadas' ? 'border-emerald-500 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800'"
        @click="filtro = 'capturadas'; carregar()"
      >
        Capturadas
      </button>
      <button
        type="button"
        class="rounded-md border px-3 py-1.5 text-sm"
        :class="filtro === 'analisadas' ? 'border-emerald-500 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'border-zinc-300 text-zinc-600 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800'"
        @click="filtro = 'analisadas'; carregar()"
      >
        Analisadas
      </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/80">
          <tr>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">ID / RFQ</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Origem</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Destino</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Peso</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Distância</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Status</th>
            <th class="px-4 py-3 font-medium text-zinc-500 dark:text-zinc-400">Data</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900/30">
          <tr v-for="c in cargas" :key="c.id">
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ c.rfq_id || c.rfq_uuid || c.id }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ c.origem || '—' }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ c.destino || '—' }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ c.peso ?? '—' }}</td>
            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ c.distancia ?? '—' }}</td>
            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400': c.status === 'capturada',
                  'bg-zinc-200 text-zinc-600 dark:bg-zinc-600/30 dark:text-zinc-400': c.status === 'analisada',
                  'bg-amber-500/20 text-amber-600 dark:text-amber-400': c.status === 'ignorada',
                  'bg-red-500/20 text-red-600 dark:text-red-400': c.status === 'erro',
                }"
              >
                {{ c.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ formatarData(c.created_at) }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="!cargas.length && !loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Nenhuma carga encontrada.</div>
      <div v-if="loading" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Carregando...</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const cargas = ref([]);
const loading = ref(false);
const filtro = ref('todas');

function formatarData(s) {
  if (!s) return '—';
  const d = new Date(s);
  return d.toLocaleString('pt-BR');
}

async function carregar() {
  loading.value = true;
  try {
    const url = filtro.value === 'capturadas'
      ? '/api/cargas/capturadas'
      : filtro.value === 'analisadas'
        ? '/api/cargas/analisadas'
        : '/api/cargas';
    const { data } = await axios.get(url, { params: { per_page: 50 } });
    cargas.value = data.data ?? data;
  } catch {
    cargas.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(carregar);
watch(filtro, carregar);
</script>

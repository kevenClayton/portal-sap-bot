<template>
  <div class="space-y-8">
    <PageHeader title="Relatório" eyebrow="Análise" description="Agregações por RFQ único (rfq_uuid). Novas leituras atualizam o mesmo registro, sem duplicar linhas.">
      <template #actions>
        <div class="flex flex-wrap items-end gap-2">
          <div>
            <label class="ui-label normal-case tracking-normal">De</label>
            <input v-model="filtros.data_inicio" type="date" class="ui-input !mt-1 w-auto min-w-[10rem]" />
          </div>
          <div>
            <label class="ui-label normal-case tracking-normal">Até</label>
            <input v-model="filtros.data_fim" type="date" class="ui-input !mt-1 w-auto min-w-[10rem]" />
          </div>
          <button type="button" class="ui-btn-primary !mt-5" @click="carregar">Atualizar</button>
        </div>
      </template>
    </PageHeader>

    <div
      v-if="resumo"
      class="rounded-lg border border-emerald-200 bg-emerald-50/80 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/30"
    >
      <p class="text-sm font-medium text-emerald-900 dark:text-emerald-200">
        Total de cargas distintas (RFQ)
        <span class="ml-2 text-2xl font-semibold tabular-nums">{{ fmtInt(resumo.total_cargas) }}</span>
      </p>
    </div>

    <div v-if="erro" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
      {{ erro }}
    </div>

    <div v-if="loading" class="text-sm text-zinc-500 dark:text-zinc-400">Carregando…</div>

    <template v-else-if="resumo">
      <section class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
          <h3 class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200">
            Por cidade de origem
          </h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
              <tr>
                <th class="px-4 py-2">Origem</th>
                <th class="px-4 py-2 text-right">Qtd.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="row in resumo.por_origem" :key="row.origem" class="bg-white dark:bg-zinc-900/20">
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.origem }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtInt(row.total) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-if="!resumo.por_origem?.length" class="px-4 py-6 text-center text-sm text-zinc-500">Sem dados.</p>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
          <h3 class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200">
            Por cidade de destino
          </h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
              <tr>
                <th class="px-4 py-2">Destino</th>
                <th class="px-4 py-2 text-right">Qtd.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="row in resumo.por_destino" :key="row.destino" class="bg-white dark:bg-zinc-900/20">
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.destino }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtInt(row.total) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-if="!resumo.por_destino?.length" class="px-4 py-6 text-center text-sm text-zinc-500">Sem dados.</p>
        </div>
      </section>

      <section class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
        <h3 class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200">
          Por rota (origem → destino)
        </h3>
        <div class="max-h-[28rem] overflow-auto">
          <table class="w-full text-left text-sm">
            <thead class="sticky top-0 border-b border-zinc-200 bg-zinc-50 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/95 dark:text-zinc-400">
              <tr>
                <th class="px-4 py-2">Origem</th>
                <th class="px-4 py-2">Destino</th>
                <th class="px-4 py-2 text-right">Qtd.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="(row, i) in resumo.por_rota" :key="i" class="bg-white dark:bg-zinc-900/20">
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.origem }}</td>
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.destino }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtInt(row.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!resumo.por_rota?.length" class="px-4 py-6 text-center text-sm text-zinc-500">Sem dados.</p>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
          <h3 class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200">
            Por status
          </h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
              <tr>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2 text-right">Qtd.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="row in resumo.por_status" :key="row.status" class="bg-white dark:bg-zinc-900/20">
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.status }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtInt(row.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800">
          <h3 class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900/80 dark:text-zinc-200">
            Peso acumulado por origem (top 50)
          </h3>
          <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
              <tr>
                <th class="px-4 py-2">Origem</th>
                <th class="px-4 py-2 text-right">Cargas</th>
                <th class="px-4 py-2 text-right">Peso total (kg)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
              <tr v-for="row in resumo.peso_por_origem" :key="row.origem" class="bg-white dark:bg-zinc-900/20">
                <td class="px-4 py-2 text-zinc-800 dark:text-zinc-200">{{ row.origem }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtInt(row.total) }}</td>
                <td class="px-4 py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-300">{{ fmtNum(row.peso_total) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-if="!resumo.peso_por_origem?.length" class="px-4 py-6 text-center text-sm text-zinc-500">Sem dados.</p>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const resumo = ref(null);
const loading = ref(false);
const erro = ref('');
const filtros = reactive({ data_inicio: '', data_fim: '' });

function fmtInt(n) {
  return new Intl.NumberFormat('pt-BR').format(Number(n) || 0);
}

function fmtNum(n) {
  return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n) || 0);
}

async function carregar() {
  loading.value = true;
  erro.value = '';
  try {
    const params = {};
    if (filtros.data_inicio) params.data_inicio = filtros.data_inicio;
    if (filtros.data_fim) params.data_fim = filtros.data_fim;
    const { data } = await axios.get('/api/relatorio/resumo', { params });
    resumo.value = data;
  } catch (e) {
    resumo.value = null;
    erro.value = e.response?.data?.message || 'Não foi possível carregar o relatório.';
  } finally {
    loading.value = false;
  }
}

onMounted(carregar);
</script>

<template>
  <div class="space-y-6">
    <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Configuração de Regras</h2>

    <div v-if="!bot" class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
      <p class="text-zinc-600 dark:text-zinc-400">Nenhum bot configurado.</p>
      <button
        type="button"
        class="mt-3 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
        @click="criarBot"
      >
        Criar Bot
      </button>
    </div>

    <template v-else>
      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">Bot</h3>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Intervalo (segundos)</label>
            <input
              v-model.number="formBot.intervalo"
              type="number"
              min="10"
              max="3600"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Horário início</label>
            <input
              v-model="formBot.horario_inicio"
              type="time"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Horário fim</label>
            <input
              v-model="formBot.horario_fim"
              type="time"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
        </div>
        <button
          type="button"
          class="mt-4 rounded-md bg-zinc-200 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-600"
          @click="salvarBot"
        >
          Salvar Bot
        </button>
      </div>

      <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
        <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">Parâmetros de filtro</h3>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Origem</label>
            <input
              v-model="formParam.origem"
              type="text"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
              placeholder="Cidade"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Destino</label>
            <input
              v-model="formParam.destino"
              type="text"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
              placeholder="Cidade"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Peso mínimo</label>
            <input
              v-model.number="formParam.peso_min"
              type="number"
              step="0.01"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Distância máxima</label>
            <input
              v-model.number="formParam.distancia_max"
              type="number"
              step="0.01"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Tipo veículo</label>
            <select
              v-model="formParam.tipo_veiculo"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            >
              <option value="">—</option>
              <option value="ZZTRUCK">ZZTRUCK</option>
              <option value="ZZBITRUCK">ZZBITRUCK</option>
              <option value="ZZCARRETA">ZZCARRETA</option>
              <option value="ZZRODOTREM">ZZRODOTREM</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-zinc-600 dark:text-zinc-500">Intervalo busca (s)</label>
            <input
              v-model.number="formParam.intervalo_busca"
              type="number"
              min="10"
              class="mt-1 w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
            />
          </div>
        </div>
        <button
          type="button"
          class="mt-4 rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700"
          @click="salvarParametro"
        >
          Salvar Parâmetros
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const bot = ref(null);
const formBot = ref({ intervalo: 60, horario_inicio: '', horario_fim: '' });
const formParam = ref({
  origem: '',
  destino: '',
  peso_min: null,
  distancia_max: null,
  tipo_veiculo: '',
  intervalo_busca: 60,
});

async function carregar() {
  try {
    const { data } = await axios.get('/api/bots');
    const b = Array.isArray(data) ? data[0] : data;
    bot.value = b || null;
    if (b) {
      formBot.value = { intervalo: b.intervalo ?? 60, horario_inicio: b.horario_inicio ?? '', horario_fim: b.horario_fim ?? '' };
      const params = b.parametros || [];
      const p = params[0];
      if (p) {
        formParam.value = {
          origem: p.origem ?? '',
          destino: p.destino ?? '',
          peso_min: p.peso_min ?? null,
          distancia_max: p.distancia_max ?? null,
          tipo_veiculo: p.tipo_veiculo ?? '',
          intervalo_busca: p.intervalo_busca ?? 60,
        };
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
    const params = bot.value.parametros || [];
    if (params.length) {
      await axios.put(`/api/parametros/${params[0].id}`, formParam.value);
    } else {
      await axios.post(`/api/bots/${bot.value.id}/parametros`, formParam.value);
    }
    await carregar();
  } catch (e) {
    console.error(e);
  }
}

onMounted(carregar);
</script>

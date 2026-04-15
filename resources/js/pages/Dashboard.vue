<template>
  <div>
    <PageHeader
      title="Dashboard"
      eyebrow="Visão geral"
      description="Acompanhe o bot e os volumes do dia. Para iniciar, parar ou ver a actividade em tempo real, use o menu Robô."
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <div
        v-for="card in statCards"
        :key="card.key"
        class="ui-card ui-card-interactive relative overflow-hidden p-5 sm:p-6"
      >
        <div
          class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 rounded-full opacity-[0.12]"
          :class="card.blob"
        />
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
          {{ card.label }}
        </p>
        <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight" :class="card.valueClass">
          {{ card.value }}
        </p>
      </div>
    </div>

    <div class="ui-card ui-card-interactive mt-8 p-6 sm:p-7">
      <h2 class="ui-section-title">
        <span
          class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M8.25 3v2.25M15.75 3v2.25M3 18.75h18M4.5 19.5h15M4.5 14.25h15M4.5 9h15M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5z"
            />
          </svg>
        </span>
        Controlo do robô
      </h2>
      <p class="ui-section-desc mb-6 max-w-xl">
        O arranque, a paragem e a vista em tempo real dos eventos do worker estão na página
        <strong class="text-zinc-800 dark:text-zinc-200">Robô</strong>: inicia o processo e segue a actividade como num terminal.
      </p>
      <router-link
        to="/robo"
        class="ui-btn-primary inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
        </svg>
        Abrir Robô
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '@/components/PageHeader.vue';

const dashboard = ref(null);

const statCards = computed(() => {
  const d = dashboard.value;
  const ativo = d?.bot?.status === 'ativo';
  return [
    {
      key: 'status',
      label: 'Status',
      value: ativo ? 'Ativo' : 'Inativo',
      valueClass: ativo ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400',
      blob: 'bg-emerald-500',
    },
    {
      key: 'analisadas',
      label: 'Analisadas hoje',
      value: d?.cargas_analisadas_hoje ?? 0,
      valueClass: 'text-zinc-900 dark:text-zinc-100',
      blob: 'bg-zinc-400',
    },
    {
      key: 'capturadas',
      label: 'Capturadas hoje',
      value: d?.cargas_capturadas_hoje ?? 0,
      valueClass: 'text-emerald-600 dark:text-emerald-400',
      blob: 'bg-emerald-500',
    },
    {
      key: 'simuladas',
      label: 'Modo teste hoje',
      value: d?.cargas_simuladas_hoje ?? 0,
      valueClass: 'text-violet-600 dark:text-violet-400',
      blob: 'bg-violet-500',
    },
    {
      key: 'taxa',
      label: 'Taxa de sucesso',
      value: `${d?.taxa_sucesso ?? 0}%`,
      valueClass: 'text-zinc-900 dark:text-zinc-100',
      blob: 'bg-sky-500',
    },
  ];
});

async function carregar() {
  try {
    const { data } = await axios.get('/api/dashboard');
    dashboard.value = data;
  } catch (e) {
    dashboard.value = {
      cargas_analisadas_hoje: 0,
      cargas_capturadas_hoje: 0,
      cargas_simuladas_hoje: 0,
      taxa_sucesso: 0,
    };
  }
}

onMounted(carregar);
</script>


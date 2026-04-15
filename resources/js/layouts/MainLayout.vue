<template>
  <div class="flex min-h-screen bg-zinc-100 dark:bg-zinc-950">
    <aside
      class="sticky top-0 flex h-screen w-64 shrink-0 flex-col border-r border-zinc-200/80 bg-white shadow-[4px_0_32px_-12px_rgba(0,0,0,0.08)] dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-[4px_0_32px_-12px_rgba(0,0,0,0.4)]"
    >
      <div class="flex items-center gap-3 border-b border-zinc-100 px-5 py-5 dark:border-zinc-800/80">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-lg font-bold text-white shadow-md shadow-emerald-500/25"
        >
          B
        </div>
        <div class="min-w-0">
          <h1 class="truncate text-sm font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Bot SAP</h1>
          <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">ArcelorMittal</p>
        </div>
      </div>

      <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3" aria-label="Principal">
        <template v-for="item in navItems" :key="item.to || item.group">
          <div v-if="item.children" class="mb-1 mt-1 space-y-0.5 first:mt-0">
            <p
              class="flex items-center gap-2 px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"
            >
              <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400"
                v-html="item.icon"
              />
              {{ item.group }}
            </p>
            <router-link
              v-for="sub in item.children"
              :key="sub.to"
              v-slot="{ href, navigate, isActive }"
              :to="sub.to"
              custom
            >
              <a
                :href="href"
                class="group ml-2 flex items-center gap-3 rounded-xl border-l-2 border-transparent py-2 pl-4 pr-3 text-sm font-medium transition-all"
                :class="
                  isActive
                    ? 'border-emerald-500 bg-emerald-50/90 text-emerald-900 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-100'
                    : 'text-zinc-600 hover:border-zinc-200 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/50 dark:hover:text-zinc-100'
                "
                @click="navigate"
              >
                {{ sub.label }}
              </a>
            </router-link>
          </div>
          <router-link
            v-else
            v-slot="{ href, navigate, isActive }"
            :to="item.to"
            custom
          >
            <a
              :href="href"
              class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
              :class="
                isActive
                  ? 'bg-emerald-50 text-emerald-800 shadow-sm ring-1 ring-emerald-500/20 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-500/25'
                  : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-100'
              "
              @click="navigate"
            >
              <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                :class="
                  isActive
                    ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
                    : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200 group-hover:text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:group-hover:bg-zinc-700 dark:group-hover:text-zinc-200'
                "
                v-html="item.icon"
              />
              {{ item.label }}
            </a>
          </router-link>
        </template>
      </nav>

      <div class="border-t border-zinc-100 p-3 dark:border-zinc-800/80">
        <div
          class="mb-3 rounded-xl bg-zinc-50 px-3 py-2.5 dark:bg-zinc-800/50"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Sessão</p>
          <p class="mt-0.5 truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
            {{ user?.name || user?.email || '—' }}
          </p>
        </div>
        <p class="mb-1.5 px-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
          Aparência
        </p>
        <ThemeSwitcher class="w-full" />
        <button
          type="button"
          class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-transparent py-2.5 text-sm font-medium text-zinc-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:text-zinc-400 dark:hover:border-red-900/50 dark:hover:bg-red-950/30 dark:hover:text-red-300"
          @click="logout"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
            />
          </svg>
          Sair
        </button>
      </div>
    </aside>

    <main class="relative min-h-screen min-w-0 flex-1">
      <div class="ui-content-wrap">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const router = useRouter();
const { user, logout: doLogout } = useAuth();

const icon = (path) =>
  `<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">${path}</svg>`;

const navItems = [
  {
    to: '/',
    label: 'Dashboard',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />',
    ),
  },
  {
    to: '/robo',
    label: 'Robô',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v2.25M15.75 3v2.25M3 18.75h18M4.5 19.5h15M4.5 14.25h15M4.5 9h15M6.75 6.75h10.5a1.5 1.5 0 011.5 1.5v10.5a1.5 1.5 0 01-1.5 1.5H6.75a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5zM9 12h.008v.008H9V12zm3 0h.008v.008H12V12zm3 0h.008v.008H15V12z" />',
    ),
  },
  {
    to: '/config',
    label: 'Configuração',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
    ),
  },
  {
    group: 'Cargas',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />',
    ),
    children: [
      { to: '/cargas', label: 'Todas as cargas' },
      { to: '/cargas/aceitas', label: 'Cargas aceitas' },
    ],
  },
  {
    to: '/relatorio',
    label: 'Relatório',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
    ),
  },
  {
    to: '/execucoes',
    label: 'Execuções',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ),
  },
  {
    to: '/logs',
    label: 'Logs',
    icon: icon(
      '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />',
    ),
  },
];

async function logout() {
  await doLogout();
  router.push('/login');
}
</script>

<template>
  <div class="flex min-h-screen bg-zinc-100 dark:bg-zinc-950">
    <aside class="flex w-56 flex-col border-r border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
      <div class="border-b border-zinc-200 p-4 dark:border-zinc-800">
        <h1 class="font-semibold text-zinc-900 dark:text-zinc-100">Bot SAP</h1>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">ArcelorMittal</p>
      </div>
      <nav class="flex-1 space-y-0.5 p-2">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors"
          :class="$route.path === item.to
            ? 'bg-emerald-600 text-white dark:bg-zinc-800 dark:text-white'
            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-zinc-200'"
        >
          {{ item.label }}
        </router-link>
      </nav>
      <div class="border-t border-zinc-200 p-2 dark:border-zinc-800">
        <div class="flex items-center justify-between rounded-md px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">
          <span class="truncate">{{ user?.name || user?.email }}</span>
        </div>
        <div class="mt-1 flex gap-1">
          <button
            type="button"
            :title="isDark ? 'Modo claro' : 'Modo escuro'"
            class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
            @click="toggleTheme"
          >
            <span v-if="isDark">☀️</span>
            <span v-else>🌙</span>
          </button>
          <button
            type="button"
            class="rounded-md px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-red-400"
            @click="logout"
          >
            Sair
          </button>
        </div>
      </div>
    </aside>
    <main class="flex-1 overflow-auto p-6">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useTheme } from '@/composables/useTheme';

const router = useRouter();
const { user, logout: doLogout } = useAuth();
const { isDark, toggle: toggleTheme } = useTheme();

const navItems = [
  { to: '/', label: 'Dashboard' },
  { to: '/config', label: 'Configuração' },
  { to: '/cargas', label: 'Cargas' },
  { to: '/execucoes', label: 'Execuções' },
  { to: '/logs', label: 'Logs' },
];

async function logout() {
  await doLogout();
  router.push('/login');
}
</script>

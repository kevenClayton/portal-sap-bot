<template>
  <div
    class="relative flex min-h-screen flex-col bg-zinc-50 dark:bg-zinc-950 lg:flex-row"
  >
    <div
      class="relative hidden flex-1 flex-col justify-between overflow-hidden bg-gradient-to-br from-emerald-700 via-teal-800 to-zinc-900 p-10 text-white lg:flex xl:p-14"
    >
      <div class="pointer-events-none absolute inset-0 opacity-40">
        <div
          class="absolute -left-20 top-0 h-96 w-96 rounded-full bg-emerald-400/30 blur-3xl"
        />
        <div
          class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-teal-300/20 blur-3xl"
        />
      </div>
      <div class="relative z-10">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-xl font-bold backdrop-blur">
          B
        </div>
        <h2 class="mt-10 max-w-md text-3xl font-bold leading-tight tracking-tight">
          Automação de tendering SAP, com regras e rastreio no painel.
        </h2>
        <p class="mt-4 max-w-sm text-sm leading-relaxed text-emerald-100/90">
          Configure cidades, limites e notificações; o worker executa em segundo plano com segurança.
        </p>
      </div>
      <p class="relative z-10 text-xs text-emerald-200/70">ArcelorMittal · Bot SAP</p>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-8">
      <div class="absolute right-4 top-4 flex flex-col items-end gap-1 sm:right-8 sm:top-8">
        <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Aparência</span>
        <ThemeSwitcher />
      </div>

      <div class="w-full max-w-md">
        <div class="mb-8 text-center lg:text-left">
          <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50">Entrar</h1>
          <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Use o e-mail corporativo e a senha do portal.</p>
        </div>

        <div
          class="rounded-2xl border border-zinc-200/90 bg-white/90 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/70 dark:shadow-black/40 sm:p-8"
        >
          <form class="space-y-5" @submit.prevent="submit">
            <div>
              <label for="email" class="ui-label">E-mail</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                class="ui-input"
                placeholder="seu@email.com"
              />
            </div>
            <div>
              <label for="password" class="ui-label">Senha</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                autocomplete="current-password"
                class="ui-input"
              />
            </div>
            <div class="flex items-center gap-2">
              <input
                id="remember"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500/30 dark:border-zinc-600 dark:bg-zinc-800"
              />
              <label for="remember" class="text-sm text-zinc-600 dark:text-zinc-400">Lembrar de mim</label>
            </div>
            <p v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/40 dark:text-red-200">
              {{ error }}
            </p>
            <button type="submit" :disabled="loading" class="ui-btn-primary w-full py-3">
              {{ loading ? 'Entrando…' : 'Entrar' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuth } from '@/composables/useAuth';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const router = useRouter();
const { login } = useAuth();

const form = reactive({ email: '', password: '', remember: false });
const loading = ref(false);
const error = ref('');

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie');
    await login(form.email, form.password, form.remember);
    const redirect = router.currentRoute.value.query.redirect || '/';
    router.replace(redirect);
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'E-mail ou senha inválidos.';
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-white px-4 dark:bg-zinc-950">
    <div class="w-full max-w-sm space-y-6 rounded-xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
      <div class="text-center">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Bot SAP</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">ArcelorMittal</p>
      </div>
      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">E-mail</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            placeholder="seu@email.com"
          />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Senha</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            autocomplete="current-password"
            class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
          />
        </div>
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800"
          />
          <label for="remember" class="ml-2 text-sm text-zinc-600 dark:text-zinc-400">Lembrar de mim</label>
        </div>
        <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-zinc-900"
        >
          {{ loading ? 'Entrando…' : 'Entrar' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuth } from '@/composables/useAuth';

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

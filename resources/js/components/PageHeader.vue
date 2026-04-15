<template>
  <header class="mb-8 sm:mb-10">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p
          v-if="eyebrow"
          class="mb-1 text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400"
        >
          {{ eyebrow }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-3xl">
          {{ title }}
        </h1>
        <p v-if="description" class="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="mt-4 shrink-0 sm:mt-0">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
});
</script>

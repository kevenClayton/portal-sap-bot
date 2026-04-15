import { ref, watch } from 'vue';

const STORAGE_KEY = 'portal-sap-bot-theme';

function readStoredDark() {
  if (typeof localStorage === 'undefined') {
    return false;
  }
  return localStorage.getItem(STORAGE_KEY) === 'dark';
}

const isDark = ref(readStoredDark());

function applyTheme(dark) {
  const html = document.documentElement;
  if (dark) {
    html.classList.add('dark');
  } else {
    html.classList.remove('dark');
  }
}

watch(isDark, (v) => applyTheme(v), { immediate: true, flush: 'sync' });

export function useTheme() {
  function toggle() {
    isDark.value = !isDark.value;
    localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light');
  }

  function setDark(dark) {
    const next = !!dark;
    if (isDark.value === next) {
      return;
    }
    isDark.value = next;
    localStorage.setItem(STORAGE_KEY, next ? 'dark' : 'light');
  }

  return {
    isDark,
    toggle,
    setDark,
  };
}

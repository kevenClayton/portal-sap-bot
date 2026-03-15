import { ref, watch, onMounted } from 'vue';

const STORAGE_KEY = 'portal-sap-bot-theme';

const isDark = ref(true);

function applyTheme(dark) {
  const html = document.documentElement;
  if (dark) {
    html.classList.add('dark');
  } else {
    html.classList.remove('dark');
  }
  isDark.value = dark;
}

export function useTheme() {
  function toggle() {
    isDark.value = !isDark.value;
    localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light');
    applyTheme(isDark.value);
  }

  function setDark(dark) {
    isDark.value = !!dark;
    localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light');
    applyTheme(isDark.value);
  }

  onMounted(() => {
    const saved = localStorage.getItem(STORAGE_KEY);
    const dark = saved === 'light' ? false : true;
    applyTheme(dark);
  });

  watch(isDark, (v) => applyTheme(v), { flush: 'sync' });

  return {
    isDark,
    toggle,
    setDark,
  };
}

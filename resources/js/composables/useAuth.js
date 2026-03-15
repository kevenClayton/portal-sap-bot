import { ref, computed } from 'vue';
import axios from 'axios';

const user = ref(null);
const authChecked = ref(false);

export function useAuth() {
  const isAuthenticated = computed(() => !!user.value);

  async function fetchUser() {
    if (authChecked.value) return;
    authChecked.value = true;
    try {
      const { data } = await axios.get('/api/user');
      user.value = data.user ?? data;
    } catch {
      user.value = null;
    }
  }

  function resetAuth() {
    authChecked.value = false;
    user.value = null;
  }

  async function login(email, password, remember = false) {
    const { data } = await axios.post('/api/login', { email, password, remember });
    user.value = data.user ?? data;
    return data;
  }

  async function logout() {
    await axios.post('/api/logout');
    user.value = null;
  }

  return {
    user,
    isAuthenticated,
    authChecked: computed(() => authChecked.value),
    fetchUser,
    login,
    logout,
    resetAuth,
  };
}

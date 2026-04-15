import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/Login.vue'),
    meta: { title: 'Login', public: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: () => import('@/pages/Dashboard.vue'), meta: { title: 'Dashboard' } },
      { path: 'robo', name: 'robo', component: () => import('@/pages/Robo.vue'), meta: { title: 'Robô' } },
      { path: 'config', name: 'config', component: () => import('@/pages/Config.vue'), meta: { title: 'Configuração' } },
      {
        path: 'cargas/aceitas',
        name: 'cargas-aceitas',
        component: () => import('@/pages/Cargas.vue'),
        meta: { title: 'Cargas aceitas', cargasModo: 'aceitas' },
      },
      {
        path: 'cargas',
        name: 'cargas',
        component: () => import('@/pages/Cargas.vue'),
        meta: { title: 'Cargas', cargasModo: 'geral' },
      },
      { path: 'execucoes', name: 'execucoes', component: () => import('@/pages/Execucoes.vue'), meta: { title: 'Execuções' } },
      { path: 'logs', name: 'logs', component: () => import('@/pages/Logs.vue'), meta: { title: 'Logs' } },
      { path: 'relatorio', name: 'relatorio', component: () => import('@/pages/Relatorio.vue'), meta: { title: 'Relatório' } },
    ],
  },
];

const router = createRouter({
  history: createWebHistory('/'),
  routes,
});

router.beforeEach(async (to) => {
  document.title = to.meta.title ? `${to.meta.title} - Bot SAP` : 'Bot SAP';

  const { fetchUser, isAuthenticated } = useAuth();
  await fetchUser();

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { path: '/login', query: { redirect: to.fullPath } };
  }
  if (to.path === '/login' && isAuthenticated.value) {
    return { path: (to.query.redirect) || '/' };
  }
});

export default router;

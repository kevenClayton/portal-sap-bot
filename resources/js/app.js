import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';
import { useAuth } from '@/composables/useAuth';

axios.defaults.baseURL = '';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
axios.interceptors.response.use(
    (r) => r,
    (err) => {
        if (err.response?.status === 401) {
            useAuth().resetAuth();
        }
        return Promise.reject(err);
    }
);

const app = createApp(App);
app.use(router);
app.mount('#app');

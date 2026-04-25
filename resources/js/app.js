import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';
import { useAuth } from '@/composables/useAuth';

axios.defaults.baseURL = '';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
axios.interceptors.response.use(
    (r) => r,
    async (err) => {
        if (err.response?.status === 419 && err.config && !err.config._csrfRetried) {
            err.config._csrfRetried = true;
            await axios.get('/sanctum/csrf-cookie');
            return axios(err.config);
        }

        if (err.response?.status === 401) {
            useAuth().resetAuth();
        }
        return Promise.reject(err);
    }
);

const app = createApp(App);
app.use(router);
app.mount('#app');

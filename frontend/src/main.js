import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { configureAuth } from './api/client';
import { useAuthStore } from './stores/auth';
import './styles/main.css';

/**
 * Инициализирует Vue-приложение, восстанавливает сессию и подключает маршрутизатор.
 *
 * @returns {Promise<void>} Промис, завершающийся после монтирования приложения.
 */
async function bootstrap() {
  const app = createApp(App);
  const pinia = createPinia();

  app.use(pinia);

  const auth = useAuthStore();

  configureAuth({
    /**
     * Возвращает актуальный access-токен из хранилища авторизации.
     *
     * @returns {string|null} Текущий токен или `null`, если пользователь не авторизован.
     */
    getToken: () => auth.token,
    refresh: auth.refresh,
    /**
     * Очищает локальную сессию и сообщает приложению об истечении авторизации.
     *
     * @returns {void}
     */
    onExpired: () => {
      auth.clear();
      window.dispatchEvent(new CustomEvent('auth:expired'));
    },
  });

  await auth.restore();

  app.use(router).mount('#app');
}

bootstrap();

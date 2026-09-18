import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import api from '../api/client';

let refreshPromise = null;

/**
 * Создаёт Pinia-хранилище состояния и действий авторизации.
 *
 * @returns {object} Состояние сессии и методы управления авторизацией.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref(null);
  const user = ref(null);
  const restored = ref(false);
  const isAuthenticated = computed(() => Boolean(token.value && user.value));

  /**
   * Сохраняет данные новой авторизованной сессии.
   *
   * @param {{token: string, user: object}} data Токен и данные пользователя.
   * @returns {void}
   */
  function setSession(data) {
    token.value = data.token;
    user.value = data.user;
  }

  /**
   * Очищает локальные данные авторизованной сессии.
   *
   * @returns {void}
   */
  function clear() {
    token.value = null;
    user.value = null;
  }

  /**
   * Выполняет вход пользователя по переданным учётным данным.
   *
   * @param {{username: string, password: string}} credentials Логин и пароль пользователя.
   * @returns {Promise<void>} Промис, завершающийся после сохранения сессии.
   */
  async function login(credentials) {
    const { data } = await api.post('/auth/login', credentials);
    setSession(data.data);
  }

  /**
   * Обновляет access-токен, объединяя параллельные запросы в один.
   *
   * @returns {Promise<object>} Обновлённые данные сессии.
   */
  async function refresh() {
    if (!refreshPromise) {
      refreshPromise = api
        .post('/auth/refresh')
        .then(({ data }) => {
          setSession(data.data);
          return data.data;
        })
        .finally(() => {
          refreshPromise = null;
        });
    }

    return refreshPromise;
  }

  /**
   * Восстанавливает сессию пользователя по refresh-cookie при запуске приложения.
   *
   * @returns {Promise<void>} Промис, завершающийся после попытки восстановления.
   */
  async function restore() {
    sessionStorage.removeItem('book-catalog-token');
    sessionStorage.removeItem('book-catalog-user');

    try {
      await refresh();
    } catch (error) {
      if (error.response?.status === 401) {
        clear();
        return;
      }
      throw error;
    } finally {
      restored.value = true;
    }
  }

  /**
   * Завершает серверную сессию и очищает локальные данные авторизации.
   *
   * @returns {Promise<void>} Промис, завершающийся после выхода.
   */
  async function logout() {
    try {
      await api.post('/auth/logout');
    } finally {
      clear();
    }
  }

  return { token, user, restored, isAuthenticated, login, restore, refresh, logout, clear };
});

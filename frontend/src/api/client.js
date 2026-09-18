import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080/api/v1',
  timeout: 15000,
  withCredentials: true,
});

/**
 * Возвращает access-токен до подключения хранилища авторизации.
 *
 * @returns {null} Пустое значение токена.
 */
let getAuthToken = () => null;
let refreshAuth = null;
/**
 * Обрабатывает окончательное истечение сессии до подключения внешнего обработчика.
 *
 * @returns {void}
 */
let handleExpired = () => {};
let expirationNotified = false;

/**
 * Подключает функции хранилища авторизации к HTTP-клиенту.
 *
 * @param {object} options Функции управления авторизацией.
 * @param {() => string|null} options.getToken Функция получения актуального access-токена.
 * @param {() => Promise<unknown>} options.refresh Функция обновления сессии.
 * @param {() => void} options.onExpired Обработчик окончательного истечения сессии.
 * @returns {void}
 */
export function configureAuth({ getToken, refresh, onExpired }) {
  getAuthToken = getToken;
  refreshAuth = refresh;
  handleExpired = onExpired;
  expirationNotified = false;
}

/**
 * Добавляет access-токен в исходящий запрос, если пользователь авторизован.
 *
 * @param {import('axios').InternalAxiosRequestConfig} config Конфигурация исходящего запроса.
 * @returns {import('axios').InternalAxiosRequestConfig} Дополненная конфигурация запроса.
 */
api.interceptors.request.use(config => {
  const token = getAuthToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  /**
   * Передаёт успешный HTTP-ответ вызывающему коду без изменений.
   *
   * @param {import('axios').AxiosResponse} response Успешный ответ сервера.
   * @returns {import('axios').AxiosResponse} Исходный ответ сервера.
   */
  response => response,
  /**
   * Обновляет истёкшую сессию и один раз повторяет исходный запрос.
   *
   * @param {import('axios').AxiosError} error Ошибка HTTP-запроса.
   * @returns {Promise<import('axios').AxiosResponse>} Повторный ответ или отклонённый промис с исходной ошибкой.
   */
  async error => {
    const originalRequest = error.config;
    const authEndpoint = ['/auth/login', '/auth/refresh', '/auth/logout'].some(path =>
      originalRequest?.url?.includes(path)
    );

    if (
      error.response?.status !== 401 ||
      authEndpoint ||
      originalRequest?._authRetried ||
      !refreshAuth
    ) {
      return Promise.reject(error);
    }

    try {
      await refreshAuth();
      expirationNotified = false;
      originalRequest._authRetried = true;

      const token = getAuthToken();
      if (token) {
        originalRequest.headers.Authorization = `Bearer ${token}`;
      } else {
        delete originalRequest.headers.Authorization;
      }

      return api(originalRequest);
    } catch {
      if (!expirationNotified) {
        expirationNotified = true;
        handleExpired();
      }
      return Promise.reject(error);
    }
  }
);

/**
 * Извлекает понятное пользователю сообщение из ошибки HTTP-клиента.
 *
 * @param {import('axios').AxiosError|Error} error Полученная ошибка.
 * @param {string} [fallback='Что-то пошло не так.'] Резервный текст сообщения.
 * @returns {string} Сообщение для отображения пользователю.
 */
export function errorMessage(error, fallback = 'Что-то пошло не так.') {
  return error.response?.data?.errors?.[0]?.message || error.message || fallback;
}

export default api;

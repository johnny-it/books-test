import { ref } from 'vue';
import { defineStore } from 'pinia';

/**
 * Создаёт хранилище всплывающих уведомлений приложения.
 *
 * @returns {object} Список уведомлений и методы управления им.
 */
export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref([]);

  /**
   * Добавляет уведомление и планирует его автоматическое удаление.
   *
   * @param {string} message Текст уведомления.
   * @param {'success'|'error'} [type='success'] Тип оформления уведомления.
   * @returns {void}
   */
  function push(message, type = 'success') {
    const id = Date.now() + Math.random();
    items.value.push({ id, message, type });
    window.setTimeout(() => remove(id), 4200);
  }

  /**
   * Удаляет уведомление по идентификатору.
   *
   * @param {number} id Идентификатор уведомления.
   * @returns {void}
   */
  function remove(id) {
    items.value = items.value.filter(item => item.id !== id);
  }
  return { items, push, remove };
});

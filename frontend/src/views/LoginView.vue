<script setup>
import { ref } from 'vue';
import { BookOpen } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { errorMessage } from '../api/client';

const route = useRoute(),
  router = useRouter(),
  auth = useAuthStore();
const username = ref('demo'),
  password = ref('demo1234'),
  loading = ref(false),
  formError = ref('');

/**
 * Авторизует пользователя и перенаправляет его на исходный маршрут.
 *
 * @returns {Promise<void>} Промис, завершающийся после попытки входа.
 */
async function submit() {
  loading.value = true;
  formError.value = '';
  try {
    await auth.login({ username: username.value, password: password.value });
    router.push(route.query.redirect || '/books');
  } catch (e) {
    formError.value = errorMessage(e, 'Не удалось войти.');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <section class="auth-card">
      <BookOpen :size="34" stroke-width="1.4" />
      <h1>Войти в каталог</h1>
      <p>После входа можно добавлять и редактировать книги и авторов.</p>
      <div v-if="route.query.expired" class="alert alert-warning">Сессия истекла. Войдите снова.</div>
      <div v-if="formError" class="alert alert-error">
        {{ formError }}
      </div>
      <form class="stack-form" @submit.prevent="submit">
        <label>
          Логин
          <input v-model="username" autocomplete="username" required />
        </label>
        <label>
          Пароль
          <input v-model="password" type="password" autocomplete="current-password" required />
        </label>
        <button class="button button-primary button-block" type="submit" :disabled="loading">
          {{ loading ? 'Входим…' : 'Войти' }}
        </button>
      </form>
      <p class="demo-hint">
        Демо-доступ:
        <strong>demo</strong>
        /
        <strong>demo1234</strong>
      </p>
    </section>
  </div>
</template>

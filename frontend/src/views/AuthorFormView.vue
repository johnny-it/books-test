<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeft } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../api/client';
import { useNotificationsStore } from '../stores/notifications';
import LoadingState from '../components/LoadingState.vue';

const route = useRoute(),
  router = useRouter(),
  notify = useNotificationsStore();
const isEdit = computed(() => Boolean(route.params.id)),
  fullName = ref(''),
  loading = ref(isEdit.value),
  saving = ref(false),
  formError = ref('');

/**
 * Создаёт нового автора или сохраняет изменения существующего.
 *
 * @returns {Promise<void>} Промис, завершающийся после отправки формы.
 */
async function submit() {
  saving.value = true;
  formError.value = '';
  try {
    const response = isEdit.value
      ? await api.put(`/authors/${route.params.id}`, { full_name: fullName.value })
      : await api.post('/authors', { full_name: fullName.value });
    notify.push(isEdit.value ? 'Автор обновлён.' : 'Автор добавлен.');
    router.push(`/authors/${response.data.data.id}`);
  } catch (e) {
    formError.value = errorMessage(e);
  } finally {
    saving.value = false;
  }
}

/**
 * Загружает данные автора при открытии формы редактирования.
 *
 * @returns {Promise<void>} Промис, завершающийся после подготовки формы.
 */
onMounted(async () => {
  if (!isEdit.value) return;
  try {
    fullName.value = (await api.get(`/authors/${route.params.id}`)).data.data.full_name;
  } catch (e) {
    formError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <LoadingState v-if="loading" />
  <section v-else class="form-page narrow-form">
    <RouterLink class="back-link" :to="isEdit ? `/authors/${route.params.id}` : '/authors'">
      <ArrowLeft :size="17" />
      К авторам
    </RouterLink>
    <div class="page-heading">
      <h1>{{ isEdit ? 'Редактировать автора' : 'Добавить автора' }}</h1>
      <p>Укажите полное имя автора.</p>
    </div>
    <div v-if="formError" class="alert alert-error">
      {{ formError }}
    </div>
    <form class="stack-form" @submit.prevent="submit">
      <label>
        ФИО
        <input v-model.trim="fullName" maxlength="160" autofocus required />
      </label>
      <div class="form-actions">
        <button class="button button-primary" type="submit" :disabled="saving">
          {{ saving ? 'Сохраняем…' : 'Сохранить' }}
        </button>
        <RouterLink class="button button-outline" to="/authors">Отмена</RouterLink>
      </div>
    </form>
  </section>
</template>

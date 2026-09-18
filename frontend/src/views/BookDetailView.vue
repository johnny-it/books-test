<script setup>
import { onMounted, ref } from 'vue';
import { ArrowLeft, Pencil, Trash2 } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';
import BookCover from '../components/BookCover.vue';
import LoadingState from '../components/LoadingState.vue';

const route = useRoute(),
  router = useRouter(),
  auth = useAuthStore(),
  notify = useNotificationsStore();
const book = ref(null),
  loading = ref(true),
  loadError = ref('');

/**
 * Загружает подробные сведения о книге из текущего маршрута.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления страницы.
 */
async function load() {
  try {
    book.value = (await api.get(`/books/${route.params.id}`)).data.data;
  } catch (e) {
    loadError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}

/**
 * Запрашивает подтверждение и удаляет открытую книгу.
 *
 * @returns {Promise<void>} Промис, завершающийся после удаления или отмены.
 */
async function remove() {
  if (!window.confirm(`Удалить книгу «${book.value.title}»?`)) return;
  try {
    await api.delete(`/books/${book.value.id}`);
    notify.push('Книга удалена.');
    router.push('/books');
  } catch (e) {
    notify.push(errorMessage(e), 'error');
  }
}
onMounted(load);
</script>

<template>
  <LoadingState v-if="loading" />
  <div v-else-if="loadError" class="alert alert-error">
    {{ loadError }}
  </div>
  <article v-else class="detail-page">
    <RouterLink class="back-link" to="/books">
      <ArrowLeft :size="17" />
      Все книги
    </RouterLink>
    <div class="book-detail">
      <BookCover :book="book" size="detail" />
      <div class="book-detail-content">
        <div class="detail-title-row">
          <div>
            <h1>{{ book.title }}</h1>
            <p class="author-links">
              <RouterLink v-for="author in book.authors" :key="author.id" :to="`/authors/${author.id}`">
                {{ author.full_name }}
              </RouterLink>
            </p>
          </div>
          <div v-if="auth.isAuthenticated" class="inline-actions">
            <RouterLink class="icon-button" :to="`/books/${book.id}/edit`" aria-label="Редактировать">
              <Pencil :size="18" />
            </RouterLink>
            <button class="icon-button danger" type="button" aria-label="Удалить" @click="remove">
              <Trash2 :size="18" />
            </button>
          </div>
        </div>
        <dl class="metadata">
          <div>
            <dt>Год издания</dt>
            <dd>{{ book.year }}</dd>
          </div>
          <div>
            <dt>ISBN</dt>
            <dd>{{ book.isbn || 'Не указан' }}</dd>
          </div>
        </dl>
        <div class="prose">
          <h2>О книге</h2>
          <p>{{ book.description || 'Описание пока не добавлено.' }}</p>
        </div>
      </div>
    </div>
  </article>
</template>

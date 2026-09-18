<script setup>
import { onMounted, ref } from 'vue';
import { Plus, Search, Pencil, Trash2, ArrowUpRight } from '@lucide/vue';
import api, { errorMessage } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';
import LoadingState from '../components/LoadingState.vue';
import EmptyState from '../components/EmptyState.vue';

const auth = useAuthStore(),
  notify = useNotificationsStore();
const authors = ref([]),
  search = ref(''),
  loading = ref(true),
  loadError = ref('');

/**
 * Загружает авторов с учётом текущей поисковой строки.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления списка авторов.
 */
async function load() {
  loading.value = true;
  try {
    authors.value = (await api.get('/authors', { params: { search: search.value, 'per-page': 100 } })).data.data.items;
  } catch (e) {
    loadError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}

/**
 * Запрашивает подтверждение и удаляет выбранного автора.
 *
 * @param {{id: number|string, full_name: string}} author Удаляемый автор.
 * @returns {Promise<void>} Промис, завершающийся после удаления или отмены.
 */
async function remove(author) {
  if (!window.confirm(`Удалить автора «${author.full_name}»?`)) return;
  try {
    await api.delete(`/authors/${author.id}`);
    notify.push('Автор удалён.');
    load();
  } catch (e) {
    notify.push(errorMessage(e), 'error');
  }
}
onMounted(load);
</script>

<template>
  <section>
    <div class="page-heading heading-with-action">
      <div>
        <h1>Авторы</h1>
        <p>Имена, за которыми стоят любимые истории.</p>
      </div>
      <RouterLink v-if="auth.isAuthenticated" class="button button-primary" to="/authors/new">
        <Plus :size="18" />
        Добавить автора
      </RouterLink>
    </div>
    <form class="single-search" @submit.prevent="load">
      <label class="search-field">
        <Search :size="20" />
        <input v-model="search" placeholder="Найти автора" />
      </label>
      <button class="button button-outline" type="submit">Найти</button>
    </form>
    <div v-if="loadError" class="alert alert-error">
      {{ loadError }}
    </div>
    <LoadingState v-else-if="loading" />
    <EmptyState v-else-if="!authors.length" title="Авторы не найдены" />
    <div v-else class="author-list">
      <article v-for="author in authors" :key="author.id" class="author-row">
        <div class="author-monogram">{{ author.full_name.slice(0, 1) }}</div>
        <RouterLink class="author-name" :to="`/authors/${author.id}`">
          {{ author.full_name }}
        </RouterLink>
        <div class="row-actions">
          <RouterLink v-if="auth.isAuthenticated" :to="`/authors/${author.id}/edit`" aria-label="Редактировать">
            <Pencil :size="17" />
          </RouterLink>
          <button v-if="auth.isAuthenticated" type="button" aria-label="Удалить" @click="remove(author)">
            <Trash2 :size="17" />
          </button>
          <RouterLink :to="`/authors/${author.id}`" aria-label="Открыть">
            <ArrowUpRight :size="18" />
          </RouterLink>
        </div>
      </article>
    </div>
  </section>
</template>

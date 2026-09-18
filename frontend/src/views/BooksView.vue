<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { Search, Plus, Pencil, Trash2 } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';
import BookCover from '../components/BookCover.vue';
import LoadingState from '../components/LoadingState.vue';
import EmptyState from '../components/EmptyState.vue';
import PaginationControl from '../components/PaginationControl.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const notify = useNotificationsStore();
const books = ref([]);
const authors = ref([]);
const loading = ref(true);
const loadError = ref('');
const pagination = reactive({ page: 1, total_pages: 1, total: 0 });
const filters = reactive({
  search: route.query.search || '',
  author_id: route.query.author_id || '',
  year: route.query.year || '',
});
let searchTimer;

/**
 * Загружает страницу каталога с параметрами из текущего маршрута.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления списка книг.
 */
async function loadBooks() {
  loading.value = true;
  loadError.value = '';
  try {
    const { data } = await api.get('/books', { params: { ...route.query, 'per-page': 10 } });
    books.value = data.data.items;
    Object.assign(pagination, data.data.pagination);
  } catch (error) {
    loadError.value = errorMessage(error, 'Не удалось загрузить каталог.');
  } finally {
    loading.value = false;
  }
}

/**
 * Загружает список авторов для фильтра каталога.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления списка авторов.
 */
async function loadAuthors() {
  const { data } = await api.get('/authors', { params: { 'per-page': 100 } });
  authors.value = data.data.items;
}

/**
 * Переносит текущие фильтры и номер страницы в query-параметры маршрута.
 *
 * @param {number} [page=1] Номер открываемой страницы каталога.
 * @returns {void}
 */
function applyFilters(page = 1) {
  router.push({
    path: '/books',
    query: Object.fromEntries(
      Object.entries({ ...filters, page: page > 1 ? page : '' }).filter(([, value]) => value !== '')
    ),
  });
}

/**
 * Запрашивает подтверждение и удаляет выбранную книгу.
 *
 * @param {{id: number|string, title: string}} book Удаляемая книга.
 * @returns {Promise<void>} Промис, завершающийся после удаления или отмены.
 */
async function removeBook(book) {
  if (!window.confirm(`Удалить книгу «${book.title}»?`)) return;
  try {
    await api.delete(`/books/${book.id}`);
    notify.push('Книга удалена.');
    loadBooks();
  } catch (error) {
    notify.push(errorMessage(error), 'error');
  }
}

watch(() => route.query, loadBooks, { deep: true });
watch(
  () => filters.search,
  /**
   * Откладывает применение поискового фильтра до окончания ввода.
   *
   * @returns {void}
   */
  () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(), 450);
  }
);

/**
 * Загружает книги и авторов при монтировании страницы каталога.
 *
 * @returns {void}
 */
onMounted(() => {
  loadBooks();
  loadAuthors().catch(() => {});
});
</script>

<template>
  <section>
    <div class="page-heading heading-with-action">
      <div><h1>Каталог книг</h1></div>
      <RouterLink v-if="auth.isAuthenticated" class="button button-primary" to="/books/new">
        <Plus :size="18" />
        Добавить книгу
      </RouterLink>
    </div>

    <form class="catalog-filters" @submit.prevent="applyFilters()">
      <label class="search-field">
        <Search :size="20" />
        <input v-model="filters.search" type="search" placeholder="Найти книгу" aria-label="Найти книгу" />
      </label>
      <select v-model="filters.author_id" aria-label="Автор" @change="applyFilters()">
        <option value="">Все авторы</option>
        <option v-for="author in authors" :key="author.id" :value="author.id">
          {{ author.full_name }}
        </option>
      </select>
      <input
        v-model="filters.year"
        type="number"
        min="1000"
        max="2100"
        placeholder="Год издания"
        aria-label="Год издания"
        @change="applyFilters()"
      />
    </form>

    <div v-if="loadError" class="alert alert-error">
      {{ loadError }}
      <button type="button" @click="loadBooks">Повторить</button>
    </div>
    <LoadingState v-else-if="loading" />
    <EmptyState v-else-if="!books.length" />
    <div v-else class="book-grid">
      <article v-for="book in books" :key="book.id" class="book-card">
        <RouterLink :to="`/books/${book.id}`" class="book-card-link">
          <BookCover :book="book" />
        </RouterLink>
        <div class="book-card-body">
          <RouterLink :to="`/books/${book.id}`">
            <h2>{{ book.title }}</h2>
          </RouterLink>
          <p class="book-card-authors">
            <template v-for="(author, index) in book.authors" :key="author.id">
              <span v-if="index">, </span>
              <RouterLink :to="`/authors/${author.id}`">{{ author.full_name }}</RouterLink>
            </template>
          </p>
          <span>{{ book.year }}</span>
        </div>
        <div v-if="auth.isAuthenticated" class="card-actions">
          <RouterLink :to="`/books/${book.id}/edit`" aria-label="Редактировать">
            <Pencil :size="16" />
          </RouterLink>
          <button type="button" aria-label="Удалить" @click="removeBook(book)">
            <Trash2 :size="16" />
          </button>
        </div>
      </article>
    </div>
    <PaginationControl :page="pagination.page" :total-pages="pagination.total_pages" @change="applyFilters" />
  </section>
</template>

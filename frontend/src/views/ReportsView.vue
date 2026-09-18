<script setup>
import { onMounted, ref } from 'vue';
import { Trophy } from '@lucide/vue';
import api, { errorMessage } from '../api/client';
import LoadingState from '../components/LoadingState.vue';
import EmptyState from '../components/EmptyState.vue';

const year = ref(new Date().getFullYear()),
  items = ref([]),
  loading = ref(true),
  loadError = ref('');

/**
 * Загружает рейтинг авторов за выбранный год.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления рейтинга.
 */
async function load() {
  loading.value = true;
  loadError.value = '';
  try {
    items.value = (await api.get('/reports/top-authors', { params: { year: year.value } })).data.data.items;
  } catch (e) {
    loadError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
}
onMounted(load);
</script>

<template>
  <section class="report-page">
    <div class="page-heading">
      <h1>Топ авторов</h1>
      <p>Авторы, выпустившие больше всего книг за выбранный год.</p>
    </div>
    <form class="report-filter" @submit.prevent="load">
      <label>
        Год
        <input v-model="year" type="number" min="1000" max="2100" @input="load" />
      </label>
      <button class="button button-primary" type="submit">Показать рейтинг</button>
    </form>
    <div v-if="loadError" class="alert alert-error">
      {{ loadError }}
    </div>
    <LoadingState v-else-if="loading" />
    <EmptyState
      v-else-if="!items.length"
      title="За этот год данных нет"
      text="Выберите другой год или добавьте книги в каталог."
    />
    <div v-else class="ranking-table">
      <div class="ranking-head">
        <span>Место</span>
        <span>Автор</span>
        <span>Книг</span>
      </div>
      <RouterLink v-for="item in items" :key="item.author_id" :to="`/authors/${item.author_id}`" class="ranking-row">
        <span class="rank">
          <Trophy v-if="item.rank <= 3" :size="18" />
          {{ item.rank }}
        </span>
        <strong>{{ item.full_name }}</strong>
        <span>{{ item.books_count }}</span>
      </RouterLink>
    </div>
  </section>
</template>

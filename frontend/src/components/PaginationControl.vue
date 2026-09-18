<script setup>
import { ChevronLeft, ChevronRight } from '@lucide/vue';
const props = defineProps({ page: Number, totalPages: Number });
defineEmits(['change']);

/**
 * Рассчитывает компактный диапазон номеров страниц вокруг текущей страницы.
 *
 * @returns {number[]} Номера кнопок пагинации.
 */
function pages() {
  const values = [];
  const start = Math.max(1, Math.min(props.page - 2, props.totalPages - 4));
  const end = Math.min(props.totalPages, start + 4);
  for (let i = start; i <= end; i += 1) values.push(i);
  return values;
}
</script>

<template>
  <nav v-if="totalPages > 1" class="pagination" aria-label="Страницы каталога">
    <button type="button" :disabled="page <= 1" aria-label="Предыдущая страница" @click="$emit('change', page - 1)">
      <ChevronLeft :size="18" />
    </button>
    <button
      v-for="item in pages()"
      :key="item"
      type="button"
      :class="{ active: item === page }"
      @click="$emit('change', item)"
    >
      {{ item }}
    </button>
    <button
      type="button"
      :disabled="page >= totalPages"
      aria-label="Следующая страница"
      @click="$emit('change', page + 1)"
    >
      <ChevronRight :size="18" />
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ book: { type: Object, required: true }, size: { type: String, default: 'card' } });
const palette = ['cover-sage', 'cover-blue', 'cover-terra', 'cover-gold', 'cover-slate'];
/**
 * Выбирает стабильный цветовой вариант обложки по идентификатору книги.
 *
 * @returns {string} CSS-класс цветового варианта.
 */
const variant = computed(() => palette[(Number(props.book.id) || 0) % palette.length]);
</script>

<template>
  <div class="book-cover" :class="[variant, `book-cover-${size}`]">
    <img v-if="book.cover_url" :src="book.cover_url" :alt="`Обложка книги «${book.title}»`" />
    <div v-else class="cover-art" aria-hidden="true"><span /></div>
    <div v-if="!book.cover_url" class="cover-copy">
      <strong>{{ book.title }}</strong>
      <small>{{ book.authors?.[0]?.full_name }}</small>
    </div>
  </div>
</template>

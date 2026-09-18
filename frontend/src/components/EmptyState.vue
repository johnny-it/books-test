<script setup>
import { BookOpen } from '@lucide/vue';
defineProps({
  title: { type: String, default: 'Ничего не найдено' },
  text: { type: String, default: 'Попробуйте изменить параметры поиска.' },
});
</script>

<template>
  <div class="empty-state">
    <BookOpen :size="36" stroke-width="1.4" />
    <h2>{{ title }}</h2>
    <p>{{ text }}</p>
  </div>
</template>

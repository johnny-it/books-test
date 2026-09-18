<script setup>
import { CircleCheck, CircleX, X } from '@lucide/vue';
import { useNotificationsStore } from '../stores/notifications';

const notifications = useNotificationsStore();
</script>

<template>
  <div class="toast-stack" aria-live="polite">
    <TransitionGroup name="toast">
      <div v-for="item in notifications.items" :key="item.id" class="toast" :class="`toast-${item.type}`">
        <CircleCheck v-if="item.type === 'success'" :size="20" />
        <CircleX v-else :size="20" />
        <span>{{ item.message }}</span>
        <button type="button" aria-label="Закрыть" @click="notifications.remove(item.id)"><X :size="16" /></button>
      </div>
    </TransitionGroup>
  </div>
</template>

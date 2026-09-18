<script setup>
import AppHeader from './components/AppHeader.vue';
import ToastStack from './components/ToastStack.vue';
import AppFooter from './components/AppFooter.vue';
</script>

<template>
  <div class="app-shell">
    <AppHeader />
    <main class="page-shell">
      <RouterView />
    </main>
    <AppFooter />
    <ToastStack />
  </div>
</template>

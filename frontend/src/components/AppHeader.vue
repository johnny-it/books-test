<script setup>
import { ref } from 'vue';
import { LogIn, LogOut, Menu, X } from '@lucide/vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const open = ref(false);
const auth = useAuthStore();
const router = useRouter();

/**
 * Завершает сессию, закрывает мобильное меню и открывает каталог.
 *
 * @returns {Promise<void>} Промис, завершающийся после навигации.
 */
async function logout() {
  await auth.logout().catch(() => undefined);
  open.value = false;
  await router.push('/books');
}
</script>

<template>
  <header class="site-header">
    <div class="header-inner">
      <RouterLink class="brand" to="/books" @click="open = false">Книжная полка</RouterLink>
      <button class="menu-toggle" type="button" :aria-expanded="open" aria-label="Открыть меню" @click="open = !open">
        <X v-if="open" :size="22" />
        <Menu v-else :size="22" />
      </button>
      <div class="header-content" :class="{ open }">
        <nav class="main-nav" aria-label="Основная навигация">
          <RouterLink to="/books" @click="open = false">Книги</RouterLink>
          <RouterLink to="/authors" @click="open = false">Авторы</RouterLink>
          <RouterLink to="/reports" @click="open = false">Рейтинг</RouterLink>
        </nav>
        <div class="session-actions">
          <span v-if="auth.isAuthenticated" class="user-name">
            {{ auth.user?.username }}
          </span>
          <button v-if="auth.isAuthenticated" class="button button-ghost button-small" type="button" @click="logout">
            <LogOut :size="16" />
            Выйти
          </button>
          <RouterLink v-else class="button button-outline button-small" to="/login" @click="open = false">
            <LogIn :size="16" />
            Войти
          </RouterLink>
        </div>
      </div>
    </div>
  </header>
</template>

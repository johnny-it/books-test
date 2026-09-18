import { createRouter, createWebHistory } from 'vue-router';
import BooksView from '../views/BooksView.vue';
import BookDetailView from '../views/BookDetailView.vue';
import AuthorsView from '../views/AuthorsView.vue';
import AuthorDetailView from '../views/AuthorDetailView.vue';
import ReportsView from '../views/ReportsView.vue';
import LoginView from '../views/LoginView.vue';
import BookFormView from '../views/BookFormView.vue';
import AuthorFormView from '../views/AuthorFormView.vue';
import { useAuthStore } from '../stores/auth';

const router = createRouter({
  history: createWebHistory(),
  /**
   * Возвращает страницу к началу после перехода по маршруту.
   *
   * @returns {{top: number}} Позиция прокрутки нового маршрута.
   */
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', redirect: '/books' },
    { path: '/books', component: BooksView },
    { path: '/books/new', component: BookFormView, meta: { auth: true } },
    { path: '/books/:id', component: BookDetailView },
    { path: '/books/:id/edit', component: BookFormView, meta: { auth: true } },
    { path: '/authors', component: AuthorsView },
    { path: '/authors/new', component: AuthorFormView, meta: { auth: true } },
    { path: '/authors/:id', component: AuthorDetailView },
    { path: '/authors/:id/edit', component: AuthorFormView, meta: { auth: true } },
    { path: '/reports', component: ReportsView },
    { path: '/login', component: LoginView },
    { path: '/:pathMatch(.*)*', redirect: '/books' },
  ],
});

/**
 * Проверяет доступ к защищённым маршрутам перед навигацией.
 *
 * @param {import('vue-router').RouteLocationNormalized} to Целевой маршрут.
 * @returns {object|undefined} Маршрут входа для гостя или `undefined` для продолжения перехода.
 */
router.beforeEach(to => {
  if (to.meta.auth && !useAuthStore().isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } };
  }
});

/**
 * Перенаправляет пользователя на страницу входа после истечения сессии.
 *
 * @returns {Promise<unknown>} Результат навигации маршрутизатора.
 */
window.addEventListener('auth:expired', () => router.push({ path: '/login', query: { expired: '1' } }));

export default router;

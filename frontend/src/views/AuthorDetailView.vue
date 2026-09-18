<script setup>
import { onMounted, ref } from 'vue';
import { ArrowLeft, Bell, Pencil } from '@lucide/vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../api/client';
import { useAuthStore } from '../stores/auth';
import { useNotificationsStore } from '../stores/notifications';
import LoadingState from '../components/LoadingState.vue';
import EmptyState from '../components/EmptyState.vue';

const route = useRoute(),
  auth = useAuthStore(),
  notify = useNotificationsStore();
const author = ref(null),
  loading = ref(true),
  loadError = ref(''),
  phoneDigits = ref(''),
  phoneFocused = ref(false),
  subscribing = ref(false);

/**
 * Форматирует введённые цифры по маске российского номера телефона.
 *
 * @param {string} digits Цифры номера без кода страны и разделителей.
 * @returns {string} Частично или полностью отформатированный номер.
 */
function formatPhone(digits) {
  if (!digits) return '';

  let formatted = `(${digits.slice(0, 3)}`;
  if (digits.length >= 3) formatted += ')';
  if (digits.length > 3) formatted += ` ${digits.slice(3, 6)}`;
  if (digits.length > 6) formatted += `-${digits.slice(6, 8)}`;
  if (digits.length > 8) formatted += `-${digits.slice(8, 10)}`;
  return formatted;
}

/**
 * Ограничивает номер десятью цифрами и синхронизирует значение поля ввода.
 *
 * @param {HTMLInputElement} input Поле ввода телефона.
 * @param {string} digits Извлечённые из ввода цифры.
 * @returns {void}
 */
function setPhoneDigits(input, digits) {
  phoneDigits.value = digits.slice(0, 10);
  input.value = formatPhone(phoneDigits.value);
}

/**
 * Обрабатывает ввод и корректное удаление цифр на границах маски.
 *
 * @param {InputEvent} event Событие изменения поля телефона.
 * @returns {void}
 */
function onPhoneInput(event) {
  const input = event.target;
  let digits = input.value.replace(/\D/g, '');

  if (digits === phoneDigits.value && ['deleteContentBackward', 'deleteContentForward'].includes(event.inputType)) {
    const digitsBeforeCaret = input.value.slice(0, input.selectionStart).replace(/\D/g, '').length;
    const digitIndex = event.inputType === 'deleteContentBackward' ? digitsBeforeCaret - 1 : digitsBeforeCaret;
    if (digitIndex >= 0 && digitIndex < digits.length) {
      digits = digits.slice(0, digitIndex) + digits.slice(digitIndex + 1);
    }
  }

  setPhoneDigits(input, digits);
}

/**
 * Нормализует вставленный номер и помещает его в поле телефона.
 *
 * @param {ClipboardEvent} event Событие вставки из буфера обмена.
 * @returns {void}
 */
function onPhonePaste(event) {
  event.preventDefault();
  let digits = event.clipboardData.getData('text').replace(/\D/g, '');
  if (digits.length === 11 && (digits.startsWith('7') || digits.startsWith('8'))) digits = digits.slice(1);
  setPhoneDigits(event.target, digits);
}

/**
 * Оформляет SMS-подписку на новые книги открытого автора.
 *
 * @returns {Promise<void>} Промис, завершающийся после попытки подписки.
 */
async function subscribe() {
  if (phoneDigits.value.length !== 10) return;

  subscribing.value = true;
  try {
    const result = await api.post(`/authors/${route.params.id}/subscriptions`, {
      phone: `7${phoneDigits.value}`,
    });
    notify.push(result.data.data.already_subscribed ? 'Вы уже подписаны на этого автора.' : 'Подписка оформлена.');
    phoneDigits.value = '';
  } catch (e) {
    notify.push(errorMessage(e), 'error');
  } finally {
    subscribing.value = false;
  }
}

/**
 * Загружает автора и его книги при монтировании страницы.
 *
 * @returns {Promise<void>} Промис, завершающийся после обновления страницы.
 */
onMounted(async () => {
  try {
    author.value = (await api.get(`/authors/${route.params.id}`)).data.data;
  } catch (e) {
    loadError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <LoadingState v-if="loading" />
  <div v-else-if="loadError" class="alert alert-error">
    {{ loadError }}
  </div>
  <article v-else class="detail-page author-detail">
    <RouterLink class="back-link" to="/authors">
      <ArrowLeft :size="17" />
      Все авторы
    </RouterLink>
    <div class="author-detail-head">
      <div class="author-monogram author-monogram-large">{{ author.full_name.slice(0, 1) }}</div>
      <div>
        <h1>{{ author.full_name }}</h1>
        <p>{{ author.books.length }} {{ author.books.length === 1 ? 'книга' : 'книг' }} в каталоге</p>
      </div>
      <RouterLink v-if="auth.isAuthenticated" class="icon-button" :to="`/authors/${author.id}/edit`">
        <Pencil :size="18" />
      </RouterLink>
    </div>
    <div class="author-content-grid">
      <section>
        <h2>Книги автора</h2>
        <EmptyState v-if="!author.books.length" title="Книг пока нет" text="Добавьте первую книгу этого автора." />
        <div v-else class="compact-book-list">
          <RouterLink v-for="book in author.books" :key="book.id" :to="`/books/${book.id}`">
            <span>{{ book.title }}</span>
            <small>{{ book.year }}</small>
          </RouterLink>
        </div>
      </section>
      <aside class="subscription-panel">
        <Bell :size="23" />
        <h2>Подписаться на автора</h2>
        <p>Сообщим по SMS, когда в каталоге появится новая книга.</p>
        <form @submit.prevent="subscribe">
          <label>
            Номер телефона
            <span class="phone-input">
              <span v-if="phoneFocused || phoneDigits" class="phone-prefix" aria-hidden="true">+7</span>
              <input
                type="tel"
                inputmode="numeric"
                autocomplete="tel-national"
                :placeholder="phoneFocused ? '' : '+7 (999) 123-45-67'"
                maxlength="15"
                :value="formatPhone(phoneDigits)"
                required
                @focus="phoneFocused = true"
                @blur="phoneFocused = false"
                @input="onPhoneInput"
                @paste="onPhonePaste"
              />
            </span>
          </label>
          <button class="button button-primary button-block" type="submit" :disabled="subscribing || phoneDigits.length !== 10">
            {{ subscribing ? 'Оформляем…' : 'Подписаться' }}
          </button>
        </form>
      </aside>
    </div>
  </article>
</template>

<style scoped>
.phone-input {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: var(--paper);
  padding-left: 0.9rem;
  transition: border-color 0.18s, box-shadow 0.18s;
}

.phone-input:focus-within {
  border-color: var(--sage);
  box-shadow: 0 0 0 3px rgba(49, 94, 72, 0.12);
}

.phone-prefix {
  flex: 0 0 auto;
}

.phone-input input,
.phone-input input:focus {
  min-width: 0;
  border: 0;
  background: transparent;
  padding-left: 0;
  box-shadow: none;
}
</style>

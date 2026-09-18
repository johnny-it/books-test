<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { ArrowLeft, ImagePlus } from '@lucide/vue';
import { useRoute, useRouter } from 'vue-router';
import api, { errorMessage } from '../api/client';
import { normalizeBookForm, updateBook } from '../api/books';
import { useNotificationsStore } from '../stores/notifications';
import LoadingState from '../components/LoadingState.vue';

const route = useRoute(),
  router = useRouter(),
  notify = useNotificationsStore();
const isEdit = computed(() => Boolean(route.params.id));
const loading = ref(isEdit.value),
  saving = ref(false),
  authors = ref([]),
  coverFile = ref(null),
  preview = ref(''),
  existingCover = ref(''),
  initialBook = ref(null),
  formError = ref('');
const form = reactive({
  title: '',
  year: new Date().getFullYear(),
  isbn: '',
  description: '',
  author_ids: [],
});

/**
 * Сохраняет выбранный файл обложки и создаёт локальный URL предпросмотра.
 *
 * @param {Event} event Событие изменения файлового поля.
 * @returns {void}
 */
function selectCover(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
  coverFile.value = file;
  preview.value = URL.createObjectURL(file);
}

/**
 * Проверяет и отправляет форму создания или редактирования книги.
 *
 * @returns {Promise<void>} Промис, завершающийся после сохранения формы.
 */
async function submit() {
  if (!form.author_ids.length) {
    formError.value = 'Выберите хотя бы одного автора.';
    return;
  }
  if (!isEdit.value && !coverFile.value) {
    formError.value = 'Добавьте обложку книги.';
    return;
  }
  saving.value = true;
  formError.value = '';
  const data = new FormData();
  Object.entries(form).forEach(([key, value]) => {
    if (key === 'author_ids') value.forEach(id => data.append('author_ids[]', id));
    else data.append(key, value ?? '');
  });
  if (coverFile.value) data.append('cover', coverFile.value);
  try {
    let response;
    if (isEdit.value) {
      const result = await updateBook({
        id: route.params.id,
        initialBook: initialBook.value,
        currentBook: form,
        coverFile: coverFile.value,
      });
      if (result.method === 'none') {
        notify.push('Изменений нет.');
        return;
      }
      response = result.response;
    } else response = await api.post('/books', data);
    notify.push(isEdit.value ? 'Книга обновлена.' : 'Книга добавлена.');
    router.push(`/books/${response.data.data.id}`);
  } catch (e) {
    formError.value = errorMessage(e, 'Не удалось сохранить книгу.');
  } finally {
    saving.value = false;
  }
}

/**
 * Загружает авторов и, в режиме редактирования, исходные данные книги.
 *
 * @returns {Promise<void>} Промис, завершающийся после подготовки формы.
 */
onMounted(async () => {
  try {
    authors.value = (await api.get('/authors', { params: { 'per-page': 100 } })).data.data.items;
    if (isEdit.value) {
      const book = (await api.get(`/books/${route.params.id}`)).data.data;
      Object.assign(form, {
        title: book.title,
        year: book.year,
        isbn: book.isbn || '',
        description: book.description || '',
        author_ids: book.authors.map(item => String(item.id)),
      });
      initialBook.value = normalizeBookForm(form);
      existingCover.value = book.cover_url;
    }
  } catch (e) {
    formError.value = errorMessage(e);
  } finally {
    loading.value = false;
  }
});

/**
 * Освобождает временный URL предпросмотра перед уничтожением компонента.
 *
 * @returns {void}
 */
onBeforeUnmount(() => {
  if (preview.value?.startsWith('blob:')) URL.revokeObjectURL(preview.value);
});
</script>

<template>
  <LoadingState v-if="loading" />
  <section v-else class="form-page">
    <RouterLink class="back-link" :to="isEdit ? `/books/${route.params.id}` : '/books'">
      <ArrowLeft :size="17" />
      {{ isEdit ? 'К книге' : 'К каталогу' }}
    </RouterLink>
    <div class="page-heading">
      <h1>{{ isEdit ? 'Редактировать книгу' : 'Добавить книгу' }}</h1>
      <p>
        {{ isEdit ? 'Обновите сведения и сохраните изменения.' : 'Заполните сведения о новой книге.' }}
      </p>
    </div>
    <div v-if="formError" class="alert alert-error">
      {{ formError }}
    </div>
    <form class="editor-form" @submit.prevent="submit">
      <div class="form-main">
        <label class="field-wide">
          Название книги
          <input v-model.trim="form.title" maxlength="255" required />
        </label>
        <div class="field-row">
          <label>
            Год издания
            <input v-model.number="form.year" type="number" min="1000" max="2100" required />
          </label>
          <label>
            ISBN
            <input v-model.trim="form.isbn" placeholder="978-5-17-123456-7" />
          </label>
        </div>
        <label>
          Авторы
          <select v-model="form.author_ids" multiple required>
            <option v-for="author in authors" :key="author.id" :value="String(author.id)">
              {{ author.full_name }}
            </option>
          </select>
          <small>Для выбора нескольких авторов удерживайте Ctrl или Cmd.</small>
        </label>
        <label>
          Описание
          <textarea v-model.trim="form.description" rows="8" maxlength="5000" />
        </label>
      </div>
      <aside class="cover-upload">
        <span class="form-label">Обложка книги</span>
        <div class="cover-preview">
          <img v-if="preview || existingCover" :src="preview || existingCover" alt="Предпросмотр обложки" />
          <ImagePlus v-else :size="38" stroke-width="1.3" />
        </div>
        <label class="button button-outline file-button">
          {{ isEdit ? 'Заменить файл' : 'Выбрать файл' }}
          <input type="file" accept="image/jpeg,image/png,image/webp" :required="!isEdit" @change="selectCover" />
        </label>
        <small>JPG, PNG или WebP, до 5 МБ.</small>
      </aside>
      <div class="form-actions">
        <button class="button button-primary" type="submit" :disabled="saving">
          {{ saving ? 'Сохраняем…' : 'Сохранить' }}
        </button>
        <RouterLink class="button button-outline" :to="isEdit ? `/books/${route.params.id}` : '/books'">
          Отмена
        </RouterLink>
      </div>
    </form>
  </section>
</template>

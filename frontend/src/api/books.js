import api from './client';

const BOOK_FIELDS = ['title', 'year', 'description', 'isbn', 'author_ids'];

/**
 * Преобразует идентификаторы авторов в уникальный отсортированный список чисел.
 *
 * @param {Array<number|string>} [authorIds=[]] Идентификаторы авторов из формы.
 * @returns {number[]} Нормализованные идентификаторы авторов.
 */
function normalizeAuthorIds(authorIds = []) {
  return [...new Set(authorIds.map(Number).filter(Number.isInteger))].sort((left, right) => left - right);
}

/**
 * Нормализует поля формы книги перед сравнением или отправкой.
 *
 * @param {object} book Данные книги из формы или API.
 * @returns {{title: string, year: number, description: string, isbn: string, author_ids: number[]}} Нормализованные данные книги.
 */
export function normalizeBookForm(book) {
  return {
    title: String(book.title ?? '').trim(),
    year: Number(book.year),
    description: String(book.description ?? '').trim(),
    isbn: String(book.isbn ?? '').trim(),
    author_ids: normalizeAuthorIds(book.author_ids),
  };
}

/**
 * Формирует объект только с изменёнными полями книги.
 *
 * @param {object} initialBook Исходные данные книги.
 * @param {object} currentBook Текущие данные формы.
 * @returns {Record<string, unknown>} Поля, значения которых изменились.
 */
export function buildBookPatch(initialBook, currentBook) {
  const initial = normalizeBookForm(initialBook);
  const current = normalizeBookForm(currentBook);

  return Object.fromEntries(
    BOOK_FIELDS.filter(field => {
      if (field === 'author_ids') {
        return JSON.stringify(initial[field]) !== JSON.stringify(current[field]);
      }
      return initial[field] !== current[field];
    }).map(field => [field, current[field]])
  );
}

/**
 * Собирает multipart-данные для обновления книги вместе с новой обложкой.
 *
 * @param {object} book Текущие данные книги.
 * @param {File} coverFile Новый файл обложки.
 * @returns {FormData} Данные multipart-запроса.
 */
function buildBookFormData(book, coverFile) {
  const normalized = normalizeBookForm(book);
  const data = new FormData();

  for (const [field, value] of Object.entries(normalized)) {
    if (field === 'author_ids') {
      value.forEach(authorId => data.append('author_ids[]', authorId));
    } else {
      data.append(field, value);
    }
  }

  data.append('cover', coverFile);
  data.append('_method', 'PUT');
  return data;
}

/**
 * Обновляет книгу подходящим способом в зависимости от состава изменений.
 *
 * @param {object} options Параметры обновления.
 * @param {number|string} options.id Идентификатор книги.
 * @param {object} options.initialBook Исходные данные книги.
 * @param {object} options.currentBook Текущие данные формы.
 * @param {File|null} options.coverFile Новый файл обложки или `null`.
 * @param {object} [options.client=api] HTTP-клиент для выполнения запроса.
 * @returns {Promise<{method: 'put'|'patch'|'none', response: object|null}>} Использованный метод и ответ сервера.
 */
export async function updateBook({ id, initialBook, currentBook, coverFile, client = api }) {
  if (coverFile) {
    const response = await client.post(`/books/${id}`, buildBookFormData(currentBook, coverFile));
    return { method: 'put', response };
  }

  const patch = buildBookPatch(initialBook, currentBook);
  if (!Object.keys(patch).length) return { method: 'none', response: null };

  const response = await client.patch(`/books/${id}`, patch);
  return { method: 'patch', response };
}

import { notify } from './toast.js';

function appendReply(chat, reply) {
  const article = document.createElement('article');
  article.className = 'is-customer';

  const bubble = document.createElement('div');
  bubble.textContent = reply.body ?? '';

  const meta = document.createElement('small');
  meta.textContent = `${reply.sender || 'Вие'} · ${reply.created_at || ''}`;

  article.append(bubble, meta);
  chat.append(article);
  chat.scrollTo({ top: chat.scrollHeight, behavior: 'smooth' });
}

export function mountAccountMessages() {
  const form = document.querySelector('[data-account-message-reply]');
  if (!(form instanceof HTMLFormElement)) return;

  const conversation = form.closest('[data-account-conversation]');
  const chat = conversation?.querySelector('.store-account-chat');
  const textarea = form.querySelector('textarea[name="body"]');
  const button = form.querySelector('button[type="submit"]');
  const label = form.querySelector('[data-submit-label]');
  const status = form.querySelector('[data-account-message-status]');
  if (!(chat instanceof HTMLElement) || !(textarea instanceof HTMLTextAreaElement) || !(button instanceof HTMLButtonElement)) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (button.disabled || textarea.value.trim().length < 2) return;

    const body = textarea.value.trim();
    const formData = new FormData(form);
    formData.set('body', body);

    button.disabled = true;
    textarea.disabled = true;
    if (label) label.textContent = 'Изпращане…';
    if (status) status.textContent = 'Изпращаме отговора…';

    try {
      const response = await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData, credentials: 'same-origin', redirect: 'manual' });
      const contentType = response.headers.get('content-type') || '';
      if (response.type === 'opaqueredirect' || response.status === 0 || response.status === 301 || response.status === 302) {
        throw new Error('Сесията Ви е изтекла. Презаредете страницата и влезте отново.');
      }
      if (!contentType.includes('application/json')) {
        throw new Error('Сървърът не потвърди записването. Презаредете страницата и опитайте отново.');
      }
      const result = await response.json();
      if (!response.ok || result?.success !== true) throw new Error(result?.message || 'Отговорът не можа да бъде записан.');
      if (!Number.isInteger(result?.data?.reply?.id) || result.data.reply.id < 1) {
        throw new Error('Сървърът не потвърди записването на отговора.');
      }

      appendReply(chat, result.data.reply);
      textarea.value = '';
      if (status) status.textContent = 'Отговорът е добавен към разговора.';
      notify(result.message || 'Отговорът Ви е изпратен.', result.data?.reply?.email_sent === false ? 'warning' : 'success');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Отговорът не можа да бъде изпратен.';
      if (status) status.textContent = message;
      notify(message, 'error');
    } finally {
      button.disabled = false;
      textarea.disabled = false;
      if (label) label.textContent = 'Изпрати';
      textarea.focus();
    }
  });
}

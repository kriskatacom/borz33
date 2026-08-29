import { useEffect } from 'react';

function isSaveShortcut(event: KeyboardEvent): boolean {
  return (event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLowerCase() === 's';
}

function formFromNode(node: EventTarget | null): HTMLFormElement | null {
  if (node instanceof HTMLFormElement) {
    return node;
  }

  if (node instanceof Element) {
    return node.closest('form');
  }

  if (node instanceof Text) {
    return node.parentElement?.closest('form') ?? null;
  }

  return null;
}

function enabledSubmitter(form: HTMLFormElement): HTMLButtonElement | HTMLInputElement | null {
  const submitters = form.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
    'button[type="submit"], input[type="submit"]'
  );

  for (const submitter of submitters) {
    if (!submitter.disabled && submitter.getAttribute('aria-disabled') !== 'true') {
      return submitter;
    }
  }

  return null;
}

function resolveForm(lastForm: HTMLFormElement | null, event: KeyboardEvent): HTMLFormElement | null {
  const focused = formFromNode(event.target) ?? formFromNode(document.activeElement);

  if (focused?.isConnected && enabledSubmitter(focused)) {
    return focused;
  }

  if (lastForm?.isConnected && enabledSubmitter(lastForm)) {
    return lastForm;
  }

  const saveable = [...document.querySelectorAll('form')].filter((form) => enabledSubmitter(form));

  return saveable.length === 1 ? saveable[0] : null;
}

export function useFormSaveShortcut() {
  useEffect(() => {
    let lastForm: HTMLFormElement | null = null;

    function onFocusIn(event: FocusEvent) {
      const form = formFromNode(event.target);

      if (form) {
        lastForm = form;
      }
    }

    function onKeyDown(event: KeyboardEvent) {
      if (!isSaveShortcut(event) || event.repeat) {
        return;
      }

      const form = resolveForm(lastForm, event);
      const submitter = form ? enabledSubmitter(form) : null;

      event.preventDefault();

      if (!form || !submitter) {
        return;
      }

      window.setTimeout(() => {
        if (!form.isConnected || submitter.disabled) {
          return;
        }

        form.requestSubmit(submitter);
      }, 0);
    }

    document.addEventListener('focusin', onFocusIn);
    window.addEventListener('keydown', onKeyDown, true);

    return () => {
      document.removeEventListener('focusin', onFocusIn);
      window.removeEventListener('keydown', onKeyDown, true);
    };
  }, []);
}

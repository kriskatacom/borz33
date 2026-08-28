const formatter = new Intl.DateTimeFormat('bg-BG', {
  dateStyle: 'short',
  timeStyle: 'short',
});

export function formatDateTime(value: string | null): string {
  if (!value) {
    return '—';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return formatter.format(date);
}

export function roleLabel(role: string): string {
  if (role === 'admin') {
    return 'Администратор';
  }

  if (role === 'customer') {
    return 'Клиент';
  }

  return role;
}

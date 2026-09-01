import { apiUpload } from '@/api/client';

export function transcribeVoice(token: string, audio: File) {
  const form = new FormData();
  form.append('audio', audio);

  return apiUpload<{ text: string }>('/admin/ai/transcribe', { form, token });
}

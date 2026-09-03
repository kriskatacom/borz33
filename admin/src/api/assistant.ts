import { apiRequest } from '@/api/client';

export type AssistantLink = { label: string; to: string };
export type AssistantResponse = { answer: string; links: AssistantLink[] };

export function askAssistant(token: string, message: string, currentPath: string) {
  return apiRequest<AssistantResponse>('/admin/assistant', {
    method: 'POST', token, body: { message, current_path: currentPath },
  });
}

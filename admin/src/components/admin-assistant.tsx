import { useEffect, useState, type FormEvent } from 'react';
import { Bot, Send, Volume2, VolumeX, X } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { askAssistant, type AssistantResponse } from '@/api/assistant';
import { useAppSelector } from '@/app/hooks';
import { Button } from '@/components/ui/Button';
import { toastError } from '@/lib/toast';

export function AdminAssistant() {
  const assistantEnabled = import.meta.env.VITE_ADMIN_ASSISTANT_ENABLED !== 'false';
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [question, setQuestion] = useState('');
  const [busy, setBusy] = useState(false);
  const [response, setResponse] = useState<AssistantResponse | null>(null);
  const [speaking, setSpeaking] = useState(false);
  const [bulgarianVoice, setBulgarianVoice] = useState<SpeechSynthesisVoice | null>(null);

  useEffect(() => {
    if (!('speechSynthesis' in window)) return;
    const selectVoice = () => {
      const voice = window.speechSynthesis.getVoices()
        .filter((item) => item.lang.toLowerCase().startsWith('bg'))
        .sort((first, second) => Number(second.localService) - Number(first.localService))[0] ?? null;
      setBulgarianVoice(voice);
    };
    selectVoice();
    window.speechSynthesis.addEventListener('voiceschanged', selectVoice);
    return () => {
      window.speechSynthesis.removeEventListener('voiceschanged', selectVoice);
      window.speechSynthesis.cancel();
    };
  }, []);

  function toggleSpeech() {
    if (!response || !('speechSynthesis' in window)) return;
    if (speaking) {
      window.speechSynthesis.cancel();
      setSpeaking(false);
      return;
    }
    const utterance = new SpeechSynthesisUtterance(response.answer);
    utterance.lang = 'bg-BG';
    if (bulgarianVoice) utterance.voice = bulgarianVoice;
    utterance.onstart = () => setSpeaking(true);
    utterance.onend = () => setSpeaking(false);
    utterance.onerror = () => setSpeaking(false);
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  }

  async function submit(event: FormEvent) {
    event.preventDefault();
    if (!question.trim() || busy) return;
    setBusy(true);
    try {
      const result = await askAssistant(token, question.trim(), location.pathname);
      setResponse(result.data);
      setQuestion('');
    } catch (error) {
      toastError(error, 'AI справката не можа да бъде генерирана.');
    } finally { setBusy(false); }
  }

  if (!assistantEnabled || !token) return null;
  return <>
    <Button type="button" size="icon" variant="default" className="admin-assistant-trigger" aria-label="Отвори AI асистента" onClick={() => setOpen(true)}><Bot /></Button>
    {open ? <div className="admin-assistant-panel" role="dialog" aria-labelledby="admin-assistant-title">
      <header><div><p className="m-0 text-sm text-muted-foreground">Помощник за справки</p><h2 id="admin-assistant-title">AI асистент</h2></div><Button type="button" size="icon" variant="ghost" aria-label="Затвори AI асистента" onClick={() => setOpen(false)}><X /></Button></header>
      <div className="admin-assistant-body">{response ? <><div className="admin-assistant-answer-row"><div className="admin-assistant-answer">{response.answer}</div>{'speechSynthesis' in window ? <Button type="button" size="icon" variant="ghost" className="admin-assistant-speech" aria-label={speaking ? 'Спри прочитането' : 'Прочети отговора'} title={speaking ? 'Спри прочитането' : 'Прочети отговора'} onClick={toggleSpeech}>{speaking ? <VolumeX /> : <Volume2 />}</Button> : null}</div>{response.links.length > 0 ? <div className="admin-assistant-links" aria-label="Навигация към страницата"><span>Бърз достъп</span>{response.links.map((link) => <Link key={link.to} to={link.to} onClick={() => setOpen(false)}>{link.label}<small>Отвори страницата</small></Link>)}</div> : null}</> : <p className="m-0 text-muted-foreground">Попитайте за продукти, наличности, поръчки, категории или как се прави нещо в панела.</p>}</div>
      <form className="admin-assistant-form" onSubmit={(event) => void submit(event)}>
        <div className="admin-assistant-input-shell">
          <textarea aria-label="Въпрос към AI асистента" value={question} onChange={(event) => setQuestion(event.target.value)} placeholder="Например: Колко продукта са под минималната наличност?" rows={3} maxLength={2000} />
          <span>{question.length}/2000</span>
        </div>
        <Button type="submit" disabled={busy || !question.trim()}><Send />{busy ? 'Проверка…' : 'Попитай'}</Button>
      </form>
    </div> : null}
  </>;
}

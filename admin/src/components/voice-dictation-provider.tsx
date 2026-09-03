import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Mic, Square, Waves } from 'lucide-react';
import { transcribeVoice } from '@/api/voice';
import { useAppSelector } from '@/app/hooks';
import { Tooltip } from '@/components/ui/Tooltip';
import { toastError } from '@/lib/toast';

type Editable = HTMLInputElement | HTMLTextAreaElement | HTMLElement;
type DictationState = 'idle' | 'recording' | 'transcribing';

const EXCLUDED_INPUT_TYPES = new Set(['button', 'checkbox', 'color', 'date', 'datetime-local', 'file', 'hidden', 'image', 'month', 'number', 'password', 'radio', 'range', 'reset', 'submit', 'time', 'week']);

function isEditable(target: EventTarget | null): target is Editable {
  if (!(target instanceof HTMLElement) || target.hasAttribute('disabled') || target.getAttribute('aria-readonly') === 'true') return false;
  if (target instanceof HTMLTextAreaElement) return !target.readOnly;
  if (target instanceof HTMLInputElement) return !target.readOnly && !EXCLUDED_INPUT_TYPES.has(target.type || 'text');
  return target.isContentEditable;
}

function fieldLabel(field: Editable): string {
  if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
    const label = field.id ? document.querySelector(`label[for="${CSS.escape(field.id)}"]`)?.textContent : null;
    return label?.trim() || field.getAttribute('aria-label') || 'полето';
  }
  return field.getAttribute('aria-label') || 'текстовия редактор';
}

function appendTranscript(field: Editable, transcript: string) {
  if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
    const value = field.value;
    const start = field.selectionStart ?? value.length;
    const end = field.selectionEnd ?? start;
    const before = value.slice(0, start);
    const separator = before.length > 0 && !/\s$/.test(before) ? ' ' : '';
    const next = `${before}${separator}${transcript}${value.slice(end)}`;
    const setter = Object.getOwnPropertyDescriptor(Object.getPrototypeOf(field), 'value')?.set;
    setter?.call(field, next);
    const cursor = start + separator.length + transcript.length;
    field.setSelectionRange(cursor, cursor);
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.focus();
    return;
  }

  field.focus();
  const selection = window.getSelection();
  const prefix = selection?.toString() ? '' : ' ';
  document.execCommand('insertText', false, `${prefix}${transcript}`);
}

export function VoiceDictationProvider() {
  const assistantEnabled = import.meta.env.VITE_ADMIN_ASSISTANT_ENABLED !== 'false';
  const token = useAppSelector((state) => state.auth.token);
  const [field, setField] = useState<Editable | null>(null);
  const [state, setState] = useState<DictationState>('idle');
  const [position, setPosition] = useState({ top: 0, left: 0 });
  const streamRef = useRef<MediaStream | null>(null);
  const recorderRef = useRef<MediaRecorder | null>(null);
  const chunksRef = useRef<Blob[]>([]);
  const fieldRef = useRef<Editable | null>(null);
  const maxDurationRef = useRef<number | null>(null);
  const stateRef = useRef<DictationState>('idle');

  useEffect(() => {
    stateRef.current = state;
  }, [state]);

  function updatePosition(next: Editable | null) {
    if (!next) return;
    const rect = next.getBoundingClientRect();
    setPosition({ top: Math.max(8, rect.top + 8), left: Math.max(8, rect.right - 42) });
  }

  function stopStream() {
    streamRef.current?.getTracks().forEach((track) => track.stop());
    streamRef.current = null;
  }

  function clearTimer() {
    if (maxDurationRef.current !== null) window.clearTimeout(maxDurationRef.current);
    maxDurationRef.current = null;
  }

  async function start() {
    if (!token || !fieldRef.current || state !== 'idle') return;
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
      toastError(new Error('Браузърът не поддържа запис от микрофон.'), 'Гласовото писане не е налично в този браузър.');
      return;
    }
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg'].find((type) => MediaRecorder.isTypeSupported(type));
      const recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
      streamRef.current = stream;
      recorderRef.current = recorder;
      chunksRef.current = [];
      recorder.ondataavailable = (event) => { if (event.data.size > 0) chunksRef.current.push(event.data); };
      recorder.onstop = () => { void submitRecording(recorder.mimeType || mimeType || 'audio/webm'); };
      recorder.start();
      setState('recording');
      maxDurationRef.current = window.setTimeout(() => stop(), 120_000);
    } catch (error) {
      toastError(error, 'Не беше разрешен достъп до микрофона.');
      stopStream();
    }
  }

  function stop() {
    clearTimer();
    if (recorderRef.current?.state === 'recording') recorderRef.current.stop();
    stopStream();
  }

  async function submitRecording(mimeType: string) {
    const target = fieldRef.current;
    const blob = new Blob(chunksRef.current, { type: mimeType });
    chunksRef.current = [];
    recorderRef.current = null;
    if (!target || blob.size === 0 || !token) { setState('idle'); return; }
    setState('transcribing');
    try {
      const extension = mimeType.includes('ogg') ? 'ogg' : 'webm';
      const response = await transcribeVoice(token, new File([blob], `dictation.${extension}`, { type: mimeType }));
      appendTranscript(target, response.data.text);
    } catch (error) {
      toastError(error, 'Гласовото писане не беше успешно.');
    } finally {
      setState('idle');
    }
  }

  useEffect(() => {
    const focus = (event: FocusEvent) => {
      if (!isEditable(event.target)) return;
      fieldRef.current = event.target;
      setField(event.target);
      updatePosition(event.target);
    };
    const blur = () => window.setTimeout(() => {
      if (stateRef.current === 'idle' && document.activeElement !== fieldRef.current) setField(null);
    }, 0);
    const reposition = () => updatePosition(fieldRef.current);
    document.addEventListener('focusin', focus);
    document.addEventListener('focusout', blur);
    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);
    return () => { document.removeEventListener('focusin', focus); document.removeEventListener('focusout', blur); window.removeEventListener('scroll', reposition, true); window.removeEventListener('resize', reposition); clearTimer(); stopStream(); };
  }, []);

  if (!assistantEnabled || !token || !field) return null;
  const recording = state === 'recording';
  const transcribing = state === 'transcribing';
  const label = recording ? 'Спри записа' : transcribing ? 'Транскрибиране…' : `Диктувай в ${fieldLabel(field)}`;

  return createPortal(<Tooltip content={label} placement="bottom"><button type="button" className={`voice-dictation-button${recording ? ' is-recording' : ''}`} style={position} aria-label={label} disabled={transcribing} onMouseDown={(event) => event.preventDefault()} onClick={() => recording ? stop() : void start()}>{recording ? <Square className="size-4 fill-current" /> : transcribing ? <Waves className="size-5 animate-pulse" /> : <Mic className="size-5" />}</button></Tooltip>, document.body);
}

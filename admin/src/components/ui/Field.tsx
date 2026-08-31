import type { ChangeEvent, InputHTMLAttributes, ReactNode } from 'react';
import { ChevronDown, ChevronUp, X } from 'lucide-react';
import { HelpHint } from '@/components/ui/HelpHint';
import { Tooltip } from '@/components/ui/Tooltip';
import { DatePicker } from '@/components/ui/DatePicker';
import { MonthPicker } from '@/components/ui/MonthPicker';

type FieldProps = InputHTMLAttributes<HTMLInputElement> & {
  id: string;
  label: string;
  error?: string;
  hint?: string;
  help?: string;
  trailing?: ReactNode;
  clearable?: boolean;
  multiline?: boolean;
  rows?: number;
};

const UNCLEARABLE_TYPES = new Set(['password', 'hidden', 'checkbox', 'radio', 'file', 'submit', 'button', 'image']);

function boundNumber(value: string | number | undefined): number | undefined {
  if (value === undefined || value === '') {
    return undefined;
  }

  const parsed = typeof value === 'number' ? value : Number(value);

  return Number.isFinite(parsed) ? parsed : undefined;
}

function stepAmount(value: string | number | undefined): number {
  if (value === undefined || value === '' || value === 'any') {
    return 1;
  }

  const parsed = typeof value === 'number' ? value : Number(value);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
}

function stepDecimals(step: number): number {
  const text = step.toString().toLowerCase();
  const scientific = text.match(/e-(\d+)$/);

  if (scientific) {
    return Number(scientific[1]);
  }

  const dot = text.indexOf('.');

  return dot === -1 ? 0 : text.length - dot - 1;
}

function formatStepped(value: number, step: number): string {
  const places = stepDecimals(step);

  return places > 0 ? value.toFixed(places) : String(Math.round(value));
}

function currentNumber(value: FieldProps['value']): number | undefined {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value !== 'string' || value.trim() === '') {
    return undefined;
  }

  const parsed = Number.parseFloat(value);

  return Number.isFinite(parsed) ? parsed : undefined;
}

function nextNumber(
  value: FieldProps['value'],
  direction: 1 | -1,
  step: number,
  min?: number,
  max?: number
): string {
  const current = currentNumber(value);
  let next: number;

  if (current === undefined) {
    if (direction < 0) {
      return min === undefined ? '' : formatStepped(min, step);
    }

    next = min ?? 0;
  } else {
    next = current + direction * step;
  }

  if (min !== undefined) {
    next = Math.max(min, next);
  }

  if (max !== undefined) {
    next = Math.min(max, next);
  }

  const places = stepDecimals(step);
  next = Number(next.toFixed(places));

  return formatStepped(next, step);
}

export function Field({
  id,
  label,
  error,
  hint,
  help,
  trailing,
  clearable,
  multiline = false,
  rows = 5,
  className = '',
  onChange,
  value,
  ...input
}: FieldProps) {
  const describedBy = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;
  const inputType = input.type ?? 'text';
  const isNumber = !multiline && inputType === 'number';
  const canClear = !multiline && (clearable ?? !UNCLEARABLE_TYPES.has(inputType));
  const hasValue = typeof value === 'string' ? value.length > 0 : value != null;
  const showClear = canClear && hasValue && !input.disabled && !input.readOnly;
  const wrapped = !multiline && (Boolean(trailing) || showClear || isNumber);
  const step = stepAmount(input.step);
  const min = boundNumber(input.min);
  const max = boundNumber(input.max);
  const upValue = nextNumber(value, 1, step, min, max);
  const downValue = nextNumber(value, -1, step, min, max);
  const current = value === undefined || value === null ? '' : String(value);
  const stepperDisabled = Boolean(input.disabled || input.readOnly);

  function emit(next: string) {
    const event = {
      target: { value: next, name: input.name ?? '', id },
      currentTarget: { value: next, name: input.name ?? '', id },
    } as ChangeEvent<HTMLInputElement>;

    onChange?.(event);
    requestAnimationFrame(() => document.getElementById(id)?.focus());
  }

  function clearField() {
    emit('');
  }

  if (!multiline && inputType === 'date') {
    return <div className={`field ${className}`.trim()}><div className="flex items-center gap-1"><label htmlFor={id}>{label}</label>{help ? <HelpHint label={label}>{help}</HelpHint> : null}</div><DatePicker id={id} label={label} value={current} min={typeof input.min === 'string' ? input.min : undefined} max={typeof input.max === 'string' ? input.max : undefined} onChange={emit} /></div>;
  }

  if (!multiline && inputType === 'month') {
    return <div className={`field ${className}`.trim()}><div className="flex items-center gap-1"><label htmlFor={id}>{label}</label>{help ? <HelpHint label={label}>{help}</HelpHint> : null}</div><MonthPicker id={id} value={current} max={typeof input.max === 'string' ? input.max : undefined} onChange={emit} /></div>;
  }

  if (!multiline && inputType === 'datetime-local') {
    const date = current.slice(0, 10);
    const time = current.slice(11, 16) || '00:00';
    return <div className={`field ${className}`.trim()}><div className="flex items-center gap-1"><label htmlFor={id}>{label}</label>{help ? <HelpHint label={label}>{help}</HelpHint> : null}</div><div className="grid grid-cols-[minmax(0,1fr)_7rem] gap-2"><DatePicker id={id} label={label} value={date} onChange={(value) => emit(`${value}T${time}`)} /><input type="time" aria-label={`${label} — час`} value={time} onChange={(event) => emit(`${date || new Date().toISOString().slice(0, 10)}T${event.target.value}`)} /></div></div>;
  }

  return (
    <div className={`field ${className}`.trim()}>
      <div className="flex items-center gap-1">
        <label htmlFor={id}>{label}</label>
        {help ? <HelpHint label={label}>{help}</HelpHint> : null}
      </div>
      <div className={wrapped ? 'field-control' : undefined}>
        {multiline ? (
          <textarea
            id={id}
            name={input.name}
            rows={rows}
            placeholder={input.placeholder}
            disabled={input.disabled}
            readOnly={input.readOnly}
            required={input.required}
            value={typeof value === 'string' || typeof value === 'number' ? value : ''}
            aria-invalid={error ? true : undefined}
            aria-describedby={describedBy}
            onChange={(event) => onChange?.(event as unknown as ChangeEvent<HTMLInputElement>)}
          />
        ) : (
          <input
            {...input}
            id={id}
            value={value}
            aria-invalid={error ? true : undefined}
            aria-describedby={describedBy}
            onChange={onChange}
          />
        )}
        {showClear || isNumber ? (
          <div className="field-addons">
            {showClear ? (
              <Tooltip content={`Изчисти: ${label}`}>
                <button type="button" className="field-action" aria-label={`Изчисти: ${label}`} onClick={clearField}>
                  <X className="size-4" aria-hidden />
                </button>
              </Tooltip>
            ) : null}
            {isNumber ? (
              <div className="field-stepper">
                <Tooltip className="min-h-0" content={`Увеличи: ${label}`}>
                  <button
                    type="button"
                    className="field-step"
                    aria-label={`Увеличи: ${label}`}
                    disabled={stepperDisabled || upValue === current}
                    onClick={() => emit(upValue)}
                  >
                    <ChevronUp className="size-4" aria-hidden />
                  </button>
                </Tooltip>
                <Tooltip className="min-h-0" content={`Намали: ${label}`}>
                  <button
                    type="button"
                    className="field-step"
                    aria-label={`Намали: ${label}`}
                    disabled={stepperDisabled || downValue === current}
                    onClick={() => emit(downValue)}
                  >
                    <ChevronDown className="size-4" aria-hidden />
                  </button>
                </Tooltip>
              </div>
            ) : null}
          </div>
        ) : null}
        {trailing}
      </div>
      {hint ? (
        <p id={`${id}-hint`} className="field-hint">
          {hint}
        </p>
      ) : null}
      {error ? (
        <p id={`${id}-error`} className="field-error" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}

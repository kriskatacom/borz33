import type { ChangeEvent, InputHTMLAttributes, ReactNode } from 'react';
import { X } from 'lucide-react';
import { HelpHint } from '@/components/ui/HelpHint';

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
  const canClear = !multiline && (clearable ?? !UNCLEARABLE_TYPES.has(inputType));
  const hasValue = typeof value === 'string' ? value.length > 0 : value != null && value !== '';
  const showClear = canClear && hasValue && !input.disabled && !input.readOnly;
  const wrapped = !multiline && (Boolean(trailing) || showClear);

  function clearField() {
    const event = {
      target: { value: '', name: input.name ?? '', id },
      currentTarget: { value: '', name: input.name ?? '', id },
    } as ChangeEvent<HTMLInputElement>;

    onChange?.(event);
    requestAnimationFrame(() => document.getElementById(id)?.focus());
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
        {showClear ? (
          <button type="button" className="field-action" aria-label={`Изчисти: ${label}`} onClick={clearField}>
            <X className="size-4" aria-hidden />
          </button>
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

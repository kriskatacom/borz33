import type { InputHTMLAttributes, ReactNode } from 'react';
import { HelpHint } from '@/components/ui/HelpHint';

type FieldProps = InputHTMLAttributes<HTMLInputElement> & {
  id: string;
  label: string;
  error?: string;
  hint?: string;
  help?: string;
  trailing?: ReactNode;
};

export function Field({ id, label, error, hint, help, trailing, className = '', ...input }: FieldProps) {
  const describedBy = [hint ? `${id}-hint` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

  return (
    <div className={`field ${className}`.trim()}>
      <div className="flex items-center gap-1">
        <label htmlFor={id}>{label}</label>
        {help ? <HelpHint label={label}>{help}</HelpHint> : null}
      </div>
      <div className={trailing ? 'field-control' : undefined}>
        <input id={id} aria-invalid={error ? true : undefined} aria-describedby={describedBy} {...input} />
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

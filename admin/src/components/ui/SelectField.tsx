import type { SelectHTMLAttributes } from 'react';
import { LabelWithHelp } from '@/components/ui/HelpHint';

type SelectFieldProps = SelectHTMLAttributes<HTMLSelectElement> & {
  id: string;
  label: string;
  error?: string;
  help?: string;
};

export function SelectField({ id, label, error, help, children, className = '', ...select }: SelectFieldProps) {
  return (
    <div className={`field ${className}`.trim()}>
      {help ? <LabelWithHelp htmlFor={id} label={label} help={help} /> : <label htmlFor={id}>{label}</label>}
      <select id={id} aria-invalid={error ? true : undefined} aria-describedby={error ? `${id}-error` : undefined} {...select}>
        {children}
      </select>
      {error ? (
        <p id={`${id}-error`} className="field-error" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}

import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PageTreeOption } from '@/features/pages/pageTree';

type ExtraOption = {
  value: string;
  label: string;
};

export function PageTreeSelect({
  id,
  value,
  options,
  extra = [],
  placeholder,
  disabled = false,
  error,
  label,
  help,
  onValueChange,
}: {
  id: string;
  value: string;
  options: PageTreeOption[];
  extra?: ExtraOption[];
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  label: string;
  help: string;
  onValueChange: (value: string) => void;
}) {
  return (
    <div className="field">
      <LabelWithHelp htmlFor={id} label={label} help={help} />
      <Select value={value} onValueChange={onValueChange} disabled={disabled}>
        <SelectTrigger id={id} className="w-full min-h-12 font-sans">
          <SelectValue placeholder={placeholder} />
        </SelectTrigger>
        <SelectContent>
          {extra.map((item) => (
            <SelectItem key={item.value} value={item.value}>
              {item.label}
            </SelectItem>
          ))}
          {options.map((option) => (
            <SelectItem key={option.id} value={String(option.id)} className="whitespace-pre">
              {option.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      {error ? (
        <p className="field-error" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}

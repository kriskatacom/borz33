import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type FormSectionProps = {
  children: ReactNode;
  className?: string;
};

export function FormSection({ children, className }: FormSectionProps) {
  return (
    <section className={cn('rounded-[6px] border border-border bg-card p-3', className)}>
      {children}
    </section>
  );
}

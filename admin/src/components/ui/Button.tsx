import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Tooltip } from '@/components/ui/Tooltip';

const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 rounded-[6px] text-base font-medium whitespace-nowrap transition-all outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90 focus-visible:bg-primary/90',
        destructive:
          'bg-destructive text-white hover:bg-destructive/90 focus-visible:bg-destructive/90 focus-visible:ring-destructive/20 dark:bg-destructive/60 dark:focus-visible:ring-destructive/40',
        outline:
          'border border-input bg-card shadow-xs hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground dark:border-input dark:bg-input/30 dark:hover:bg-input/50 dark:focus-visible:bg-input/50',
        secondary:
          'bg-secondary text-secondary-foreground hover:bg-secondary/80 focus-visible:bg-secondary/80',
        ghost:
          'hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground dark:hover:bg-accent/50 dark:focus-visible:bg-accent/50',
        link: 'text-primary underline-offset-4 hover:underline focus-visible:underline',
      },
      size: {
        default: 'min-h-12 px-4 py-2 has-[>svg]:px-3',
        sm: 'h-8 gap-1.5 rounded-[6px] px-3 has-[>svg]:px-2.5',
        lg: 'h-10 rounded-[6px] px-6 has-[>svg]:px-4',
        icon: 'size-9',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

function Button({
  className,
  variant = 'default',
  size = 'default',
  asChild = false,
  tooltip,
  ...props
}: React.ComponentProps<'button'> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean;
    tooltip?: string;
  }) {
  const Comp = asChild ? Slot : 'button';
  const hint =
    tooltip ??
    (size === 'icon' && typeof props['aria-label'] === 'string' ? props['aria-label'] : undefined);
  const button = <Comp data-slot="button" className={cn(buttonVariants({ variant, size, className }))} {...props} />;

  return hint ? <Tooltip content={hint}>{button}</Tooltip> : button;
}

export { Button, buttonVariants };

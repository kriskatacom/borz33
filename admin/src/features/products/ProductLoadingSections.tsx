import { Images, Layers, List, LoaderCircle, Palette, Shirt, Sparkles, Type, type LucideIcon } from 'lucide-react';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';

type LoadingSection = { title: string; icon: LucideIcon };

const VIEW_SECTIONS: LoadingSection[] = [
  { title: 'Изображения', icon: Images },
  { title: 'Общи данни', icon: Shirt },
  { title: 'Параметри', icon: List },
  { title: 'Опции', icon: Palette },
  { title: 'Варианти', icon: Layers },
  { title: 'Персонализация', icon: Type },
];

const EDIT_SECTIONS: LoadingSection[] = [
  { title: 'Изображения', icon: Images },
  { title: 'Общи данни', icon: Shirt },
  { title: 'Шаблон за атрибути', icon: Sparkles },
  { title: 'Параметри', icon: List },
  { title: 'Опции', icon: Palette },
  { title: 'Варианти', icon: Layers },
  { title: 'Персонализация', icon: Type },
];

function ProductImagesLoadingContent() {
  return (
    <div className="grid gap-4">
      <div className="max-w-sm">
        <div className="product-image-skeleton aspect-square w-full rounded-[6px] border border-border">
          <span className="relative z-10 flex flex-col items-center gap-2 text-sm text-muted-foreground">
            <LoaderCircle className="size-5 animate-spin" aria-hidden />
            Зареждане на изображение…
          </span>
        </div>
      </div>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {Array.from({ length: 4 }, (_, index) => (
          <div key={index} className="product-image-skeleton aspect-square rounded-[6px] border border-border" aria-hidden />
        ))}
      </div>
    </div>
  );
}

export function ProductLoadingSections({ mode }: { mode: 'view' | 'edit' }) {
  const sections = mode === 'edit' ? EDIT_SECTIONS : VIEW_SECTIONS;

  return (
    <div className="flex min-w-0 max-w-full flex-col gap-3" aria-busy="true" aria-live="polite">
      {sections.map(({ title, icon }) => (
        <CollapsibleSection key={title} title={<span>{title}</span>} icon={icon} defaultOpen>
          {title === 'Изображения'
            ? <ProductImagesLoadingContent />
            : <p className="m-0 flex min-h-14 items-center text-base text-muted-foreground">Зареждане…</p>}
        </CollapsibleSection>
      ))}
    </div>
  );
}

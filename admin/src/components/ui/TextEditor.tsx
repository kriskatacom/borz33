import { useEffect, useState, type ReactNode } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import { AlignCenter, AlignJustify, AlignLeft, AlignRight, Bold, Italic, Link as LinkIcon, List, ListOrdered, Minus, Underline as UnderlineIcon, Unlink } from 'lucide-react';
import { LabelWithHelp } from '@/components/ui/HelpHint';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip } from '@/components/ui/Tooltip';
import { cn } from '@/lib/utils';

type TextEditorProps = {
  id: string;
  label: string;
  help: string;
  value: string;
  onChange: (value: string) => void;
  error?: string;
  disabled?: boolean;
};

function ToolbarButton({
  label,
  active,
  disabled,
  onClick,
  children,
}: {
  label: string;
  active?: boolean;
  disabled?: boolean;
  onClick: () => void;
  children: ReactNode;
}) {
  return (
    <Tooltip content={label} placement="bottom">
      <button
        type="button"
        aria-label={label}
        aria-pressed={active}
        disabled={disabled}
        className={cn(
          'inline-flex size-8 items-center justify-center rounded-[6px] text-muted-foreground transition-colors',
          'hover:bg-accent hover:text-foreground focus-visible:bg-accent focus-visible:text-foreground',
          'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
          'disabled:pointer-events-none disabled:opacity-40',
          active && 'bg-accent text-foreground'
        )}
        onClick={onClick}
      >
        {children}
      </button>
    </Tooltip>
  );
}

export function TextEditor({ id, label, help, value, onChange, error, disabled = false }: TextEditorProps) {
  const [linkOpen, setLinkOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkNewTab, setLinkNewTab] = useState(false);
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: { levels: [1, 2, 3, 4, 5, 6] },
        codeBlock: false,
        code: false,
        blockquote: false,
        strike: false,
        link: false,
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        autolink: true,
        linkOnPaste: true,
        protocols: ['http', 'https', 'mailto', 'tel'],
        HTMLAttributes: { target: null, rel: null },
      }),
      TextAlign.configure({
        types: ['heading', 'paragraph'],
        alignments: ['left', 'center', 'right', 'justify'],
        defaultAlignment: 'left',
      }),
    ],
    content: value || '',
    editable: !disabled,
    shouldRerenderOnTransaction: true,
    editorProps: {
      attributes: {
        id,
        class: 'text-editor-content',
        'aria-invalid': error ? 'true' : 'false',
      },
    },
    onUpdate: ({ editor: current }) => {
      onChange(current.getHTML());
    },
  });

  useEffect(() => {
    if (!editor) {
      return;
    }

    editor.setEditable(!disabled);
  }, [disabled, editor]);

  useEffect(() => {
    if (!editor || editor.isFocused) {
      return;
    }

    const next = value || '';

    if (editor.getHTML() !== next) {
      editor.commands.setContent(next, { emitUpdate: false });
    }
  }, [editor, value]);

  function openLinkEditor() {
    if (!editor) return;
    const attributes = editor.getAttributes('link');
    setLinkUrl(String(attributes.href ?? ''));
    setLinkNewTab(attributes.target === '_blank');
    setLinkOpen(true);
  }

  function normalizedLink(value: string): string {
    const url = value.trim();
    if (url === '' || /^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) return url;
    return `https://${url}`;
  }

  function applyLink() {
    if (!editor) return;
    const href = normalizedLink(linkUrl);
    if (href === '') {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
      editor.chain().focus().extendMarkRange('link').setLink({
        href,
        target: linkNewTab ? '_blank' : null,
        rel: linkNewTab ? 'noopener noreferrer' : null,
      }).run();
    }
    setLinkOpen(false);
  }

  function removeLink() {
    editor?.chain().focus().extendMarkRange('link').unsetLink().run();
    setLinkUrl('');
    setLinkNewTab(false);
    setLinkOpen(false);
  }

  function headingValue(): string {
    if (!editor) return '0';
    for (const level of [1, 2, 3, 4, 5, 6] as const) {
      if (editor.isActive('heading', { level })) return String(level);
    }
    return '0';
  }

  function setTextStyle(value: string) {
    if (!editor) return;
    if (value === '0') {
      editor.chain().focus().setParagraph().run();
      return;
    }
    const level = Number(value) as 1 | 2 | 3 | 4 | 5 | 6;
    editor.chain().focus().setHeading({ level }).run();
  }

  return (
    <div className="field">
      <LabelWithHelp htmlFor={id} label={label} help={help} />
      <div className={cn('text-editor', error && 'is-invalid', disabled && 'is-disabled')}>
        <div className="text-editor-toolbar" role="toolbar" aria-label="Форматиране">
          <div className="text-editor-heading-menu">
            <Select value={headingValue()} disabled={!editor || disabled} onValueChange={setTextStyle}>
              <SelectTrigger size="sm" aria-label="Стил на текста">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="0">Абзац</SelectItem>
                <SelectItem value="1">Заглавие H1</SelectItem>
                <SelectItem value="2">Заглавие H2</SelectItem>
                <SelectItem value="3">Заглавие H3</SelectItem>
                <SelectItem value="4">Заглавие H4</SelectItem>
                <SelectItem value="5">Заглавие H5</SelectItem>
                <SelectItem value="6">Заглавие H6</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <span className="text-editor-toolbar-divider" aria-hidden />
          <ToolbarButton
            label="Удебелен"
            active={editor?.isActive('bold')}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().toggleBold().run()}
          >
            <Bold className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Курсив"
            active={editor?.isActive('italic')}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().toggleItalic().run()}
          >
            <Italic className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Подчертан"
            active={editor?.isActive('underline')}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().toggleUnderline().run()}
          >
            <UnderlineIcon className="size-4" aria-hidden />
          </ToolbarButton>
          <span className="text-editor-toolbar-divider" aria-hidden />
          <ToolbarButton
            label="Подравни вляво"
            active={editor?.isActive({ textAlign: 'left' })}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().setTextAlign('left').run()}
          >
            <AlignLeft className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Центрирай"
            active={editor?.isActive({ textAlign: 'center' })}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().setTextAlign('center').run()}
          >
            <AlignCenter className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Подравни вдясно"
            active={editor?.isActive({ textAlign: 'right' })}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().setTextAlign('right').run()}
          >
            <AlignRight className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Двустранно подравняване"
            active={editor?.isActive({ textAlign: 'justify' })}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().setTextAlign('justify').run()}
          >
            <AlignJustify className="size-4" aria-hidden />
          </ToolbarButton>
          <span className="text-editor-toolbar-divider" aria-hidden />
          <ToolbarButton
            label="Добави или редактирай линк"
            active={editor?.isActive('link')}
            disabled={!editor || disabled || (editor.state.selection.empty && !editor.isActive('link'))}
            onClick={openLinkEditor}
          >
            <LinkIcon className="size-4" aria-hidden />
          </ToolbarButton>
          <span className="text-editor-toolbar-divider" aria-hidden />
          <ToolbarButton
            label="Точков списък"
            active={editor?.isActive('bulletList')}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().toggleBulletList().run()}
          >
            <List className="size-4" aria-hidden />
          </ToolbarButton>
          <ToolbarButton
            label="Номериран списък"
            active={editor?.isActive('orderedList')}
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().toggleOrderedList().run()}
          >
            <ListOrdered className="size-4" aria-hidden />
          </ToolbarButton>
          <span className="text-editor-toolbar-divider" aria-hidden />
          <ToolbarButton
            label="Добави разделител"
            disabled={!editor || disabled}
            onClick={() => editor?.chain().focus().setHorizontalRule().run()}
          >
            <Minus className="size-4" aria-hidden />
          </ToolbarButton>
        </div>
        {linkOpen ? (
          <div className="text-editor-link-panel">
            <label htmlFor={`${id}-link-url`}>Адрес на линка</label>
            <input
              id={`${id}-link-url`}
              type="url"
              value={linkUrl}
              placeholder="https://example.com или /catalog"
              autoFocus
              onChange={(event) => setLinkUrl(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === 'Enter') { event.preventDefault(); applyLink(); }
                if (event.key === 'Escape') { event.preventDefault(); setLinkOpen(false); editor?.commands.focus(); }
              }}
            />
            <label className="text-editor-link-target">
              <input type="checkbox" checked={linkNewTab} onChange={(event) => setLinkNewTab(event.target.checked)} />
              <span>Отвори в нов раздел</span>
            </label>
            <button type="button" onClick={applyLink}>Приложи</button>
            {editor?.isActive('link') ? (
              <button type="button" className="is-remove" onClick={removeLink}><Unlink aria-hidden /> Премахни</button>
            ) : null}
          </div>
        ) : null}
        <EditorContent editor={editor} />
      </div>
      {error ? (
        <p className="field-error" role="alert">
          {error}
        </p>
      ) : null}
    </div>
  );
}

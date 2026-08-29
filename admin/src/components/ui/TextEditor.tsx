import { useEffect, type ReactNode } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import { Bold, Italic, List, ListOrdered, Underline as UnderlineIcon } from 'lucide-react';
import { LabelWithHelp } from '@/components/ui/HelpHint';
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
  );
}

export function TextEditor({ id, label, help, value, onChange, error, disabled = false }: TextEditorProps) {
  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        heading: false,
        codeBlock: false,
        code: false,
        blockquote: false,
        horizontalRule: false,
        strike: false,
      }),
      Underline,
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

  return (
    <div className="field">
      <LabelWithHelp htmlFor={id} label={label} help={help} />
      <div className={cn('text-editor', error && 'is-invalid', disabled && 'is-disabled')}>
        <div className="text-editor-toolbar" role="toolbar" aria-label="Форматиране">
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
        </div>
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

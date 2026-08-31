import { useEffect, useRef, useState } from 'react';
import { FolderOpen, Image, Landmark, Monitor, Moon, Palette, Sun, Trash2, Upload } from 'lucide-react';
import { getSiteSettings, updateSiteSettings, type SiteSettings } from '@/api/settings';
import { uploadMediaFile } from '@/api/media';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useTheme, type Theme } from '@/components/theme-provider';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { HelpHint } from '@/components/ui/HelpHint';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { toast, toastError } from '@/lib/toast';

const options: Array<{ value: Theme; label: string; help: string; icon: typeof Sun }> = [
  { value: 'light', label: 'Светла', help: 'Бял фон с горско зелено, независимо от темата на устройството.', icon: Sun },
  { value: 'dark', label: 'Тъмна', help: 'Тъмен фон с по-мек контраст за вечерна работа.', icon: Moon },
  { value: 'system', label: 'Системна', help: 'Следва светлата или тъмната тема на устройството.', icon: Monitor },
];

export function SettingsPage() {
  const { theme, setTheme } = useTheme();
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const uploadRef = useRef<HTMLInputElement>(null);
  const [settings, setSettings] = useState<SiteSettings>({ logo_media_file_id: null, logo: null, vat_enabled: true });
  const [busy, setBusy] = useState(true);
  const [pickerOpen, setPickerOpen] = useState(false);
  useGlobalLoading(busy);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    void getSiteSettings(token)
      .then((response) => { if (!cancelled) setSettings(response.data.settings); })
      .catch((error) => { if (!cancelled) toastError(error, 'Настройките не можаха да се заредят.'); })
      .finally(() => { if (!cancelled) setBusy(false); });
    return () => { cancelled = true; };
  }, [token]);

  async function saveLogo(id: number | null) {
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, { logo_media_file_id: id });
      setSettings(response.data.settings);
      toast.success(id === null ? 'Логото е премахнато.' : 'Логото е обновено.');
    } catch (error) {
      toastError(error, 'Логото не можа да се обнови.');
    } finally {
      setBusy(false);
    }
  }

  async function uploadLogo(file: File) {
    setBusy(true);
    try {
      const uploaded = await uploadMediaFile(token, file);
      const logo = uploaded.data.files[0];
      if (!logo) throw new Error('Файлът не беше качен.');
      const response = await updateSiteSettings(token, { logo_media_file_id: logo.id });
      setSettings(response.data.settings);
      toast.success('Логото е качено и приложено.');
    } catch (error) {
      toastError(error, 'Логото не можа да се качи.');
    } finally {
      setBusy(false);
      if (uploadRef.current) uploadRef.current.value = '';
    }
  }

  async function saveVatEnabled(vat_enabled: boolean) {
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, { vat_enabled });
      setSettings(response.data.settings);
      toast.success(vat_enabled ? 'ДДС е включено за новите поръчки.' : 'ДДС е изключено за новите поръчки.');
    } catch (error) {
      toastError(error, 'Настройката за ДДС не можа да се запази.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="page">
      <PageHeader title="Настройки" help="Външен вид на администрацията и визуална идентичност на магазина." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Настройки' }]} />
      <div className="grid gap-3">
        <CollapsibleSection title="Тема на приложението" icon={Palette} persistKey="settings.theme">
          <RadioGroup value={theme} onValueChange={(value) => {
            const next = value as Theme;
            setTheme(next);
            toast.success(next === 'light' ? 'Светлата тема е включена.' : next === 'dark' ? 'Тъмната тема е включена.' : 'Системната тема е включена.');
          }} className="grid max-w-xl gap-3" aria-label="Тема на приложението">
            {options.map((option) => {
              const Icon = option.icon;
              return <div key={option.value} className="flex items-center gap-3 rounded-[6px] border border-border bg-card p-4">
                <Label htmlFor={`theme-${option.value}`} className="flex min-w-0 flex-1 cursor-pointer items-center gap-3 font-sans text-foreground">
                  <RadioGroupItem id={`theme-${option.value}`} value={option.value} />
                  <Icon className="size-5 shrink-0 text-muted-foreground" aria-hidden />
                  <span className="text-base font-bold">{option.label}</span>
                </Label>
                <HelpHint label={option.label}>{option.help}</HelpHint>
              </div>;
            })}
          </RadioGroup>
        </CollapsibleSection>

        <CollapsibleSection title="Данъчно облагане" icon={Landmark} persistKey="settings.vat" help="Важи за поръчки, създадени след промяната. Вече създадените поръчки и документи пазят използваната ставка.">
          <div className="flex max-w-xl items-center justify-between gap-5 rounded-[6px] border border-border bg-card p-4">
            <div><h3 className="m-0 text-base">Фирмата е регистрирана по ДДС</h3><p className="mt-1 mb-0 text-sm leading-relaxed text-muted-foreground">При „Да“ сумите се изчисляват автоматично по ставката от фирмените настройки. При „Не“ фактурите не начисляват ДДС.</p></div>
            <Switch checked={settings.vat_enabled} disabled={busy} aria-label="Фирмата е регистрирана по ДДС" onCheckedChange={(checked) => void saveVatEnabled(checked)} />
          </div>
        </CollapsibleSection>

        <CollapsibleSection title="Лого на сайта" icon={Image} persistKey="settings.logo" help="Показва се в header-а и footer-а. Без лого остава името на сайта.">
          <div className="grid gap-4 lg:grid-cols-[minmax(16rem,24rem)_minmax(0,1fr)] lg:items-start">
            <div className="flex min-h-40 items-center justify-center rounded-[6px] border border-border bg-muted p-5">
              {settings.logo
                ? <img src={settings.logo.url} alt={settings.logo.alt || 'Лого на сайта'} className="max-h-28 max-w-full object-contain" />
                : <div className="text-center"><strong className="block text-2xl">Borz33</strong><span className="mt-1 block text-sm text-muted-foreground">В момента се показва името на сайта.</span></div>}
            </div>
            <div className="grid gap-3">
              <div><h3 className="m-0 text-base">Основно лого</h3><p className="mt-1 mb-0 max-w-xl text-sm leading-relaxed text-muted-foreground">PNG, JPEG, WebP или GIF. Изберете изображение, което е четимо и в двете теми.</p></div>
              <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" disabled={busy} onClick={() => setPickerOpen(true)}><FolderOpen />Избери от медията</Button>
                <Button type="button" variant="outline" disabled={busy} onClick={() => uploadRef.current?.click()}><Upload />Качи ново</Button>
                {settings.logo ? <Button type="button" variant="outline" disabled={busy} onClick={() => void saveLogo(null)}><Trash2 />Премахни</Button> : null}
              </div>
              <input ref={uploadRef} className="sr-only" type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={(event) => { const file = event.target.files?.[0]; if (file) void uploadLogo(file); }} />
            </div>
          </div>
        </CollapsibleSection>
      </div>

      {pickerOpen ? <MediaPickerDialog token={token} title="Избор на лого" onSelect={(files) => { setPickerOpen(false); const file = files[0]; if (file) void saveLogo(file.id); }} onClose={() => setPickerOpen(false)} /> : null}
    </div>
  );
}

import { useEffect, useRef, useState } from 'react';
import { AlertTriangle, FolderOpen, Image, Landmark, PackageCheck, PlugZap, Trash2, Truck, Upload } from 'lucide-react';
import { getSiteSettings, testEcontConnection, updateSiteSettings, type SiteSettings } from '@/api/settings';
import { uploadMediaFile } from '@/api/media';
import { routes } from '@/app/constants';
import { useAppSelector } from '@/app/hooks';
import { useGlobalLoading } from '@/components/loading-provider';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/Button';
import { CollapsibleSection } from '@/components/ui/CollapsibleSection';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { MediaPickerDialog } from '@/features/media/MediaPickerDialog';
import { toast, toastError } from '@/lib/toast';

export function SettingsPage() {
  const token = useAppSelector((state) => state.auth.token) ?? '';
  const uploadRef = useRef<HTMLInputElement>(null);
  const [settings, setSettings] = useState<SiteSettings>({ logo_media_file_id: null, logo: null, admin_background: null, admin_background_overlay: 48, vat_enabled: true, free_shipping_threshold: 0, low_stock_threshold: 5, econt_operations_enabled: true, econt: { environment: 'demo', production_username: '', production_password_configured: false, production_password_masked: '', production_verified_at: null } });
  const [freeShippingThreshold, setFreeShippingThreshold] = useState('');
  const [lowStockThreshold, setLowStockThreshold] = useState('5');
  const [econtForm, setEcontForm] = useState({ environment: 'demo' as 'demo' | 'production', username: '', password: '' });
  const [busy, setBusy] = useState(true);
  const [pickerOpen, setPickerOpen] = useState(false);
  useGlobalLoading(busy);

  useEffect(() => {
    let cancelled = false;
    setBusy(true);
    void getSiteSettings(token)
      .then((response) => { if (!cancelled) { setSettings(response.data.settings); setFreeShippingThreshold(response.data.settings.free_shipping_threshold.toFixed(2)); setLowStockThreshold(String(response.data.settings.low_stock_threshold)); setEcontForm({ environment: response.data.settings.econt.environment, username: response.data.settings.econt.production_username, password: '' }); } })
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

  async function saveEcontOperationsEnabled(econt_operations_enabled: boolean) {
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, { econt_operations_enabled });
      setSettings(response.data.settings);
      toast.success(econt_operations_enabled ? 'Товарителниците и заявяването на куриер са разрешени.' : 'Товарителниците и заявяването на куриер са заключени.');
    } catch (error) {
      toastError(error, 'Настройката за товарителници и куриер не можа да се запази.');
    } finally {
      setBusy(false);
    }
  }

  async function saveEcont() {
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, {
        econt_environment: econtForm.environment,
        econt_production_username: econtForm.username,
        ...(econtForm.password ? { econt_production_password: econtForm.password } : {}),
      });
      setSettings(response.data.settings);
      setEcontForm((value) => ({ ...value, password: '' }));
      toast.success(econtForm.environment === 'production' ? 'Production средата е избрана. Тествайте връзката преди реална операция.' : 'Demo средата е активирана.');
    } catch (error) {
      toastError(error, 'Econt настройките не можаха да се запазят.');
    } finally {
      setBusy(false);
    }
  }

  async function saveFreeShippingThreshold() {
    const threshold = Number(freeShippingThreshold);
    if (!Number.isFinite(threshold) || threshold < 0) {
      toast.error('Въведете валиден неотрицателен праг.');
      return;
    }
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, { free_shipping_threshold: threshold });
      setSettings(response.data.settings);
      setFreeShippingThreshold(response.data.settings.free_shipping_threshold.toFixed(2));
      toast.success('Прагът за безплатна доставка е обновен.');
    } catch (error) {
      toastError(error, 'Прагът не можа да се запази.');
    } finally {
      setBusy(false);
    }
  }

  async function saveLowStockThreshold() {
    const threshold = Number(lowStockThreshold);
    if (!Number.isInteger(threshold) || threshold < 0) {
      toast.error('Въведете валидно цяло число за минималната наличност.');
      return;
    }
    setBusy(true);
    try {
      const response = await updateSiteSettings(token, { low_stock_threshold: threshold });
      setSettings(response.data.settings);
      setLowStockThreshold(String(response.data.settings.low_stock_threshold));
      toast.success('Минималната наличност е обновена.');
    } catch (error) {
      toastError(error, 'Минималната наличност не можа да се запази.');
    } finally {
      setBusy(false);
    }
  }

  async function testEcont() {
    setBusy(true);
    try {
      const response = await testEcontConnection(token, {
        environment: econtForm.environment,
        ...(econtForm.environment === 'production' ? { username: econtForm.username, ...(econtForm.password ? { password: econtForm.password } : {}) } : {}),
      });
      setSettings(response.data.settings);
      setEcontForm((value) => ({ ...value, password: '' }));
      toast.success(`Връзката с Econt ${econtForm.environment === 'production' ? 'Production' : 'Demo'} е успешна.`);
    } catch (error) {
      toastError(error, 'Връзката с Econt е неуспешна.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="page">
      <PageHeader title="Настройки" help="Външен вид на администрацията и визуална идентичност на магазина." crumbs={[{ label: 'Табло', to: routes.home }, { label: 'Настройки' }]} />
      <Tabs defaultValue="general">
        <TabsList className="h-auto w-full justify-start overflow-x-auto">
          <TabsTrigger value="general">Основни</TabsTrigger>
          <TabsTrigger value="delivery">Доставки</TabsTrigger>
          <TabsTrigger value="taxes">Данъци</TabsTrigger>
        </TabsList>

        <TabsContent value="taxes" className="grid gap-3">
        <CollapsibleSection title="Данъчно облагане" icon={Landmark} persistKey="settings.vat" help="Важи за поръчки, създадени след промяната. Вече създадените поръчки и документи пазят използваната ставка.">
          <div className="flex max-w-xl items-center justify-between gap-5 rounded-[6px] border border-border bg-card p-4">
            <div><h3 className="m-0 text-base">Фирмата е регистрирана по ДДС</h3><p className="mt-1 mb-0 text-sm leading-relaxed text-muted-foreground">При „Да“ сумите се изчисляват автоматично по ставката от фирмените настройки. При „Не“ фактурите не начисляват ДДС.</p></div>
            <Switch checked={settings.vat_enabled} disabled={busy} aria-label="Фирмата е регистрирана по ДДС" onCheckedChange={(checked) => void saveVatEnabled(checked)} />
          </div>
        </CollapsibleSection>
        </TabsContent>

        <TabsContent value="delivery" className="grid gap-3">
        <CollapsibleSection title="Доставка" icon={PackageCheck} persistKey="settings.shipping" help="Управлява кога магазинът може да поеме куриерската такса за нова поръчка.">
          <div className="grid max-w-xl gap-3 rounded-[6px] border border-border bg-card p-4">
            <Label htmlFor="free-shipping-threshold" className="grid gap-2 font-sans"><span>Праг за безплатна доставка (€)</span><input id="free-shipping-threshold" type="number" min="0" max="999999.99" step="0.01" className="h-10 border border-input bg-background px-3 text-foreground outline-none focus:border-ring" value={freeShippingThreshold} onChange={(event) => setFreeShippingThreshold(event.target.value)} /><small className="leading-relaxed text-muted-foreground">Магазинът може да плати доставката само когато стойността на продуктите е строго над този праг.</small></Label>
            <div><Button type="button" disabled={busy} onClick={() => void saveFreeShippingThreshold()}><PackageCheck />Запази прага</Button></div>
          </div>
        </CollapsibleSection>

        <CollapsibleSection title="Товарителници и куриер" icon={Truck} persistKey="settings.econt-operations" help="Разрешава създаването на товарителници и заявяването на куриер от онлайн магазина. Когато е изключено, свързаните Econt действия в счетоводството са заключени.">
          <div className="flex max-w-xl items-center justify-between gap-5 rounded-[6px] border border-border bg-card p-4">
            <div><h3 className="m-0 text-base">Създаване на товарителници и заявяване на куриер</h3><p className="mt-1 mb-0 text-sm leading-relaxed text-muted-foreground">Изключете опцията, когато магазинът не трябва да създава товарителници или да изпраща заявки към куриер. Данните от вече издадени документи се запазват.</p></div>
            <Switch checked={settings.econt_operations_enabled} disabled={busy} aria-label="Създаване на товарителници и заявяване на куриер" onCheckedChange={(checked) => void saveEcontOperationsEnabled(checked)} />
          </div>
        </CollapsibleSection>

        <CollapsibleSection title="Econt" icon={Truck} persistKey="settings.econt" help="Избира средата и credentials, използвани централизирано от всички Econt операции.">
          <div className="grid max-w-3xl gap-5">
            <RadioGroup value={econtForm.environment} onValueChange={(value) => setEcontForm((current) => ({ ...current, environment: value as 'demo' | 'production' }))} className="grid gap-3 sm:grid-cols-2" aria-label="Econt среда">
              <Label htmlFor="econt-demo" className="flex cursor-pointer items-start gap-3 rounded-[6px] border border-border bg-card p-4 font-sans text-foreground">
                <RadioGroupItem id="econt-demo" value="demo" />
                <span><strong className="block">Demo</strong><small className="mt-1 block leading-relaxed text-muted-foreground">Тестови endpoints и demo credentials. Не създава реални пратки.</small></span>
              </Label>
              <Label htmlFor="econt-production" className="flex cursor-pointer items-start gap-3 rounded-[6px] border border-border bg-card p-4 font-sans text-foreground">
                <RadioGroupItem id="econt-production" value="production" />
                <span><strong className="block">Production</strong><small className="mt-1 block leading-relaxed text-muted-foreground">Реалният фирмен Econt акаунт и production endpoints.</small></span>
              </Label>
            </RadioGroup>

            {econtForm.environment === 'production' ? <>
              <div className="flex items-start gap-3 border border-amber-500/50 bg-amber-500/10 p-4 text-sm leading-relaxed text-foreground" role="alert">
                <AlertTriangle className="mt-0.5 size-5 shrink-0 text-amber-600" />
                <div><strong className="block">Внимание: реална Econt среда</strong><span>Калкулациите и създадените товарителници са реални. Действията могат да променят и таксуват реалния Econt акаунт на фирмата.</span></div>
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <Label htmlFor="econt-username" className="grid gap-2 font-sans"><span>Username</span><input id="econt-username" className="h-10 border border-input bg-background px-3 text-foreground outline-none focus:border-ring" autoComplete="off" value={econtForm.username} onChange={(event) => setEcontForm((current) => ({ ...current, username: event.target.value }))} /></Label>
                <Label htmlFor="econt-password" className="grid gap-2 font-sans"><span>Password</span><input id="econt-password" type="password" className="h-10 border border-input bg-background px-3 text-foreground outline-none focus:border-ring" autoComplete="new-password" placeholder={settings.econt.production_password_configured ? settings.econt.production_password_masked : 'Въведете Production парола'} value={econtForm.password} onChange={(event) => setEcontForm((current) => ({ ...current, password: event.target.value }))} /><small className="text-muted-foreground">Оставете празно, за да запазите вече криптираната парола.</small></Label>
              </div>
              <p className="m-0 text-sm text-muted-foreground">Статус: {settings.econt.production_verified_at ? `Проверена връзка · ${new Date(settings.econt.production_verified_at).toLocaleString('bg-BG')}` : 'Връзката още не е проверена. Реалните операции са блокирани.'}</p>
            </> : <div className="border border-border bg-muted/50 p-4 text-sm leading-relaxed text-muted-foreground">Demo credentials се зареждат от сървърната конфигурация и не се показват или редактират в администрацията.</div>}

            <div className="flex flex-wrap gap-2">
              <Button type="button" disabled={busy} onClick={() => void saveEcont()}><Truck />Запази Econt настройките</Button>
              <Button type="button" variant="outline" disabled={busy || (econtForm.environment === 'production' && (!econtForm.username || (!econtForm.password && !settings.econt.production_password_configured)))} onClick={() => void testEcont()}><PlugZap />Тествай връзката</Button>
            </div>
          </div>
        </CollapsibleSection>

        </TabsContent>

        <TabsContent value="general" className="grid gap-3">
        <CollapsibleSection title="Наличности" icon={PackageCheck} persistKey="settings.inventory" help="Определя кога продукт или негов вариант се счита за ниско наличен в администрацията.">
          <div className="grid max-w-xl gap-3 rounded-[6px] border border-border bg-card p-4">
            <Label htmlFor="low-stock-threshold" className="grid gap-2 font-sans"><span>Минимална наличност (бр.)</span><input id="low-stock-threshold" type="number" min="0" max="999999" step="1" className="h-10 border border-input bg-background px-3 text-foreground outline-none focus:border-ring" value={lowStockThreshold} onChange={(event) => setLowStockThreshold(event.target.value)} /><small className="leading-relaxed text-muted-foreground">Вариант с наличност под тази стойност се отбелязва в редакцията на продукта. Филтърът „Под минималната наличност“ показва продуктите с поне един такъв вариант. Стойност 0 изключва отбелязването.</small></Label>
            <div><Button type="button" disabled={busy} onClick={() => void saveLowStockThreshold()}><PackageCheck />Запази минималната наличност</Button></div>
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
        </TabsContent>
      </Tabs>

      {pickerOpen ? <MediaPickerDialog token={token} title="Избор на лого" onSelect={(files) => { setPickerOpen(false); const file = files[0]; if (file) void saveLogo(file.id); }} onClose={() => setPickerOpen(false)} /> : null}
    </div>
  );
}

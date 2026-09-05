export const phoneCountries = [
  ['BG', 'България', '+359'], ['GR', 'Гърция', '+30'], ['RO', 'Румъния', '+40'], ['RS', 'Сърбия', '+381'],
  ['MK', 'Северна Македония', '+389'], ['TR', 'Турция', '+90'], ['DE', 'Германия', '+49'], ['AT', 'Австрия', '+43'],
  ['CH', 'Швейцария', '+41'], ['IT', 'Италия', '+39'], ['ES', 'Испания', '+34'], ['PT', 'Португалия', '+351'],
  ['FR', 'Франция', '+33'], ['BE', 'Белгия', '+32'], ['NL', 'Нидерландия', '+31'], ['LU', 'Люксембург', '+352'],
  ['GB', 'Обединеното кралство', '+44'], ['IE', 'Ирландия', '+353'], ['DK', 'Дания', '+45'], ['SE', 'Швеция', '+46'],
  ['NO', 'Норвегия', '+47'], ['FI', 'Финландия', '+358'], ['IS', 'Исландия', '+354'], ['PL', 'Полша', '+48'],
  ['CZ', 'Чехия', '+420'], ['SK', 'Словакия', '+421'], ['HU', 'Унгария', '+36'], ['HR', 'Хърватия', '+385'],
  ['SI', 'Словения', '+386'], ['BA', 'Босна и Херцеговина', '+387'], ['UA', 'Украйна', '+380'], ['MD', 'Молдова', '+373'],
  ['GE', 'Грузия', '+995'], ['US', 'Съединени щати', '+1'], ['CA', 'Канада', '+1'], ['AU', 'Австралия', '+61'],
  ['NZ', 'Нова Зеландия', '+64'], ['IL', 'Израел', '+972'], ['AE', 'Обединени арабски емирства', '+971'],
] as const;

export const defaultPhoneCountry = phoneCountries[0][2];

export function splitPhone(value: string | null | undefined): { country: string; number: string } {
  const raw = String(value ?? '').trim();
  if (raw === '') return { country: defaultPhoneCountry, number: '' };
  const match = [...phoneCountries].sort((left, right) => right[2].length - left[2].length).find((country) => raw.startsWith(country[2]));
  return match ? { country: match[2], number: raw.slice(match[2].length).trim() } : { country: defaultPhoneCountry, number: raw.replace(/^\+/, '') };
}

export function composePhone(country: string, number: string): string {
  const local = number.trim();
  return local === '' ? '' : `${country || defaultPhoneCountry} ${local}`;
}

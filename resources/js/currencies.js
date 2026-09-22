import { intlLocale } from './i18n.js';

/**
 * The currencies a user can bill in. Keep in step with app/Support/Currency.php.
 */
const codes = [
    'EUR',
    'USD',
    'GBP',
    'CHF',
    'BAM',
    'RSD',
    'HUF',
    'CZK',
    'PLN',
    'SEK',
    'NOK',
    'DKK',
    'CAD',
    'AUD',
];

/**
 * The sign the app writes after amounts: "€" for euro, the code otherwise,
 * exactly as formatMoney() shows it.
 */
export function currencySymbol(code) {
    const parts = new Intl.NumberFormat(intlLocale(), {
        style: 'currency',
        currency: code || 'EUR',
    }).formatToParts(0);

    return parts.find((part) => part.type === 'currency')?.value ?? code;
}

/**
 * The shortest sign a currency has — € for euro, $ for dollar, £ for pound,
 * the code where there is nothing shorter — for places with room for one
 * mark, like the billable toggle.
 */
export function currencySign(code) {
    const parts = new Intl.NumberFormat(intlLocale(), {
        style: 'currency',
        currency: code || 'EUR',
        currencyDisplay: 'narrowSymbol',
    }).formatToParts(0);

    return parts.find((part) => part.type === 'currency')?.value ?? code;
}

/**
 * The picker's options, with names in the language on screen ("EUR — Euro",
 * "EUR — euro", "EUR — Euro"), so it is a function rather than a constant.
 */
export function currencyOptions() {
    const names = new Intl.DisplayNames([intlLocale()], {
        type: 'currency',
    });

    return codes.map((code) => ({
        value: code,
        label: `${code} — ${names.of(code)}`,
        symbol: currencySymbol(code),
    }));
}

import { ref } from 'vue';
import de from './lang/de.js';
import en from './lang/en.js';
import hr from './lang/hr.js';
import sl from './lang/sl.js';

/**
 * The app's languages. Keep in step with app/Support/Locale.php.
 * `intl` drives dates and money, `name` is shown in the language picker.
 */
export const languages = {
    hr: {
        name: 'Hrvatski',
        intl: 'hr-HR',
        messages: hr,
    },
    en: {
        name: 'English',
        intl: 'en-GB',
        messages: en,
    },
    de: {
        name: 'Deutsch',
        intl: 'de-DE',
        messages: de,
    },
    sl: {
        name: 'Slovenščina',
        intl: 'sl-SI',
        messages: sl,
    },
};

/**
 * Starts from the page's <html lang>, which the server set from the user's
 * choice, so the first paint is already in the right language.
 */
const pageLanguage = document.documentElement.lang;

export const locale = ref(pageLanguage in languages ? pageLanguage : 'hr');

export function setLocale(code) {
    if (! (code in languages)) {
        return;
    }

    locale.value = code;
    document.documentElement.lang = code;
}

/**
 * The few texts PrimeVue writes itself, such as an empty filtered list.
 */
export const primeVueTexts = {
    hr: {
        emptyFilterMessage: 'Nema rezultata',
        emptyMessage: 'Nema dostupnih opcija',
        emptySearchMessage: 'Nema rezultata',
        searchMessage: '{0} rezultata',
    },
    en: {
        emptyFilterMessage: 'No results found',
        emptyMessage: 'No options available',
        emptySearchMessage: 'No results found',
        searchMessage: '{0} results are available',
    },
    de: {
        emptyFilterMessage: 'Keine Ergebnisse',
        emptyMessage: 'Keine Optionen verfügbar',
        emptySearchMessage: 'Keine Ergebnisse',
        searchMessage: '{0} Ergebnisse verfügbar',
    },
    sl: {
        emptyFilterMessage: 'Ni rezultatov',
        emptyMessage: 'Ni razpoložljivih možnosti',
        emptySearchMessage: 'Ni rezultatov',
        searchMessage: '{0} rezultatov',
    },
};

export function intlLocale() {
    return languages[locale.value].intl;
}

/**
 * The message for a key in the current language, falling back to Croatian
 * and then to the key itself. {name} placeholders are filled from params.
 */
export function t(key, params = {}) {
    const message = languages[locale.value].messages[key]
        ?? languages.hr.messages[key]
        ?? key;

    return message.replace(/\{(\w+)\}/g, (match, name) => params[name] ?? match);
}

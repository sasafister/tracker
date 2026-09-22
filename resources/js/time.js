import { intlLocale, t } from './i18n.js';

/**
 * The server stores and returns UTC. Everything here converts to the
 * browser's local time for display only.
 */

export function secondsBetween(startIso, end) {
    const start = new Date(startIso);
    const elapsed = Math.floor((end.getTime() - start.getTime()) / 1000);

    return Math.max(0, elapsed);
}

export function entryDuration(entry, now) {
    const end = entry.ended_at === null ? now : new Date(entry.ended_at);

    return secondsBetween(entry.started_at, end);
}

export function formatDuration(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return [hours, minutes, seconds]
        .map((part, index) => (index === 0 ? String(part) : String(part).padStart(2, '0')))
        .join(':');
}

export function formatClock(iso) {
    return new Date(iso).toLocaleTimeString(intlLocale(), {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/**
 * Local calendar day, as YYYY-MM-DD, used to group the list.
 */
export function dayKey(iso) {
    const date = new Date(iso);
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

export function formatDayLabel(key) {
    const today = dayKey(new Date().toISOString());

    const yesterdayDate = new Date();
    yesterdayDate.setDate(yesterdayDate.getDate() - 1);
    const yesterday = dayKey(yesterdayDate.toISOString());

    if (key === today) {
        return t('common.today');
    }

    if (key === yesterday) {
        return t('common.yesterday');
    }

    const [year, month, day] = key.split('-');

    return new Date(Number(year), Number(month) - 1, Number(day)).toLocaleDateString(intlLocale(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

/**
 * <input type="datetime-local"> speaks local wall-clock time with no zone,
 * while the API speaks UTC. These two convert between the pair.
 */
export function toLocalInput(iso) {
    const date = new Date(iso);
    const pad = (value) => String(value).padStart(2, '0');

    const day = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const time = `${pad(date.getHours())}:${pad(date.getMinutes())}`;

    return `${day}T${time}`;
}

export function fromLocalInput(value) {
    return new Date(value).toISOString();
}

export function formatMoney(amount, currency) {
    return new Intl.NumberFormat(intlLocale(), {
        style: 'currency',
        currency: currency || 'EUR',
    }).format(amount);
}

export function formatHours(totalSeconds) {
    const hours = new Intl.NumberFormat(intlLocale(), {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(totalSeconds / 3600);

    return `${hours} h`;
}

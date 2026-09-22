import { onUnmounted, watchEffect } from 'vue';
import { t } from './i18n.js';
import { runningEntry, state } from './store.js';
import { entryDuration } from './time.js';

const idleTitle = document.title;
const idleIcon = '/favicon.svg';
const runningIcon = '/favicon-running.svg';

/**
 * Elapsed time the way Toggl shows it in a tab: "2s" for the first minute,
 * then "4:05", then "1:02:03".
 */
export function formatTabDuration(totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (value) => String(value).padStart(2, '0');

    if (totalSeconds < 60) {
        return `${seconds}s`;
    }

    if (hours === 0) {
        return `${minutes}:${pad(seconds)}`;
    }

    return `${hours}:${pad(minutes)}:${pad(seconds)}`;
}

function setIcon(href) {
    const link = document.querySelector('link[rel="icon"]');

    if (link && link.getAttribute('href') !== href) {
        link.setAttribute('href', href);
    }
}

/**
 * While a timer runs, the browser tab shows its clock and description and
 * the favicon gets a red dot; otherwise both go back to plain "Timer".
 */
export function useTabTitle() {
    const stop = watchEffect(() => {
        const entry = runningEntry.value;

        if (entry === null) {
            document.title = idleTitle;
            setIcon(idleIcon);

            return;
        }

        const elapsed = formatTabDuration(entryDuration(entry, state.now));
        const description = entry.description || t('common.noDescription');

        document.title = `${elapsed} • ${description}`;
        setIcon(runningIcon);
    });

    onUnmounted(() => {
        stop();
        document.title = idleTitle;
        setIcon(idleIcon);
    });
}

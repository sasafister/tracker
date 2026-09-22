<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import deLocale from '@fullcalendar/core/locales/de';
import enLocale from '@fullcalendar/core/locales/en-gb';
import hrLocale from '@fullcalendar/core/locales/hr';
import slLocale from '@fullcalendar/core/locales/sl';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import EntryDialog from '../components/EntryDialog.vue';
import { intlLocale, locale, t } from '../i18n.js';
import {
    addEntry,
    clientFor,
    editEntry,
    projectFor,
    removeEntry,
    state,
    totalAmount,
    totalSeconds,
} from '../store.js';
import { dayKey, entryDuration, formatHours, formatMoney } from '../time.js';

/**
 * Zoom steps; the height of a quarter hour at each one is in app.css under
 * .timer-zoom-N. The grid is cut in quarters so a click lands on a 15-minute
 * slot, as in Toggl.
 */
const zoomSteps = 4;

/**
 * What the grid shows. Most weeks are worked Monday to Friday, so that is
 * the default on a desktop; a phone only has room for one day.
 */
const modes = {
    day: {
        label: 'calendar.day',
        view: 'timeGridDay',
        weekends: true,
        totalLabel: 'calendar.totalDay',
    },
    workweek: {
        label: 'calendar.workweek',
        view: 'timeGridWeek',
        weekends: false,
        totalLabel: 'calendar.totalWorkweek',
    },
    week: {
        label: 'calendar.week',
        view: 'timeGridWeek',
        weekends: true,
        totalLabel: 'calendar.totalWeek',
    },
};

const calendarLocales = {
    hr: hrLocale,
    en: enLocale,
    de: deLocale,
    sl: slLocale,
};

const modeStorageKey = 'timer.calendarMode';
const narrowQuery = window.matchMedia('(max-width: 767px)');

const calendar = ref(null);
const isNarrow = ref(narrowQuery.matches);
const mode = ref(initialMode());
const dialogVisible = ref(false);
const dialogEntry = ref(null);
const visibleRange = ref(null);
const title = ref('');
const zoom = ref(1);

/**
 * The running entry has no end yet, so it is drawn up to the end of the
 * current minute. Rounding keeps the calendar from re-rendering every
 * second; rounding up keeps the end after the start in the first minute,
 * where FullCalendar would otherwise drop it and draw a whole hour.
 */
const nowToTheMinute = computed(() => {
    const now = new Date(state.now);
    now.setSeconds(0, 0);
    now.setMinutes(now.getMinutes() + 1);

    return now.toISOString();
});

const events = computed(() => {
    return state.entries.map((entry) => {
        const isRunning = entry.ended_at === null;
        const project = projectFor(entry);

        const classNames = ['timer-entry'];

        if (project === null) {
            classNames.push('timer-entry--no-project');
        }

        if (! entry.billable) {
            classNames.push('timer-entry--non-billable');
        }

        if (isRunning) {
            classNames.push('timer-entry--running');
        }

        return {
            id: String(entry.id),
            title: entry.description ?? '',
            start: entry.started_at,
            end: entry.ended_at ?? nowToTheMinute.value,
            editable: ! isRunning,
            classNames,
            // An opaque tint of the project colour, so grid lines stay behind it.
            backgroundColor: project
                ? `color-mix(in srgb, ${project.color} 16%, white)`
                : undefined,
            textColor: project?.color,
            extendedProps: {
                entry,
                project,
                client: clientFor(project),
            },
        };
    });
});

/**
 * Seconds logged per local day, for the totals under each day's name.
 */
const secondsByDay = computed(() => {
    const totals = new Map();

    state.entries.forEach((entry) => {
        const key = dayKey(entry.started_at);

        totals.set(key, (totals.get(key) ?? 0) + entryDuration(entry, state.now));
    });

    return totals;
});

function formatDayTotal(date) {
    const seconds = secondsByDay.value.get(dayKey(date.toISOString())) ?? 0;

    if (seconds === 0) {
        return '–';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
}

function formatEventDuration(event) {
    // FullCalendar drops the end when it is not after the start.
    const end = event.end ?? event.start;
    const minutes = Math.max(0, Math.round((end - event.start) / 60000));
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest}m`;
    }

    return `${hours}:${String(rest).padStart(2, '0')}`;
}

/**
 * Under 45 minutes there is room for one line only.
 */
function isShort(event) {
    const end = event.end ?? event.start;

    return end - event.start < 45 * 60000;
}

/**
 * Zooming changes the pixel height of an hour but not the scroll offset, so
 * the time at the top of the view is noted first and scrolled back to after.
 */
async function setZoom(step) {
    const scroller = document.querySelector('.timer-calendar .fc-timegrid-body')
        ?.closest('.fc-scroller');
    const quarter = document.querySelector('.timer-calendar .fc-timegrid-slot-lane');

    const minutesAtTop = scroller && quarter
        ? Math.round((scroller.scrollTop / quarter.offsetHeight) * 15)
        : null;

    zoom.value = step;

    if (minutesAtTop === null) {
        return;
    }

    await nextTick();

    calendar.value?.getApi().scrollToTime({ minutes: minutesAtTop });
}

function zoomOut() {
    setZoom(Math.max(0, zoom.value - 1));
}

function zoomIn() {
    setZoom(Math.min(zoomSteps - 1, zoom.value + 1));
}

function goTo(direction) {
    calendar.value?.getApi()[direction]();
}

/**
 * The remembered choice, except that a phone always opens on one day.
 * Storage can be unavailable (private windows), so it is only a nicety.
 */
function initialMode() {
    if (narrowQuery.matches) {
        return 'day';
    }

    try {
        const saved = window.localStorage.getItem(modeStorageKey);

        return saved in modes && saved !== 'day' ? saved : 'workweek';
    } catch {
        return 'workweek';
    }
}

function setMode(key) {
    mode.value = key;

    if (isNarrow.value) {
        return;
    }

    try {
        window.localStorage.setItem(modeStorageKey, key);
    } catch {
        // Not remembered; the choice still applies for this visit.
    }
}

const visibleModes = computed(() => {
    const keys = isNarrow.value ? ['day', 'workweek', 'week'] : ['workweek', 'week'];

    return keys.map((key) => ({
        key,
        label: t(modes[key].label),
    }));
});

function isShownDay(date) {
    const weekday = date.getDay();
    const isWeekend = weekday === 0 || weekday === 6;

    return modes[mode.value].weekends || ! isWeekend;
}

/**
 * Entries in the visible range, minus the weekend when it is hidden, so the
 * totals match what is on screen.
 */
const weekEntries = computed(() => {
    if (visibleRange.value === null) {
        return [];
    }

    return state.entries.filter((entry) => {
        const startedAt = new Date(entry.started_at);
        const isInRange = startedAt >= visibleRange.value.start
            && startedAt < visibleRange.value.end;

        return isInRange && isShownDay(startedAt);
    });
});

function handleNarrowChange(event) {
    isNarrow.value = event.matches;

    if (event.matches && mode.value !== 'day') {
        mode.value = 'day';
    }

    if (! event.matches && mode.value === 'day') {
        mode.value = initialMode();
    }
}

// initialView is only read once, so later switches go through the API.
watch(mode, (key) => {
    calendar.value?.getApi().changeView(modes[key].view);
});

onMounted(() => {
    narrowQuery.addEventListener('change', handleNarrowChange);
});

onUnmounted(() => {
    narrowQuery.removeEventListener('change', handleNarrowChange);
});

const weekSeconds = computed(() => totalSeconds(weekEntries.value, state.now));

const weekAmount = computed(() => totalAmount(weekEntries.value, state.now));

function openNewEntry(selection) {
    dialogEntry.value = {
        id: null,
        description: '',
        project_id: null,
        billable: true,
        started_at: selection.start.toISOString(),
        ended_at: selection.end.toISOString(),
    };
    dialogVisible.value = true;
}

function openExistingEntry(clicked) {
    const entry = state.entries.find((candidate) => String(candidate.id) === clicked.event.id);

    if (! entry) {
        return;
    }

    // Passed as is: a running entry keeps its null end so the dialog knows.
    dialogEntry.value = { ...entry };
    dialogVisible.value = true;
}

function closeDialog() {
    dialogVisible.value = false;
    dialogEntry.value = null;
}

async function saveDialog(changes) {
    if (dialogEntry.value?.id) {
        await editEntry(dialogEntry.value.id, changes);
    } else {
        await addEntry(changes);
    }

    closeDialog();
}

async function removeDialogEntry(id) {
    await removeEntry(id);
    closeDialog();
}

/**
 * Dragging or resizing an event writes the new span straight through.
 */
async function moveEvent(info) {
    await editEntry(Number(info.event.id), {
        started_at: info.event.start.toISOString(),
        ended_at: info.event.end.toISOString(),
    });
}

const calendarOptions = computed(() => ({
    plugins: [timeGridPlugin, interactionPlugin],
    initialView: modes[mode.value].view,
    weekends: modes[mode.value].weekends,
    locale: calendarLocales[locale.value],
    firstDay: 1,
    allDaySlot: false,
    nowIndicator: true,
    slotDuration: '00:15:00',
    slotLabelInterval: '01:00',
    slotLabelFormat: {
        hour: 'numeric',
        minute: '2-digit',
        hour12: false,
    },
    scrollTime: '08:00:00',
    height: '100%',
    selectable: true,
    selectMirror: true,
    editable: true,
    eventDurationEditable: true,
    headerToolbar: false,
    // The slot height comes from CSS, which FullCalendar does not watch. A
    // class that changes with the zoom re-renders the rows, and FullCalendar
    // measures them again so the events line up with the new grid.
    slotLaneClassNames: [`timer-zoom-${zoom.value}`],
    events: events.value,
    select: openNewEntry,
    eventClick: openExistingEntry,
    eventDrop: moveEvent,
    eventResize: moveEvent,
    datesSet: (info) => {
        title.value = info.view.title;
        visibleRange.value = {
            start: info.start,
            end: info.end,
        };
    },
}));
</script>

<template>
    <div class="flex h-full flex-col gap-4">
        <!--
            Phones get two rows: navigation and view switch, then the date and
            its totals. From md up it is one row. The order classes and the
            md:hidden break arrange that.
        -->
        <div class="flex flex-wrap items-center gap-x-2 gap-y-3">
            <div class="order-1 flex overflow-hidden rounded-lg border border-slate-200 bg-white">
                <button
                    type="button"
                    class="px-3 py-1.5 text-slate-600 hover:bg-slate-50"
                    :aria-label="t('calendar.previous')"
                    @click="goTo('prev')"
                >
                    ‹
                </button>

                <button
                    type="button"
                    class="border-x border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="goTo('today')"
                >
                    {{ t('common.today') }}
                </button>

                <button
                    type="button"
                    class="px-3 py-1.5 text-slate-600 hover:bg-slate-50"
                    :aria-label="t('calendar.next')"
                    @click="goTo('next')"
                >
                    ›
                </button>
            </div>

            <div
                class="order-2 ml-auto flex rounded-lg border border-slate-200 bg-white p-0.5 md:ml-0"
                role="group"
                :aria-label="t('calendar.view')"
            >
                <button
                    v-for="option in visibleModes"
                    :key="option.key"
                    type="button"
                    class="rounded-md px-3 py-1 text-sm font-medium transition"
                    :class="option.key === mode
                        ? 'bg-slate-900 text-white'
                        : 'text-slate-600 hover:text-slate-900'"
                    :aria-pressed="option.key === mode"
                    @click="setMode(option.key)"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="order-3 basis-full md:hidden"></div>

            <h2 class="order-4 min-w-0 flex-1 truncate text-base font-semibold capitalize md:ml-2">
                {{ title }}
            </h2>

            <div class="order-5 flex items-center gap-4 rounded-lg border border-slate-200 bg-white px-3 py-1.5 md:gap-6 md:px-4 md:py-2">
                <div>
                    <div class="text-[0.6875rem] uppercase tracking-wide text-slate-500 md:text-xs">
                        {{ t(modes[mode].totalLabel) }}
                    </div>
                    <div class="text-sm font-semibold tabular-nums md:text-base">
                        {{ formatHours(weekSeconds) }}
                    </div>
                </div>

                <div>
                    <div class="text-[0.6875rem] uppercase tracking-wide text-slate-500 md:text-xs">{{ t('common.amount') }}</div>
                    <div class="text-sm font-semibold tabular-nums md:text-base">
                        {{ formatMoney(weekAmount, state.settings.currency) }}
                    </div>
                </div>
            </div>
        </div>

        <div
            class="timer-calendar relative min-h-0 flex-1 overflow-hidden rounded-lg border border-slate-200 bg-white"
        >
            <div class="absolute top-0 left-0 z-10 flex h-14 w-14 items-center justify-center gap-1 text-slate-400">
                <button
                    type="button"
                    class="rounded px-1 text-lg leading-none hover:text-slate-700 disabled:opacity-30"
                    :aria-label="t('calendar.zoomOut')"
                    :disabled="zoom === 0"
                    @click="zoomOut"
                >
                    −
                </button>

                <button
                    type="button"
                    class="rounded px-1 text-lg leading-none hover:text-slate-700 disabled:opacity-30"
                    :aria-label="t('calendar.zoomIn')"
                    :disabled="zoom === zoomSteps - 1"
                    @click="zoomIn"
                >
                    +
                </button>
            </div>

            <FullCalendar
                ref="calendar"
                :options="calendarOptions"
            >
                <template #dayHeaderContent="{ date, isToday }">
                    <div class="flex items-center gap-2 py-1.5">
                        <span
                            class="flex size-9 items-center justify-center rounded-full text-xl font-normal"
                            :class="isToday ? 'bg-fuchsia-50 text-[var(--timer-accent)]' : 'text-slate-800'"
                        >
                            {{ date.getDate() }}
                        </span>

                        <span class="flex flex-col items-start leading-tight">
                            <span class="text-sm font-medium capitalize text-slate-800">
                                {{ date.toLocaleDateString(intlLocale(), { weekday: 'short' }) }}
                            </span>

                            <span
                                class="text-xs font-medium"
                                :class="isToday ? 'text-[var(--timer-accent)]' : 'text-slate-400'"
                            >
                                {{ formatDayTotal(date) }}
                            </span>
                        </span>
                    </div>
                </template>

                <template #eventContent="{ event }">
                    <div
                        v-if="isShort(event)"
                        class="flex h-full items-start gap-1.5 overflow-hidden px-1.5 py-0.5 text-xs leading-snug"
                    >
                        <span class="truncate font-medium">
                            {{ event.title || t('common.noDescription') }}
                        </span>

                        <span class="shrink-0 opacity-70">
                            {{ formatEventDuration(event) }}
                        </span>
                    </div>

                    <div
                        v-else
                        class="flex h-full flex-col overflow-hidden px-1.5 py-1 text-xs leading-snug"
                    >
                        <span class="truncate font-medium">
                            {{ event.title || t('common.noDescription') }}
                        </span>

                        <span
                            v-if="event.extendedProps.project"
                            class="truncate opacity-80"
                        >
                            {{ event.extendedProps.project.name }}
                            <template v-if="event.extendedProps.client">
                                · {{ event.extendedProps.client.name }}
                            </template>
                        </span>

                        <span class="mt-auto truncate opacity-70">
                            {{ formatEventDuration(event) }}
                            <template v-if="! event.extendedProps.entry.billable"> · {{ t('calendar.nonBillable') }}</template>
                        </span>
                    </div>
                </template>

                <template #nowIndicatorContent="{ isAxis }">
                    <span
                        v-if="isAxis"
                        class="timer-now-dot"
                    ></span>
                </template>
            </FullCalendar>
        </div>

        <EntryDialog
            :visible="dialogVisible"
            :entry="dialogEntry"
            @save="saveDialog"
            @remove="removeDialogEntry"
            @close="closeDialog"
        />
    </div>
</template>

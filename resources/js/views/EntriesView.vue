<script setup>
import { computed, ref } from 'vue';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Select from 'primevue/select';
import { useConfirm } from 'primevue/useconfirm';
import { currencySign } from '../currencies.js';
import EntryDialog from '../components/EntryDialog.vue';
import { intlLocale, t } from '../i18n.js';
import {
    addEntry,
    amountForEntry,
    clientFor,
    editEntry,
    jiraIssueUrl,
    jiraIssuesFor,
    projectFor,
    projectsById,
    removeEntry,
    state,
    totalAmount,
    totalSeconds,
} from '../store.js';
import {
    entryDuration,
    formatClock,
    formatDuration,
    formatHours,
    formatMoney,
} from '../time.js';

const confirm = useConfirm();

const month = ref(startOfMonth(new Date()));

const sign = computed(() => currencySign(state.settings.currency));
const clientId = ref(null);
const dialogVisible = ref(false);
const dialogEntry = ref(null);

const monthLabel = computed(() => {
    const label = month.value.toLocaleDateString(intlLocale(), {
        month: 'long',
        year: 'numeric',
    });

    return label.charAt(0).toUpperCase() + label.slice(1);
});

/**
 * The PDF of the chosen month. The browser's timezone goes along so the
 * report shows the same days and times as the screen.
 */
const exportUrl = computed(() => {
    const query = new URLSearchParams({
        from: month.value.toISOString(),
        to: shiftedMonth(month.value, 1).toISOString(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    });

    if (clientId.value !== null) {
        query.set('client_id', clientId.value);
    }

    return `/api/export/pdf?${query}`;
});

const isCurrentMonth = computed(() => {
    return month.value.getTime() === startOfMonth(new Date()).getTime();
});

const clientOptions = computed(() => {
    return state.clients.map((client) => ({
        label: client.name,
        value: client.id,
    }));
});

/**
 * An entry belongs to a client through its project.
 */
function isForClient(entry) {
    if (clientId.value === null) {
        return true;
    }

    const project = projectsById.value.get(entry.project_id);

    return project?.client_id === clientId.value;
}

/**
 * Entries that started in the chosen month, local time, newest first,
 * narrowed to the chosen client if there is one.
 */
const monthEntries = computed(() => {
    const start = month.value;
    const end = shiftedMonth(start, 1);

    return state.entries
        .filter((entry) => {
            const startedAt = new Date(entry.started_at);

            return startedAt >= start && startedAt < end && isForClient(entry);
        })
        .sort((a, b) => new Date(b.started_at) - new Date(a.started_at));
});

const rows = computed(() => {
    return monthEntries.value.map((entry) => ({
        id: entry.id,
        entry,
        project: projectFor(entry),
        jiraIssues: jiraIssuesFor(entry).map((issue) => ({
            ...issue,
            url: jiraIssueUrl(entry, issue.key),
        })),
        client: clientFor(projectFor(entry)),
        day: new Date(entry.started_at).toLocaleDateString(intlLocale(), {
            weekday: 'short',
            day: 'numeric',
            month: 'numeric',
        }),
        seconds: entryDuration(entry, state.now),
        amount: amountForEntry(entry, state.now),
    }));
});

const totals = computed(() => {
    const billable = monthEntries.value.filter((entry) => entry.billable);

    return {
        seconds: totalSeconds(monthEntries.value, state.now),
        billableSeconds: totalSeconds(billable, state.now),
        amount: totalAmount(monthEntries.value, state.now),
    };
});

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function shiftedMonth(date, months) {
    return new Date(date.getFullYear(), date.getMonth() + months, 1);
}

function goToMonth(months) {
    month.value = shiftedMonth(month.value, months);
}

function goToCurrentMonth() {
    month.value = startOfMonth(new Date());
}

function money(amount) {
    return formatMoney(amount, state.settings.currency);
}

function openEntry(event) {
    const entry = event.data.entry;

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

function confirmRemove(row) {
    const label = row.entry.description || t('common.noDescription');

    confirm.require({
        header: t('entries.deleteTitle'),
        message: t('entries.deleteMessage', { label }),
        rejectProps: {
            label: t('common.cancel'),
            severity: 'secondary',
            text: true,
        },
        acceptProps: {
            label: t('common.delete'),
        },
        accept: () => removeEntry(row.id),
    });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-slate-600 hover:bg-slate-50"
                        :aria-label="t('entries.previousMonth')"
                        @click="goToMonth(-1)"
                    >
                        ‹
                    </button>

                    <button
                        type="button"
                        class="border-x border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:text-slate-400"
                        :disabled="isCurrentMonth"
                        @click="goToCurrentMonth"
                    >
                        {{ t('entries.thisMonth') }}
                    </button>

                    <button
                        type="button"
                        class="px-3 py-1.5 text-slate-600 hover:bg-slate-50"
                        :aria-label="t('entries.nextMonth')"
                        @click="goToMonth(1)"
                    >
                        ›
                    </button>
                </div>

                <h2 class="ml-2 text-base font-semibold">{{ monthLabel }}</h2>

                <Select
                    v-if="clientOptions.length > 0"
                    v-model="clientId"
                    :options="clientOptions"
                    option-label="label"
                    option-value="value"
                    :placeholder="t('entries.allClients')"
                    show-clear
                    size="small"
                    class="ml-2 w-44"
                />

                <a
                    :href="exportUrl"
                    target="_blank"
                    rel="noopener"
                    class="ml-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                >
                    {{ t('entries.exportPdf') }}
                </a>
            </div>

            <dl class="flex items-stretch overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="px-4 py-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">{{ t('entries.total') }}</dt>
                    <dd class="font-semibold tabular-nums">{{ formatHours(totals.seconds) }}</dd>
                </div>

                <div class="px-4 py-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">{{ t('entries.billable') }}</dt>
                    <dd class="font-semibold tabular-nums">{{ formatHours(totals.billableSeconds) }}</dd>
                </div>

                <!-- The month's amount is what this screen is for, so it stands out. -->
                <div class="flex flex-col justify-center bg-slate-900 px-5 py-2 text-white">
                    <dt class="text-xs uppercase tracking-wide text-slate-400">{{ t('common.amount') }}</dt>
                    <dd class="text-xl font-semibold tabular-nums">{{ money(totals.amount) }}</dd>
                </div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <DataTable
                :value="rows"
                data-key="id"
                row-hover
                class="text-sm"
                :row-class="() => 'cursor-pointer'"
                @row-click="openEntry"
            >
                <template #empty>
                    <p class="py-6 text-center text-sm text-slate-500">
                        {{ clientId === null
                            ? t('entries.emptyMonth')
                            : t('entries.emptyClient') }}
                    </p>
                </template>

                <Column
                    :header="t('entries.colDay')"
                    header-class="hidden sm:table-cell"
                    body-class="hidden whitespace-nowrap text-slate-500 sm:table-cell"
                >
                    <template #body="{ data }">
                        {{ data.day }}
                    </template>
                </Column>

                <Column
                    :header="t('entries.colDescription')"
                    body-class="max-w-0 w-full"
                >
                    <template #body="{ data }">
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                class="min-w-0 truncate"
                                :class="data.entry.description ? '' : 'text-slate-400'"
                                :title="data.entry.description ?? ''"
                            >
                                {{ data.entry.description || t('common.noDescription') }}
                            </span>

                            <!-- Tickets open in Jira; clicking one does not open the entry. -->
                            <a
                                v-for="issue in data.jiraIssues"
                                :key="issue.key"
                                :href="issue.url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex shrink-0 items-center gap-1 rounded border border-slate-200 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600 transition hover:border-slate-400 hover:text-slate-900"
                                :title="[t('jira.openTicket', { key: issue.key }), issue.summary]
                                    .filter(Boolean)
                                    .join(' — ')"
                                @click.stop
                            >
                                {{ issue.key }}
                                <span aria-hidden="true">↗</span>
                            </a>
                        </span>

                        <!-- On a phone the day and time columns are hidden and shown here. -->
                        <span class="block truncate text-xs text-slate-400 sm:hidden">
                            {{ data.day }} ·
                            <template v-if="data.client">{{ data.client.name }} · </template>
                            {{ formatClock(data.entry.started_at) }}
                            –
                            <template v-if="data.entry.ended_at">{{ formatClock(data.entry.ended_at) }}</template>
                            <template v-else>{{ t('common.inProgress') }}</template>
                        </span>
                    </template>
                </Column>

                <Column
                    :header="t('entries.colProject')"
                    header-class="hidden md:table-cell"
                    body-class="hidden whitespace-nowrap md:table-cell"
                >
                    <template #body="{ data }">
                        <span
                            v-if="data.project"
                            class="flex items-center gap-1.5 text-slate-600"
                        >
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :style="{ backgroundColor: data.project.color }"
                            ></span>
                            {{ data.project.name }}
                        </span>

                        <span
                            v-if="data.client"
                            class="block pl-3.5 text-xs text-slate-400"
                        >
                            {{ data.client.name }}
                        </span>
                    </template>
                </Column>

                <Column
                    :header="t('entries.colTime')"
                    header-class="hidden sm:table-cell"
                    body-class="hidden whitespace-nowrap tabular-nums text-slate-500 sm:table-cell"
                >
                    <template #body="{ data }">
                        {{ formatClock(data.entry.started_at) }}
                        –
                        <template v-if="data.entry.ended_at">{{ formatClock(data.entry.ended_at) }}</template>
                        <template v-else>{{ t('common.inProgress') }}</template>
                    </template>
                </Column>

                <Column
                    :header="t('entries.colDuration')"
                    body-class="whitespace-nowrap text-right font-mono tabular-nums"
                    header-class="[&>div]:justify-end"
                >
                    <template #body="{ data }">
                        {{ formatDuration(data.seconds) }}
                    </template>
                </Column>

                <Column
                    :header="sign"
                    header-class="hidden sm:table-cell [&>div]:justify-center"
                    body-class="hidden text-center sm:table-cell"
                >
                    <template #body="{ data }">
                        <span
                            class="font-semibold"
                            :class="data.entry.billable ? 'text-slate-900' : 'text-slate-300'"
                            :title="data.entry.billable ? t('common.billable') : t('common.nonBillable')"
                        >
                            {{ sign }}
                        </span>
                    </template>
                </Column>

                <Column
                    :header="t('common.amount')"
                    body-class="whitespace-nowrap text-right tabular-nums"
                    header-class="[&>div]:justify-end"
                >
                    <template #body="{ data }">
                        <span
                            :class="data.entry.billable
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-400'"
                        >
                            {{ money(data.amount) }}
                        </span>
                    </template>
                </Column>

                <!-- On a phone, deleting goes through the dialog a row click opens. -->
                <Column
                    header-class="hidden sm:table-cell"
                    body-class="hidden w-px whitespace-nowrap text-right sm:table-cell"
                >
                    <template #body="{ data }">
                        <button
                            type="button"
                            class="rounded px-1.5 text-slate-400 hover:text-slate-900"
                            :aria-label="t('entries.deleteLabel', { label: data.entry.description || t('common.noDescription') })"
                            :title="t('common.delete')"
                            @click.stop="confirmRemove(data)"
                        >
                            ✕
                        </button>
                    </template>
                </Column>
            </DataTable>
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

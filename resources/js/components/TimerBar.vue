<script setup>
import { computed, ref, watch } from 'vue';
import DescriptionInput from './DescriptionInput.vue';
import JiraIssueInput from './JiraIssueInput.vue';
import ProjectSelect from './ProjectSelect.vue';
import {
    editEntry,
    jiraConnectionFor,
    projectsById,
    runningEntry,
    startTimer,
    state,
    stopTimer,
} from '../store.js';
import { currencySign } from '../currencies.js';
import { t } from '../i18n.js';
import { entryDuration, formatDuration } from '../time.js';

const description = ref('');
const projectId = ref(null);
const billable = ref(true);
const jiraKey = ref(null);
const jiraSummary = ref(null);

const isRunning = computed(() => runningEntry.value !== null);

const sign = computed(() => currencySign(state.settings.currency));

// The ticket field only shows for a project linked to Jira.
const hasJira = computed(() => {
    return jiraConnectionFor(projectsById.value.get(projectId.value)) !== null;
});

const elapsed = computed(() => {
    if (! isRunning.value) {
        return 0;
    }

    return entryDuration(runningEntry.value, state.now);
});

function toggle() {
    if (isRunning.value) {
        stopTimer(runningEntry.value.id);

        return;
    }

    startTimer({
        description: description.value.trim() || null,
        project_id: projectId.value,
        jira_issue_key: hasJira.value ? jiraKey.value : null,
        jira_issue_summary: hasJira.value ? jiraSummary.value : null,
        billable: billable.value,
    });
}

/**
 * Enter starts the timer when nothing runs. While one runs it saves the
 * description instead — stopping is the button's job only.
 */
function submitDescription() {
    if (isRunning.value) {
        saveRunningDescription();

        return;
    }

    toggle();
}

function saveRunningDescription() {
    if (! isRunning.value) {
        return;
    }

    const text = description.value.trim() || null;

    if (text !== (runningEntry.value.description ?? null)) {
        editEntry(runningEntry.value.id, { description: text });
    }
}

/**
 * Before starting, these only shape the next entry; while running, they
 * change the entry that is counting.
 */
function changeRunning(changes) {
    if (isRunning.value) {
        editEntry(runningEntry.value.id, changes);
    }
}

/**
 * A ticket belongs to its project's Jira; moving to a project without one
 * drops it.
 */
function selectProject(id) {
    projectId.value = id;

    const changes = {
        project_id: id,
    };

    if (! hasJira.value && jiraKey.value !== null) {
        jiraKey.value = null;
        jiraSummary.value = null;
        changes.jira_issue_key = null;
    }

    changeRunning(changes);
}

function pickTicket(issue) {
    jiraKey.value = issue.key;
    jiraSummary.value = issue.summary;
    description.value = `${issue.key} ${issue.summary}`.trim();

    changeRunning({
        description: description.value,
        jira_issue_key: issue.key,
        jira_issue_summary: issue.summary,
    });
}

function clearTicket(key) {
    if (key === null && jiraKey.value !== null) {
        jiraKey.value = null;
        jiraSummary.value = null;
        changeRunning({ jira_issue_key: null });
    }
}

function toggleBillable() {
    billable.value = ! billable.value;
    changeRunning({ billable: billable.value });
}

function pickDescription(suggestion) {
    projectId.value = suggestion.project_id;
    billable.value = suggestion.billable;
    jiraKey.value = suggestion.jira_issue_key;
    jiraSummary.value = suggestion.jira_issue_summary;

    changeRunning({
        description: suggestion.description,
        project_id: suggestion.project_id,
        jira_issue_key: suggestion.jira_issue_key,
        jira_issue_summary: suggestion.jira_issue_summary,
        billable: suggestion.billable,
    });
}

// While something runs, the fields show what it is rather than a stale draft.
watch(runningEntry, (entry) => {
    description.value = entry === null ? '' : (entry.description ?? '');
    projectId.value = entry === null ? null : entry.project_id;
    billable.value = entry === null ? true : entry.billable;
    jiraKey.value = entry === null ? null : (entry.jira_issue_key ?? null);
    jiraSummary.value = entry === null ? null : (entry.jira_issue_summary ?? null);
});
</script>

<template>
    <!--
        One row from md up. On a phone it wraps in two: what you are doing and
        the profile on top, project, billable, clock and button below. The
        order classes and the md:hidden break arrange that.
    -->
    <header
        class="flex shrink-0 flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-4 py-3 md:h-16 md:flex-nowrap md:gap-3 md:px-6 md:py-0"
    >
        <div class="order-1 min-w-0 flex-1">
            <DescriptionInput
                v-model="description"
                :placeholder="t('timer.placeholder')"
                @pick="pickDescription"
                @enter="submitDescription"
                @blur="saveRunningDescription"
            />
        </div>

        <div class="order-2 flex items-center md:order-5 md:ml-2 md:border-l md:border-slate-200 md:pl-4">
            <slot />
        </div>

        <div class="order-3 basis-full md:hidden"></div>

        <!-- On a phone the ticket gets a row of its own, above the project. -->
        <div
            v-if="hasJira"
            class="order-4 basis-full md:basis-auto md:w-44 md:flex-none"
        >
            <JiraIssueInput
                :project-id="projectId"
                :model-value="jiraKey"
                :summary="jiraSummary"
                @pick="pickTicket"
                @update:model-value="clearTicket"
            />
        </div>

        <div class="order-4 min-w-0 flex-1 md:w-52 md:flex-none">
            <ProjectSelect
                :model-value="projectId"
                @update:model-value="selectProject"
            />
        </div>

        <button
            type="button"
            class="order-4 flex h-10 min-w-10 shrink-0 items-center justify-center rounded-lg border px-2 font-semibold transition"
            :class="[
                billable
                    ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                    : 'border-slate-200 text-slate-400 hover:text-slate-600',
                sign.length > 1 ? 'text-sm' : 'text-lg',
            ]"
            :title="billable ? t('common.billable') : t('common.nonBillable')"
            :aria-pressed="billable"
            @click="toggleBillable"
        >
            {{ sign }}
        </button>

        <span class="order-4 w-[4.5rem] shrink-0 text-right font-mono text-base tabular-nums md:w-24 md:text-lg">
            {{ formatDuration(elapsed) }}
        </span>

        <button
            type="button"
            class="order-4 shrink-0 rounded-lg px-4 py-2 text-sm font-semibold text-white transition"
            :class="isRunning
                ? 'bg-red-600 hover:bg-red-700'
                : 'bg-emerald-600 hover:bg-emerald-700'"
            @click="toggle"
        >
            {{ isRunning ? t('timer.stop') : t('timer.start') }}
        </button>
    </header>
</template>

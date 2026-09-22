<script setup>
import { computed, ref, watch } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ToggleSwitch from 'primevue/toggleswitch';
import DescriptionInput from './DescriptionInput.vue';
import JiraIssueInput from './JiraIssueInput.vue';
import ProjectSelect from './ProjectSelect.vue';
import { t } from '../i18n.js';
import { jiraConnectionFor, projectsById } from '../store.js';
import { fromLocalInput, toLocalInput } from '../time.js';

const props = defineProps({
    visible: {
        type: Boolean,
        required: true,
    },
    entry: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['save', 'remove', 'close']);

const description = ref('');
const projectId = ref(null);
const billable = ref(true);
const jiraKey = ref(null);
const jiraSummary = ref(null);
const startsAt = ref('');
const endsAt = ref('');
const isRunning = ref(false);
const validationError = ref(null);

// What the time fields started as. The inputs only hold whole minutes, so
// times are sent back only when the user changed them — otherwise a short
// entry could not be saved and every edit would drop the seconds.
// The ticket field only shows for a project linked to Jira.
const hasJira = computed(() => {
    return jiraConnectionFor(projectsById.value.get(projectId.value)) !== null;
});

let initialStartsAt = '';
let initialEndsAt = '';

watch(
    () => props.entry,
    (entry) => {
        if (entry === null) {
            return;
        }

        description.value = entry.description ?? '';
        projectId.value = entry.project_id ?? null;
        billable.value = entry.billable ?? true;
        jiraKey.value = entry.jira_issue_key ?? null;
        jiraSummary.value = entry.jira_issue_summary ?? null;
        // A running entry has no end yet and must keep counting after a save.
        isRunning.value = Boolean(entry.id) && entry.ended_at === null;

        startsAt.value = toLocalInput(entry.started_at);
        endsAt.value = isRunning.value ? '' : toLocalInput(entry.ended_at);

        // A new entry has no stored times yet, so its times always go out.
        initialStartsAt = entry.id ? startsAt.value : '';
        initialEndsAt = entry.id ? endsAt.value : '';
        validationError.value = null;
    },
    { immediate: true },
);

function pickDescription(suggestion) {
    projectId.value = suggestion.project_id;
    billable.value = suggestion.billable;
    jiraKey.value = suggestion.jira_issue_key;
    jiraSummary.value = suggestion.jira_issue_summary;
}

function pickTicket(issue) {
    jiraKey.value = issue.key;
    jiraSummary.value = issue.summary;
    description.value = `${issue.key} ${issue.summary}`.trim();
}

function save() {
    const startChanged = startsAt.value !== initialStartsAt;
    const endChanged = ! isRunning.value && endsAt.value !== initialEndsAt;
    const startsAtDate = new Date(startsAt.value);

    if (isRunning.value && startChanged && startsAtDate > new Date()) {
        validationError.value = t('entry.errorFuture');

        return;
    }

    const timesChanged = startChanged || endChanged;

    if (! isRunning.value && timesChanged && new Date(endsAt.value) <= startsAtDate) {
        validationError.value = t('entry.errorEnd');

        return;
    }

    const changes = {
        description: description.value.trim() || null,
        project_id: projectId.value,
        jira_issue_key: hasJira.value ? jiraKey.value : null,
        jira_issue_summary: hasJira.value && jiraKey.value ? jiraSummary.value : null,
        billable: billable.value,
    };

    if (startChanged) {
        changes.started_at = fromLocalInput(startsAt.value);
    }

    // Sending an end would stop the timer, so a running entry never gets one.
    if (endChanged) {
        changes.ended_at = fromLocalInput(endsAt.value);
    }

    emit('save', changes);
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :header="entry?.id ? t('entry.edit') : t('entry.new')"
        :style="{ width: '28rem' }"
        :breakpoints="{ '640px': 'calc(100vw - 2rem)' }"
        @update:visible="$emit('close')"
    >
        <div class="flex flex-col gap-4">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">{{ t('entry.description') }}</span>

                <DescriptionInput
                    v-model="description"
                    :placeholder="t('entry.descriptionPlaceholder')"
                    autofocus
                    @pick="pickDescription"
                />
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium text-slate-700">{{ t('entry.project') }}</span>

                <ProjectSelect v-model="projectId" />
            </label>

            <label
                v-if="hasJira"
                class="flex flex-col gap-1 text-sm"
            >
                <span class="font-medium text-slate-700">{{ t('jira.ticket') }}</span>

                <JiraIssueInput
                    v-model="jiraKey"
                    :project-id="projectId"
                    :summary="jiraSummary"
                    @pick="pickTicket"
                />
            </label>

            <label class="flex items-center gap-3 text-sm">
                <ToggleSwitch v-model="billable" />

                <span class="font-medium text-slate-700">{{ t('common.billable') }}</span>
            </label>

            <div class="grid grid-cols-2 gap-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">{{ t('entry.from') }}</span>

                    <input
                        v-model="startsAt"
                        type="datetime-local"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500"
                    >
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium text-slate-700">{{ t('entry.to') }}</span>

                    <span
                        v-if="isRunning"
                        class="flex items-center gap-2 rounded-md border border-dashed border-slate-300 px-3 py-2 text-sm text-slate-500"
                    >
                        <span class="size-2 shrink-0 animate-pulse rounded-full bg-red-600"></span>
                        {{ t('entry.running') }}
                    </span>

                    <input
                        v-else
                        v-model="endsAt"
                        type="datetime-local"
                        class="rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500"
                    >
                </label>
            </div>

            <p
                v-if="validationError"
                class="text-sm text-red-600"
            >
                {{ validationError }}
            </p>
        </div>

        <template #footer>
            <div class="flex w-full items-center justify-between">
                <Button
                    v-if="entry?.id"
                    :label="t('common.delete')"
                    severity="danger"
                    text
                    @click="$emit('remove', entry.id)"
                />

                <span v-else></span>

                <div class="flex gap-2">
                    <Button
                        :label="t('common.cancel')"
                        severity="secondary"
                        text
                        @click="$emit('close')"
                    />

                    <Button
                        :label="t('common.save')"
                        @click="save"
                    />
                </div>
            </div>
        </template>
    </Dialog>
</template>

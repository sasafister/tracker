<script setup>
import { onBeforeUnmount, ref } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import { searchJiraIssues } from '../api.js';
import { pastDescriptions, projectsById, state } from '../store.js';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    // A project linked to Jira: its tickets are offered after past entries.
    jiraProjectId: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'pick', 'pick-ticket', 'enter']);

const suggestions = ref([]);
const overlayOpen = ref(false);

// Jira answers slower than the local list and costs a request, so it waits for
// a pause in typing and only the latest search may fill the list.
const JIRA_DELAY = 300;
const JIRA_MIN_LENGTH = 2;
let jiraTimer = null;
let latestSearch = 0;

function pastMatches(query) {
    return pastDescriptions.value
        .filter((item) => item.description.toLowerCase().includes(query))
        .slice(0, 10)
        .map((item) => ({ ...item, kind: 'past' }));
}

/**
 * Past entries show at once. Jira's tickets follow when they arrive, minus
 * any a past entry in the list already carries.
 */
function search(event) {
    const query = event.query.trim();
    const past = pastMatches(query.toLowerCase());
    const searchId = ++latestSearch;
    const askJira = Boolean(props.jiraProjectId) && query.length >= JIRA_MIN_LENGTH;

    clearTimeout(jiraTimer);

    // An empty list would flash "no results" before Jira answers, so with
    // nothing local the list waits for Jira instead.
    if (past.length || ! askJira) {
        suggestions.value = past;
    }

    if (! askJira) {
        return;
    }

    jiraTimer = setTimeout(async () => {
        try {
            const issues = await searchJiraIssues(props.jiraProjectId, query);

            if (searchId !== latestSearch) {
                return;
            }

            const known = new Set(
                past.flatMap((item) => (item.jira_issues ?? []).map((issue) => issue.key)),
            );

            suggestions.value = [
                ...past,
                ...issues
                    .filter((issue) => ! known.has(issue.key))
                    .map((issue) => ({ ...issue, kind: 'jira' })),
            ];
        } catch (error) {
            if (searchId === latestSearch) {
                suggestions.value = past;
            }

            state.error = error.message;
        }
    }, JIRA_DELAY);
}

function optionText(option) {
    return option.kind === 'jira'
        ? `${option.key} ${option.summary ?? ''}`.trim()
        : option.description;
}

/**
 * With object suggestions the model becomes the picked object; the parent
 * only ever deals in the text.
 */
function update(value) {
    const text = typeof value === 'string' ? value : (value ? optionText(value) : '');

    emit('update:modelValue', text);
}

function pick(event) {
    if (event.value.kind === 'jira') {
        emit('pick-ticket', {
            key: event.value.key,
            summary: event.value.summary,
        });

        return;
    }

    emit('pick', event.value);
}

// Enter while the list is open picks a suggestion; only a closed list
// passes it on.
function enter() {
    if (! overlayOpen.value) {
        emit('enter');
    }
}

onBeforeUnmount(() => clearTimeout(jiraTimer));
</script>

<template>
    <AutoComplete
        :model-value="modelValue"
        :suggestions="suggestions"
        :option-label="optionText"
        :placeholder="placeholder"
        :autofocus="autofocus"
        :delay="0"
        fluid
        @update:model-value="update"
        @complete="search"
        @option-select="pick"
        @show="overlayOpen = true"
        @hide="overlayOpen = false"
        @keydown.enter="enter"
    >
        <template #option="{ option }">
            <div
                v-if="option.kind === 'jira'"
                class="flex w-full min-w-0 items-baseline gap-2"
                :title="optionText(option)"
            >
                <span class="shrink-0 font-mono text-xs font-semibold">{{ option.key }}</span>
                <span class="truncate text-sm">{{ option.summary }}</span>
                <span class="ml-auto shrink-0 rounded bg-slate-100 px-1.5 text-xs text-slate-500">
                    Jira
                </span>
            </div>

            <div
                v-else
                class="flex w-full items-center justify-between gap-3"
            >
                <span class="truncate">{{ option.description }}</span>

                <span
                    v-if="option.project_id && projectsById.get(option.project_id)"
                    class="flex shrink-0 items-center gap-1.5 text-xs text-slate-500"
                >
                    <span
                        class="size-2 rounded-full"
                        :style="{ backgroundColor: projectsById.get(option.project_id).color }"
                    ></span>
                    {{ projectsById.get(option.project_id).name }}
                </span>
            </div>
        </template>
    </AutoComplete>
</template>

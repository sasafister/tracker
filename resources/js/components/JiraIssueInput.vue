<script setup>
import { ref, watch } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import { searchJiraIssues } from '../api.js';
import { t } from '../i18n.js';
import { state } from '../store.js';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
    modelValue: {
        type: String,
        default: null,
    },
    summary: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'pick']);

const root = ref(null);
const text = ref(label(props.modelValue, props.summary));
const suggestions = ref([]);
const listMaxWidth = ref(null);

/**
 * "FUR-800 · Connect admin branding…": the key and the title, cut short by
 * the input's ellipsis when it does not fit.
 */
function label(key, summary) {
    if (! key) {
        return '';
    }

    return summary ? `${key} · ${summary}` : key;
}

/**
 * Asks the server, which asks Jira. The dropdown button searches with an
 * empty query and gets the project's latest open tickets.
 */
async function search(event) {
    measureList();

    try {
        suggestions.value = await searchJiraIssues(props.projectId, event.query.trim());
    } catch (error) {
        suggestions.value = [];
        state.error = error.message;
    }
}

/**
 * The list grows with the titles up to the width set in app.css. Inside a
 * dialog it also stops at the dialog's right edge. Measured before the list
 * opens — the list starts under the field — so it never paints wider first.
 */
function measureList() {
    const field = root.value?.$el;
    const dialog = field?.closest('.p-dialog');

    if (! field || ! dialog) {
        listMaxWidth.value = null;

        return;
    }

    const right = dialog.getBoundingClientRect().right - 16;

    listMaxWidth.value = `${Math.round(right - field.getBoundingClientRect().left)}px`;
}

function pick(event) {
    text.value = label(event.value.key, event.value.summary);
    emit('update:modelValue', event.value.key);
    emit('pick', event.value);
}

/**
 * Clearing the field drops the ticket; typing alone does not pick one.
 */
function update(value) {
    if (typeof value !== 'string') {
        return;
    }

    text.value = value;

    if (value.trim() === '') {
        emit('update:modelValue', null);
    }
}

watch(
    () => [props.modelValue, props.summary],
    ([key, summary]) => {
        text.value = label(key, summary);
    },
);
</script>

<template>
    <AutoComplete
        ref="root"
        :model-value="text"
        :suggestions="suggestions"
        option-label="key"
        :placeholder="t('jira.search')"
        :empty-search-message="t('jira.noResults')"
        :delay="300"
        input-class="truncate"
        overlay-class="jira-issue-list"
        :pt="{ overlay: { style: { maxWidth: listMaxWidth } } }"
        :title="text"
        dropdown
        fluid
        @update:model-value="update"
        @complete="search"
        @option-select="pick"
    >
        <template #option="{ option }">
            <span
                class="flex min-w-0 items-baseline gap-2"
                :title="`${option.key} · ${option.summary}`"
            >
                <span class="shrink-0 font-mono text-xs font-semibold">{{ option.key }}</span>
                <span class="truncate text-sm">{{ option.summary }}</span>
            </span>
        </template>
    </AutoComplete>
</template>

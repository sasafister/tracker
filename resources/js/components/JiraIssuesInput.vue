<script setup>
import { ref } from 'vue';
import JiraIssueInput from './JiraIssueInput.vue';
import { t } from '../i18n.js';

const props = defineProps({
    projectId: {
        type: Number,
        required: true,
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
    // The timer bar has a fixed height, so there the chips sit beside the
    // picker instead of in a row above it.
    inline: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'pick']);
const pickerInstance = ref(0);

function addIssue(issue) {
    if (props.modelValue.some((selected) => selected.key === issue.key)) {
        pickerInstance.value++;

        return;
    }

    emit('update:modelValue', [...props.modelValue, {
        key: issue.key,
        summary: issue.summary,
    }]);
    emit('pick', issue);
    pickerInstance.value++;
}

function removeIssue(key) {
    emit('update:modelValue', props.modelValue.filter((issue) => issue.key !== key));
}
</script>

<template>
    <div
        class="flex min-w-0 gap-1"
        :class="inline ? 'flex-row items-center' : 'flex-col'"
    >
        <div
            v-if="modelValue.length"
            class="flex gap-1"
            :class="inline ? 'min-w-0 shrink overflow-hidden' : 'flex-wrap'"
        >
            <span
                v-for="issue in modelValue"
                :key="issue.key"
                class="inline-flex min-w-0 items-center gap-1 rounded border border-slate-200 bg-slate-50 py-0.5 pl-2 pr-1 font-mono text-xs text-slate-700"
                :title="issue.summary ? `${issue.key} · ${issue.summary}` : issue.key"
            >
                <span class="truncate">{{ issue.key }}</span>
                <button
                    type="button"
                    class="flex size-5 shrink-0 items-center justify-center rounded text-slate-500 hover:bg-slate-200 hover:text-slate-900"
                    :aria-label="`${t('common.delete')} ${issue.key}`"
                    :title="t('common.delete')"
                    @click="removeIssue(issue.key)"
                >
                    ×
                </button>
            </span>
        </div>

        <div :class="inline ? 'min-w-28 flex-1' : ''">
            <JiraIssueInput
                :key="pickerInstance"
                :project-id="projectId"
                @pick="addIssue"
            />
        </div>
    </div>
</template>
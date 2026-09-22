<script setup>
import { computed, ref, watch } from 'vue';
import DescriptionInput from './DescriptionInput.vue';
import ProjectSelect from './ProjectSelect.vue';
import { editEntry, runningEntry, startTimer, state, stopTimer } from '../store.js';
import { currencySign } from '../currencies.js';
import { t } from '../i18n.js';
import { entryDuration, formatDuration } from '../time.js';

const description = ref('');
const projectId = ref(null);
const billable = ref(true);

const isRunning = computed(() => runningEntry.value !== null);

const sign = computed(() => currencySign(state.settings.currency));

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

function selectProject(id) {
    projectId.value = id;
    changeRunning({ project_id: id });
}

function toggleBillable() {
    billable.value = ! billable.value;
    changeRunning({ billable: billable.value });
}

function pickDescription(suggestion) {
    projectId.value = suggestion.project_id;
    billable.value = suggestion.billable;

    changeRunning({
        description: suggestion.description,
        project_id: suggestion.project_id,
        billable: suggestion.billable,
    });
}

// While something runs, the fields show what it is rather than a stale draft.
watch(runningEntry, (entry) => {
    description.value = entry === null ? '' : (entry.description ?? '');
    projectId.value = entry === null ? null : entry.project_id;
    billable.value = entry === null ? true : entry.billable;
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

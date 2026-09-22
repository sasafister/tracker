<script setup>
import { ref } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import { pastDescriptions, projectsById } from '../store.js';

defineProps({
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
});

const emit = defineEmits(['update:modelValue', 'pick', 'enter']);

const suggestions = ref([]);
const overlayOpen = ref(false);

function search(event) {
    const query = event.query.trim().toLowerCase();

    suggestions.value = pastDescriptions.value
        .filter((item) => item.description.toLowerCase().includes(query))
        .slice(0, 10);
}

/**
 * With object suggestions the model becomes the picked object; the parent
 * only ever deals in the text.
 */
function update(value) {
    const text = typeof value === 'string' ? value : (value?.description ?? '');

    emit('update:modelValue', text);
}

function pick(event) {
    emit('pick', event.value);
}

// Enter while the list is open picks a suggestion; only a closed list
// passes it on.
function enter() {
    if (! overlayOpen.value) {
        emit('enter');
    }
}
</script>

<template>
    <AutoComplete
        :model-value="modelValue"
        :suggestions="suggestions"
        option-label="description"
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
            <div class="flex w-full items-center justify-between gap-3">
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

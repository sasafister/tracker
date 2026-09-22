<script setup>
import Select from 'primevue/select';
import { t } from '../i18n.js';
import { clientFor, projectsById, state } from '../store.js';

defineProps({
    modelValue: {
        type: Number,
        default: null,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <Select
        :model-value="modelValue"
        :options="state.projects"
        option-label="name"
        option-value="id"
        :placeholder="t('common.noProject')"
        show-clear
        filter
        fluid
        @update:model-value="$emit('update:modelValue', $event ?? null)"
    >
        <template #value="{ value, placeholder }">
            <span
                v-if="value && projectsById.get(value)"
                class="flex items-center gap-2"
            >
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: projectsById.get(value).color }"
                ></span>
                <span class="truncate">{{ projectsById.get(value).name }}</span>
            </span>

            <span
                v-else
                class="text-slate-400"
            >
                {{ placeholder }}
            </span>
        </template>

        <template #option="{ option }">
            <span class="flex min-w-0 items-center gap-2">
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: option.color }"
                ></span>
                <span class="truncate">{{ option.name }}</span>

                <span
                    v-if="clientFor(option)"
                    class="truncate text-xs text-slate-400"
                >
                    {{ clientFor(option).name }}
                </span>
            </span>
        </template>
    </Select>
</template>

<script setup>
import AppLogo from './AppLogo.vue';

defineProps({
    items: {
        type: Array,
        required: true,
    },
    active: {
        type: String,
        required: true,
    },
});

defineEmits(['select']);
</script>

<template>
    <aside class="hidden w-56 shrink-0 flex-col border-r border-slate-200 bg-white md:flex">
        <div class="flex h-16 items-center gap-2.5 border-b border-slate-200 px-5">
            <AppLogo class="size-8" />
            <span class="text-lg font-semibold tracking-tight">Timer</span>
        </div>

        <nav class="flex flex-col gap-1 p-3">
            <button
                v-for="item in items"
                :key="item.key"
                type="button"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium transition"
                :class="item.key === active
                    ? 'bg-slate-900 text-white'
                    : 'text-slate-600 hover:bg-slate-100'"
                @click="$emit('select', item.key)"
            >
                <span>{{ item.label }}</span>
            </button>
        </nav>
    </aside>
</template>

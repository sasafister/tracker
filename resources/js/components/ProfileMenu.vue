<script setup>
import { computed, ref } from 'vue';
import Menu from 'primevue/menu';
import { t } from '../i18n.js';
import { logout, state } from '../store.js';

const emit = defineEmits(['settings']);

const menu = ref(null);

const initials = computed(() => {
    const parts = state.settings.name.trim().split(/\s+/).filter(Boolean);

    return parts
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('') || '?';
});

const items = computed(() => [
    {
        label: t('profile.settings'),
        command: () => emit('settings'),
    },
    {
        separator: true,
    },
    {
        label: t('profile.logout'),
        command: logout,
    },
]);

function toggle(event) {
    menu.value.toggle(event);
}
</script>

<template>
    <button
        type="button"
        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-fuchsia-100 text-sm font-semibold text-fuchsia-800 ring-offset-2 transition hover:ring-2 hover:ring-fuchsia-200"
        aria-haspopup="true"
        aria-controls="profile-menu"
        :title="state.settings.name"
        @click="toggle"
    >
        {{ initials }}
    </button>

    <Menu
        id="profile-menu"
        ref="menu"
        :model="items"
        popup
    >
        <template #start>
            <div class="border-b border-slate-100 px-4 py-3">
                <div class="truncate text-sm font-semibold">{{ state.settings.name }}</div>
                <div class="truncate text-xs text-slate-500">{{ state.settings.email }}</div>
            </div>
        </template>
    </Menu>
</template>

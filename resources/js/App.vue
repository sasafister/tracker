<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePrimeVue } from 'primevue/config';
import ConfirmDialog from 'primevue/confirmdialog';
import AppSidebar from './components/AppSidebar.vue';
import ProfileMenu from './components/ProfileMenu.vue';
import TimerBar from './components/TimerBar.vue';
import CalendarView from './views/CalendarView.vue';
import EntriesView from './views/EntriesView.vue';
import ProjectsView from './views/ProjectsView.vue';
import SettingsView from './views/SettingsView.vue';
import {
    loadClients,
    loadEntries,
    loadJiraConnections,
    loadProjects,
    loadSettings,
    startClock,
    state,
} from './store.js';
import { locale, primeVueTexts, t } from './i18n.js';
import { useTabTitle } from './tabTitle.js';

// A computed so the labels follow a change of language.
const navigation = computed(() => [
    {
        key: 'calendar',
        label: t('nav.timer'),
    },
    {
        key: 'entries',
        label: t('nav.entries'),
    },
    {
        key: 'projects',
        label: t('nav.projects'),
    },
]);

const views = {
    calendar: CalendarView,
    entries: EntriesView,
    projects: ProjectsView,
    settings: SettingsView,
};

const activeView = ref('calendar');
const stopClock = ref(null);

const currentView = computed(() => views[activeView.value]);

useTabTitle();

const primevue = usePrimeVue();

// PrimeVue's own texts follow the chosen language too.
watch(
    locale,
    (code) => {
        Object.assign(primevue.config.locale, primeVueTexts[code]);
    },
    { immediate: true },
);

function selectView(key) {
    activeView.value = key;
}

function dismissError() {
    state.error = null;
}

onMounted(async () => {
    stopClock.value = startClock();

    await Promise.all([
        loadSettings(),
        loadClients(),
        loadJiraConnections(),
        loadProjects(),
        loadEntries(),
    ]);
});

onUnmounted(() => {
    if (stopClock.value) {
        stopClock.value();
    }
});
</script>

<template>
    <div class="flex h-full">
        <!-- The one confirmation popup every view asks through useConfirm(). -->
        <ConfirmDialog
            :style="{ width: '24rem' }"
            :breakpoints="{ '640px': 'calc(100vw - 2rem)' }"
        />

        <AppSidebar
            :items="navigation"
            :active="activeView"
            @select="selectView"
        />

        <main class="flex min-w-0 flex-1 flex-col">
            <TimerBar>
                <ProfileMenu @settings="selectView('settings')" />
            </TimerBar>

            <div
                v-if="state.error"
                class="mx-4 mt-4 flex md:mx-6 items-start justify-between gap-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <span>{{ state.error }}</span>

                <button
                    type="button"
                    class="shrink-0 font-medium underline"
                    @click="dismissError"
                >
                    {{ t('common.close') }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto p-4 md:p-6">
                <component :is="currentView" />
            </div>

            <!-- Phones get the navigation as a tab bar instead of the sidebar. -->
            <nav class="flex shrink-0 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden">
                <button
                    v-for="item in navigation"
                    :key="item.key"
                    type="button"
                    class="flex-1 py-3 text-sm font-medium transition"
                    :class="item.key === activeView
                        ? 'text-slate-900'
                        : 'text-slate-400'"
                    @click="selectView(item.key)"
                >
                    <span
                        class="border-b-2 pb-1"
                        :class="item.key === activeView ? 'border-slate-900' : 'border-transparent'"
                    >
                        {{ item.label }}
                    </span>
                </button>
            </nav>
        </main>
    </div>
</template>

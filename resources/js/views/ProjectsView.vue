<script setup>
import { computed, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirm } from 'primevue/useconfirm';
import {
    removeClient,
    removeProject,
    saveClient,
    saveProject,
    state,
    totalSeconds,
} from '../store.js';
import { currencySymbol } from '../currencies.js';
import { intlLocale, t } from '../i18n.js';
import { formatHours, formatMoney } from '../time.js';

/**
 * Muted tones only, so the calendar stays calm; the first three are plain
 * black, grey and light grey.
 */
const palette = [
    '#18181b',
    '#52525b',
    '#a1a1aa',
    '#78716c',
    '#64748b',
    '#6b7a5e',
    '#8a6f5a',
    '#6f6488',
];

const confirm = useConfirm();

const projectDialogVisible = ref(false);
const projectForm = reactive(blankProject());
const projectError = ref(null);

const clientDialogVisible = ref(false);
const clientForm = reactive(blankClient());
const clientError = ref(null);

const attachMenu = ref(null);
const attachClient = ref(null);

const isEditingProject = computed(() => projectForm.id !== null);

const isEditingClient = computed(() => clientForm.id !== null);

const defaultRate = computed(() => {
    return formatMoney(Number(state.settings.hourly_rate), state.settings.currency);
});

/**
 * My own hours on each project, from the entries already loaded.
 */
const secondsByProject = computed(() => {
    const totals = new Map();

    state.projects.forEach((project) => {
        const entries = state.entries.filter((entry) => entry.project_id === project.id);

        totals.set(project.id, totalSeconds(entries, state.now));
    });

    return totals;
});

/**
 * One group per client, alphabetical, and the projects without a client
 * last. A client with no projects still gets its group, so it can get one.
 */
const groups = computed(() => {
    const clientGroups = state.clients.map((client) => ({
        key: `client-${client.id}`,
        client,
        projects: state.projects.filter((project) => project.client_id === client.id),
    }));

    const unassigned = state.projects.filter((project) => project.client_id === null);

    if (unassigned.length > 0) {
        clientGroups.push({
            key: 'no-client',
            client: null,
            projects: unassigned,
        });
    }

    return clientGroups;
});

const clientOptions = computed(() => {
    return state.clients.map((client) => ({
        label: client.name,
        value: client.id,
    }));
});

function blankProject() {
    return {
        id: null,
        client_id: null,
        name: '',
        color: palette[0],
        hourly_rate: null,
        jira_connection_id: null,
        jira_project_key: '',
        jira_only_mine: false,
    };
}

const jiraOptions = computed(() => {
    return state.jiraConnections.map((connection) => ({
        label: `${connection.name} · ${connection.site}`,
        value: connection.id,
    }));
});

function blankClient() {
    return {
        id: null,
        name: '',
        address: '',
        tax_id: '',
    };
}

function openNewProject(clientId = null) {
    Object.assign(projectForm, blankProject(), {
        client_id: clientId,
    });
    projectError.value = null;
    projectDialogVisible.value = true;
}

function openEditProject(project) {
    Object.assign(projectForm, {
        id: project.id,
        client_id: project.client_id,
        name: project.name,
        color: project.color,
        hourly_rate: project.hourly_rate === null ? null : Number(project.hourly_rate),
        jira_connection_id: project.jira_connection_id,
        jira_project_key: project.jira_project_key ?? '',
        jira_only_mine: project.jira_only_mine,
    });
    projectError.value = null;
    projectDialogVisible.value = true;
}

async function submitProject() {
    const name = projectForm.name.trim();

    if (! name) {
        projectError.value = t('projects.nameRequired');

        return;
    }

    const saved = await saveProject({
        ...projectForm,
        name,
    });

    if (saved) {
        projectDialogVisible.value = false;
    }
}

function confirmRemoveProject(project) {
    confirm.require({
        header: t('projects.deleteTitle', { name: project.name }),
        message: t('projects.deleteMessage'),
        rejectProps: {
            label: t('common.cancel'),
            severity: 'secondary',
            text: true,
        },
        acceptProps: {
            label: t('projects.deleteConfirm'),
        },
        accept: () => removeProject(project.id),
    });
}

/**
 * What "+ Projekt" on a client offers: projects without a client first,
 * then those of other clients (moved over), then a brand new one. Projects
 * are often set up before anyone knows who they are for.
 */
const attachItems = computed(() => {
    const client = attachClient.value;

    if (client === null) {
        return [];
    }

    const attach = (project) => {
        saveProject({
            ...project,
            client_id: client.id,
        });
    };

    const unassigned = state.projects
        .filter((project) => project.client_id === null)
        .map((project) => ({
            label: project.name,
            color: project.color,
            command: () => attach(project),
        }));

    const elsewhere = state.projects
        .filter((project) => project.client_id !== null && project.client_id !== client.id)
        .map((project) => ({
            label: project.name,
            color: project.color,
            note: t('projects.moveFrom', {
                name: state.clients.find((other) => other.id === project.client_id)?.name ?? '',
            }),
            command: () => attach(project),
        }));

    const items = [];

    if (unassigned.length > 0) {
        items.push({
            label: t('projects.unassigned'),
            items: unassigned,
        });
    }

    if (elsewhere.length > 0) {
        items.push({
            label: t('projects.elsewhere'),
            items: elsewhere,
        });
    }

    if (items.length > 0) {
        items.push({
            separator: true,
        });
    }

    items.push({
        label: t('projects.newProjectMenu'),
        command: () => openNewProject(client.id),
    });

    return items;
});

function toggleAttachMenu(event, client) {
    attachClient.value = client;
    attachMenu.value.toggle(event);
}

function openNewClient() {
    Object.assign(clientForm, blankClient());
    clientError.value = null;
    clientDialogVisible.value = true;
}

function openEditClient(client) {
    Object.assign(clientForm, {
        id: client.id,
        name: client.name,
        address: client.address ?? '',
        tax_id: client.tax_id ?? '',
    });
    clientError.value = null;
    clientDialogVisible.value = true;
}

async function submitClient() {
    const name = clientForm.name.trim();

    if (! name) {
        clientError.value = t('projects.clientNameRequired');

        return;
    }

    const saved = await saveClient({
        id: clientForm.id,
        name,
        address: clientForm.address.trim() || null,
        tax_id: clientForm.tax_id.trim() || null,
    });

    if (saved) {
        clientDialogVisible.value = false;
    }
}

function confirmRemoveClient(client) {
    confirm.require({
        header: t('projects.deleteClientTitle', { name: client.name }),
        message: t('projects.deleteClientMessage'),
        rejectProps: {
            label: t('common.cancel'),
            severity: 'secondary',
            text: true,
        },
        acceptProps: {
            label: t('projects.deleteClientConfirm'),
        },
        accept: () => removeClient(client.id),
    });
}

function rateLabel(project) {
    if (project.hourly_rate === null) {
        return `${defaultRate.value}/h`;
    }

    return `${formatMoney(Number(project.hourly_rate), state.settings.currency)}/h`;
}

/**
 * Eleven digits are an OIB; anything with a country code is a VAT ID.
 */
function taxIdLabel(taxId) {
    return /^\d{11}$/.test(taxId) ? 'OIB' : 'VAT ID';
}

function clientDetails(client) {
    const parts = [
        client.address?.replace(/\n/g, ', '),
        client.tax_id ? `${taxIdLabel(client.tax_id)} ${client.tax_id}` : null,
    ];

    return parts.filter(Boolean).join(' · ');
}
</script>

<template>
    <div class="flex max-w-4xl flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-lg font-semibold">{{ t('projects.title') }}</h1>

                <p class="mt-1 text-sm text-zinc-500">
                    {{ t('projects.intro', { rate: defaultRate }) }}
                </p>
            </div>

            <div class="flex gap-2">
                <Button
                    :label="t('projects.newClient')"
                    severity="secondary"
                    outlined
                    @click="openNewClient"
                />

                <Button
                    :label="t('projects.newProject')"
                    @click="openNewProject()"
                />
            </div>
        </div>

        <p
            v-if="groups.length === 0"
            class="rounded-lg border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500"
        >
            {{ t('projects.empty') }}
        </p>

        <section
            v-for="group in groups"
            :key="group.key"
            class="overflow-hidden rounded-lg border border-zinc-200 bg-white"
        >
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3">
                <div class="min-w-0">
                    <h2 class="truncate font-semibold">
                        {{ group.client ? group.client.name : t('projects.noClient') }}
                    </h2>

                    <p
                        v-if="group.client && clientDetails(group.client)"
                        class="truncate text-xs text-zinc-500"
                    >
                        {{ clientDetails(group.client) }}
                    </p>
                </div>

                <div
                    v-if="group.client"
                    class="flex shrink-0 text-sm"
                >
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-zinc-500 hover:text-zinc-900"
                        aria-haspopup="true"
                        @click="toggleAttachMenu($event, group.client)"
                    >
                        {{ t('projects.addProject') }}
                    </button>

                    <button
                        type="button"
                        class="rounded px-2 py-1 text-zinc-500 hover:text-zinc-900"
                        @click="openEditClient(group.client)"
                    >
                        {{ t('common.edit') }}
                    </button>

                    <button
                        type="button"
                        class="rounded px-2 py-1 text-zinc-400 hover:text-zinc-900"
                        @click="confirmRemoveClient(group.client)"
                    >
                        {{ t('common.delete') }}
                    </button>
                </div>
            </header>

            <p
                v-if="group.projects.length === 0"
                class="px-4 py-4 text-sm text-zinc-500"
            >
                {{ t('projects.noProjects') }}

                <button
                    type="button"
                    class="font-medium text-zinc-900 underline"
                    aria-haspopup="true"
                    @click="toggleAttachMenu($event, group.client)"
                >
                    {{ t('projects.addFirst') }}
                </button>
            </p>

            <table
                v-else
                class="w-full text-sm"
            >
                <thead>
                    <tr class="border-b border-zinc-100 text-left text-xs uppercase tracking-wide text-zinc-500">
                        <th class="px-4 py-2 font-medium">{{ t('projects.colProject') }}</th>
                        <th class="px-4 py-2 font-medium">{{ t('projects.colRate') }}</th>
                        <th class="hidden px-4 py-2 text-right font-medium sm:table-cell">{{ t('projects.colHours') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-100">
                    <tr
                        v-for="project in group.projects"
                        :key="project.id"
                        class="cursor-pointer hover:bg-zinc-50"
                        @click="openEditProject(project)"
                    >
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-2.5 font-medium">
                                <span
                                    class="size-2.5 shrink-0 rounded-full"
                                    :style="{ backgroundColor: project.color }"
                                ></span>
                                {{ project.name }}

                                <span
                                    v-if="project.jira_connection_id"
                                    class="rounded border border-zinc-200 px-1.5 py-0.5 font-mono text-[11px] font-medium text-zinc-500"
                                >
                                    Jira{{ project.jira_project_key ? ` · ${project.jira_project_key}` : '' }}
                                </span>
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span :class="project.hourly_rate === null ? 'text-zinc-400' : ''">
                                {{ rateLabel(project) }}
                            </span>

                            <span
                                v-if="project.hourly_rate === null"
                                class="ml-1 hidden text-xs text-zinc-400 sm:inline"
                            >
                                {{ t('projects.default') }}
                            </span>
                        </td>

                        <td class="hidden px-4 py-3 text-right tabular-nums text-zinc-600 sm:table-cell">
                            {{ formatHours(secondsByProject.get(project.id) ?? 0) }}
                        </td>

                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button
                                type="button"
                                class="rounded px-2 py-1 text-zinc-500 hover:text-zinc-900"
                                @click.stop="openEditProject(project)"
                            >
                                {{ t('common.edit') }}
                            </button>

                            <button
                                type="button"
                                class="rounded px-2 py-1 text-zinc-400 hover:text-zinc-900"
                                @click.stop="confirmRemoveProject(project)"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <Menu
            ref="attachMenu"
            :model="attachItems"
            popup
            class="min-w-60"
        >
            <template #item="{ item, props }">
                <a
                    v-bind="props.action"
                    class="flex items-center gap-2"
                >
                    <span
                        v-if="item.color"
                        class="size-2.5 shrink-0 rounded-full"
                        :style="{ backgroundColor: item.color }"
                    ></span>

                    <span class="truncate">{{ item.label }}</span>

                    <span
                        v-if="item.note"
                        class="ml-auto truncate pl-2 text-xs text-zinc-400"
                    >
                        {{ item.note }}
                    </span>
                </a>
            </template>
        </Menu>

        <Dialog
            v-model:visible="projectDialogVisible"
            modal
            :header="isEditingProject ? t('projects.editProject') : t('projects.newProject')"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': 'calc(100vw - 2rem)' }"
        >
            <form
                id="project-form"
                class="flex flex-col gap-5"
                @submit.prevent="submitProject"
            >
                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.client') }}</span>

                    <Select
                        v-model="projectForm.client_id"
                        :options="clientOptions"
                        option-label="label"
                        option-value="value"
                        :placeholder="t('projects.noClient')"
                        show-clear
                        fluid
                    />
                </label>

                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.name') }}</span>

                    <InputText
                        v-model="projectForm.name"
                        :placeholder="t('projects.namePlaceholder')"
                        autofocus
                    />
                </label>

                <div class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.color') }}</span>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in palette"
                            :key="color"
                            type="button"
                            class="flex size-7 items-center justify-center rounded-full ring-offset-2 transition"
                            :class="projectForm.color === color ? 'ring-2 ring-zinc-900' : 'hover:ring-2 hover:ring-zinc-200'"
                            :style="{ backgroundColor: color }"
                            :aria-label="t('projects.colorLabel', { color })"
                            :aria-pressed="projectForm.color === color"
                            @click="projectForm.color = color"
                        ></button>
                    </div>
                </div>

                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.rate') }}</span>

                    <InputNumber
                        v-model="projectForm.hourly_rate"
                        :min="0"
                        :max-fraction-digits="2"
                        :suffix="` ${currencySymbol(state.settings.currency)}/h`"
                        :placeholder="t('projects.ratePlaceholder', { rate: defaultRate })"
                        :locale="intlLocale()"
                        fluid
                    />

                    <span class="text-xs text-zinc-500">
                        {{ t('projects.rateHint') }}
                    </span>
                </label>

                <!-- Jira: which site to pick tickets from, narrowed to one project. -->
                <div class="flex flex-col gap-3 border-t border-zinc-100 pt-5 text-sm">
                    <p
                        v-if="jiraOptions.length === 0"
                        class="text-xs text-zinc-500"
                    >
                        {{ t('jira.addConnectionFirst') }}
                    </p>

                    <template v-else>
                        <label class="flex flex-col gap-1.5">
                            <span class="font-medium text-zinc-700">{{ t('jira.projectConnection') }}</span>

                            <Select
                                v-model="projectForm.jira_connection_id"
                                :options="jiraOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="t('jira.noConnection')"
                                show-clear
                                fluid
                            />
                        </label>

                        <template v-if="projectForm.jira_connection_id">
                            <label class="flex flex-col gap-1.5">
                                <span class="font-medium text-zinc-700">{{ t('jira.projectKey') }}</span>

                                <InputText
                                    v-model="projectForm.jira_project_key"
                                    :placeholder="t('jira.projectKeyPlaceholder')"
                                    class="uppercase"
                                    maxlength="32"
                                    @update:model-value="projectForm.jira_project_key = ($event ?? '').toUpperCase()"
                                />
                            </label>

                            <label class="flex items-center gap-3">
                                <ToggleSwitch v-model="projectForm.jira_only_mine" />
                                <span class="text-zinc-700">{{ t('jira.onlyMine') }}</span>
                            </label>
                        </template>
                    </template>
                </div>

                <p
                    v-if="projectError"
                    class="text-sm text-red-600"
                >
                    {{ projectError }}
                </p>
            </form>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    severity="secondary"
                    text
                    @click="projectDialogVisible = false"
                />

                <Button
                    type="submit"
                    form="project-form"
                    :label="isEditingProject ? t('common.save') : t('projects.add')"
                />
            </template>
        </Dialog>

        <Dialog
            v-model:visible="clientDialogVisible"
            modal
            :header="isEditingClient ? t('projects.editClient') : t('projects.newClient')"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': 'calc(100vw - 2rem)' }"
        >
            <form
                id="client-form"
                class="flex flex-col gap-5"
                @submit.prevent="submitClient"
            >
                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.name') }}</span>

                    <InputText
                        v-model="clientForm.name"
                        :placeholder="t('projects.clientNamePlaceholder')"
                        autofocus
                    />
                </label>

                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.address') }}</span>

                    <Textarea
                        v-model="clientForm.address"
                        rows="2"
                        auto-resize
                        :placeholder="t('projects.addressPlaceholder')"
                    />
                </label>

                <label class="flex flex-col gap-1.5 text-sm">
                    <span class="font-medium text-zinc-700">{{ t('projects.taxId') }}</span>

                    <InputText
                        v-model="clientForm.tax_id"
                        maxlength="20"
                        :placeholder="t('projects.taxIdPlaceholder')"
                        class="uppercase"
                    />

                    <span class="text-xs text-zinc-500">
                        {{ t('projects.taxIdHint') }}
                    </span>
                </label>

                <p
                    v-if="clientError"
                    class="text-sm text-red-600"
                >
                    {{ clientError }}
                </p>
            </form>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    severity="secondary"
                    text
                    @click="clientDialogVisible = false"
                />

                <Button
                    type="submit"
                    form="client-form"
                    :label="isEditingClient ? t('common.save') : t('projects.addClient')"
                />
            </template>
        </Dialog>
    </div>
</template>

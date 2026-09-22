<script setup>
import { computed, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { useConfirm } from 'primevue/useconfirm';
import { t } from '../i18n.js';
import { removeJiraConnection, saveJiraConnection, state } from '../store.js';

const confirm = useConfirm();

const dialogVisible = ref(false);
const saving = ref(false);
const formError = ref(null);
const form = reactive(blankConnection());

const isEditing = computed(() => form.id !== null);

function blankConnection() {
    return {
        id: null,
        name: '',
        site: '',
        email: state.settings.email ?? '',
        token: '',
    };
}

function openNew() {
    Object.assign(form, blankConnection());
    formError.value = null;
    dialogVisible.value = true;
}

function openEdit(connection) {
    Object.assign(form, {
        id: connection.id,
        name: connection.name,
        site: connection.site,
        email: connection.email,
        token: '',
    });
    formError.value = null;
    dialogVisible.value = true;
}

/**
 * The server checks the token with Jira before saving, so this can take a
 * moment and can fail with Jira's answer, shown in the dialog.
 */
async function submit() {
    saving.value = true;
    formError.value = null;

    try {
        await saveJiraConnection({ ...form });
        dialogVisible.value = false;
    } catch (error) {
        formError.value = error.message;
    } finally {
        saving.value = false;
    }
}

function confirmRemove(connection) {
    confirm.require({
        header: t('jira.deleteTitle', { name: connection.name }),
        message: t('jira.deleteMessage'),
        rejectProps: {
            label: t('common.cancel'),
            severity: 'secondary',
            text: true,
        },
        acceptProps: {
            label: t('jira.deleteConfirm'),
        },
        accept: () => removeJiraConnection(connection.id),
    });
}
</script>

<template>
    <section class="rounded-lg border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold">{{ t('jira.connections') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ t('jira.connectionsHint') }}</p>
            </div>

            <Button
                :label="t('jira.add')"
                severity="secondary"
                outlined
                size="small"
                class="shrink-0"
                @click="openNew"
            />
        </div>

        <p
            v-if="state.jiraConnections.length === 0"
            class="mt-4 text-sm text-slate-400"
        >
            {{ t('jira.none') }}
        </p>

        <ul
            v-else
            class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200"
        >
            <li
                v-for="connection in state.jiraConnections"
                :key="connection.id"
                class="flex items-center gap-3 px-4 py-3 text-sm"
            >
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium">{{ connection.name }}</div>
                    <div class="truncate text-xs text-slate-500">
                        {{ connection.site }}
                        <template v-if="connection.account_name">
                            · {{ t('jira.connectedAs', { name: connection.account_name }) }}
                        </template>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded px-2 py-1 text-slate-500 hover:text-slate-900"
                    @click="openEdit(connection)"
                >
                    {{ t('common.edit') }}
                </button>

                <button
                    type="button"
                    class="rounded px-2 py-1 text-slate-400 hover:text-slate-900"
                    @click="confirmRemove(connection)"
                >
                    {{ t('common.delete') }}
                </button>
            </li>
        </ul>

        <Dialog
            v-model:visible="dialogVisible"
            modal
            :header="isEditing ? t('jira.editConnection') : t('jira.newConnection')"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': 'calc(100vw - 2rem)' }"
        >
            <form
                id="jira-connection-form"
                class="flex flex-col gap-4 text-sm"
                @submit.prevent="submit"
            >
                <label class="flex flex-col gap-1.5">
                    <span class="font-medium text-slate-700">{{ t('jira.name') }}</span>
                    <InputText
                        v-model="form.name"
                        :placeholder="t('jira.namePlaceholder')"
                        autofocus
                    />
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="font-medium text-slate-700">{{ t('jira.site') }}</span>
                    <InputText
                        v-model="form.site"
                        :placeholder="t('jira.sitePlaceholder')"
                        autocomplete="url"
                    />
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="font-medium text-slate-700">{{ t('jira.email') }}</span>
                    <InputText
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                    />
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="font-medium text-slate-700">{{ t('jira.token') }}</span>
                    <Password
                        v-model="form.token"
                        :feedback="false"
                        :placeholder="isEditing ? t('jira.tokenKeep') : ''"
                        autocomplete="off"
                        toggle-mask
                        fluid
                    />
                    <span class="text-xs text-slate-500">{{ t('jira.tokenHint') }}</span>
                </label>

                <p
                    v-if="saving"
                    class="text-slate-500"
                >
                    {{ t('jira.checking') }}
                </p>

                <p
                    v-if="formError"
                    class="rounded-md bg-red-50 px-3 py-2 text-red-700"
                >
                    {{ formError }}
                </p>
            </form>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    severity="secondary"
                    text
                    @click="dialogVisible = false"
                />

                <Button
                    type="submit"
                    form="jira-connection-form"
                    :label="t('jira.saveAndCheck')"
                    :loading="saving"
                />
            </template>
        </Dialog>
    </section>
</template>

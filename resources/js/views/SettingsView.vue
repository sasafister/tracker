<script setup>
import { computed, reactive, ref, watch } from 'vue';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import {
    changePassword,
    saveCompany,
    saveSettings,
    state,
    totalAmount,
    totalSeconds,
} from '../store.js';
import { currencyOptions, currencySymbol } from '../currencies.js';
import { intlLocale, languages, locale, t } from '../i18n.js';
import { formatHours, formatMoney } from '../time.js';

const profile = reactive(profileFrom(state.settings));
const company = reactive(companyFrom(state.settings.company));
const passwords = reactive(blankPasswords());
const profileSaved = ref(false);
const companySaved = ref(false);
const passwordSaved = ref(false);

const allTimeSeconds = computed(() => totalSeconds(state.entries, state.now));

const allTimeAmount = computed(() => totalAmount(state.entries, state.now));

// Currency names come from the browser in the language on screen.
const currencyChoices = computed(() => {
    // Read so the list is rebuilt when the language changes.
    locale.value;

    return currencyOptions();
});

const languageChoices = Object.entries(languages).map(([code, language]) => ({
    value: code,
    label: language.name,
}));

function profileFrom(settings) {
    return {
        name: settings.name ?? '',
        email: settings.email ?? '',
        hourly_rate: Number(settings.hourly_rate) || 0,
        currency: settings.currency ?? 'EUR',
        locale: settings.locale ?? 'hr',
    };
}

function companyFrom(saved) {
    return {
        company_name: saved?.company_name ?? '',
        company_address: saved?.company_address ?? '',
        company_tax_id: saved?.company_tax_id ?? '',
        company_iban: saved?.company_iban ?? '',
    };
}

function blankPasswords() {
    return {
        current_password: '',
        password: '',
        password_confirmation: '',
    };
}

async function saveProfile() {
    profileSaved.value = false;

    await saveSettings({
        name: profile.name.trim(),
        email: profile.email.trim(),
        hourly_rate: profile.hourly_rate ?? 0,
        currency: profile.currency || 'EUR',
        locale: profile.locale,
    });

    profileSaved.value = state.error === null;
}

/**
 * Empty fields are sent as null, so clearing one removes it from the report.
 */
async function saveCompanyDetails() {
    companySaved.value = false;

    const payload = Object.fromEntries(
        Object.entries(company).map(([key, value]) => [key, value.trim() || null]),
    );

    companySaved.value = await saveCompany(payload) === true;
}

async function savePassword() {
    passwordSaved.value = false;

    const changed = await changePassword({ ...passwords });

    if (changed) {
        Object.assign(passwords, blankPasswords());
        passwordSaved.value = true;
    }
}

// The settings load after this view may already have been rendered once.
watch(
    () => state.settings,
    (settings) => {
        Object.assign(profile, profileFrom(settings));
        Object.assign(company, companyFrom(settings.company));
    },
    { deep: true },
);
</script>

<template>
    <!-- Two columns from lg up: the account on the left, the business on the right. -->
    <div class="grid max-w-6xl items-start gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-6">
            <form
                class="rounded-lg border border-slate-200 bg-white p-6"
                @submit.prevent="saveProfile"
            >
                <h2 class="text-base font-semibold">{{ t('settings.profile') }}</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.name') }}</span>

                        <InputText
                            v-model="profile.name"
                            autocomplete="name"
                        />
                    </label>

                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.email') }}</span>

                        <InputText
                            v-model="profile.email"
                            type="email"
                            autocomplete="email"
                        />
                    </label>
                </div>

                <label class="mt-4 flex flex-col gap-1 text-sm sm:w-1/2 sm:pr-2">
                    <span class="font-medium text-slate-700">{{ t('settings.language') }}</span>

                    <Select
                        v-model="profile.locale"
                        :options="languageChoices"
                        option-label="label"
                        option-value="value"
                    />
                </label>

                <h3 class="mt-6 text-sm font-semibold">{{ t('settings.defaultRate') }}</h3>

                <p class="mt-1 text-sm text-slate-500">
                    {{ t('settings.defaultRateHint') }}
                </p>

                <div class="mt-4 flex flex-wrap items-end gap-4">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.rate') }}</span>

                        <InputNumber
                            v-model="profile.hourly_rate"
                            :min="0"
                            :max-fraction-digits="2"
                            :min-fraction-digits="2"
                            :suffix="` ${currencySymbol(profile.currency)}/h`"
                            :locale="intlLocale()"
                            input-class="w-36"
                        />
                    </label>

                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.currency') }}</span>

                        <Select
                            v-model="profile.currency"
                            :options="currencyChoices"
                            option-label="label"
                            option-value="value"
                            filter
                            :filter-placeholder="t('settings.searchCurrency')"
                            class="w-56"
                        >
                            <template #option="{ option }">
                                <span class="flex w-full items-center justify-between gap-3">
                                    <span>{{ option.label }}</span>
                                    <span class="text-slate-400">{{ option.symbol }}</span>
                                </span>
                            </template>
                        </Select>
                    </label>

                    <Button
                        type="submit"
                        :label="t('common.save')"
                    />
                </div>

                <p
                    v-if="profileSaved"
                    class="mt-3 text-sm text-emerald-600"
                >
                    {{ t('common.saved') }}
                </p>
            </form>

            <form
                class="rounded-lg border border-slate-200 bg-white p-6"
                @submit.prevent="savePassword"
            >
                <h2 class="text-base font-semibold">{{ t('settings.password') }}</h2>

                <div class="mt-4 flex flex-col gap-4">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.currentPassword') }}</span>

                        <Password
                            v-model="passwords.current_password"
                            :feedback="false"
                            autocomplete="current-password"
                            toggle-mask
                            fluid
                        />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-slate-700">{{ t('settings.newPassword') }}</span>

                            <Password
                                v-model="passwords.password"
                                :feedback="false"
                                autocomplete="new-password"
                                toggle-mask
                                fluid
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-slate-700">{{ t('settings.repeatPassword') }}</span>

                            <Password
                                v-model="passwords.password_confirmation"
                                :feedback="false"
                                autocomplete="new-password"
                                toggle-mask
                                fluid
                            />
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :label="t('settings.changePassword')"
                            severity="secondary"
                        />

                        <span
                            v-if="passwordSaved"
                            class="text-sm text-emerald-600"
                        >
                            {{ t('settings.passwordChanged') }}
                        </span>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex flex-col gap-6">
            <form
                class="rounded-lg border border-slate-200 bg-white p-6"
                @submit.prevent="saveCompanyDetails"
            >
                <h2 class="text-base font-semibold">{{ t('settings.company') }}</h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ t('settings.companyHint') }}
                </p>

                <div class="mt-4 flex flex-col gap-4">
                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.companyName') }}</span>

                        <InputText
                            v-model="company.company_name"
                            :placeholder="t('settings.companyNamePlaceholder')"
                            autocomplete="organization"
                        />
                    </label>

                    <label class="flex flex-col gap-1 text-sm">
                        <span class="font-medium text-slate-700">{{ t('settings.address') }}</span>

                        <Textarea
                            v-model="company.company_address"
                            rows="2"
                            auto-resize
                            :placeholder="t('projects.addressPlaceholder')"
                            autocomplete="street-address"
                        />
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-slate-700">{{ t('projects.taxId') }}</span>

                            <InputText
                                v-model="company.company_tax_id"
                                maxlength="20"
                                :placeholder="t('settings.taxIdPlaceholder')"
                                class="uppercase"
                            />
                        </label>

                        <label class="flex flex-col gap-1 text-sm">
                            <span class="font-medium text-slate-700">IBAN</span>

                            <InputText
                                v-model="company.company_iban"
                                placeholder="HR12 1001 0051 8630 0016 0"
                                class="uppercase"
                            />
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :label="t('settings.saveCompany')"
                        />

                        <span
                            v-if="companySaved"
                            class="text-sm text-emerald-600"
                        >
                            {{ t('common.saved') }}
                        </span>
                    </div>
                </div>
            </form>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-base font-semibold">{{ t('settings.allTime') }}</h2>

                <dl class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">{{ t('settings.time') }}</dt>
                        <dd class="mt-1 text-xl font-semibold">{{ formatHours(allTimeSeconds) }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">{{ t('common.amount') }}</dt>
                        <dd class="mt-1 text-xl font-semibold">
                            {{ formatMoney(allTimeAmount, state.settings.currency) }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</template>

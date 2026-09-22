import { computed, reactive } from 'vue';
import * as api from './api.js';
import { setLocale } from './i18n.js';
import { entryDuration } from './time.js';

/**
 * One shared store for the whole dashboard: the views all read the same
 * entries and projects, and the settings carry the signed-in user.
 */
export const state = reactive({
    entries: [],
    clients: [],
    projects: [],
    jiraConnections: [],
    settings: {
        name: '',
        email: '',
        hourly_rate: '0.00',
        currency: 'EUR',
        locale: 'hr',
        company: {
            company_name: null,
            company_address: null,
            company_tax_id: null,
            company_iban: null,
        },
    },
    now: new Date(),
    error: null,
    loading: false,
});

export const runningEntry = computed(() => {
    return state.entries.find((entry) => entry.ended_at === null) ?? null;
});

export const clientsById = computed(() => {
    return new Map(state.clients.map((client) => [client.id, client]));
});

export function clientFor(project) {
    if (! project || project.client_id === null) {
        return null;
    }

    return clientsById.value.get(project.client_id) ?? null;
}

export const jiraConnectionsById = computed(() => {
    return new Map(state.jiraConnections.map((connection) => [connection.id, connection]));
});

/**
 * The Jira connection a project searches tickets through, if any.
 */
export function jiraConnectionFor(project) {
    if (! project || ! project.jira_connection_id) {
        return null;
    }

    return jiraConnectionsById.value.get(project.jira_connection_id) ?? null;
}

export function jiraIssueUrl(entry) {
    const connection = jiraConnectionFor(projectFor(entry));

    if (! connection || ! entry.jira_issue_key) {
        return null;
    }

    return `https://${connection.site}/browse/${entry.jira_issue_key}`;
}

export const projectsById = computed(() => {
    return new Map(state.projects.map((project) => [project.id, project]));
});

export function projectFor(entry) {
    if (entry.project_id === null) {
        return null;
    }

    return projectsById.value.get(entry.project_id) ?? null;
}

/**
 * Descriptions used before, newest first, each with the project and billable
 * flag it was last logged with — picking one fills in all three, as in Toggl.
 */
export const pastDescriptions = computed(() => {
    const seen = new Map();

    state.entries.forEach((entry) => {
        const description = entry.description?.trim();

        if (! description || seen.has(description)) {
            return;
        }

        seen.set(description, {
            description,
            project_id: entry.project_id,
            billable: entry.billable,
            jira_issue_key: entry.jira_issue_key ?? null,
            jira_issue_summary: entry.jira_issue_summary ?? null,
        });
    });

    return [...seen.values()];
});

/**
 * Each entry carries the rate it was logged at, so money is summed per
 * entry rather than as total hours times one rate. Non-billable time is
 * worth nothing.
 */
export function amountForEntry(entry, now) {
    if (! entry.billable) {
        return 0;
    }

    return (entryDuration(entry, now) / 3600) * (Number(entry.hourly_rate) || 0);
}

export function totalAmount(entries, now) {
    return entries.reduce((sum, entry) => sum + amountForEntry(entry, now), 0);
}

export function totalSeconds(entries, now) {
    return entries.reduce((sum, entry) => sum + entryDuration(entry, now), 0);
}

/**
 * Every call the views make goes through here so a failure lands in one
 * place instead of each component growing its own try/catch.
 */
async function run(action) {
    state.loading = true;
    state.error = null;

    try {
        return await action();
    } catch (error) {
        state.error = error.message;

        return null;
    } finally {
        state.loading = false;
    }
}

export function loadEntries() {
    return run(async () => {
        state.entries = await api.listEntries();
    });
}

export function loadSettings() {
    return run(async () => {
        state.settings = await api.getSettings();
        setLocale(state.settings.locale);
    });
}

export function loadClients() {
    return run(async () => {
        state.clients = await api.listClients();
    });
}

export function saveClient(client) {
    return run(async () => {
        const payload = {
            name: client.name,
            address: client.address,
            tax_id: client.tax_id,
        };

        if (client.id) {
            await api.updateClient(client.id, payload);
        } else {
            await api.createClient(payload);
        }

        state.clients = await api.listClients();

        return true;
    });
}

/**
 * Its projects lose the client on the server, so they are reloaded too.
 */
export function removeClient(id) {
    return run(async () => {
        await api.deleteClient(id);
        state.clients = await api.listClients();
        state.projects = await api.listProjects();
    });
}

export function loadJiraConnections() {
    return run(async () => {
        state.jiraConnections = await api.listJiraConnections();
    });
}

/**
 * Saving checks the token with Jira, so a failure is returned to the form
 * that asked rather than shown in the page banner.
 */
export async function saveJiraConnection(connection) {
    const payload = {
        name: connection.name,
        site: connection.site,
        email: connection.email,
        token: connection.token || null,
    };

    if (connection.id) {
        await api.updateJiraConnection(connection.id, payload);
    } else {
        await api.createJiraConnection(payload);
    }

    state.jiraConnections = await api.listJiraConnections();
}

/**
 * Projects using it lose their Jira link on the server, so they are
 * reloaded too.
 */
export function removeJiraConnection(id) {
    return run(async () => {
        await api.deleteJiraConnection(id);
        state.jiraConnections = await api.listJiraConnections();
        state.projects = await api.listProjects();
    });
}

export function loadProjects() {
    return run(async () => {
        state.projects = await api.listProjects();
    });
}

export function startTimer(entry) {
    return run(async () => {
        await api.startEntry(entry);
        state.entries = await api.listEntries();
    });
}

export function stopTimer(id) {
    return run(async () => {
        await api.updateEntry(id, { stop: true });
        state.entries = await api.listEntries();
    });
}

export function addEntry(entry) {
    return run(async () => {
        await api.createEntry(entry);
        state.entries = await api.listEntries();
    });
}

export function editEntry(id, changes) {
    return run(async () => {
        await api.updateEntry(id, changes);
        state.entries = await api.listEntries();
    });
}

export function removeEntry(id) {
    return run(async () => {
        await api.deleteEntry(id);
        state.entries = state.entries.filter((entry) => entry.id !== id);
    });
}

export function saveSettings(settings) {
    return run(async () => {
        state.settings = await api.saveSettings(settings);
        setLocale(state.settings.locale);
    });
}

export function saveCompany(company) {
    return run(async () => {
        state.settings.company = await api.saveCompany(company);

        return true;
    });
}

export function changePassword(passwords) {
    return run(async () => {
        await api.changePassword(passwords);

        return true;
    });
}

export function saveProject(project) {
    return run(async () => {
        const payload = {
            client_id: project.client_id,
            jira_connection_id: project.jira_connection_id ?? null,
            jira_project_key: project.jira_project_key || null,
            jira_only_mine: Boolean(project.jira_only_mine),
            name: project.name,
            color: project.color,
            hourly_rate: project.hourly_rate,
        };

        if (project.id) {
            await api.updateProject(project.id, payload);
        } else {
            await api.createProject(payload);
        }

        state.projects = await api.listProjects();

        return true;
    });
}

/**
 * Entries on a deleted project lose it on the server, so they are reloaded
 * too.
 */
export function removeProject(id) {
    return run(async () => {
        await api.deleteProject(id);
        state.projects = await api.listProjects();
        state.entries = await api.listEntries();
    });
}

export function logout() {
    api.logout();
}

export function startClock() {
    const ticker = setInterval(() => {
        state.now = new Date();
    }, 1000);

    return () => clearInterval(ticker);
}

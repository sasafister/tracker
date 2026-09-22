import { locale, t } from './i18n.js';

/**
 * Thin wrapper over fetch for the same-origin JSON routes in routes/web.php.
 * They sit behind the web middleware, so every write carries the CSRF token
 * rendered into the page head.
 */

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function request(method, url, body = null) {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            // Validation messages come back in the language on screen.
            'Accept-Language': locale.value,
        },
        body: body === null ? null : JSON.stringify(body),
    });

    // The session ran out; the login page is the only way back.
    if (response.status === 401 || response.status === 419) {
        window.location.assign('/login');

        throw new Error(t('api.sessionExpired'));
    }

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => null);

    if (! response.ok) {
        const message = payload?.message ?? t('api.requestFailed', { status: response.status });

        throw new Error(message);
    }

    return payload;
}

export function listEntries(range = null) {
    if (range === null) {
        return request('GET', '/api/entries');
    }

    const query = new URLSearchParams({
        from: range.from,
        to: range.to,
    });

    return request('GET', `/api/entries?${query}`);
}

export function startEntry(entry) {
    return request('POST', '/api/entries', entry);
}

export function createEntry(entry) {
    return request('POST', '/api/entries', entry);
}

export function updateEntry(id, changes) {
    return request('PATCH', `/api/entries/${id}`, changes);
}

export function deleteEntry(id) {
    return request('DELETE', `/api/entries/${id}`);
}

export function getSettings() {
    return request('GET', '/api/settings');
}

export function saveSettings(settings) {
    return request('PUT', '/api/settings', settings);
}

export function saveCompany(company) {
    return request('PUT', '/api/company', company);
}

export function changePassword(passwords) {
    return request('PUT', '/api/password', passwords);
}

export function listClients() {
    return request('GET', '/api/clients');
}

export function createClient(client) {
    return request('POST', '/api/clients', client);
}

export function updateClient(id, client) {
    return request('PUT', `/api/clients/${id}`, client);
}

export function deleteClient(id) {
    return request('DELETE', `/api/clients/${id}`);
}

export function listJiraConnections() {
    return request('GET', '/api/jira-connections');
}

export function createJiraConnection(connection) {
    return request('POST', '/api/jira-connections', connection);
}

export function updateJiraConnection(id, connection) {
    return request('PUT', `/api/jira-connections/${id}`, connection);
}

export function deleteJiraConnection(id) {
    return request('DELETE', `/api/jira-connections/${id}`);
}

/**
 * Tickets for a project, through the server — Jira does not accept calls
 * from the browser, and the token stays on the server.
 */
export function searchJiraIssues(projectId, query) {
    const parameters = new URLSearchParams({ q: query });

    return request('GET', `/api/projects/${projectId}/jira-issues?${parameters}`);
}

export function listProjects() {
    return request('GET', '/api/projects');
}

export function createProject(project) {
    return request('POST', '/api/projects', project);
}

export function updateProject(id, project) {
    return request('PUT', `/api/projects/${id}`, project);
}

export function deleteProject(id) {
    return request('DELETE', `/api/projects/${id}`);
}

/**
 * A plain form post, so the server's redirect to /login lands as a page load.
 */
export function logout() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrfToken();

    form.append(token);
    document.body.append(form);
    form.submit();
}

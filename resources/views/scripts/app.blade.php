/**
 * API Docs — Core Application Module
 * State management, initialization, utilities
 */

const docsConfig = window.apiMagicDocsConfig || {};

// ─── Global State ───
let schema = { endpoints: {}, baseUrl: window.location.origin, servers: [] };
let selectedPath = null;
let selectedMethod = null;
let token = localStorage.getItem('api-docs-token') || '';
let useSanctum = localStorage.getItem('api-docs-sanctum') === 'true';
let requestCount = 0;
let successCount = 0;
let selectedVersion = 'all';
let selectedServerUrl = '';
let collapsedGroups = JSON.parse(
    localStorage.getItem('api-docs-collapsed') || '{}',
);

// Request History & Chaining
let requestHistory = JSON.parse(localStorage.getItem('api-docs-history') || '[]');
let lastResponse = null;
const MAX_HISTORY = 50;
const overviewTemplate = document.getElementById('content-area')?.innerHTML || '';
let legacyIconObserverStarted = false;
const legacyIconObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node instanceof Element) {
                upgradeLegacyIcons(node);
            }
        });
    });
});

function createSvgIcon(iconName, classNames = '') {
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    const safeIconName = iconName || 'default';

    icon.setAttribute('aria-hidden', 'true');
    icon.setAttribute('focusable', 'false');
    icon.setAttribute(
        'class',
        ['am-icon', classNames].filter(Boolean).join(' '),
    );
    use.setAttribute('href', `#api-magic-icon-${safeIconName}`);
    icon.appendChild(use);

    return icon;
}

function upgradeLegacyIcons(root = document) {
    root.querySelectorAll?.('i[class*="fa-"]').forEach((legacyIcon) => {
        const iconClass = Array.from(legacyIcon.classList).find(
            (className) =>
                className.startsWith('fa-') &&
                className !== 'fa-spin' &&
                className !== 'fa-solid' &&
                className !== 'fa-regular' &&
                className !== 'fa-brands',
        );

        if (!iconClass) {
            return;
        }

        const iconName = iconClass.replace(/^fa-/, '');
        const remainingClasses = Array.from(legacyIcon.classList).filter(
            (className) =>
                !['fas', 'far', 'fab', 'fa', iconClass].includes(className) &&
                className !== 'fa-spin',
        );

        if (legacyIcon.classList.contains('fa-spin')) {
            remainingClasses.push('animate-spin');
        }

        const svgIcon = createSvgIcon(iconName, remainingClasses.join(' '));
        legacyIcon.replaceWith(svgIcon);
    });
}

function startLegacyIconObserver() {
    if (legacyIconObserverStarted) {
        return;
    }

    legacyIconObserverStarted = true;
    legacyIconObserver.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

// ─── Utilities ───
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) {
        return '';
    }

    return unsafe
        .toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className =
        'toast fixed bottom-4 right-4 lg:bottom-6 lg:right-6 px-4 py-3 bg-cyan-500 text-slate-950 rounded-xl shadow-lg shadow-cyan-500/20 text-sm z-50 flex items-center gap-2 font-medium';
    toast.innerHTML = '<i class="fas fa-check-circle"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

function copyWithFeedback(btnId, text) {
    navigator.clipboard.writeText(text);
    const btn = document.getElementById(btnId);

    if (btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = original;
        }, 1500);
    }

    showToast('Copied to clipboard');
}

function getDocsUrl(key) {
    return docsConfig.urls?.[key] || '';
}

function getAvailableServers() {
    if (Array.isArray(schema.servers) && schema.servers.length > 0) {
        return schema.servers;
    }

    return [
        {
            url: schema.baseUrl || window.location.origin,
            description: 'Current Environment',
        },
    ];
}

function getActiveBaseUrl() {
    const input = document.getElementById('base-url');

    return input?.value || selectedServerUrl || getAvailableServers()[0]?.url || schema.baseUrl || window.location.origin;
}

function setActiveBaseUrl(url) {
    selectedServerUrl = url;

    const baseUrlInput = document.getElementById('base-url');
    if (baseUrlInput) {
        baseUrlInput.value = url;
    }

    const detailSelect = document.getElementById('base-url-select');
    if (detailSelect) {
        detailSelect.value = url;
    }

    const sidebarSelect = document.getElementById('server-switcher');
    if (sidebarSelect) {
        sidebarSelect.value = url;
    }

    const currentServer = getAvailableServers().find(
        (server) => server.url === url,
    );
    const serverLabel = currentServer?.description || url;

    const sidebarServer = document.getElementById('server-current');
    if (sidebarServer) {
        sidebarServer.innerHTML = '<i class="fas fa-globe text-sky-400"></i><span class="truncate">' + escapeHtml(serverLabel) + '</span>';
    }

    const headerBadge = document.getElementById('header-server-badge');
    const headerServerName = document.getElementById('header-server-name');
    if (headerBadge && headerServerName) {
        headerBadge.classList.remove('hidden');
        headerBadge.classList.add('inline-flex');
        headerServerName.textContent = serverLabel;
    }

    const footerBaseUrl = document.getElementById('footer-base-url');
    if (footerBaseUrl) {
        footerBaseUrl.textContent = url;
    }

    const overviewServer = document.getElementById('overview-server');
    if (overviewServer) {
        overviewServer.textContent = serverLabel;
    }
}

function switchEnvironment(url) {
    setActiveBaseUrl(url);
    showToast('Server switched');
}

function countEndpoints() {
    let count = 0;

    for (const path in schema.endpoints) {
        count += Object.keys(schema.endpoints[path]).length;
    }

    return count;
}

function getEnabledFeatureLabels() {
    const labels = ['Docs UI', 'OpenAPI Export', 'Request History'];

    if (schema.features?.health) {
        labels.push('Health');
    }

    if (schema.features?.changelog) {
        labels.push('Changelog');
    }

    if (schema.webhooks?.length) {
        labels.push('Webhooks');
    }

    if (Object.keys(schema.events || {}).length) {
        labels.push('WebSockets');
    }

    return labels;
}

function focusSearchBox() {
    const searchInput = document.getElementById('search');

    if (!searchInput) {
        return;
    }

    searchInput.focus();
    searchInput.select();
}

function openFirstEndpoint() {
    const availablePaths = Object.keys(schema.endpoints || {});

    if (availablePaths.length === 0) {
        showToast('No API endpoints are available yet');

        return;
    }

    const selectedPathCandidate = availablePaths.find((path) => {
        const endpointGroup = schema.endpoints[path] || {};
        return Object.keys(endpointGroup).length > 0;
    });

    if (!selectedPathCandidate) {
        showToast('No API endpoints are available yet');

        return;
    }

    const methods = Object.keys(schema.endpoints[selectedPathCandidate] || {});
    const preferredMethod =
        methods.find((method) => method === 'get') || methods[0];

    if (!preferredMethod) {
        showToast('No endpoint methods are available yet');

        return;
    }

    selectEndpoint(selectedPathCandidate, preferredMethod);
    showToast('Opened the first available endpoint');
}

function updateOverviewMetrics() {
    const endpointCount = countEndpoints();
    const servers = getAvailableServers();
    const rate = requestCount > 0
        ? Math.round((successCount / requestCount) * 100)
        : 100;

    const heroEndpointCount = document.getElementById('hero-endpoint-count');
    if (heroEndpointCount) {
        heroEndpointCount.textContent = endpointCount;
    }

    const heroServerCount = document.getElementById('hero-server-count');
    if (heroServerCount) {
        heroServerCount.textContent = servers.length;
    }

    const heroSuccessRate = document.getElementById('hero-success-rate');
    if (heroSuccessRate) {
        heroSuccessRate.textContent = rate + '%';
    }

    const heroRequestCount = document.getElementById('hero-request-count');
    if (heroRequestCount) {
        heroRequestCount.textContent = requestCount;
    }

    const generatedAt = schema.generated_at
        ? new Date(schema.generated_at).toLocaleString()
        : 'Waiting for schema';

    const overviewGeneratedAt = document.getElementById(
        'overview-generated-at',
    );
    if (overviewGeneratedAt) {
        overviewGeneratedAt.textContent = generatedAt;
    }

    const sidebarGeneratedAt = document.getElementById('sidebar-generated-at');
    if (sidebarGeneratedAt) {
        sidebarGeneratedAt.textContent = generatedAt;
    }

    const featureContainer = document.getElementById('overview-features');
    if (featureContainer) {
        featureContainer.innerHTML = getEnabledFeatureLabels()
            .map(
                (label) =>
                    '<span class="rounded-full border border-white/10 bg-slate-800/80 px-2.5 py-1 text-xs text-slate-300">' +
                    escapeHtml(label) +
                    '</span>',
            )
            .join('');
    }
}

function updateStats() {
    const rate = requestCount > 0
        ? Math.round((successCount / requestCount) * 100)
        : 100;

    document.getElementById('success-rate').textContent = rate + '%';
    document.getElementById('request-count').textContent = requestCount;
    document.getElementById('endpoint-count').textContent = countEndpoints();

    const authStatus = token || useSanctum ? 'Authenticated' : 'Set Auth';
    document.getElementById('auth-status').textContent = authStatus;

    updateOverviewMetrics();
}

function setupVersionFilter() {
    if (!schema.versions || schema.versions.length <= 1) {
        return;
    }

    const container = document.getElementById('version-filter-container');
    const select = document.getElementById('version-filter');

    select.innerHTML = '<option value="all">All Versions</option>';
    container.classList.remove('hidden');

    schema.versions.forEach((version) => {
        const option = document.createElement('option');
        option.value = version;
        option.textContent = `Version ${version}`;
        select.appendChild(option);
    });

    select.addEventListener('change', (event) => {
        selectedVersion = event.target.value;
        renderEndpoints(document.getElementById('search').value);
    });
}

function setupServerSwitcher() {
    const wrapper = document.getElementById('server-switcher-wrapper');
    const select = document.getElementById('server-switcher');
    const servers = getAvailableServers();

    if (!wrapper || !select) {
        return;
    }

    select.innerHTML = servers
        .map(
            (server) =>
                '<option value="' +
                escapeHtml(server.url) +
                '">' +
                escapeHtml(server.description || server.url) +
                '</option>',
        )
        .join('');

    if (servers.length > 1) {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
    }

    setActiveBaseUrl(servers[0]?.url || schema.baseUrl || window.location.origin);
}

function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function closeMobileSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
}

function toggleSanctumAuth(checkbox) {
    const tokenInput = document.getElementById('auth-token');

    if (!checkbox || !tokenInput) {
        return;
    }

    tokenInput.disabled = checkbox.checked;
    tokenInput.classList.toggle('opacity-50', checkbox.checked);
    tokenInput.classList.toggle('cursor-not-allowed', checkbox.checked);
    tokenInput.placeholder = checkbox.checked
        ? 'Using Sanctum session cookie'
        : '1|abc123xyz...';
}

function openAuthModal() {
    document.getElementById('auth-modal').classList.remove('hidden');
    document.getElementById('auth-token').value = token;

    const sanctumCheck = document.getElementById('auth-sanctum-cookie');
    if (sanctumCheck) {
        sanctumCheck.checked = useSanctum;
        toggleSanctumAuth(sanctumCheck);
    }

    const oauthSection = document.getElementById('oauth2-section');
    if (oauthSection) {
        const configured = Boolean(
            schema.oauth?.authUrl && schema.oauth?.clientId,
        );
        oauthSection.classList.toggle('hidden', !configured);
    }
}

function closeAuthModal() {
    document.getElementById('auth-modal').classList.add('hidden');
}

function saveToken() {
    token = document.getElementById('auth-token').value;
    const sanctumCheck = document.getElementById('auth-sanctum-cookie');
    useSanctum = sanctumCheck ? sanctumCheck.checked : false;

    localStorage.setItem('api-docs-token', token);
    localStorage.setItem('api-docs-sanctum', useSanctum);

    updateStats();
    closeAuthModal();
    showToast('Auth settings saved');
}

function clearToken() {
    token = '';
    useSanctum = false;
    localStorage.removeItem('api-docs-token');
    localStorage.removeItem('api-docs-sanctum');
    updateStats();
    closeAuthModal();
    showToast('Auth cleared');
}

function showOverviewPanel() {
    selectedPath = null;
    selectedMethod = null;

    if (typeof closeActiveWebSocket === 'function') {
        closeActiveWebSocket();
    }

    const contentArea = document.getElementById('content-area');
    if (contentArea && !document.getElementById('overview-root')) {
        contentArea.innerHTML = overviewTemplate;
    }

    setActiveBaseUrl(selectedServerUrl || getAvailableServers()[0]?.url || schema.baseUrl);
    renderEndpoints(document.getElementById('search').value);
    updateOverviewMetrics();
}

document.getElementById('search').addEventListener('input', (event) => {
    renderEndpoints(event.target.value);
});

document
    .getElementById('auth-sanctum-cookie')
    ?.addEventListener('change', (event) => {
        toggleSanctumAuth(event.target);
    });

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        document.getElementById('search').focus();
    }

    if (event.key === 'Escape') {
        closeAuthModal();
        closeMobileSidebar();
    }
});

function startOAuthLogin() {
    const oauthConfig = schema.oauth || {};
    if (!oauthConfig.authUrl || !oauthConfig.clientId) {
        showToast('OAuth2 is not configured in api-magic.php');
        return;
    }

    const clientId = oauthConfig.clientId;
    const redirectUri = getDocsUrl('oauthCallback') || window.location.origin;
    const authUrl =
        `${oauthConfig.authUrl}?response_type=token&client_id=${clientId}` +
        `&redirect_uri=${encodeURIComponent(redirectUri)}` +
        `&scope=${encodeURIComponent(oauthConfig.scopes || '')}`;

    const width = 600;
    const height = 700;
    const left = window.innerWidth / 2 - width / 2;
    const top = window.innerHeight / 2 - height / 2;
    const popup = window.open(
        authUrl,
        'oauth2',
        `width=${width},height=${height},top=${top},left=${left}`,
    );

    const messageListener = (event) => {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === 'oauth2_token') {
            document.getElementById('auth-token').value = event.data.token;

            const sanctumCheck = document.getElementById('auth-sanctum-cookie');
            if (sanctumCheck) {
                sanctumCheck.checked = false;
                toggleSanctumAuth(sanctumCheck);
            }

            saveToken();
            cleanup();
            if (popup) {
                popup.close();
            }
            showToast('OAuth2 login successful');
        }
    };

    const cleanup = () => {
        window.removeEventListener('message', messageListener);
        clearInterval(checkClosedInterval);
    };

    const checkClosedInterval = setInterval(() => {
        if (popup && popup.closed) {
            cleanup();
            if (!token) {
                showToast('OAuth2 login cancelled');
            }
        }
    }, 1000);

    window.addEventListener('message', messageListener);
}

async function init() {
    try {
        upgradeLegacyIcons(document);
        startLegacyIconObserver();

        const response = await fetch(getDocsUrl('json'));
        schema = await response.json();

        document.getElementById('version').textContent = schema.version;
        document.getElementById('schema-title').textContent = schema.title;
        document.getElementById('mobile-title').textContent = schema.title;

        setupVersionFilter();
        setupServerSwitcher();
        updateStats();
        renderEndpoints();
        showOverviewPanel();
        upgradeLegacyIcons(document);

        document.getElementById('loading').classList.add('hidden');
        document.getElementById('main-content').classList.remove('hidden');
    } catch (error) {
        console.error('Failed to load:', error);
        document.getElementById('loading').innerHTML = `
            <div class="text-center px-6">
                <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                <p class="text-red-400 font-medium">Failed to load documentation</p>
                <p class="text-slate-500 text-sm mt-2">${escapeHtml(error.message)}</p>
                <button onclick="init()" class="mt-4 px-4 py-2 bg-cyan-500 rounded-xl text-slate-950 text-sm font-semibold hover:bg-cyan-400 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Retry
                </button>
            </div>
        `;
    }
}

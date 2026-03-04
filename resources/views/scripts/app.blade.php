/**
 * API Docs — Core Application Module
 * State management, initialization, utilities
 */

// ─── Global State ───
let schema = { endpoints: {}, baseUrl: window.location.origin };
let selectedPath = null;
let selectedMethod = null;
let token = localStorage.getItem('api-docs-token') || '';
let useSanctum = localStorage.getItem('api-docs-sanctum') === 'true';
let requestCount = 0;
let successCount = 0;
let selectedVersion = 'all';
let collapsedGroups = JSON.parse(localStorage.getItem('api-docs-collapsed') || '{}');

// Request History & Chaining
let requestHistory = JSON.parse(localStorage.getItem('api-docs-history') || '[]');
let lastResponse = null;
const MAX_HISTORY = 50;

// ─── Utilities ───
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast fixed bottom-4 right-4 lg:bottom-6 lg:right-6 px-4 py-3 bg-green-500 text-white rounded-lg shadow-lg text-sm z-50 flex items-center gap-2';
    toast.innerHTML = '<i class="fas fa-check-circle"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

function copyWithFeedback(btnId, text) {
    navigator.clipboard.writeText(text);
    const btn = document.getElementById(btnId);
    if (btn) {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-green-400"></i> Copied!';
        setTimeout(() => { btn.innerHTML = original; }, 1500);
    }
    showToast('Copied to clipboard!');
}

// ─── Stats & Environment ───
function switchEnvironment(env) {
    let url = schema.baseUrl || window.location.origin;
    
    // Naive heuristic if environments aren't completely defined in schema
    if (env === 'staging') {
        url = url.replace('localhost', 'staging.api.example.com').replace('.test', '.staging.example.com');
    } else if (env === 'production') {
        url = url.replace('localhost', 'api.example.com').replace('.test', '.example.com');
        url = url.replace(/^http:\/\//i, 'https://'); // Enforce HTTPS for production
    }

    const input = document.getElementById('base-url');
    if (input) {
        input.value = url;
        // Trigger visual feedback
        input.classList.add('ring-2', 'ring-indigo-500');
        setTimeout(() => input.classList.remove('ring-2', 'ring-indigo-500'), 500);
    }
    showToast(`Environment set to ${env.toUpperCase()}`);
}

function countEndpoints() {
    let count = 0;
    for (const path in schema.endpoints) {
        count += Object.keys(schema.endpoints[path]).length;
    }
    return count;
}

function updateStats() {
    const rate = requestCount > 0 ? Math.round((successCount / requestCount) * 100) : 100;
    document.getElementById('success-rate').textContent = rate + '%';
    document.getElementById('request-count').textContent = requestCount;
    if (token || useSanctum) {
        document.getElementById('auth-status').textContent = 'Authenticated';
    } else {
        document.getElementById('auth-status').textContent = 'Set Auth';
    }
}

// ─── Version Filter ───
function setupVersionFilter() {
    if (!schema.versions || schema.versions.length <= 1) return;
    const container = document.getElementById('version-filter-container');
    const select = document.getElementById('version-filter');
    container.classList.remove('hidden');
    schema.versions.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = `Version ${v}`;
        select.appendChild(opt);
    });
    select.addEventListener('change', (e) => {
        selectedVersion = e.target.value;
        renderEndpoints();
    });
}

// ─── Sidebar Mobile ───
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

// ─── Auth ───
function openAuthModal() {
    document.getElementById('auth-modal').classList.remove('hidden');
    document.getElementById('auth-token').value = token;
    
    const sanctumCheck = document.getElementById('auth-sanctum-cookie');
    if (sanctumCheck) {
        sanctumCheck.checked = useSanctum;
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
    
    document.getElementById('auth-status').textContent = (token || useSanctum) ? 'Authenticated' : 'Set Auth';
    closeAuthModal();
    showToast('Auth settings saved!');
}

function clearToken() {
    token = '';
    useSanctum = false;
    localStorage.removeItem('api-docs-token');
    localStorage.removeItem('api-docs-sanctum');
    document.getElementById('auth-status').textContent = 'Set Auth';
    closeAuthModal();
    showToast('Auth cleared!');
}

// ─── Keyboard Shortcuts ───
document.getElementById('search').addEventListener('input', (e) => renderEndpoints(e.target.value));

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('search').focus();
    }
    if (e.key === 'Escape') {
        closeAuthModal();
        closeMobileSidebar();
    }
});

// ─── OAuth 2.0 ───
function startOAuthLogin() {
    const oauthConfig = schema.oauth || {};
    if (!oauthConfig.authUrl || !oauthConfig.clientId) {
        showToast('OAuth2 is not configured in api-magic.php');
        return;
    }
    
    const clientId = oauthConfig.clientId;
    const redirectUri = window.location.origin + '/api/docs/oauth2-callback';
    
    // Using implicit flow for SPAs to quickly get token without backend secret
    const authUrl = `${oauthConfig.authUrl}?response_type=token&client_id=${clientId}&redirect_uri=${encodeURIComponent(redirectUri)}&scope=${encodeURIComponent(oauthConfig.scopes || '')}`;
    
    const width = 600, height = 700;
    const left = (window.innerWidth / 2) - (width / 2);
    const top = (window.innerHeight / 2) - (height / 2);
    const popup = window.open(authUrl, 'oauth2', `width=${width},height=${height},top=${top},left=${left}`);
    
    // Listen for messages from the popup callback
    const messageListener = (event) => {
        if (event.origin !== window.location.origin) return;
        if (event.data && event.data.type === 'oauth2_token') {
            document.getElementById('auth-token').value = event.data.token;
            
            const sanctumCheck = document.getElementById('auth-sanctum-cookie');
            if (sanctumCheck) {
                sanctumCheck.checked = false;
            }
            toggleSanctumAuth(document.getElementById('auth-sanctum-cookie'));
            
            saveToken();
            cleanup();
            if (popup) popup.close();
            showToast('OAuth2 Login Successful!');
        }
    };
    
    const cleanup = () => {
        window.removeEventListener('message', messageListener);
        clearInterval(checkClosedInterval);
    };

    const checkClosedInterval = setInterval(() => {
        if (popup && popup.closed) {
            cleanup();
            if (!token) showToast('OAuth2 Login Cancelled');
        }
    }, 1000);
    
    window.addEventListener('message', messageListener);
}

// ─── Initialize ───
async function init() {
    try {
        const res = await fetch('/api/docs/json');
        schema = await res.json();
        document.getElementById('version').textContent = schema.version;
        document.getElementById('schema-title').textContent = schema.title;
        document.getElementById('mobile-title').textContent = schema.title;
        document.getElementById('endpoint-count').textContent = countEndpoints();
        updateStats();
        setupVersionFilter();
        renderEndpoints();
        renderWebhooksBadge();
        renderEventsBadge();
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('main-content').classList.remove('hidden');
    } catch (e) {
        console.error('Failed to load:', e);
        document.getElementById('loading').innerHTML = `
            <div class="text-center px-6">
                <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                <p class="text-red-400 font-medium">Failed to load documentation</p>
                <p class="text-slate-500 text-sm mt-2">${e.message}</p>
                <button onclick="init()" class="mt-4 px-4 py-2 bg-indigo-500 rounded-lg text-white text-sm hover:bg-indigo-600 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Retry
                </button>
            </div>
        `;
    }
}

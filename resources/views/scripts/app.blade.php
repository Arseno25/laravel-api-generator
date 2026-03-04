/**
 * API Docs — Core Application Module
 * State management, initialization, utilities
 */

// ─── Global State ───
let schema = { endpoints: {}, baseUrl: window.location.origin };
let selectedPath = null;
let selectedMethod = null;
let token = localStorage.getItem('api-docs-token') || '';
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

// ─── Stats ───
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
    if (token) {
        document.getElementById('auth-status').textContent = 'Authenticated';
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
}

function closeAuthModal() {
    document.getElementById('auth-modal').classList.add('hidden');
}

function saveToken() {
    token = document.getElementById('auth-token').value;
    localStorage.setItem('api-docs-token', token);
    document.getElementById('auth-status').textContent = token ? 'Authenticated' : 'Set Token';
    closeAuthModal();
    showToast('Token saved!');
}

function clearToken() {
    token = '';
    localStorage.removeItem('api-docs-token');
    document.getElementById('auth-status').textContent = 'Set Token';
    closeAuthModal();
    showToast('Token cleared!');
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

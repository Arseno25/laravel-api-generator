/**
 * API Docs — History & Chaining Module
 * Request history (localStorage), replay, request chaining
 */

// ─── History Management ───
function saveToHistory(entry) {
    requestHistory.unshift(entry);
    if (requestHistory.length > MAX_HISTORY) requestHistory.pop();
    localStorage.setItem('api-docs-history', JSON.stringify(requestHistory));
    updateHistoryCount();
}

function updateHistoryCount() {
    const el = document.getElementById('history-count');
    if (el) el.textContent = requestHistory.length;
}

function clearHistory() {
    requestHistory = [];
    localStorage.removeItem('api-docs-history');
    updateHistoryCount();
    showHistoryPanel();
    showToast('History cleared!');
}

function showHistoryPanel() {
    selectedPath = null;
    selectedMethod = null;
    const container = document.getElementById('content-area');
    updateHistoryCount();

    if (requestHistory.length === 0) {
        container.innerHTML = `<div class="text-center py-16 lg:py-20"><div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-800/50 flex items-center justify-center"><i class="fas fa-inbox text-2xl text-slate-600"></i></div><h3 class="text-xl font-semibold text-slate-400 mb-2">No Request History</h3><p class="text-sm text-slate-500">Send some API requests and they'll appear here.</p></div>`;
        return;
    }

    const rows = requestHistory.map((h, i) => {
        const methodClass = 'method-' + h.method.toLowerCase();
        const statusClass = 'status-' + Math.floor(h.status / 100) + 'xx';
        const time = new Date(h.timestamp).toLocaleString();
        return `<div class="flex items-center gap-2 lg:gap-3 px-3 lg:px-4 py-2.5 lg:py-3 hover:bg-slate-800/30 transition-colors cursor-pointer border-b border-white/5" onclick="replayHistory(${i})"><span class="method-badge ${methodClass} text-[10px]">${h.method}</span><span class="flex-1 font-mono text-xs lg:text-sm text-slate-300 truncate">${escapeHtml(h.path)}</span><span class="status-badge ${statusClass} text-[10px] py-0.5 px-1.5">${h.status}</span><span class="text-[10px] lg:text-xs text-slate-500 flex-shrink-0">${h.responseTime}ms</span><span class="text-[10px] text-slate-600 hidden lg:inline flex-shrink-0">${time}</span></div>`;
    }).join('');

    container.innerHTML = `<div class="max-w-4xl mx-auto space-y-4"><div class="bg-slate-900/50 backdrop-blur-xl rounded-2xl border border-white/5 overflow-hidden shadow-xl shadow-black/20"><div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-white/5 bg-slate-800/20 flex items-center justify-between"><div class="flex items-center gap-3"><i class="fas fa-history text-cyan-400"></i><h3 class="text-base lg:text-lg font-semibold text-white">Request History</h3><span class="text-xs text-slate-500">${requestHistory.length} entries</span></div><button onclick="clearHistory()" class="px-3 py-1.5 text-xs bg-red-500/10 text-red-400 rounded-lg hover:bg-red-500/20 transition-colors flex-shrink-0"><i class="fas fa-trash mr-1"></i> Clear</button></div><div class="max-h-[500px] lg:max-h-[600px] overflow-y-auto">${rows}</div></div><div class="bg-slate-900/30 rounded-xl border border-white/5 p-3 lg:p-4"><h4 class="text-sm font-medium text-slate-400 mb-2"><i class="fas fa-link mr-1.5 text-indigo-400"></i>Request Chaining</h4><p class="text-xs text-slate-500 leading-relaxed">Use <code class="bg-slate-800 px-1.5 py-0.5 rounded text-indigo-400">` + '@{{response.field}}' + `</code> in any field to reference the last response. Example: <code class="bg-slate-800 px-1.5 py-0.5 rounded text-indigo-400">` + '@{{response.data.id}}' + `</code></p></div></div>`;
}

function replayHistory(index) {
    const entry = requestHistory[index];
    if (!entry) return;
    if (schema.endpoints[entry.path] && schema.endpoints[entry.path][entry.method.toLowerCase()]) {
        selectedPath = entry.path;
        selectedMethod = entry.method.toLowerCase();
        const endpoint = schema.endpoints[selectedPath][selectedMethod];
        renderEndpointDetail(selectedPath, selectedMethod, endpoint);
        
        // Restore request body
        if (entry.body) {
            setTimeout(() => {
                const bodyField = document.getElementById('request-body');
                if (bodyField) bodyField.value = entry.body;
            }, 100);
        }

        // Restore past response immediately to the screen
        if (entry.response) {
            setTimeout(() => {
                const responseDiv = document.getElementById('response');
                if (responseDiv) {
                    responseDiv.classList.remove('hidden');
                    document.getElementById('response-status').textContent = entry.status + ' (From History)';
                    document.getElementById('response-status').className = 'status-badge status-' + Math.floor(entry.status / 100) + 'xx';
                    document.getElementById('response-time-val').textContent = entry.responseTime + 'ms';
                    
                    let data;
                    try { 
                        data = JSON.parse(entry.response); 
                    } catch { 
                        data = { raw: entry.response }; 
                    }
                    
                    if (entry.status >= 400 || !data || (typeof data === 'object' && data.exception)) {
                        displayErrorBody(entry.status, data);
                    } else {
                        displaySuccessBody(data);
                    }
                }
            }, 150);
        }

        showToast('Request loaded from history!');
    } else {
        showToast('Endpoint not found in current schema');
    }
}

// ─── Request Chaining ───
function resolveChainedValues(text) {
    if (!text || !lastResponse) return text;
    return text.replace(/\{\{response\.([\w.]+)\}\}/g, (match, path) => {
        const keys = path.split('.');
        let val = lastResponse;
        for (const k of keys) {
            if (val == null || typeof val !== 'object') return match;
            val = val[k];
        }
        return val !== undefined ? (typeof val === 'object' ? JSON.stringify(val) : String(val)) : match;
    });
}

// Override sendRequest to support chaining
const _origSend = sendRequest;
sendRequest = async function (btn) {
    const bodyField = document.getElementById('request-body');
    if (bodyField && bodyField.value && lastResponse) {
        bodyField.value = resolveChainedValues(bodyField.value);
    }
    document.querySelectorAll('[id^="param-"]').forEach(input => {
        if (input.value) input.value = resolveChainedValues(input.value);
    });
    document.querySelectorAll('.query-value').forEach(input => {
        if (input.value) input.value = resolveChainedValues(input.value);
    });
    return _origSend.call(this, btn);
};

// Initialize history count
updateHistoryCount();

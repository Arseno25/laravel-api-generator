/**
 * API Docs — Request Module
 * Send requests, display responses
 */

async function sendRequest(btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    btn.disabled = true;
    const baseUrl = document.getElementById('base-url').value;
    let url = selectedPath;
    const endpoint = schema.endpoints[selectedPath][selectedMethod];
    if (endpoint.parameters?.path) {
        endpoint.parameters.path.forEach(p => {
            const val = document.getElementById('param-' + p.name)?.value || '1';
            url = url.replace('{' + p.name + '}', encodeURIComponent(val));
        });
    }
    const queryRows = document.querySelectorAll('.query-param-row');
    const params = new URLSearchParams();
    queryRows.forEach(row => {
        const key = row.querySelector('.query-key')?.value;
        const val = row.querySelector('.query-value')?.value;
        if (key && val) params.append(key, val);
    });
    const qs = params.toString();
    if (qs) url += '?' + qs;
    const options = { method: selectedMethod.toUpperCase(), headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' } };
    if (token && endpoint.security && endpoint.security.length > 0) options.headers['Authorization'] = 'Bearer ' + token;
    if (endpoint.parameters?.body && ['post', 'put', 'patch'].includes(selectedMethod)) options.body = document.getElementById('request-body')?.value;
    const start = performance.now();
    try {
        const res = await fetch(baseUrl + url, options);
        const text = await res.text();
        const responseTime = Math.round(performance.now() - start);
        displayResponse(res.status, text, responseTime);
        if (res.ok) successCount++;
        let parsedBody = null;
        try { parsedBody = JSON.parse(text); } catch { }
        lastResponse = parsedBody;
        saveToHistory({ method: selectedMethod.toUpperCase(), path: selectedPath, url: baseUrl + url, status: res.status, responseTime, body: options.body || null, response: text.substring(0, 2000), timestamp: new Date().toISOString() });
    } catch (e) { displayNetworkError(e.message); }
    requestCount++;
    updateStats();
    btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Send Request';
    btn.disabled = false;
}

function displayResponse(status, text, responseTime) {
    const responseDiv = document.getElementById('response');
    responseDiv.classList.remove('hidden');
    document.getElementById('response-status').textContent = status;
    document.getElementById('response-status').className = 'status-badge status-' + Math.floor(status / 100) + 'xx';
    document.getElementById('response-time-val').textContent = responseTime + 'ms';
    let data;
    try { data = JSON.parse(text); } catch { data = { raw: text }; }
    if (!data || (typeof data === 'object' && (data.exception || (status >= 400 && data.message)))) {
        displayErrorBody(status, data);
    } else {
        displaySuccessBody(data);
    }
}

function displaySuccessBody(data) {
    document.getElementById('response-body').innerHTML = `<div class="p-4"><div class="flex items-center gap-3 p-4 rounded-2xl success-card"><div class="success-icon-wrapper flex-shrink-0"><i class="fas fa-check text-green-400 text-xl"></i></div><div><h4 class="font-bold text-green-400 text-lg">Success!</h4><p class="text-sm text-slate-300">Request completed successfully</p></div></div><div class="mt-4 p-3 bg-slate-950/80 rounded-xl overflow-x-auto border border-white/5"><pre class="text-xs text-green-400"><code class="font-mono">${JSON.stringify(data, null, 2)}</code></pre></div></div>`;
}

function displayErrorBody(status, data) {
    const exception = data?.exception || 'Error';
    const message = data?.message || 'An error occurred';
    const file = data?.file || '';
    const line = data?.line || '';
    const errors = data?.errors || null;
    let errorDetails = '';
    if (errors && typeof errors === 'object') {
        errorDetails = Object.entries(errors).map(([field, msgs]) => `<div class="flex items-start gap-2 text-sm"><span class="text-red-400 font-mono text-xs flex-shrink-0">${field}:</span><div class="flex-1 flex flex-wrap gap-1">${(Array.isArray(msgs) ? msgs : [msgs]).map(msg => `<span class="inline-block px-2 py-1 rounded bg-red-400/10 text-red-400 text-xs">${msg}</span>`).join('')}</div></div>`).join('');
    }
    document.getElementById('response-body').innerHTML = `<div class="p-4 space-y-4"><div class="error-card p-4"><div class="flex items-start gap-3"><div class="error-icon-wrapper flex-shrink-0"><i class="fas fa-exclamation-triangle text-red-400 text-lg"></i></div><div class="flex-1 min-w-0"><h4 class="text-lg font-bold text-red-400 mb-2">${getStatusTitle(status)}</h4><p class="text-slate-300 text-sm break-words">${message}</p>${exception !== 'Error' ? `<span class="inline-flex items-center px-2 py-1 rounded text-sm bg-red-400/10 text-red-400 font-mono mt-2 break-all">${exception}</span>` : ''}</div></div>${errorDetails ? `<div class="mt-4 pt-4 border-t border-red-400/20"><h5 class="text-sm font-semibold text-slate-300 mb-3"><i class="fas fa-exclamation-circle text-yellow-400 mr-2"></i>Validation Errors</h5><div class="space-y-2">${errorDetails}</div></div>` : ''}${file ? `<div class="mt-4 pt-4 border-t border-red-400/20"><div class="flex items-center gap-2 text-xs text-slate-400"><i class="fas fa-file-code"></i><span class="code-inline break-all">${file}:${line}</span></div></div>` : ''}</div><div><button onclick="this.nextElementSibling.classList.toggle('hidden')" class="w-full flex items-center justify-between p-3 rounded-xl bg-slate-800/50 border border-white/5 text-sm text-slate-300 hover:bg-slate-700/50 transition-colors"><span class="flex items-center gap-2"><i class="fas fa-code"></i><span>View Raw Payload</span></span><i class="fas fa-chevron-down"></i></button><div class="hidden mt-2"><pre class="text-xs text-slate-400 p-3 bg-slate-950/80 rounded-xl overflow-x-auto border border-white/5"><code>${JSON.stringify(data, null, 2)}</code></pre></div></div></div>`;
}

function displayNetworkError(message) {
    const responseDiv = document.getElementById('response');
    responseDiv.classList.remove('hidden');
    document.getElementById('response-status').textContent = 'ERROR';
    document.getElementById('response-status').className = 'status-badge status-5xx';
    document.getElementById('response-time-val').textContent = '-';
    document.getElementById('response-body').innerHTML = `<div class="p-4"><div class="flex items-start gap-3 p-4 rounded-xl bg-slate-900 border border-slate-700"><div class="w-10 h-10 rounded-full bg-red-400/10 flex items-center justify-center flex-shrink-0"><i class="fas fa-wifi text-red-400 text-lg"></i></div><div class="min-w-0"><h4 class="text-lg font-bold text-red-400 mb-1">Network Error</h4><p class="text-slate-300 text-sm break-words">${message}</p><p class="text-xs text-slate-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Check your connection and ensure the API server is running.</p></div></div></div>`;
}

function getStatusTitle(status) {
    return { 400: 'Bad Request', 401: 'Unauthorized', 403: 'Forbidden', 404: 'Not Found', 405: 'Method Not Allowed', 422: 'Validation Error', 429: 'Too Many Requests', 500: 'Internal Server Error', 503: 'Service Unavailable' }[status] || 'Error ' + status;
}

function copyResponse() {
    const el = document.getElementById('response-body');
    const code = el.querySelector('code') || el;
    copyWithFeedback('copy-response-btn', code.textContent);
}

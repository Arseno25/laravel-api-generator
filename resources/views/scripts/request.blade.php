/**
 * API Docs — Request Module
 * Send requests, display responses
 */

async function sendRequest(btn) {
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    btn.disabled = true;
    const baseUrl = getActiveBaseUrl();
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

    if (useSanctum) {
        options.credentials = 'include';
        // Ensure CSRF token is fetched before sending actual JSON request
        try {
            await fetch(baseUrl + '/sanctum/csrf-cookie', { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'include' });
        } catch (e) {
            console.warn('Sanctum CSRF fetch failed:', e);
        }
    } else if (token && endpoint.security && endpoint.security.length > 0) {
        options.headers['Authorization'] = 'Bearer ' + token;
    }
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
    document.getElementById('response-body').innerHTML = `
        <div class="space-y-3 p-4">
            <div class="rounded-2xl border border-emerald-400/15 bg-emerald-400/10 px-4 py-3">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-300">
                    <i class="fas fa-check-circle"></i>
                    <span>Request completed successfully.</span>
                </div>
            </div>
            <pre class="overflow-x-auto rounded-2xl border border-white/6 bg-slate-950/80 p-3 text-xs text-green-400"><code class="font-mono">${JSON.stringify(data, null, 2)}</code></pre>
        </div>
    `;
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
    document.getElementById('response-body').innerHTML = `
        <div class="space-y-3 p-4">
            <div class="rounded-2xl border border-red-400/15 bg-red-400/10 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-400/10 text-red-300">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-base font-semibold text-red-300">${getStatusTitle(status)}</h4>
                        <p class="mt-1 text-sm text-slate-300 break-words">${message}</p>
                        ${exception !== 'Error' ? `<span class="mt-3 inline-flex items-center rounded-full border border-red-400/15 bg-red-400/10 px-3 py-1 text-xs font-mono text-red-300 break-all">${exception}</span>` : ''}
                    </div>
                </div>
                ${errorDetails ? `<div class="mt-4 border-t border-red-400/15 pt-4"><p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.22em] text-red-200/80">Validation</p><div class="space-y-2">${errorDetails}</div></div>` : ''}
                ${file ? `<div class="mt-4 border-t border-red-400/15 pt-4"><div class="flex items-center gap-2 text-xs text-slate-400"><i class="fas fa-file-code"></i><span class="code-inline break-all">${file}:${line}</span></div></div>` : ''}
            </div>
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="flex w-full items-center justify-between rounded-2xl border border-white/8 bg-white/5 p-3 text-sm text-slate-300 transition-colors hover:bg-white/8">
                    <span class="flex items-center gap-2"><i class="fas fa-code text-slate-400"></i><span>View Raw Payload</span></span>
                    <i class="fas fa-chevron-down text-slate-500"></i>
                </button>
                <div class="hidden mt-2">
                    <pre class="overflow-x-auto rounded-2xl border border-white/6 bg-slate-950/80 p-3 text-xs text-slate-300"><code>${JSON.stringify(data, null, 2)}</code></pre>
                </div>
            </div>
        </div>
    `;
}

function displayNetworkError(message) {
    const responseDiv = document.getElementById('response');
    responseDiv.classList.remove('hidden');
    document.getElementById('response-status').textContent = 'ERROR';
    document.getElementById('response-status').className = 'status-badge status-5xx';
    document.getElementById('response-time-val').textContent = '-';
    document.getElementById('response-body').innerHTML = `
        <div class="p-4">
            <div class="rounded-2xl border border-red-400/15 bg-red-400/10 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-400/10 text-red-300">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-base font-semibold text-red-300">Network Error</h4>
                        <p class="mt-1 text-sm text-slate-300 break-words">${message}</p>
                        <p class="mt-2 text-xs text-slate-500"><i class="fas fa-info-circle mr-1"></i>Check your connection and ensure the API server is running.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getStatusTitle(status) {
    return { 400: 'Bad Request', 401: 'Unauthorized', 403: 'Forbidden', 404: 'Not Found', 405: 'Method Not Allowed', 422: 'Validation Error', 429: 'Too Many Requests', 500: 'Internal Server Error', 503: 'Service Unavailable' }[status] || 'Error ' + status;
}

function copyResponse() {
    const el = document.getElementById('response-body');
    const code = el.querySelector('code') || el;
    copyWithFeedback('copy-response-btn', code.textContent);
}

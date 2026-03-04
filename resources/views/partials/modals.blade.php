{{-- Modal Dialogs --}}

{{-- Auth Modal --}}
<div id="auth-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-700">
        <div class="p-5 lg:p-6">
            <h3 class="text-lg font-bold mb-3">Set Bearer Token</h3>
            <p class="text-sm text-slate-400 mb-4">Enter your Laravel Sanctum/Passport token for authenticated requests.</p>
            <input type="password" id="auth-token" placeholder="1|abc123xyz..." class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono mb-4 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <button onclick="saveToken()" class="flex-1 px-4 py-2.5 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 font-medium transition-colors text-sm">Save Token</button>
                <button onclick="clearToken()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-sm">Clear</button>
                <button onclick="closeAuthModal()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>

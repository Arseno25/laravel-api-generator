{{-- Modal Dialogs --}}

{{-- Auth Modal --}}
<div id="auth-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-700">
        <div class="p-5 lg:p-6">
            <h3 class="text-lg font-bold mb-3">Authentication Settings</h3>
            <p class="text-sm text-slate-400 mb-4">Configure how requests are authenticated.</p>

            <div class="mb-5 bg-slate-900 border border-slate-700 rounded-lg p-3 flex items-center gap-3">
                <input type="checkbox" id="auth-sanctum-cookie" class="w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800">
                <div>
                    <span class="block text-sm font-medium text-slate-200">Use Sanctum SPA Cookie Auth</span>
                    <span class="block text-xs text-slate-400">Fetch CSRF and include credentials</span>
                </div>
            </div>

            <div id="oauth2-section" class="mb-5">
                <button onclick="startOAuthLogin()" type="button" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-800 border border-slate-600 rounded-lg text-sm font-medium hover:bg-slate-700 transition-colors text-slate-200">
                    <i class="fab fa-openid text-indigo-400 text-lg"></i>
                    <span>Login with OAuth 2.0</span>
                </button>
            </div>

            <div class="mb-4 relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-700"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-slate-800 px-2 text-slate-500">OR BEARER TOKEN</span>
                </div>
            </div>

            <input type="password" id="auth-token" placeholder="1|abc123xyz..." class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono mb-4 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <button onclick="saveToken()" class="flex-1 px-4 py-2.5 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 font-medium transition-colors text-sm">Save Settings</button>
                <button onclick="clearToken()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-sm">Clear</button>
                <button onclick="closeAuthModal()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>

{{-- Styles for API Documentation --}}
<style type="text/tailwindcss">
    * { font-family: 'Inter', sans-serif; }
    code, pre, .font-mono { font-family: 'JetBrains Mono', monospace !important; }

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #1e293b; }
    ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }

    .method-badge {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        flex-shrink: 0;
        backdrop-filter: blur(4px);
        white-space: nowrap;
    }
    .method-get { @apply bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-[0_0_10px_rgba(59,130,246,0.1)]; }
    .method-post { @apply bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_10px_rgba(16,185,129,0.1)]; }
    .method-put { @apply bg-orange-500/10 text-orange-400 border border-orange-500/20 shadow-[0_0_10px_rgba(249,115,22,0.1)]; }
    .method-patch { @apply bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 shadow-[0_0_10px_rgba(234,179,8,0.1)]; }
    .method-delete { @apply bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-[0_0_10px_rgba(244,63,94,0.1)]; }

    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        letter-spacing: 0.025em;
        backdrop-filter: blur(4px);
        white-space: nowrap;
    }
    .status-2xx { @apply bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_10px_rgba(16,185,129,0.1)]; }
    .status-3xx { @apply bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-[0_0_10px_rgba(59,130,246,0.1)]; }
    .status-4xx { @apply bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 shadow-[0_0_10px_rgba(234,179,8,0.1)]; }
    .status-5xx { @apply bg-rose-500/10 text-rose-400 border border-rose-500/20 shadow-[0_0_10px_rgba(244,63,94,0.1)]; }

    .sidebar-item { transition: all 0.15s ease; }
    .sidebar-item:hover { background: rgba(99, 102, 241, 0.1); }
    .sidebar-item.active { background: rgba(99, 102, 241, 0.15); border-left: 2px solid #6366f1; }

    .error-card {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
        border: 1px solid rgba(239, 68, 68, 0.2);
        border-radius: 1rem;
    }
    .error-icon-wrapper {
        width: 3rem; height: 3rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.75rem;
        background: rgba(239, 68, 68, 0.15);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }
    .success-card {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 1rem;
    }
    .success-icon-wrapper {
        width: 3rem; height: 3rem;
        display: flex; align-items: center; justify-content: center;
        border-radius: 0.75rem;
        background: rgba(34, 197, 94, 0.15);
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
    }
    .code-inline {
        background: rgba(0, 0, 0, 0.3);
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
    }

    .toast {
        animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.2s forwards;
    }
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    /* Mobile sidebar overlay */
    .sidebar-overlay { transition: opacity 0.3s ease; }
    .sidebar-panel { transition: transform 0.3s ease; }

    .group-header { cursor: pointer; user-select: none; }
    .group-header:hover { background: rgba(255, 255, 255, 0.03); }

    /* Sidebar footer scroll fix */
    .sidebar-footer {
        flex-shrink: 0;
        overflow: hidden;
    }
</style>

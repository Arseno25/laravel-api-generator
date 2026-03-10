{{-- Styles for API Documentation --}}
<style>
    :root {
        --api-magic-bg: #020617;
        --api-magic-panel: rgba(15, 23, 42, 0.78);
        --api-magic-border: rgba(148, 163, 184, 0.12);
        --api-magic-cyan: #22d3ee;
        --api-magic-blue: #3b82f6;
        --api-magic-indigo: #818cf8;
    }

    * { font-family: "Segoe UI", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; }
    code, pre, .font-mono { font-family: "Cascadia Code", "SFMono-Regular", ui-monospace, Monaco, Consolas, "Liberation Mono", monospace !important; }
    body { background: var(--api-magic-bg); }

    .am-icon {
        width: 1em;
        height: 1em;
        display: inline-block;
        flex-shrink: 0;
        vertical-align: -0.125em;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.85;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

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
    .method-get {
        background: rgba(59, 130, 246, 0.1);
        color: rgb(96, 165, 250);
        border: 1px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.1);
    }
    .method-post {
        background: rgba(16, 185, 129, 0.1);
        color: rgb(52, 211, 153);
        border: 1px solid rgba(16, 185, 129, 0.2);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
    }
    .method-put {
        background: rgba(249, 115, 22, 0.1);
        color: rgb(251, 146, 60);
        border: 1px solid rgba(249, 115, 22, 0.2);
        box-shadow: 0 0 10px rgba(249, 115, 22, 0.1);
    }
    .method-patch {
        background: rgba(234, 179, 8, 0.1);
        color: rgb(234, 179, 8);
        border: 1px solid rgba(234, 179, 8, 0.2);
        box-shadow: 0 0 10px rgba(234, 179, 8, 0.1);
    }
    .method-delete {
        background: rgba(244, 63, 94, 0.1);
        color: rgb(251, 113, 133);
        border: 1px solid rgba(244, 63, 94, 0.2);
        box-shadow: 0 0 10px rgba(244, 63, 94, 0.1);
    }

    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        letter-spacing: 0.025em;
        backdrop-filter: blur(4px);
        white-space: nowrap;
    }
    .status-2xx {
        background: rgba(16, 185, 129, 0.1);
        color: rgb(52, 211, 153);
        border: 1px solid rgba(16, 185, 129, 0.2);
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
    }
    .status-3xx {
        background: rgba(59, 130, 246, 0.1);
        color: rgb(96, 165, 250);
        border: 1px solid rgba(59, 130, 246, 0.2);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.1);
    }
    .status-4xx {
        background: rgba(234, 179, 8, 0.1);
        color: rgb(234, 179, 8);
        border: 1px solid rgba(234, 179, 8, 0.2);
        box-shadow: 0 0 10px rgba(234, 179, 8, 0.1);
    }
    .status-5xx {
        background: rgba(244, 63, 94, 0.1);
        color: rgb(251, 113, 133);
        border: 1px solid rgba(244, 63, 94, 0.2);
        box-shadow: 0 0 10px rgba(244, 63, 94, 0.1);
    }

    .sidebar-item { transition: all 0.18s ease; }
    .sidebar-item:hover {
        background: linear-gradient(90deg, rgba(34, 211, 238, 0.08), rgba(59, 130, 246, 0.05));
        border-color: rgba(34, 211, 238, 0.12);
        transform: translateX(2px);
    }
    .sidebar-item.active {
        background: linear-gradient(90deg, rgba(34, 211, 238, 0.16), rgba(59, 130, 246, 0.08));
        border-color: rgba(34, 211, 238, 0.22);
        box-shadow: inset 3px 0 0 rgba(34, 211, 238, 0.9);
    }

    .glass-panel {
        background: var(--api-magic-panel);
        border: 1px solid var(--api-magic-border);
        backdrop-filter: blur(18px);
    }

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
    .group-header:hover { background: rgba(255, 255, 255, 0.04); }

    /* Sidebar footer scroll fix */
    .sidebar-footer {
        flex-shrink: 0;
        overflow: hidden;
    }

    .hero-shine {
        position: relative;
        overflow: hidden;
    }

    .hero-shine::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(110deg, transparent 25%, rgba(255, 255, 255, 0.05) 50%, transparent 75%);
        transform: translateX(-120%);
        animation: shimmer 10s linear infinite;
        pointer-events: none;
    }

    @keyframes shimmer {
        to { transform: translateX(120%); }
    }
</style>

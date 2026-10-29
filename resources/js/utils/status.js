// Single source of truth for complaint status presentation.
export const STATUS_META = {
    pending: { label: 'Pending', color: '#f59e0b', badge: 'bg-amber-50 text-amber-700 ring-amber-600/20', dot: 'bg-amber-500' },
    in_progress: { label: 'In Progress', color: '#3b82f6', badge: 'bg-blue-50 text-blue-700 ring-blue-600/20', dot: 'bg-blue-500' },
    resolved: { label: 'Resolved', color: '#22c55e', badge: 'bg-green-50 text-green-700 ring-green-600/20', dot: 'bg-green-500' },
    rejected: { label: 'Rejected', color: '#ef4444', badge: 'bg-red-50 text-red-700 ring-red-600/20', dot: 'bg-red-500' },
};

export const STATUS_OPTIONS = Object.entries(STATUS_META).map(([value, meta]) => ({ value, label: meta.label }));

export function statusLabel(status) {
    return STATUS_META[status]?.label || String(status || '').replace(/_/g, ' ');
}

export function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Laravel resource collections nest pagination under `meta`; plain paginators are flat.
export function pageMeta(payload) {
    const meta = payload?.meta ?? payload ?? {};
    return {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? 0,
        from: meta.from ?? null,
        to: meta.to ?? null,
    };
}

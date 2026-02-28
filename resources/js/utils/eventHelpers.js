/**
 * Shared helpers for event display across Admin and Student dashboards.
 */

export function getEventBadgeClass(daysUntil) {
    if (daysUntil === 0) return 'bg-red-100 text-red-800';
    if (daysUntil <= 3) return 'bg-orange-100 text-orange-800';
    if (daysUntil <= 7) return 'bg-yellow-100 text-yellow-800';
    return 'bg-blue-100 text-blue-800';
}

export function getEventLabel(daysUntil) {
    if (daysUntil === 0) return 'Today';
    if (daysUntil === 1) return 'Tomorrow';
    return `In ${daysUntil} days`;
}

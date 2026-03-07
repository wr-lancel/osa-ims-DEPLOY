/**
 * Format slug/code values for display in the UI.
 * e.g. "sports_admin" -> "Sports Admin", "active" -> "Active"
 */

export function formatLabel(value) {
    if (value == null || value === '') {
        return '';
    }
    const str = String(value).trim();
    if (!str) return '';
    return str.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

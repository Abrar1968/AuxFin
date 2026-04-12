const DEFAULT_LOCALE = 'en-US';

export function formatCurrency(value, options = {}) {
    const amount = toFiniteNumber(value, 0);

    return new Intl.NumberFormat(DEFAULT_LOCALE, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options,
    }).format(amount);
}

export function formatNumber(value, options = {}) {
    const amount = toFiniteNumber(value, 0);

    return new Intl.NumberFormat(DEFAULT_LOCALE, {
        maximumFractionDigits: 2,
        ...options,
    }).format(amount);
}

export function formatPercent(value, { decimals = 2, signed = false } = {}) {
    const amount = toFiniteNumber(value, 0);
    const formatted = `${amount.toFixed(decimals)}%`;

    if (signed && amount > 0) {
        return `+${formatted}`;
    }

    return formatted;
}

export function formatDate(value, options = {}) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...options,
    }).format(date);
}

export function formatMonth(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, {
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export function humanizeKey(value) {
    return String(value ?? '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
        .trim();
}

export function maskAccountNumber(value, visibleDigits = 4) {
    const raw = String(value ?? '').replaceAll(' ', '');
    if (!raw) {
        return '-';
    }

    if (raw.length <= visibleDigits) {
        return raw;
    }

    const hiddenLength = raw.length - visibleDigits;
    return `${'*'.repeat(hiddenLength)}${raw.slice(-visibleDigits)}`;
}

function toFiniteNumber(value, fallback) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

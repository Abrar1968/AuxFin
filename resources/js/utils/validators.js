export function required(message = 'This field is required.') {
    return (value) => {
        const normalized = String(value ?? '').trim();
        return normalized ? '' : message;
    };
}

export function validEmail(message = 'Please enter a valid email address.') {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return (value) => {
        const normalized = String(value ?? '').trim();
        if (!normalized) {
            return '';
        }

        return regex.test(normalized) ? '' : message;
    };
}

export function minNumber(min, message = `Value must be at least ${min}.`) {
    return (value) => {
        const parsed = Number(value);
        if (!Number.isFinite(parsed)) {
            return 'Please enter a valid number.';
        }

        return parsed >= min ? '' : message;
    };
}

export function optionalMinNumber(min, message = `Value must be at least ${min}.`) {
    return (value) => {
        if (value === '' || value === null || value === undefined) {
            return '';
        }

        return minNumber(min, message)(value);
    };
}

export function integerRange(min, max, message = `Value must be between ${min} and ${max}.`) {
    return (value) => {
        const parsed = Number(value);
        if (!Number.isInteger(parsed)) {
            return 'Please enter a whole number.';
        }

        return parsed >= min && parsed <= max ? '' : message;
    };
}

export function optionalIntegerRange(min, max, message = `Value must be between ${min} and ${max}.`) {
    return (value) => {
        if (value === '' || value === null || value === undefined) {
            return '';
        }

        return integerRange(min, max, message)(value);
    };
}

export function validateFields(values, schema) {
    const errors = {};

    for (const [field, rules] of Object.entries(schema)) {
        const value = values[field];
        const message = runFieldValidation(value, rules);
        if (message) {
            errors[field] = message;
        }
    }

    return errors;
}

export function toNumberOrNull(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
}

function runFieldValidation(value, rules) {
    for (const rule of rules ?? []) {
        const result = rule(value);
        if (result) {
            return result;
        }
    }

    return '';
}

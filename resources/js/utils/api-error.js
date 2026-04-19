export function getApiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
    const responseData = error?.response?.data;

    if (typeof responseData?.message === 'string' && responseData.message.trim() !== '') {
        return responseData.message;
    }

    const errors = responseData?.errors;
    if (errors && typeof errors === 'object') {
        const firstKey = Object.keys(errors)[0];
        const firstError = firstKey ? errors[firstKey]?.[0] : null;
        if (typeof firstError === 'string' && firstError.trim() !== '') {
            return firstError;
        }
    }

    if (typeof error?.message === 'string' && error.message.trim() !== '') {
        return error.message;
    }

    return fallback;
}

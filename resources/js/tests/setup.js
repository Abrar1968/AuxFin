import { beforeEach, vi } from 'vitest';

beforeEach(() => {
    localStorage.clear();
    sessionStorage.clear();

    if (!window.configureEchoAuth) {
        window.configureEchoAuth = vi.fn();
    }
});

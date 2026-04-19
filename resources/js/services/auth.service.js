import api from './api.service';

export const AuthService = {
    login: (email, passkey) => api.post('/auth/login', { email, passkey }),
    logout: () => api.post('/auth/logout'),
    me: () => api.get('/auth/me'),
    changePasskey: (payload) => api.post('/auth/change-passkey', payload),
};

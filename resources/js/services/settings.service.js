import api from './api.service';

export const SettingsService = {
    getLatePolicy: () => api.get('/admin/settings/late-policy'),
    updateLatePolicy: (payload) => api.put('/admin/settings/late-policy', payload),
    getLoanPolicy: () => api.get('/admin/settings/loan-policy'),
    updateLoanPolicy: (payload) => api.put('/admin/settings/loan-policy', payload),
    holidays: (params = {}) => api.get('/admin/settings/holidays', { params }),
    createHoliday: (payload) => api.post('/admin/settings/holidays', payload),
    deleteHoliday: (id) => api.delete(`/admin/settings/holidays/${id}`),
};

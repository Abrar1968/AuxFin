import api from './api.service';

export const SettingsService = {
    getGeneral: () => api.get('/admin/settings/general'),
    updateGeneral: (payload) => api.put('/admin/settings/general', payload),
    getLatePolicy: () => api.get('/admin/settings/late-policy'),
    updateLatePolicy: (payload) => api.put('/admin/settings/late-policy', payload),
    getLoanPolicy: () => api.get('/admin/settings/loan-policy'),
    updateLoanPolicy: (payload) => api.put('/admin/settings/loan-policy', payload),
    getTaxPolicy: () => api.get('/admin/settings/tax-policy'),
    updateTaxPolicy: (payload) => api.put('/admin/settings/tax-policy', payload),
    holidays: (params = {}) => api.get('/admin/settings/holidays', { params }),
    createHoliday: (payload) => api.post('/admin/settings/holidays', payload),
    deleteHoliday: (id) => api.delete(`/admin/settings/holidays/${id}`),
};

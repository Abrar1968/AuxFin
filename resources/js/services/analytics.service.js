import api from './api.service';

export const AnalyticsService = {
    overview: () => api.get('/admin/analytics/overview'),
    cmgr: () => api.get('/admin/analytics/cmgr'),
    forecast: () => api.get('/admin/analytics/forecast'),
    anomalies: () => api.get('/admin/analytics/anomalies'),
    burnRate: (availableCash = 0) => api.get('/admin/analytics/burn-rate', { params: { available_cash: availableCash } }),
    arHealth: () => api.get('/admin/analytics/ar-health'),
    growth: () => api.get('/admin/analytics/growth'),
};

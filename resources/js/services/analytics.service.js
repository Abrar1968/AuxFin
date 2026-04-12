import api from './api.service';

export const AnalyticsService = {
    overview: () => api.get('/admin/analytics/overview'),
    cmgr: () => api.get('/admin/analytics/cmgr'),
    forecast: () => api.get('/admin/analytics/forecast'),
    anomalies: () => api.get('/admin/analytics/anomalies'),
    growth: () => api.get('/admin/analytics/growth'),
};

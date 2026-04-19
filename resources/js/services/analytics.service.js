import api from './api.service';

export const AnalyticsService = {
    overview: (params = {}) => api.get('/admin/analytics/overview', { params }),
    cmgr: (params = {}) => api.get('/admin/analytics/cmgr', { params }),
    forecast: (params = {}) => api.get('/admin/analytics/forecast', { params }),
    anomalies: (params = {}) => api.get('/admin/analytics/anomalies', { params }),
    burnRate: (availableCash = 0, params = {}) => api.get('/admin/analytics/burn-rate', {
        params: {
            available_cash: availableCash,
            ...params,
        },
    }),
    arHealth: (params = {}) => api.get('/admin/analytics/ar-health', { params }),
    growth: (params = {}) => api.get('/admin/analytics/growth', { params }),
};

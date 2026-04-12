import api from './api.service';

export const ReportService = {
    profitLoss: (params = {}) => api.get('/admin/reports/profit-loss', { params }),
    taxSummary: (params = {}) => api.get('/admin/reports/tax-summary', { params }),
    arAging: (params = {}) => api.get('/admin/reports/ar-aging', { params }),
};

import api from './api.service';

export const ReportService = {
    profitLoss: (params = {}) => api.get('/admin/reports/profit-loss', { params }),
    taxSummary: (params = {}) => api.get('/admin/reports/tax-summary', { params }),
    arAging: (params = {}) => api.get('/admin/reports/ar-aging', { params }),
    trialBalance: (params = {}) => api.get('/admin/reports/trial-balance', { params }),
    balanceSheet: (params = {}) => api.get('/admin/reports/balance-sheet', { params }),
    cashFlow: (params = {}) => api.get('/admin/reports/cash-flow', { params }),
    generalLedger: (params = {}) => api.get('/admin/reports/general-ledger', { params }),
    paymentLedger: (params = {}) => api.get('/admin/reports/payment-ledger', { params }),
};

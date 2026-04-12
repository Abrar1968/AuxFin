import api from './api.service';

export const FinanceService = {
    overview: (params = {}) => api.get('/admin/finance/overview', { params }),

    clients: (params = {}) => api.get('/admin/clients', { params }),
    createClient: (payload) => api.post('/admin/clients', payload),
    updateClient: (id, payload) => api.put(`/admin/clients/${id}`, payload),
    deleteClient: (id) => api.delete(`/admin/clients/${id}`),

    projects: (params = {}) => api.get('/admin/projects', { params }),
    createProject: (payload) => api.post('/admin/projects', payload),
    updateProject: (id, payload) => api.put(`/admin/projects/${id}`, payload),
    deleteProject: (id) => api.delete(`/admin/projects/${id}`),
    projectRevenue: (id) => api.get(`/admin/projects/${id}/revenue`),

    projectInvoices: (projectId, params = {}) => api.get(`/admin/projects/${projectId}/invoices`, { params }),
    createInvoice: (projectId, payload) => api.post(`/admin/projects/${projectId}/invoices`, payload),
    updateInvoice: (projectId, id, payload) => api.put(`/admin/projects/${projectId}/invoices/${id}`, payload),
    deleteInvoice: (projectId, id) => api.delete(`/admin/projects/${projectId}/invoices/${id}`),
    transitionInvoice: (projectId, id, payload) => api.post(`/admin/projects/${projectId}/invoices/${id}/status`, payload),

    expenses: (params = {}) => api.get('/admin/expenses', { params }),
    expenseSummary: (params = {}) => api.get('/admin/expenses-summary', { params }),
    createExpense: (payload) => api.post('/admin/expenses', payload),
    updateExpense: (id, payload) => api.put(`/admin/expenses/${id}`, payload),
    deleteExpense: (id) => api.delete(`/admin/expenses/${id}`),

    liabilities: (params = {}) => api.get('/admin/liabilities', { params }),
    liabilitiesDueSoon: (params = {}) => api.get('/admin/liabilities-due-soon', { params }),
    createLiability: (payload) => api.post('/admin/liabilities', payload),
    updateLiability: (id, payload) => api.put(`/admin/liabilities/${id}`, payload),
    processLiabilityPayment: (id, payload = {}) => api.post(`/admin/liabilities/${id}/process-payment`, payload),
    deleteLiability: (id) => api.delete(`/admin/liabilities/${id}`),

    assets: (params = {}) => api.get('/admin/assets', { params }),
    createAsset: (payload) => api.post('/admin/assets', payload),
    updateAsset: (id, payload) => api.put(`/admin/assets/${id}`, payload),
    depreciateAsset: (id) => api.post(`/admin/assets/${id}/depreciate`),
    deleteAsset: (id) => api.delete(`/admin/assets/${id}`),
};

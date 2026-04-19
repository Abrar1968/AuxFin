import api from './api.service';

export const LoanService = {
    adminList: (params = {}) => api.get('/admin/loans', { params }),
    adminCreate: (payload) => api.post('/admin/loans', payload),
    adminGet: (id) => api.get(`/admin/loans/${id}`),
    adminUpdate: (id, payload) => api.put(`/admin/loans/${id}`, payload),
    adminDelete: (id) => api.delete(`/admin/loans/${id}`),
    approve: (id, payload) => api.post(`/admin/loans/${id}/approve`, payload),
    reject: (id, payload) => api.post(`/admin/loans/${id}/reject`, payload),
    myList: () => api.get('/employee/loans'),
    myGet: (id) => api.get(`/employee/loans/${id}`),
    myPolicy: () => api.get('/employee/loans/policy'),
    apply: (payload) => api.post('/employee/loans/apply', payload),
};

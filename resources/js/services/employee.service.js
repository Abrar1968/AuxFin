import api from './api.service';

export const EmployeeService = {
    list: (params = {}) => api.get('/admin/employees', { params }),
    show: (id) => api.get(`/admin/employees/${id}`),
    create: (payload) => api.post('/admin/employees', payload),
    update: (id, payload) => api.put(`/admin/employees/${id}`, payload),
    remove: (id) => api.delete(`/admin/employees/${id}`),
    resetPasskey: (id) => api.post(`/admin/employees/${id}/reset-passkey`),
    dashboard: () => api.get('/employee/dashboard'),
};

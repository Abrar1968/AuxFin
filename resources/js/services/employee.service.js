import api from './api.service';

export const EmployeeService = {
    departments: (params = {}) => api.get('/admin/departments', { params }),
    createDepartment: (payload) => api.post('/admin/departments', payload),
    updateDepartment: (id, payload) => api.put(`/admin/departments/${id}`, payload),
    removeDepartment: (id) => api.delete(`/admin/departments/${id}`),
    list: (params = {}) => api.get('/admin/employees', { params }),
    show: (id) => api.get(`/admin/employees/${id}`),
    create: (payload) => api.post('/admin/employees', payload),
    update: (id, payload) => api.put(`/admin/employees/${id}`, payload),
    remove: (id) => api.delete(`/admin/employees/${id}`),
    resetPasskey: (id) => api.post(`/admin/employees/${id}/reset-passkey`),
    dashboard: () => api.get('/employee/dashboard'),
};

import api from './api.service';

export const LeaveService = {
    adminList: (params = {}) => api.get('/admin/leaves', { params }),
    adminCreate: (payload) => api.post('/admin/leaves', payload),
    adminShow: (id) => api.get(`/admin/leaves/${id}`),
    adminUpdate: (id, payload) => api.put(`/admin/leaves/${id}`, payload),
    adminDelete: (id) => api.delete(`/admin/leaves/${id}`),
    decide: (id, payload) => api.post(`/admin/leaves/${id}/decision`, payload),
    myList: () => api.get('/employee/leaves'),
    apply: (payload) => api.post('/employee/leaves/apply', payload),
};

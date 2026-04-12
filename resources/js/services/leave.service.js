import api from './api.service';

export const LeaveService = {
    adminList: (params = {}) => api.get('/admin/leaves', { params }),
    decide: (id, payload) => api.post(`/admin/leaves/${id}/decision`, payload),
    myList: () => api.get('/employee/leaves'),
    apply: (payload) => api.post('/employee/leaves/apply', payload),
};

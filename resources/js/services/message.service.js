import api from './api.service';

export const MessageService = {
    adminList: (params = {}) => api.get('/admin/messages', { params }),
    adminCreate: (payload) => api.post('/admin/messages', payload),
    adminShow: (id) => api.get(`/admin/messages/${id}`),
    adminDelete: (id) => api.delete(`/admin/messages/${id}`),
    adminMarkAllRead: () => api.post('/admin/messages/mark-all-read'),
    adminReply: (id, payload) => api.post(`/admin/messages/${id}/reply`, payload),
    adminResolve: (id) => api.post(`/admin/messages/${id}/resolve`),
    adminReject: (id, payload) => api.post(`/admin/messages/${id}/reject`, payload),
    inbox: (params = {}) => api.get('/employee/messages', { params }),
    inboxShow: (id) => api.get(`/employee/messages/${id}`),
    inboxMarkAllRead: () => api.post('/employee/messages/mark-all-read'),
    create: (payload) => api.post('/employee/messages', payload),
};

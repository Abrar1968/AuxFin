import api from './api.service';

export const AttendanceService = {
    employeeList: (params = {}) => api.get('/employee/attendance', { params }),
    adminMonth: (params) => api.get('/admin/attendance', { params }),
    adminUpsert: (payload) => api.post('/admin/attendance', payload),
};

import api from './api.service';

export const PayrollService = {
    getMonth: (month) => api.get(`/admin/payroll/${month}`),
    adminPayslip: (id) => api.get(`/admin/payroll/${id}/payslip`),
    process: (payload) => api.post('/admin/payroll/process', payload),
    bulkProcess: (month) => api.post('/admin/payroll/bulk-process', { month }),
    update: (id, payload) => api.put(`/admin/payroll/${id}`, payload),
    markPaid: (id) => api.post(`/admin/payroll/${id}/mark-paid`),
    mySalary: (params = {}) => api.get('/employee/salary', { params }),
    getPayslip: (month) => api.get(`/employee/salary/${month}/payslip`),
    getPayslipPdfPayload: (month) => api.get(`/employee/salary/${month}/payslip/pdf`),
};

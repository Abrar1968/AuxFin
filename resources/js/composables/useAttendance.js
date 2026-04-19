import api from '../services/api.service';

export function useAttendance() {
    const fetchAttendance = (month) => api.get('/employee/attendance', { params: { month } });
    return { fetchAttendance };
}

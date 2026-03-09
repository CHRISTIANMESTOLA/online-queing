import { api } from 'boot/axios'

const officeService = {
  getPublicOffices() {
    return api.get('/offices/public')
  },

  getAdminOffices() {
    return api.get('/admin/offices')
  },

  createOffice(payload) {
    return api.post('/admin/offices', payload)
  },

  updateOffice(officeId, payload) {
    return api.put(`/admin/offices/${officeId}`, payload)
  },

  deleteOffice(officeId) {
    return api.delete(`/admin/offices/${officeId}`)
  },

  assignStaff(officeId, userId) {
    return api.post(`/admin/offices/${officeId}/staff`, { user_id: userId })
  },

  unassignStaff(officeId, userId) {
    return api.delete(`/admin/offices/${officeId}/staff/${userId}`)
  },

  getStaffUsers() {
    return api.get('/admin/staff')
  },

  createStaff(payload) {
    return api.post('/admin/staff', payload)
  },

  updateStaff(staffId, payload) {
    return api.put(`/admin/staff/${staffId}`, payload)
  },

  deleteStaff(staffId) {
    return api.delete(`/admin/staff/${staffId}`)
  },
}

export default officeService

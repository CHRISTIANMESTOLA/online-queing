import { api } from 'boot/axios'

const queueService = {
  generateQueue(officeId) {
    return api.post('/queues/generate', { office_id: officeId })
  },

  getMonitor(officeId = null) {
    if (officeId) {
      return api.get('/queues/monitor', { params: { office_id: officeId } })
    }

    return api.get('/queues/monitor')
  },

  getStaffOffices() {
    return api.get('/staff/offices')
  },

  getStaffDashboard(officeId) {
    return api.get(`/staff/offices/${officeId}/queue`)
  },

  callNext(officeId) {
    return api.post(`/staff/offices/${officeId}/call-next`)
  },

  markServing(queueTicketId) {
    return api.patch(`/staff/queues/${queueTicketId}/serving`)
  },

  markDone(queueTicketId) {
    return api.patch(`/staff/queues/${queueTicketId}/done`)
  },

  skip(queueTicketId) {
    return api.patch(`/staff/queues/${queueTicketId}/skip`)
  },
}

export default queueService

import { defineStore } from 'pinia'
import queueService from 'src/services/queue.service'
import { getErrorMessage } from 'src/utils/error'

export const useQueueStore = defineStore('queue', {
  state: () => ({
    generatedTicket: null,
    monitorItems: [],
    monitorLoading: false,
    ticketLoading: false,
    staffOffices: [],
    staffDashboard: null,
    staffLoading: false,
    staffActionLoading: false,
  }),

  actions: {
    async generateQueue(officeId) {
      this.ticketLoading = true

      try {
        const response = await queueService.generateQueue(officeId)
        this.generatedTicket = response.data?.data?.queue_ticket || null

        return this.generatedTicket
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to generate queue number.'))
      } finally {
        this.ticketLoading = false
      }
    },

    async fetchMonitor(officeId = null) {
      this.monitorLoading = true

      try {
        const response = await queueService.getMonitor(officeId)
        const payload = response.data?.data

        this.monitorItems = Array.isArray(payload) ? payload : payload ? [payload] : []
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to fetch monitor data.'))
      } finally {
        this.monitorLoading = false
      }
    },

    async fetchStaffOffices() {
      this.staffLoading = true

      try {
        const response = await queueService.getStaffOffices()
        this.staffOffices = response.data?.data || []
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to fetch assigned offices.'))
      } finally {
        this.staffLoading = false
      }
    },

    async fetchStaffDashboard(officeId) {
      this.staffLoading = true

      try {
        const response = await queueService.getStaffDashboard(officeId)
        this.staffDashboard = response.data?.data || null
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to fetch staff dashboard.'))
      } finally {
        this.staffLoading = false
      }
    },

    async callNext(officeId) {
      this.staffActionLoading = true

      try {
        await queueService.callNext(officeId)
        await this.fetchStaffDashboard(officeId)
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to call next queue.'))
      } finally {
        this.staffActionLoading = false
      }
    },

    async markServing(queueTicketId, officeId) {
      this.staffActionLoading = true

      try {
        await queueService.markServing(queueTicketId)
        await this.fetchStaffDashboard(officeId)
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to mark queue as serving.'))
      } finally {
        this.staffActionLoading = false
      }
    },

    async markDone(queueTicketId, officeId) {
      this.staffActionLoading = true

      try {
        await queueService.markDone(queueTicketId)
        await this.fetchStaffDashboard(officeId)
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to mark queue as done.'))
      } finally {
        this.staffActionLoading = false
      }
    },

    async skip(queueTicketId, officeId) {
      this.staffActionLoading = true

      try {
        await queueService.skip(queueTicketId)
        await this.fetchStaffDashboard(officeId)
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to skip queue number.'))
      } finally {
        this.staffActionLoading = false
      }
    },
  },
})

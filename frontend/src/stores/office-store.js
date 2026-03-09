import { defineStore } from 'pinia'
import officeService from 'src/services/office.service'
import { getErrorMessage } from 'src/utils/error'

export const useOfficeStore = defineStore('office', {
  state: () => ({
    publicOffices: [],
    adminOffices: [],
    staffUsers: [],
    loading: false,
    saving: false,
    error: null,
  }),

  actions: {
    async fetchPublicOffices() {
      this.loading = true
      this.error = null

      try {
        const response = await officeService.getPublicOffices()
        this.publicOffices = response.data?.data || []
      } catch (error) {
        this.error = getErrorMessage(error, 'Failed to load offices.')
        throw new Error(this.error)
      } finally {
        this.loading = false
      }
    },

    async fetchAdminOffices() {
      this.loading = true
      this.error = null

      try {
        const response = await officeService.getAdminOffices()
        this.adminOffices = response.data?.data || []
      } catch (error) {
        this.error = getErrorMessage(error, 'Failed to load admin offices.')
        throw new Error(this.error)
      } finally {
        this.loading = false
      }
    },

    async createOffice(payload) {
      this.saving = true

      try {
        await officeService.createOffice(payload)
        await this.fetchAdminOffices()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to create office.'))
      } finally {
        this.saving = false
      }
    },

    async updateOffice(officeId, payload) {
      this.saving = true

      try {
        await officeService.updateOffice(officeId, payload)
        await this.fetchAdminOffices()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to update office.'))
      } finally {
        this.saving = false
      }
    },

    async deleteOffice(officeId) {
      this.saving = true

      try {
        await officeService.deleteOffice(officeId)
        await this.fetchAdminOffices()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to delete office.'))
      } finally {
        this.saving = false
      }
    },

    async fetchStaffUsers() {
      this.loading = true

      try {
        const response = await officeService.getStaffUsers()
        this.staffUsers = response.data?.data || []
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to load staff users.'))
      } finally {
        this.loading = false
      }
    },

    async createStaff(payload) {
      this.saving = true

      try {
        await officeService.createStaff(payload)
        await this.fetchStaffUsers()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to create staff account.'))
      } finally {
        this.saving = false
      }
    },

    async updateStaff(staffId, payload) {
      this.saving = true

      try {
        await officeService.updateStaff(staffId, payload)
        await this.fetchStaffUsers()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to update staff account.'))
      } finally {
        this.saving = false
      }
    },

    async deleteStaff(staffId) {
      this.saving = true

      try {
        await officeService.deleteStaff(staffId)
        await this.fetchStaffUsers()
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to delete staff account.'))
      } finally {
        this.saving = false
      }
    },

    async assignStaffToOffice(officeId, userId) {
      this.saving = true

      try {
        await officeService.assignStaff(officeId, userId)
        await Promise.all([this.fetchAdminOffices(), this.fetchStaffUsers()])
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to assign staff to office.'))
      } finally {
        this.saving = false
      }
    },

    async unassignStaffFromOffice(officeId, userId) {
      this.saving = true

      try {
        await officeService.unassignStaff(officeId, userId)
        await Promise.all([this.fetchAdminOffices(), this.fetchStaffUsers()])
      } catch (error) {
        throw new Error(getErrorMessage(error, 'Failed to unassign staff from office.'))
      } finally {
        this.saving = false
      }
    },
  },
})

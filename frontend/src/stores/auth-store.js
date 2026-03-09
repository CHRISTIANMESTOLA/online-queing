import { defineStore } from 'pinia'
import authService from 'src/services/auth.service'
import { getErrorMessage } from 'src/utils/error'

const TOKEN_KEY = 'qserve_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY),
    user: null,
    loading: false,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),
    role: (state) => state.user?.role || null,
    isAdmin: (state) => state.user?.role === 'admin',
    isStaff: (state) => state.user?.role === 'staff',
  },

  actions: {
    setToken(token) {
      this.token = token

      if (token) {
        localStorage.setItem(TOKEN_KEY, token)
      } else {
        localStorage.removeItem(TOKEN_KEY)
      }
    },

    clearSession() {
      this.setToken(null)
      this.user = null
    },

    async initialize() {
      const storedToken = localStorage.getItem(TOKEN_KEY)

      if (storedToken !== this.token) {
        this.token = storedToken
      }

      if (this.token) {
        try {
          await this.fetchMe()
        } catch {
          this.clearSession()
        }
      } else {
        this.user = null
      }

      this.initialized = true
    },

    async login(credentials) {
      this.loading = true

      try {
        const response = await authService.login(credentials)
        this.setToken(response.data?.data?.token || null)
        this.user = response.data?.data?.user || null
        this.initialized = true

        return this.user
      } catch (error) {
        this.clearSession()
        throw new Error(getErrorMessage(error, 'Failed to login.'))
      } finally {
        this.loading = false
      }
    },

    async fetchMe() {
      if (!this.token) {
        this.user = null
        return null
      }

      const response = await authService.me()
      this.user = response.data?.data || null

      return this.user
    },

    async logout() {
      try {
        if (this.token) {
          await authService.logout()
        }
      } catch {
        // Ignore logout API errors and clear local session anyway.
      } finally {
        this.clearSession()
      }
    },
  },
})

import { api } from 'boot/axios'

const authService = {
  login(payload) {
    return api.post('/auth/login', payload)
  },

  me() {
    return api.get('/auth/me')
  },

  logout() {
    return api.post('/auth/logout')
  },
}

export default authService

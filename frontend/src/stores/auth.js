import { defineStore } from 'pinia'
import api from '@/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user:  JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
  }),

  getters: {
    isLoggedIn:   state => !!state.token,
    isAdmin:      state => state.user?.role === 'admin',
    isInstructor: state => state.user?.role === 'instructor',
    isStudent:    state => state.user?.role === 'student',
    userRole:     state => state.user?.role || '',
  },

  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.token = data.token
      this.user  = data.user
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    },

    async logout() {
      try { await api.post('/logout') } catch (_) {}
      this.token = null
      this.user  = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },

    async changePassword(currentPassword, newPassword, confirmation) {
      await api.post('/change-password', {
        current_password:           currentPassword,
        new_password:               newPassword,
        new_password_confirmation:  confirmation,
      })
    },
  },
})

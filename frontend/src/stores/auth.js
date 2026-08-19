import { defineStore } from 'pinia'
import authApi from '@/api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    accessToken: localStorage.getItem('access_token') || null,
    refreshToken: localStorage.getItem('refresh_token') || null,
    user: null,
    loading: false,
    error: null,
    initialized: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.accessToken,
    userName: (state) => state.user?.name || '',
    userEmail: (state) => state.user?.email || '',
  },

  actions: {
    /**
     * Инициализация при загрузке приложения.
     * Восстанавливает сессию из localStorage.
     */
    async initialize() {
      if (this.initialized) return

      this.initialized = true

      if (!this.accessToken) return

      try {
        // Пробуем загрузить пользователя с текущим токеном
        await this.fetchUser()
      } catch (error) {
        // Если токен истёк, пробуем refresh
        if (error.response?.status === 401 && this.refreshToken) {
          try {
            await this.refreshTokenAction()
            await this.fetchUser()
          } catch (refreshError) {
            this.logout()
          }
        } else {
          this.logout()
        }
      }
    },

    async login(email, password) {
      this.loading = true
      this.error = null

      try {
        const response = await authApi.login(email, password)
        const data = response.data

        this.accessToken = data.access_token
        this.refreshToken = data.refresh_token
        this.user = data.user

        localStorage.setItem('access_token', data.access_token)
        localStorage.setItem('refresh_token', data.refresh_token)

        return data
      } catch (error) {
        this.error = error.response?.data?.message || 'Ошибка входа'
        throw error
      } finally {
        this.loading = false
      }
    },

    async refreshTokenAction() {
      if (!this.refreshToken) {
        throw new Error('No refresh token')
      }

      const response = await authApi.refresh(this.refreshToken)
      const data = response.data.data

      this.accessToken = data.access_token
      this.refreshToken = data.refresh_token

      localStorage.setItem('access_token', data.access_token)
      localStorage.setItem('refresh_token', data.refresh_token)

      return data
    },

    async fetchUser() {
      const response = await authApi.me()
      this.user = response.data.user
    },

    async logout() {
      try {
        await authApi.logout()
      } catch (error) {
        // Ignore
      }

      this.accessToken = null
      this.refreshToken = null
      this.user = null

      localStorage.removeItem('access_token')
      localStorage.removeItem('refresh_token')
    },
  },
})

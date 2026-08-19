import apiClient from './client'

export default {
  login(email, password) {
    return apiClient.post('/auth/login', { email, password })
  },

  register(data) {
    return apiClient.post('/auth/register', data)
  },

  verifyRegistration(email, code) {
    return apiClient.post('/auth/verify-registration', { email, code })
  },

  resendVerification(email) {
    return apiClient.post('/auth/resend-verification', { email })
  },

  refresh(refreshToken) {
    return apiClient.post('/auth/refresh', { refresh_token: refreshToken })
  },

  logout() {
    return apiClient.post('/auth/logout')
  },

  me() {
    return apiClient.get('/auth/me')
  },

  forgotPassword(email) {
    return apiClient.post('/auth/forgot-password', { email })
  },

  resetPassword(data) {
    return apiClient.post('/auth/reset-password', data)
  },

  changeEmail(email, password) {
    return apiClient.post('/auth/change-email', { email, password })
  },

  verifyEmailChange(code) {
    return apiClient.post('/auth/verify-email-change', { code })
  },

  changePassword(data) {
    return apiClient.post('/auth/change-password', data)
  },
  sessions() {
    return apiClient.get('/auth/sessions')
  },

  terminateSession(sessionId, password) {
    return apiClient.delete(`/auth/sessions/${sessionId}`, {
      data: { password },
    })
  },

  terminateAllSessions(password) {
    return apiClient.delete('/auth/sessions', {
      data: { password },
    })
  },
}

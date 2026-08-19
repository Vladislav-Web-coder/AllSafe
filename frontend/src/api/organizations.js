import apiClient from './client'

export default {
  list() {
    return apiClient.get('/organizations')
  },

  get(organizationId) {
    return apiClient.get(`/organizations/${organizationId}`)
  },

  create(data) {
    return apiClient.post('/organizations', data)
  },

  update(organizationId, data) {
    return apiClient.put(`/organizations/${organizationId}`, data)
  },

  // Участники
  listMembers(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/members`)
  },

  addMember(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/members`, data)
  },

  updateMember(organizationId, userId, data) {
    return apiClient.put(`/organizations/${organizationId}/members/${userId}`, data)
  },

  removeMember(organizationId, userId) {
    return apiClient.delete(`/organizations/${organizationId}/members/${userId}`)
  },

  // Профиль
  getProfile(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/profile`)
  },

  updateProfile(organizationId, data) {
    return apiClient.put(`/organizations/${organizationId}/profile`, data)
  },

  // Обязательные документы
  getRequiredDocuments(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/required-documents`)
  },

  getMissingDocuments(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/missing-documents`)
  },
  delete(organizationId) {
    return apiClient.delete(`/organizations/${organizationId}`)
  },

  leave(organizationId) {
    return apiClient.post(`/organizations/${organizationId}/leave`)
  },
}

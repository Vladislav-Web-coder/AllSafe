import apiClient from './client'

export default {
  list(organizationId, params = {}) {
    return apiClient.get(`/organizations/${organizationId}/audit`, { params })
  },

  get(organizationId, auditLogId) {
    return apiClient.get(`/organizations/${organizationId}/audit/${auditLogId}`)
  },

  userActions(organizationId, userId, params = {}) {
    return apiClient.get(`/organizations/${organizationId}/audit/user/${userId}`, { params })
  },

  clear(organizationId) {
    return apiClient.delete(`/organizations/${organizationId}/audit`)
  },
}

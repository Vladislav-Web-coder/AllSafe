import apiClient from './client'

export default {
  dashboard(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/compliance/dashboard`)
  },

  summary(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/compliance/summary`)
  },
}

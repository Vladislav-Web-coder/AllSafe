import apiClient from './client'

export default {
  listTemplates(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/generation/templates`)
  },

  start(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/generation`, data)
  },

  listRuns(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/generation/runs`)
  },

  getRun(organizationId, runId) {
    return apiClient.get(`/organizations/${organizationId}/generation/runs/${runId}`)
  },
}

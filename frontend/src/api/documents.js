import apiClient from './client'

export default {
  list(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/documents`)
  },

  get(organizationId, documentId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}`)
  },

  create(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/documents`, data)
  },

  upload(organizationId, documentId, formData) {
    return apiClient.post(
      `/organizations/${organizationId}/documents/${documentId}/upload`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
  },

  download(organizationId, documentId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}/download`)
  },

  delete(organizationId, documentId) {
    return apiClient.delete(`/organizations/${organizationId}/documents/${documentId}`)
  },

  analyze(organizationId, documentId) {
    return apiClient.post(`/organizations/${organizationId}/documents/${documentId}/analyze`)
  },

  getAnalysis(organizationId, documentId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}/analysis`)
  },

  getIssues(organizationId, documentId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}/issues`)
  },
}

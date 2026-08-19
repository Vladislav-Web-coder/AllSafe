import apiClient from './client'

export default {
  list(organizationId, params = {}) {
    return apiClient.get(`/organizations/${organizationId}/issues`, { params })
  },

  listForDocument(organizationId, documentId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}/issues`)
  },

  get(organizationId, documentId, issueId) {
    return apiClient.get(`/organizations/${organizationId}/documents/${documentId}/issues/${issueId}`)
  },

  updateStatus(organizationId, documentId, issueId, data) {
    return apiClient.patch(
      `/organizations/${organizationId}/documents/${documentId}/issues/${issueId}/status`,
      data
    )
  },

  addComment(organizationId, documentId, issueId, data) {
    return apiClient.post(
      `/organizations/${organizationId}/documents/${documentId}/issues/${issueId}/comments`,
      data
    )
  },

  listComments(organizationId, documentId, issueId) {
    return apiClient.get(
      `/organizations/${organizationId}/documents/${documentId}/issues/${issueId}/comments`
    )
  },

  listHistory(organizationId, documentId, issueId) {
    return apiClient.get(
      `/organizations/${organizationId}/documents/${documentId}/issues/${issueId}/history`
    )
  },

  bulkUpdate(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/issues/bulk`, data)
  },
  deleteComment(organizationId, documentId, issueId, commentId) {
    return apiClient.delete(
      `/organizations/${organizationId}/documents/${documentId}/issues/${issueId}/comments/${commentId}`
    )
  },
}

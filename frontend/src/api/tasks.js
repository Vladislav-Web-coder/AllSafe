import apiClient from './client'

export default {
  list(organizationId, params = {}) {
    return apiClient.get(`/organizations/${organizationId}/tasks`, { params })
  },

  get(organizationId, taskId) {
    return apiClient.get(`/organizations/${organizationId}/tasks/${taskId}`)
  },

  create(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/tasks`, data)
  },

  createFromIssue(organizationId, data) {
    return apiClient.post(`/organizations/${organizationId}/tasks/from-issue`, data)
  },

  updateStatus(organizationId, taskId, data) {
    return apiClient.patch(`/organizations/${organizationId}/tasks/${taskId}/status`, data)
  },

  assign(organizationId, taskId, data) {
    return apiClient.post(`/organizations/${organizationId}/tasks/${taskId}/assign`, data)
  },

  delete(organizationId, taskId) {
    return apiClient.delete(`/organizations/${organizationId}/tasks/${taskId}`)
  },

  addComment(organizationId, taskId, data) {
    return apiClient.post(`/organizations/${organizationId}/tasks/${taskId}/comments`, data)
  },

  listComments(organizationId, taskId) {
    return apiClient.get(`/organizations/${organizationId}/tasks/${taskId}/comments`)
  },

  stats(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/tasks/stats`)
  },

  myTasks(organizationId) {
    return apiClient.get(`/organizations/${organizationId}/tasks/my`)
  },

  update(organizationId, taskId, data) {
    return apiClient.patch(`/organizations/${organizationId}/tasks/${taskId}`, data)
  },
  deleteComment(organizationId, taskId, commentId) {
    return apiClient.delete(
      `/organizations/${organizationId}/tasks/${taskId}/comments/${commentId}`
    )
  },
}

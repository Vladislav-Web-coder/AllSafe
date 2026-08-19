import apiClient from './client'

export default {
  organizationTypes() {
    return apiClient.get('/dictionaries/organization-types')
  },

  industries() {
    return apiClient.get('/dictionaries/industries')
  },

  documentTypes() {
    return apiClient.get('/dictionaries/document-types')
  },
}

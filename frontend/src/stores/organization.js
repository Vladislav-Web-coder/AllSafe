import { defineStore } from 'pinia'
import organizationsApi from '@/api/organizations'

export const useOrganizationStore = defineStore('organization', {
  state: () => ({
    currentOrganizationId: localStorage.getItem('current_organization_id')
      ? parseInt(localStorage.getItem('current_organization_id'))
      : null,
    organizations: [],
    currentOrganization: null,
    profile: null,
    loading: false,
  }),

  getters: {
    hasOrganization: (state) => !!state.currentOrganizationId,
  },

  actions: {
    async fetchOrganizations() {
      this.loading = true

      try {
        const { data } = await organizationsApi.list()
        this.organizations = data.data

        // Если текущая организация не выбрана, берём первую
        if (!this.currentOrganizationId && this.organizations.length > 0) {
          this.setCurrentOrganization(this.organizations[0].id)
        }
      } catch (error) {
        console.error('Failed to fetch organizations:', error)
      } finally {
        this.loading = false
      }
    },

    setCurrentOrganization(organizationId) {
      this.currentOrganizationId = organizationId

      if (organizationId) {
        localStorage.setItem('current_organization_id', organizationId.toString())
      } else {
        localStorage.removeItem('current_organization_id')
      }
    },

    async fetchCurrentOrganization() {
      if (!this.currentOrganizationId) return

      try {
        const { data } = await organizationsApi.get(this.currentOrganizationId)
        this.currentOrganization = data.data
      } catch (error) {
        console.error('Failed to fetch organization:', error)
      }
    },

    async fetchProfile() {
      if (!this.currentOrganizationId) return

      try {
        const { data } = await organizationsApi.getProfile(this.currentOrganizationId)
        this.profile = data.data
      } catch (error) {
        if (error.response?.status === 404) {
          this.profile = null
        }
      }
    },

    async updateProfile(profileData) {
      const { data } = await organizationsApi.updateProfile(
        this.currentOrganizationId,
        profileData
      )
      this.profile = data.data
      return data.data
    },
  },
})

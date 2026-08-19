<template>
  <div class="issues-page">
    <div class="page-header">
      <h1>Замечания</h1>

      <div class="filters">
        <select v-model="filterStatus" @change="fetchIssues" class="form-control">
          <option value="">Все статусы</option>
          <option value="open">Открытые</option>
          <option value="accepted">Принятые</option>
          <option value="fixed">Исправленные</option>
          <option value="rejected">Отклонённые</option>
          <option value="deferred">Отложенные</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="loading">Загрузка...</div>

    <div v-else-if="issues.length === 0" class="empty">
      Замечаний нет
    </div>

    <div v-else class="issues-list">
      <div v-for="issue in issues" :key="issue.id" class="issue-card">
        <div class="issue-header">
          <span class="badge" :class="getSeverityClass(issue.severity)">
            {{ issue.severity_label }}
          </span>
          <span class="badge" :class="getStatusClass(issue.status)">
            {{ issue.status_label }}
          </span>
        </div>

        <h3 class="issue-title">{{ issue.title }}</h3>

        <p v-if="issue.description" class="issue-description">
          {{ issue.description }}
        </p>

        <div v-if="issue.recommendation" class="issue-recommendation">
          <strong>Рекомендация:</strong> {{ issue.recommendation }}
        </div>

        <div v-if="issue.legal_basis?.length" class="issue-legal">
          <strong>Основание:</strong> {{ issue.legal_basis.join(', ') }}
        </div>

        <div class="issue-actions">
          <button
            v-for="transition in issue.allowed_transitions"
            :key="transition"
            @click="changeStatus(issue.id, transition)"
            class="btn btn-sm"
          >
            {{ getTransitionLabel(transition) }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '@/api/client'

const organizationId = 1

const issues = ref([])
const loading = ref(true)
const filterStatus = ref('')

onMounted(() => {
  fetchIssues()
})

async function fetchIssues() {
  try {
    loading.value = true

    let url = `/organizations/${organizationId}/issues`
    if (filterStatus.value) {
      url += `?status=${filterStatus.value}`
    }

    const { data } = await apiClient.get(url)
    issues.value = data.data
  } catch (err) {
    console.error('Failed to fetch issues:', err)
  } finally {
    loading.value = false
  }
}

async function changeStatus(issueId, newStatus) {
  try {
    await apiClient.patch(`/organizations/${organizationId}/documents/0/issues/${issueId}/status`, {
      status: newStatus,
    })
    await fetchIssues()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
  }
}

function getSeverityClass(severity) {
  const map = {
    critical: 'badge-danger',
    high: 'badge-danger',
    medium: 'badge-warning',
    low: 'badge-info',
    info: 'badge-secondary',
  }
  return map[severity] || 'badge-secondary'
}

function getStatusClass(status) {
  const map = {
    open: 'badge-danger',
    accepted: 'badge-warning',
    fixed: 'badge-success',
    rejected: 'badge-secondary',
    deferred: 'badge-info',
  }
  return map[status] || 'badge-secondary'
}

function getTransitionLabel(transition) {
  const map = {
    accepted: 'Принять',
    fixed: 'Исправлено',
    rejected: 'Отклонить',
    deferred: 'Отложить',
    open: 'Переоткрыть',
  }
  return map[transition] || transition
}
</script>

<style scoped>
.issues-page {
  max-width: 1000px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.filters {
  width: 200px;
}

.issues-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.issue-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.issue-header {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.issue-title {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
}

.issue-description {
  color: #666;
  margin-bottom: 0.75rem;
}

.issue-recommendation,
.issue-legal {
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.issue-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.loading, .empty {
  padding: 2rem;
  text-align: center;
  color: #666;
}
</style>

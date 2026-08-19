<template>
  <div class="issues-page">
    <div class="page-header">
      <h1>Замечания</h1>

      <div class="filters">
        <select v-model="filterSeverity" @change="fetchIssues" class="form-control filter-select">
          <option value="">Вся критичность</option>
          <option value="critical">Критическая</option>
          <option value="high">Высокая</option>
          <option value="medium">Средняя</option>
          <option value="low">Низкая</option>
          <option value="info">Информационная</option>
        </select>

        <select v-model="filterStatus" @change="fetchIssues" class="form-control filter-select">
          <option value="">Все статусы</option>
          <option value="open">Открытые</option>
          <option value="accepted">Принятые</option>
          <option value="fixed">Исправленные</option>
          <option value="rejected">Отклонённые</option>
          <option value="deferred">Отложенные</option>
        </select>
      </div>
    </div>

    <!-- Массовые действия -->
    <div v-if="selectedIssues.length > 0" class="bulk-actions">
      <span class="bulk-count">Выбрано: {{ selectedIssues.length }}</span>
      <button @click="bulkUpdate('accepted')" class="btn btn-sm btn-primary">
        <Icon name="check" size="14" />
        Принять
      </button>
      <button @click="bulkUpdate('fixed')" class="btn btn-sm btn-success">
        <Icon name="check-circle" size="14" />
        Исправлено
      </button>
      <button @click="bulkUpdate('rejected')" class="btn btn-sm btn-secondary">
        <Icon name="x" size="14" />
        Отклонить
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="issues.length === 0" class="empty-state">
      <Icon name="check-circle" size="48" />
      <h2>Замечаний нет</h2>
      <p>Все документы соответствуют требованиям</p>
    </div>

    <!-- Группировка по документам -->
    <div v-else class="documents-groups">
      <div
        v-for="group in groupedIssues"
        :key="group.document?.id || 'no-document'"
        class="document-group"
      >
        <div class="document-group-header">
          <div class="document-info">
            <Icon name="file-text" size="20" />
            <router-link
              v-if="group.document"
              :to="{ name: 'document-detail', params: { id: group.document.id } }"
              class="document-title"
            >
              {{ group.document.title }}
            </router-link>
            <span v-else class="document-title">Без документа</span>
          </div>
          <span class="badge badge-secondary">
            <Icon name="alert-triangle" size="14" />
            {{ group.issues.length }}
          </span>
        </div>

        <div class="issues-list">
          <router-link
            v-for="issue in group.issues"
            :key="issue.id"
            :to="{ name: 'issue-detail', params: { documentId: issue.document_id, issueId: issue.id } }"
            class="issue-card-link"
          >
            <div class="issue-card">
              <div class="issue-checkbox" @click.prevent>
                <input
                  type="checkbox"
                  :value="issue.id"
                  v-model="selectedIssues"
                />
              </div>

              <div class="issue-body">
                <div class="issue-badges">
                  <span class="badge" :class="getSeverityClass(issue.severity)">
                    <Icon :name="getSeverityIcon(issue.severity)" size="14" />
                    {{ issue.severity_label }}
                  </span>
                  <span class="badge" :class="getStatusClass(issue.status)">
                    <Icon :name="getStatusIcon(issue.status)" size="14" />
                    {{ issue.status_label }}
                  </span>
                </div>

                <h3 class="issue-title">{{ issue.title }}</h3>

                <p v-if="issue.description" class="issue-description">
                  {{ issue.description }}
                </p>

                <div v-if="issue.recommendation" class="issue-recommendation">
                  <Icon name="zap" size="14" />
                  <span>{{ issue.recommendation }}</span>
                </div>

                <div v-if="issue.legal_basis?.length" class="issue-legal">
                  <Icon name="book" size="14" />
                  <span>{{ issue.legal_basis.join(', ') }}</span>
                </div>

                <div class="issue-actions" @click.prevent>
                  <button
                    v-for="transition in issue.allowed_transitions"
                    :key="transition"
                    @click="changeStatus(issue, transition)"
                    class="btn btn-sm"
                    :class="getTransitionClass(transition)"
                  >
                    <Icon :name="getTransitionIcon(transition)" size="14" />
                    {{ getTransitionLabel(transition) }}
                  </button>

                  <button
                    @click="createTaskFromIssue(issue)"
                    class="btn btn-sm btn-secondary"
                  >
                    <Icon name="plus" size="14" />
                    Задача
                  </button>
                </div>
              </div>
            </div>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useOrganizationStore } from '@/stores/organization'
import issuesApi from '@/api/issues'
import tasksApi from '@/api/tasks'
import Icon from '@/components/common/Icon.vue'

const organizationStore = useOrganizationStore()

const issues = ref([])
const loading = ref(true)
const filterSeverity = ref('')
const filterStatus = ref('')
const selectedIssues = ref([])

const groupedIssues = computed(() => {
  const groups = {}

  for (const issue of issues.value) {
    const docKey = issue.document?.id || 'no-document'

    if (!groups[docKey]) {
      groups[docKey] = {
        document: issue.document || null,
        issues: [],
      }
    }

    groups[docKey].issues.push(issue)
  }

  return Object.values(groups)
})

onMounted(() => {
  fetchIssues()
})

async function fetchIssues() {
  try {
    loading.value = true

    const params = {}
    if (filterStatus.value) params.status = filterStatus.value

    const { data } = await issuesApi.list(
      organizationStore.currentOrganizationId,
      params
    )

    let filtered = data.data
    if (filterSeverity.value) {
      filtered = filtered.filter(i => i.severity === filterSeverity.value)
    }

    issues.value = filtered
  } catch (err) {
    console.error('Failed to fetch issues:', err)
  } finally {
    loading.value = false
  }
}

async function changeStatus(issue, newStatus) {
  try {
    await issuesApi.updateStatus(
      organizationStore.currentOrganizationId,
      issue.document_id,
      issue.id,
      { status: newStatus }
    )
    await fetchIssues()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
  }
}

async function bulkUpdate(newStatus) {
  try {
    await issuesApi.bulkUpdate(organizationStore.currentOrganizationId, {
      issue_ids: selectedIssues.value,
      status: newStatus,
    })

    selectedIssues.value = []
    await fetchIssues()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка массового обновления')
  }
}

async function createTaskFromIssue(issue) {
  try {
    await tasksApi.createFromIssue(organizationStore.currentOrganizationId, {
      issue_id: issue.id,
    })
    alert('Задача создана из замечания')
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка создания задачи')
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

function getSeverityIcon(severity) {
  const map = {
    critical: 'alert-octagon',
    high: 'alert-triangle',
    medium: 'alert-circle',
    low: 'info',
    info: 'info',
  }
  return map[severity] || 'info'
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

function getStatusIcon(status) {
  const map = {
    open: 'circle',
    accepted: 'check-circle',
    fixed: 'check',
    rejected: 'x-circle',
    deferred: 'clock',
  }
  return map[status] || 'circle'
}

function getTransitionClass(transition) {
  const map = {
    accepted: 'btn-primary',
    fixed: 'btn-success',
    rejected: 'btn-secondary',
    deferred: 'btn-secondary',
    open: 'btn-primary',
  }
  return map[transition] || ''
}

function getTransitionIcon(transition) {
  const map = {
    accepted: 'check',
    fixed: 'check-circle',
    rejected: 'x',
    deferred: 'clock',
    open: 'rotate-ccw',
  }
  return map[transition] || 'check'
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

.page-header h1 {
  margin: 0;
}

.filters {
  display: flex;
  gap: 0.75rem;
}

.filter-select {
  width: 180px;
}

/* Bulk actions */
.bulk-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #e3f2fd;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.bulk-count {
  font-weight: 500;
  color: #1565c0;
}

/* Loading & Empty */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  color: #666;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e0e0e0;
  border-top-color: #4a90d9;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  background: white;
  border-radius: 12px;
  color: #28a745;
}

.empty-state h2 {
  color: #333;
  margin: 0;
}

.empty-state p {
  color: #666;
  margin: 0;
}

/* Document groups */
.documents-groups {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.document-group {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.document-group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 2px solid #eee;
  margin-bottom: 1rem;
}

.document-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #333;
}

.document-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #4a90d9;
  text-decoration: none;
}

.document-title:hover {
  text-decoration: underline;
}

/* Issues list */
.issues-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.issue-card-link {
  text-decoration: none;
  color: inherit;
}

.issue-card {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  border: 1px solid #eee;
  border-radius: 8px;
  transition: all 0.2s;
}

.issue-card-link:hover .issue-card {
  border-color: #4a90d9;
  box-shadow: 0 4px 12px rgba(74, 144, 217, 0.15);
}

.issue-checkbox {
  padding-top: 0.25rem;
}

.issue-checkbox input {
  width: 18px;
  height: 18px;
}

.issue-body {
  flex: 1;
}

.issue-badges {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.issue-title {
  margin: 0 0 0.5rem;
  font-size: 1rem;
}

.issue-description {
  color: #666;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.issue-recommendation,
.issue-legal {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  color: #666;
}

.issue-recommendation {
  color: #f57c00;
}

.issue-legal {
  color: #1565c0;
}

.issue-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f0;
  flex-wrap: wrap;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}

.badge-danger {
  background: #fee;
  color: #c33;
}

.badge-warning {
  background: #fff8e1;
  color: #f57c00;
}

.badge-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-info {
  background: #e3f2fd;
  color: #1565c0;
}

.badge-secondary {
  background: #f5f5f5;
  color: #616161;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-sm {
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
}

.btn-primary {
  background: #4a90d9;
  color: white;
}

.btn-primary:hover {
  background: #357abd;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}
</style>

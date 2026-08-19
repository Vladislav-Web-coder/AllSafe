<template>
  <div class="audit-page">
    <div class="page-header">
      <h1>Аудит</h1>

      <button
        v-if="isOwner"
        @click="clearAudit"
        class="btn btn-danger"
      >
        <Icon name="trash" size="16" />
        Очистить аудит
      </button>
    </div>

    <div class="filters">
      <div class="filter-group">
        <Icon name="search" size="16" />
        <input
          v-model="filters.action"
          @input="debouncedFetch"
          placeholder="Фильтр по действию..."
          class="form-control"
        />
      </div>

      <select v-model="filters.subject_type" @change="fetchLogs" class="form-control">
        <option value="">Все типы</option>
        <option value="document">Документ</option>
        <option value="issue">Замечание</option>
        <option value="task">Задача</option>
        <option value="organization">Организация</option>
        <option value="organization_profile">Профиль</option>
      </select>

      <select v-model="filters.user_id" @change="fetchLogs" class="form-control">
        <option value="">Все пользователи</option>
        <option v-for="member in members" :key="member.user_id" :value="member.user_id">
          {{ member.name }} ({{ member.email }})
        </option>
      </select>

      <input
        v-model="filters.date_from"
        @change="fetchLogs"
        type="date"
        class="form-control"
      />

      <input
        v-model="filters.date_to"
        @change="fetchLogs"
        type="date"
        class="form-control"
      />
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="logs.length === 0" class="empty-state">
      <Icon name="list" size="48" />
      <h2>Записей аудита нет</h2>
      <p>Действия пользователей будут отображаться здесь</p>
    </div>

    <div v-else class="audit-table-wrapper">
      <table class="table">
        <thead>
        <tr>
          <th>Время</th>
          <th>Пользователь</th>
          <th>Действие</th>
          <th>Объект</th>
          <th>Описание</th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="log in logs"
          :key="log.id"
          @click="goToAuditDetail(log.id)"
          class="audit-row"
        >
          <td>{{ formatDateTime(log.created_at) }}</td>
          <td>{{ log.user_email || '—' }}</td>
          <td>
            <span class="badge badge-info">{{ log.action }}</span>
          </td>
          <td>
              <span v-if="log.subject_type">
                {{ log.subject_type }} #{{ log.subject_id }}
              </span>
            <span v-else>—</span>
          </td>
          <td class="description-cell">{{ log.description || '—' }}</td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Пагинация -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page <= 1"
        class="btn btn-sm btn-secondary"
      >
        <Icon name="chevron-left" size="16" />
        Назад
      </button>

      <span class="page-info">
        Страница {{ pagination.current_page }} из {{ pagination.last_page }}
      </span>

      <button
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page >= pagination.last_page"
        class="btn btn-sm btn-secondary"
      >
        Вперёд
        <Icon name="chevron-right" size="16" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import { useAuthStore } from '@/stores/auth'
import auditApi from '@/api/audit'
import organizationsApi from '@/api/organizations'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()
const authStore = useAuthStore()

const logs = ref([])
const members = ref([])
const loading = ref(true)
const isOwner = ref(false)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
})

const filters = ref({
  action: '',
  subject_type: '',
  user_id: '',
  date_from: '',
  date_to: '',
})

let debounceTimer = null

onMounted(async () => {
  await Promise.all([
    fetchMembers(),
    checkOwnerStatus(),
  ])
  await fetchLogs()
})

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchLogs()
  }, 500)
}

async function fetchMembers() {
  try {
    const { data } = await organizationsApi.listMembers(
      organizationStore.currentOrganizationId
    )
    members.value = data.data || []
  } catch (err) {
    console.error('Failed to fetch members:', err)
  }
}

async function checkOwnerStatus() {
  try {
    const { data } = await organizationsApi.listMembers(
      organizationStore.currentOrganizationId
    )

    const currentMember = data.data.find(m => m.user_id === authStore.user?.id)
    isOwner.value = currentMember?.pivot?.role === 'owner'
  } catch (err) {
    console.error('Failed to check owner status:', err)
  }
}

async function fetchLogs(page = 1) {
  try {
    loading.value = true

    const params = {
      page,
      per_page: 50,
    }

    if (filters.value.action) params.action = filters.value.action
    if (filters.value.subject_type) params.subject_type = filters.value.subject_type
    if (filters.value.user_id) params.user_id = filters.value.user_id
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to

    const { data } = await auditApi.list(
      organizationStore.currentOrganizationId,
      params
    )

    logs.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
  } catch (err) {
    console.error('Failed to fetch audit logs:', err)
  } finally {
    loading.value = false
  }
}

function goToAuditDetail(auditLogId) {
  router.push({ name: 'audit-detail', params: { auditLogId } })
}

async function clearAudit() {
  if (!confirm('Очистить весь аудит организации? Это действие нельзя отменить.')) return

  if (!confirm('Вы уверены? Все записи аудита будут удалены.')) return

  try {
    const { data } = await auditApi.clear(organizationStore.currentOrganizationId)
    alert(data.message)
    await fetchLogs()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка очистки аудита')
  }
}

function changePage(page) {
  fetchLogs(page)
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.audit-page {
  max-width: 1200px;
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

/* Filters */
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 200px;
}

.filter-group .icon {
  position: absolute;
  left: 0.75rem;
  color: #999;
}

.filter-group input {
  padding-left: 2.5rem;
}

.filters .form-control {
  width: auto;
  min-width: 150px;
}

/* Loading */
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

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  background: white;
  border-radius: 12px;
  color: #999;
}

.empty-state h2 {
  color: #333;
  margin: 0;
}

.empty-state p {
  color: #666;
  margin: 0;
}

/* Table */
.audit-table-wrapper {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 1rem 1.25rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table th {
  font-weight: 600;
  color: #666;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: #f9f9f9;
}

.audit-row {
  cursor: pointer;
}

.audit-row:hover td {
  background-color: #f9f9f9;
}

.description-cell {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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

.badge-info {
  background: #e3f2fd;
  color: #1565c0;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.page-info {
  color: #666;
  font-size: 0.9rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-sm {
  padding: 0.35rem 0.75rem;
  font-size: 0.85rem;
}

.btn-primary {
  background: #4a90d9;
  color: white;
}

.btn-primary:hover {
  background: #357abd;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

<template>
  <div class="audit-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/audit" class="back-link">
          <Icon name="arrow-left" size="16" />
          <span>Аудит</span>
        </router-link>
        <h1>Запись аудита #{{ auditLog?.id }}</h1>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="error" class="error-state">
      <Icon name="alert-circle" size="48" />
      <h2>Ошибка загрузки</h2>
      <p>{{ error }}</p>
      <button @click="loadData" class="btn btn-primary">Попробовать снова</button>
    </div>

    <template v-else-if="auditLog">
      <!-- Основная информация -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="activity" size="20" />
            Информация о событии
          </h2>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">
              <Icon name="zap" size="14" />
              Действие
            </span>
            <span class="badge badge-info">{{ auditLog.action }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="clock" size="14" />
              Время
            </span>
            <span>{{ formatDateTime(auditLog.created_at) }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="user" size="14" />
              Пользователь
            </span>
            <span>{{ auditLog.user_email || 'Система' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="check-circle" size="14" />
              Результат
            </span>
            <span class="badge" :class="auditLog.result === 'success' ? 'badge-success' : 'badge-danger'">
              {{ auditLog.result || 'success' }}
            </span>
          </div>
        </div>

        <div v-if="auditLog.description" class="description-block">
          <h3>Описание</h3>
          <p>{{ auditLog.description }}</p>
        </div>
      </div>

      <!-- Объект -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="target" size="20" />
            Объект
          </h2>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">Тип объекта</span>
            <span>{{ auditLog.subject_type || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">ID объекта</span>
            <span>{{ auditLog.subject_id || '—' }}</span>
          </div>
        </div>

        <div v-if="auditLog.subject_type" class="subject-links">
          <router-link
            v-if="auditLog.subject_type === 'document'"
            :to="{ name: 'document-detail', params: { id: auditLog.subject_id } }"
            class="btn btn-secondary btn-sm"
          >
            <Icon name="external-link" size="14" />
            Открыть документ
          </router-link>

          <router-link
            v-if="auditLog.subject_type === 'task'"
            :to="{ name: 'task-detail', params: { id: auditLog.subject_id } }"
            class="btn btn-secondary btn-sm"
          >
            <Icon name="external-link" size="14" />
            Открыть задачу
          </router-link>

          <router-link
            v-if="auditLog.subject_type === 'issue'"
            :to="{ name: 'documents' }"
            class="btn btn-secondary btn-sm"
          >
            <Icon name="external-link" size="14" />
            Перейти к документам
          </router-link>
        </div>
      </div>

      <!-- Изменения -->
      <div v-if="auditLog.old_values || auditLog.new_values" class="card">
        <div class="card-header">
          <h2>
            <Icon name="refresh-cw" size="20" />
            Изменения
          </h2>
        </div>

        <div class="changes-grid">
          <div v-if="auditLog.old_values" class="changes-column">
            <h3>
              <Icon name="x-circle" size="16" class="text-danger" />
              Было
            </h3>
            <pre class="values-block">{{ formatValues(auditLog.old_values) }}</pre>
          </div>

          <div v-if="auditLog.new_values" class="changes-column">
            <h3>
              <Icon name="check-circle" size="16" class="text-success" />
              Стало
            </h3>
            <pre class="values-block">{{ formatValues(auditLog.new_values) }}</pre>
          </div>
        </div>
      </div>

      <!-- Метаданные запроса -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="server" size="20" />
            Метаданные запроса
          </h2>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">
              <Icon name="globe" size="14" />
              IP-адрес
            </span>
            <span class="mono">{{ auditLog.ip_address || '—' }}</span>
          </div>

          <div class="info-item full-width">
            <span class="info-label">
              <Icon name="monitor" size="14" />
              User Agent
            </span>
            <span class="user-agent">{{ auditLog.user_agent || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="hash" size="14" />
              Request ID
            </span>
            <span class="request-id">{{ auditLog.request_id || '—' }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import auditApi from '@/api/audit'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const organizationStore = useOrganizationStore()

const auditLogId = parseInt(route.params.auditLogId)
const orgId = organizationStore.currentOrganizationId

const auditLog = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  error.value = null

  try {
    const { data } = await auditApi.get(orgId, auditLogId)
    auditLog.value = data.data
  } catch (err) {
    console.error('Failed to fetch audit log:', err)
    error.value = err.response?.data?.message || 'Не удалось загрузить запись аудита'
  } finally {
    loading.value = false
  }
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function formatValues(values) {
  if (!values) return '—'
  return JSON.stringify(values, null, 2)
}
</script>

<style scoped>
.audit-detail {
  max-width: 900px;
}

.page-header {
  margin-bottom: 1.5rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a90d9;
  text-decoration: none;
  font-size: 0.9rem;
}

.back-link:hover {
  text-decoration: underline;
}

.page-header h1 {
  margin: 0;
}

/* Loading & Error */
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

.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  color: #dc3545;
}

.error-state h2 {
  color: #333;
}

.error-state p {
  color: #666;
}

/* Cards */
.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.card-header {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}

.card-header h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 1.1rem;
}

.card h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Info grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: #666;
}

.mono {
  font-family: 'Courier New', monospace;
}

.description-block {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
}

.description-block p {
  margin: 0;
  line-height: 1.6;
  color: #333;
}

.subject-links {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
}

/* Changes */
.changes-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.values-block {
  background: #f9f9f9;
  padding: 1rem;
  border-radius: 8px;
  font-family: 'Courier New', monospace;
  font-size: 0.85rem;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 400px;
  overflow-y: auto;
  margin: 0;
}

.user-agent {
  font-size: 0.85rem;
  color: #666;
  word-break: break-all;
}

.request-id {
  font-family: 'Courier New', monospace;
  font-size: 0.85rem;
}

.text-danger {
  color: #dc3545;
}

.text-success {
  color: #28a745;
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

.badge-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-danger {
  background: #fee;
  color: #c33;
}

.badge-secondary {
  background: #f5f5f5;
  color: #616161;
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
  text-decoration: none;
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
</style>

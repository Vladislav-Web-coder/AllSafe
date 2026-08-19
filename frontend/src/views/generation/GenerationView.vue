<template>
  <div class="generation-page">
    <h1>Генерация документов</h1>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <template v-else>
      <!-- Шаблоны -->
      <div class="section">
        <h2>
          <Icon name="file-text" size="20" />
          Доступные шаблоны
        </h2>

        <div v-if="templates.length === 0" class="empty-state">
          <Icon name="file" size="32" />
          <p>Шаблонов нет</p>
        </div>

        <div v-else class="templates-grid">
          <div v-for="template in templates" :key="template.id" class="template-card">
            <div class="template-header">
              <h3>{{ template.name }}</h3>
              <span v-if="template.document_type" class="badge badge-info">
                {{ template.document_type.category }}
              </span>
            </div>

            <p v-if="template.description" class="template-description">
              {{ template.description }}
            </p>

            <div v-if="template.required_sections?.length" class="template-sections">
              <Icon name="list" size="14" />
              <span>Разделов: {{ template.required_sections.length }}</span>
            </div>

            <button
              @click="startGeneration(template)"
              :disabled="generating"
              class="btn btn-primary"
            >
              <Icon name="zap" size="16" />
              {{ generating ? 'Генерация...' : 'Сгенерировать' }}
            </button>
          </div>
        </div>
      </div>

      <!-- История генераций -->
      <div class="section">
        <h2>
          <Icon name="clock" size="20" />
          История генераций
        </h2>

        <div v-if="runs.length === 0" class="empty-state">
          <Icon name="clock" size="32" />
          <p>Генераций ещё не было</p>
        </div>

        <div v-else class="runs-table-wrapper">
          <table class="table">
            <thead>
            <tr>
              <th>Шаблон</th>
              <th>Статус</th>
              <th>Создан</th>
              <th>Действия</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="run in runs" :key="run.id">
              <td>{{ run.template?.name || '—' }}</td>
              <td>
                  <span class="badge" :class="getRunStatusClass(run.status)">
                    <Icon :name="getRunStatusIcon(run.status)" size="14" />
                    {{ getRunStatusLabel(run.status) }}
                  </span>
              </td>
              <td>{{ formatDate(run.created_at) }}</td>
              <td>
                <button
                  v-if="run.status === 'completed' && run.document_id"
                  @click="viewDocument(run.document_id)"
                  class="btn btn-sm btn-primary"
                >
                  <Icon name="eye" size="14" />
                  Просмотреть
                </button>
                <span v-if="run.status === 'failed'" class="error-text">
                    {{ run.error_message }}
                  </span>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import generationApi from '@/api/generation'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()

const templates = ref([])
const runs = ref([])
const loading = ref(true)
const generating = ref(false)

let pollInterval = null

onMounted(async () => {
  await Promise.all([
    fetchTemplates(),
    fetchRuns(),
  ])
  loading.value = false

  pollInterval = setInterval(() => {
    const hasActive = runs.value.some(r =>
      ['pending', 'processing'].includes(r.status)
    )
    if (hasActive) {
      fetchRuns()
    }
  }, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

async function fetchTemplates() {
  try {
    const { data } = await generationApi.listTemplates(
      organizationStore.currentOrganizationId
    )
    templates.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch templates:', err)
  }
}

async function fetchRuns() {
  try {
    const { data } = await generationApi.listRuns(
      organizationStore.currentOrganizationId
    )
    runs.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch runs:', err)
  }
}

async function startGeneration(template) {
  try {
    generating.value = true

    await generationApi.start(organizationStore.currentOrganizationId, {
      document_template_id: template.id,
    })

    await fetchRuns()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка запуска генерации')
  } finally {
    generating.value = false
  }
}

function viewDocument(documentId) {
  router.push({ name: 'document-detail', params: { id: documentId } })
}

function getRunStatusClass(status) {
  const map = {
    pending: 'badge-secondary',
    processing: 'badge-warning',
    completed: 'badge-success',
    failed: 'badge-danger',
  }
  return map[status] || 'badge-secondary'
}

function getRunStatusIcon(status) {
  const map = {
    pending: 'clock',
    processing: 'activity',
    completed: 'check-circle',
    failed: 'x-circle',
  }
  return map[status] || 'clock'
}

function getRunStatusLabel(status) {
  const map = {
    pending: 'Ожидает',
    processing: 'Генерируется',
    completed: 'Завершена',
    failed: 'Ошибка',
  }
  return map[status] || status
}

function formatDate(dateStr) {
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
.generation-page {
  max-width: 1000px;
}

.generation-page h1 {
  margin-bottom: 1.5rem;
}

.section {
  margin-bottom: 2rem;
}

.section h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 1.2rem;
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
  gap: 0.75rem;
  padding: 2rem;
  color: #999;
}

.empty-state p {
  margin: 0;
}

/* Templates grid */
.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.template-card {
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.template-header h3 {
  margin: 0;
  font-size: 1rem;
}

.template-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  flex: 1;
}

.template-sections {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  color: #666;
}

.template-card .btn {
  width: 100%;
  justify-content: center;
}

/* Runs table */
.runs-table-wrapper {
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

.table tr:hover td {
  background-color: #f9f9f9;
}

.error-text {
  color: #dc3545;
  font-size: 0.85rem;
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

.badge-secondary {
  background: #f5f5f5;
  color: #616161;
}

.badge-warning {
  background: #fff8e1;
  color: #f57c00;
}

.badge-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-danger {
  background: #fee;
  color: #c33;
}

.badge-info {
  background: #e3f2fd;
  color: #1565c0;
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

.btn:hover {
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
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
</style>

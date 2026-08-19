<template>
  <div class="document-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/documents" class="back-link">
          <Icon name="arrow-left" size="16" />
          <span>Документы</span>
        </router-link>
        <h1>{{ document?.title || 'Документ' }}</h1>
      </div>

      <div class="header-actions">
        <button @click="downloadDocument" class="btn btn-secondary">
          <Icon name="download" size="16" />
          Скачать
        </button>
        <button @click="analyzeDocument" :disabled="analyzing" class="btn btn-primary">
          <Icon name="zap" size="16" />
          {{ analyzing ? 'Анализ...' : 'Запустить анализ' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <template v-else-if="document">
      <!-- Информация о документе -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="file-text" size="20" />
            Информация
          </h2>
        </div>

        <div class="info-grid">
          <div class="info-item">
            <span class="info-label">
              <Icon name="file" size="14" />
              Тип
            </span>
            <span>{{ document.document_type?.name || '—' }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">
              <Icon name="activity" size="14" />
              Статус
            </span>
            <span class="badge" :class="getStatusClass(document.status)">
              {{ document.status_label }}
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">
              <Icon name="upload" size="14" />
              Источник
            </span>
            <span>{{ document.source_label }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">
              <Icon name="clock" size="14" />
              Создан
            </span>
            <span>{{ formatDate(document.created_at) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">
              <Icon name="git-branch" size="14" />
              Версия
            </span>
            <span>{{ document.current_version_id || '—' }}</span>
          </div>
        </div>
      </div>

      <!-- Результаты анализа -->
      <div v-if="analysis" class="card">
        <div class="card-header">
          <h2>
            <Icon name="bar-chart" size="20" />
            Результаты анализа
          </h2>
        </div>

        <div class="analysis-summary">
          <div class="score-circle" :class="getScoreClass(analysis.score)">
            {{ analysis.score }}
          </div>

          <div class="summary-stats">
            <div class="stat">
              <span class="stat-value">{{ analysis.summary?.total_checks || 0 }}</span>
              <span class="stat-label">Проверок</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ analysis.summary?.passed || 0 }}</span>
              <span class="stat-label">Пройдено</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ analysis.summary?.failed || 0 }}</span>
              <span class="stat-label">Провалено</span>
            </div>
            <div class="stat">
              <span class="stat-value">{{ analysis.summary?.warnings || 0 }}</span>
              <span class="stat-label">Предупреждений</span>
            </div>
          </div>
        </div>

        <div v-if="analysis.missing_sections?.length" class="missing-sections">
          <h3>
            <Icon name="alert-circle" size="16" />
            Отсутствующие разделы
          </h3>
          <ul>
            <li v-for="section in analysis.missing_sections" :key="section">
              {{ section }}
            </li>
          </ul>
        </div>

        <div v-if="analysis.legal_references?.length" class="legal-refs">
          <h3>
            <Icon name="book" size="16" />
            Нормативные ссылки
          </h3>
          <div class="ref-tags">
            <span v-for="ref in analysis.legal_references" :key="ref" class="ref-tag">
              <Icon name="link" size="12" />
              {{ ref }}
            </span>
          </div>
        </div>

        <div class="analysis-meta">
          <span>
            <Icon name="cpu" size="14" />
            {{ analysis.model_provider }} / {{ analysis.model_name }}
          </span>
          <span>
            <Icon name="clock" size="14" />
            Завершён: {{ formatDate(analysis.finished_at) }}
          </span>
        </div>
      </div>

      <!-- Замечания -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="alert-triangle" size="20" />
            Замечания
          </h2>
          <span class="badge badge-secondary">{{ issues.length }}</span>
        </div>

        <div v-if="issues.length === 0" class="empty-state">
          <Icon name="check-circle" size="32" />
          <p>Замечаний нет</p>
        </div>

        <div v-else class="issues-list">
          <div v-for="issue in issues" :key="issue.id" class="issue-item">
            <div class="issue-badges">
              <span class="badge" :class="getSeverityClass(issue.severity)">
                <Icon :name="getSeverityIcon(issue.severity)" size="14" />
                {{ issue.severity_label }}
              </span>
              <span class="badge" :class="getIssueStatusClass(issue.status)">
                <Icon :name="getIssueStatusIcon(issue.status)" size="14" />
                {{ issue.status_label }}
              </span>
            </div>

            <div class="issue-content">
              <h4>{{ issue.title }}</h4>
              <p v-if="issue.description">{{ issue.description }}</p>
              <p v-if="issue.recommendation" class="recommendation">
                <Icon name="zap" size="14" />
                <strong>Рекомендация:</strong> {{ issue.recommendation }}
              </p>
            </div>

            <div class="issue-actions">
              <button
                v-for="transition in issue.allowed_transitions"
                :key="transition"
                @click="changeIssueStatus(issue.id, transition)"
                class="btn btn-sm"
                :class="getTransitionClass(transition)"
              >
                <Icon :name="getTransitionIcon(transition)" size="14" />
                {{ getTransitionLabel(transition) }}
              </button>
            </div>
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
import documentsApi from '@/api/documents'
import issuesApi from '@/api/issues'
import { downloadFile } from '@/utils/download'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const organizationStore = useOrganizationStore()

const documentId = parseInt(route.params.id)
const orgId = organizationStore.currentOrganizationId

const document = ref(null)
const analysis = ref(null)
const issues = ref([])
const loading = ref(true)
const analyzing = ref(false)

onMounted(async () => {
  await fetchDocument()
  await Promise.all([
    fetchAnalysis(),
    fetchIssues(),
  ])
  loading.value = false
})

async function fetchDocument() {
  try {
    const { data } = await documentsApi.get(orgId, documentId)
    document.value = data.data
  } catch (err) {
    console.error('Failed to fetch document:', err)
  }
}

async function fetchAnalysis() {
  try {
    const { data } = await documentsApi.getAnalysis(orgId, documentId)
    analysis.value = data.data
  } catch (err) {
    if (err.response?.status !== 404) {
      console.error('Failed to fetch analysis:', err)
    }
  }
}

async function fetchIssues() {
  try {
    const { data } = await documentsApi.getIssues(orgId, documentId)
    issues.value = data.data
  } catch (err) {
    console.error('Failed to fetch issues:', err)
  }
}

async function analyzeDocument() {
  try {
    analyzing.value = true
    await documentsApi.analyze(orgId, documentId)

    const pollInterval = setInterval(async () => {
      await fetchAnalysis()
      await fetchDocument()

      if (analysis.value && ['completed', 'failed'].includes(analysis.value.status)) {
        clearInterval(pollInterval)
        analyzing.value = false
        await fetchIssues()
      }
    }, 3000)

    setTimeout(() => {
      clearInterval(pollInterval)
      analyzing.value = false
    }, 300000)
  } catch (err) {
    analyzing.value = false
    alert(err.response?.data?.message || 'Ошибка запуска анализа')
  }
}

async function downloadDocument() {
  try {
    const { data } = await documentsApi.download(orgId, documentId)
    downloadFile(data.download_url, document.value?.title || 'document')
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка скачивания')
  }
}

async function changeIssueStatus(issueId, newStatus) {
  try {
    await issuesApi.updateStatus(orgId, documentId, issueId, { status: newStatus })
    await fetchIssues()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
  }
}

function getStatusClass(status) {
  const map = {
    completed: 'badge-success',
    uploaded: 'badge-info',
    analyzing: 'badge-warning',
    failed: 'badge-danger',
    draft: 'badge-secondary',
  }
  return map[status] || 'badge-secondary'
}

function getScoreClass(score) {
  if (score >= 80) return 'score-good'
  if (score >= 50) return 'score-medium'
  return 'score-bad'
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

function getIssueStatusClass(status) {
  const map = {
    open: 'badge-danger',
    accepted: 'badge-warning',
    fixed: 'badge-success',
    rejected: 'badge-secondary',
    deferred: 'badge-info',
  }
  return map[status] || 'badge-secondary'
}

function getIssueStatusIcon(status) {
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

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.document-detail {
  max-width: 900px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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

.header-actions {
  display: flex;
  gap: 0.75rem;
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
  gap: 0.75rem;
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
  font-size: 1rem;
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

.info-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: #666;
}

/* Analysis */
.analysis-summary {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.score-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  color: white;
}

.score-good { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.score-medium { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
.score-bad { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }

.summary-stats {
  display: flex;
  gap: 2rem;
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.8rem;
  color: #666;
}

.missing-sections, .legal-refs {
  margin-bottom: 1rem;
}

.missing-sections h3, .legal-refs h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.missing-sections ul {
  padding-left: 1.5rem;
}

.ref-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.ref-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #e3f2fd;
  color: #1565c0;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
}

.analysis-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #666;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.analysis-meta span {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

/* Issues */
.issues-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.issue-item {
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 8px;
}

.issue-badges {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.issue-content h4 {
  margin: 0 0 0.5rem;
}

.issue-content p {
  margin: 0 0 0.5rem;
  color: #666;
}

.recommendation {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.issue-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f0f0f0;
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

.badge-success { background: #e8f5e9; color: #2e7d32; }
.badge-info { background: #e3f2fd; color: #1565c0; }
.badge-warning { background: #fff8e1; color: #f57c00; }
.badge-danger { background: #fee; color: #c33; }
.badge-secondary { background: #f5f5f5; color: #616161; }

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

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
}
</style>

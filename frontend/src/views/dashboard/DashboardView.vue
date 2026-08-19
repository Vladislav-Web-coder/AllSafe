<template>
  <div class="dashboard">
    <h1>Дашборд</h1>

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

    <template v-else-if="data">
      <!-- Score -->
      <div class="score-card">
        <div class="score-circle" :class="getScoreClass(data.overall_score)">
          <span class="score-value">{{ data.overall_score }}</span>
        </div>
        <div class="score-info">
          <h2>Уровень соответствия</h2>
          <p>Общая оценка комплаенса организации</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <Icon name="file-text" size="24" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.documents.total_present }} / {{ data.documents.total_required }}</span>
            <span class="stat-label">Обязательные документы</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon warning">
            <Icon name="alert-triangle" size="24" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.issues.open }}</span>
            <span class="stat-label">Открытые замечания</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon danger">
            <Icon name="alert-octagon" size="24" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.issues.critical_open }}</span>
            <span class="stat-label">Критические замечания</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon info">
            <Icon name="activity" size="24" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.tasks.in_progress }}</span>
            <span class="stat-label">Задачи в работе</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" :class="{ danger: data.tasks.overdue > 0 }">
            <Icon name="clock" size="24" />
          </div>
          <div class="stat-content">
            <span class="stat-value">{{ data.tasks.overdue }}</span>
            <span class="stat-label">Просроченные задачи</span>
          </div>
        </div>
      </div>

      <!-- Missing documents -->
      <div v-if="data.documents.missing.length > 0" class="card">
        <div class="card-header">
          <h2>
            <Icon name="clipboard" size="20" />
            Отсутствующие документы
          </h2>
        </div>

        <div class="missing-list">
          <div v-for="doc in data.documents.missing" :key="doc.rule_code" class="missing-item">
            <div class="missing-info">
              <Icon name="file-text" size="18" />
              <span class="missing-name">{{ doc.document_type_name }}</span>
            </div>
            <span class="badge badge-danger">{{ doc.obligation_level }}</span>
          </div>
        </div>
      </div>

      <!-- Recommendations -->
      <div v-if="data.recommendations.length > 0" class="card">
        <div class="card-header">
          <h2>
            <Icon name="zap" size="20" />
            Рекомендации
          </h2>
        </div>

        <div class="recommendations">
          <div
            v-for="(rec, index) in data.recommendations"
            :key="index"
            class="recommendation"
            :class="'priority-' + rec.priority"
          >
            <div class="rec-icon">
              <Icon :name="getRecIcon(rec.priority)" size="20" />
            </div>
            <div class="rec-content">
              <div class="rec-title">{{ rec.title }}</div>
              <div class="rec-description">{{ rec.description }}</div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useOrganizationStore } from '@/stores/organization'
import complianceApi from '@/api/compliance'
import Icon from '@/components/common/Icon.vue'

const organizationStore = useOrganizationStore()

const data = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  error.value = null

  try {
    if (organizationStore.organizations.length === 0) {
      await organizationStore.fetchOrganizations()
    }

    if (!organizationStore.hasOrganization) {
      error.value = 'Организация не выбрана. Создайте организацию.'
      loading.value = false
      return
    }

    const response = await complianceApi.dashboard(organizationStore.currentOrganizationId)
    data.value = response.data.data
  } catch (err) {
    console.error('Failed to load dashboard:', err)
    error.value = err.response?.data?.message || 'Ошибка загрузки дашборда'
  } finally {
    loading.value = false
  }
}

function getScoreClass(score) {
  if (score >= 80) return 'score-good'
  if (score >= 50) return 'score-medium'
  return 'score-bad'
}

function getRecIcon(priority) {
  const map = {
    critical: 'alert-octagon',
    high: 'alert-triangle',
    medium: 'alert-circle',
    low: 'info',
  }
  return map[priority] || 'info'
}
</script>

<style scoped>
.dashboard {
  max-width: 1200px;
}

.dashboard h1 {
  margin-bottom: 1.5rem;
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

/* Score card */
.score-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 2rem;
  background: white;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.score-circle {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.score-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: white;
}

.score-good {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.score-medium {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.score-bad {
  background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
}

.score-info h2 {
  margin: 0 0 0.5rem;
  font-size: 1.3rem;
}

.score-info p {
  margin: 0;
  color: #666;
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e3f2fd;
  color: #1565c0;
}

.stat-icon.warning {
  background: #fff8e1;
  color: #f57c00;
}

.stat-icon.danger {
  background: #fee;
  color: #c33;
}

.stat-icon.info {
  background: #e3f2fd;
  color: #1565c0;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
}

.stat-label {
  font-size: 0.85rem;
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

/* Missing documents */
.missing-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.missing-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.missing-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #333;
}

.missing-name {
  font-weight: 500;
}

/* Recommendations */
.recommendations {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.recommendation {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
  border-left: 4px solid #ccc;
}

.recommendation.priority-critical {
  border-left-color: #dc3545;
}

.recommendation.priority-high {
  border-left-color: #fd7e14;
}

.recommendation.priority-medium {
  border-left-color: #ffc107;
}

.recommendation.priority-low {
  border-left-color: #28a745;
}

.rec-icon {
  padding-top: 0.25rem;
}

.priority-critical .rec-icon {
  color: #dc3545;
}

.priority-high .rec-icon {
  color: #fd7e14;
}

.priority-medium .rec-icon {
  color: #ffc107;
}

.priority-low .rec-icon {
  color: #28a745;
}

.rec-content {
  flex: 1;
}

.rec-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.rec-description {
  color: #666;
  font-size: 0.9rem;
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

.btn-primary {
  background: #4a90d9;
  color: white;
}

.btn-primary:hover {
  background: #357abd;
}
</style>

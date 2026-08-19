<template>
  <div class="required-documents-page">
    <h1>Обязательные документы</h1>

    <div v-if="!profileFilled" class="warning-card">
      <Icon name="alert-triangle" size="20" />
      <span>Профиль организации не заполнен. Заполните профиль для определения обязательных документов.</span>
      <router-link to="/organization/profile" class="btn btn-primary btn-sm">
        Заполнить профиль
      </router-link>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <template v-else>
      <!-- Сводка -->
      <div class="summary-card">
        <div class="summary-item">
          <Icon name="clipboard" size="24" />
          <span class="summary-value">{{ requiredDocuments.length }}</span>
          <span class="summary-label">Обязательных</span>
        </div>
        <div class="summary-item success">
          <Icon name="check-circle" size="24" />
          <span class="summary-value">{{ presentCount }}</span>
          <span class="summary-label">Есть</span>
        </div>
        <div class="summary-item danger">
          <Icon name="x-circle" size="24" />
          <span class="summary-value">{{ missingCount }}</span>
          <span class="summary-label">Отсутствует</span>
        </div>
        <div class="summary-item">
          <Icon name="pie-chart" size="24" />
          <span class="summary-value">{{ compliancePercent }}%</span>
          <span class="summary-label">Покрытие</span>
        </div>
      </div>

      <!-- Прогресс бар -->
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: compliancePercent + '%' }"></div>
      </div>

      <!-- Список обязательных документов -->
      <div class="documents-list">
        <div
          v-for="doc in requiredDocuments"
          :key="doc.rule_code"
          class="document-card"
          :class="{ present: doc.is_present, missing: !doc.is_present }"
        >
          <div class="document-status-icon">
            <Icon v-if="doc.is_present" name="check-circle" size="24" />
            <Icon v-else name="x-circle" size="24" />
          </div>

          <div class="document-info">
            <h3>{{ doc.document_type?.name || 'Не указан' }}</h3>

            <div class="document-meta">
              <span class="badge" :class="getObligationClass(doc.obligation_level)">
                <Icon name="shield" size="14" />
                {{ getObligationLabel(doc.obligation_level) }}
              </span>

              <span v-if="doc.legal_basis?.length" class="legal-basis">
                <Icon name="book" size="14" />
                {{ doc.legal_basis.join(', ') }}
              </span>
            </div>

            <p v-if="doc.description" class="document-description">
              {{ doc.description }}
            </p>
          </div>

          <div class="document-actions">
            <button
              v-if="!doc.is_present"
              @click="generateDocument(doc)"
              class="btn btn-primary btn-sm"
            >
              <Icon name="zap" size="14" />
              Сгенерировать
            </button>

            <router-link
              v-if="doc.is_present"
              :to="{ name: 'documents' }"
              class="btn btn-secondary btn-sm"
            >
              <Icon name="eye" size="14" />
              Просмотреть
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import organizationsApi from '@/api/organizations'
import generationApi from '@/api/generation'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()

const requiredDocuments = ref([])
const loading = ref(true)

const profileFilled = computed(() => organizationStore.profile !== null)
const presentCount = computed(() => requiredDocuments.value.filter(d => d.is_present).length)
const missingCount = computed(() => requiredDocuments.value.filter(d => !d.is_present).length)
const compliancePercent = computed(() => {
  if (requiredDocuments.value.length === 0) return 0
  return Math.round((presentCount.value / requiredDocuments.value.length) * 100)
})

onMounted(async () => {
  await organizationStore.fetchProfile()
  await fetchRequiredDocuments()
})

async function fetchRequiredDocuments() {
  try {
    loading.value = true

    const { data } = await organizationsApi.getRequiredDocuments(
      organizationStore.currentOrganizationId
    )

    requiredDocuments.value = data.data
  } catch (err) {
    if (err.response?.status === 422) {
      requiredDocuments.value = []
    } else {
      console.error('Failed to fetch required documents:', err)
    }
  } finally {
    loading.value = false
  }
}

async function generateDocument(doc) {
  try {
    const orgId = organizationStore.currentOrganizationId

    const documentTypeId = doc.document_type?.id

    if (!documentTypeId) {
      alert('Тип документа не определён')
      return
    }

    const documentTypeName = doc.document_type?.name || 'Не указан'

    const { data: templatesResponse } = await generationApi.listTemplates(orgId)
    const templates = templatesResponse.data || templatesResponse

    const template = templates.find(t => {
      const templateTypeId = t.document_type?.id
      return templateTypeId === documentTypeId
    })

    if (!template) {
      const availableTypes = templates
        .map(t => t.document_type?.name || t.name)
        .join(', ')

      alert(
        `Шаблон для типа документа "${documentTypeName}" не найден.\n` +
        `Доступные шаблоны: ${availableTypes}`
      )
      return
    }

    await generationApi.start(orgId, {
      document_template_id: template.id,
    })

    alert('Генерация запущена. Перейдите в раздел "Генерация" для отслеживания.')
    router.push({ name: 'generation' })
  } catch (err) {
    console.error('Generation error:', err)
    alert(err.response?.data?.message || 'Ошибка запуска генерации')
  }
}

function getObligationClass(level) {
  const map = {
    required: 'badge-danger',
    recommended: 'badge-warning',
    optional: 'badge-secondary',
  }
  return map[level] || 'badge-secondary'
}

function getObligationLabel(level) {
  const map = {
    required: 'Обязательный',
    recommended: 'Рекомендуемый',
    optional: 'Опциональный',
  }
  return map[level] || level
}
</script>

<style scoped>
.required-documents-page {
  max-width: 900px;
}

.required-documents-page h1 {
  margin-bottom: 1.5rem;
}

/* Warning card */
.warning-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: #fff8e1;
  color: #f57c00;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.warning-card span {
  flex: 1;
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

/* Summary */
.summary-card {
  display: flex;
  gap: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  color: #666;
}

.summary-item.success {
  color: #28a745;
}

.summary-item.danger {
  color: #dc3545;
}

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
}

.summary-item.success .summary-value {
  color: #28a745;
}

.summary-item.danger .summary-value {
  color: #dc3545;
}

.summary-label {
  font-size: 0.85rem;
}

/* Progress bar */
.progress-bar {
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  margin-bottom: 2rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #4a90d9 0%, #28a745 100%);
  border-radius: 4px;
  transition: width 0.5s ease;
}

/* Documents list */
.documents-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.document-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border-left: 4px solid #ccc;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.document-card.present {
  border-left-color: #28a745;
}

.document-card.missing {
  border-left-color: #dc3545;
}

.document-status-icon {
  padding-top: 0.25rem;
}

.document-card.present .document-status-icon {
  color: #28a745;
}

.document-card.missing .document-status-icon {
  color: #dc3545;
}

.document-info {
  flex: 1;
}

.document-info h3 {
  margin: 0 0 0.5rem;
}

.document-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.legal-basis {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: #666;
}

.document-description {
  color: #666;
  font-size: 0.9rem;
  margin: 0;
}

.document-actions {
  display: flex;
  gap: 0.5rem;
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

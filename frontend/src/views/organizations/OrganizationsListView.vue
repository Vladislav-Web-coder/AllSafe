<template>
  <div class="organizations-page">
    <div class="page-header">
      <h1>Мои организации</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <Icon name="plus" size="16" />
        Создать организацию
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="organizations.length === 0" class="empty-state">
      <Icon name="building" size="48" />
      <h2>У вас пока нет организаций</h2>
      <p>Создайте первую организацию, чтобы начать работу</p>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <Icon name="plus" size="16" />
        Создать организацию
      </button>
    </div>

    <div v-else class="organizations-grid">
      <div
        v-for="org in organizations"
        :key="org.id"
        class="organization-card"
        :class="{ active: organizationStore.currentOrganizationId === org.id }"
      >
        <div class="org-header">
          <div class="org-icon">
            <Icon name="building" size="24" />
          </div>
          <div class="org-title">
            <h3>{{ org.name }}</h3>
            <span v-if="organizationStore.currentOrganizationId === org.id" class="badge badge-success">
          <Icon name="check" size="14" />
          Текущая
        </span>
          </div>
        </div>

        <div class="org-info">
          <div v-if="org.organization_type" class="org-meta">
            <Icon name="briefcase" size="14" />
            <span>{{ org.organization_type.name }}</span>
          </div>
          <div v-if="org.industry" class="org-meta">
            <Icon name="globe" size="14" />
            <span>{{ org.industry.name }}</span>
          </div>
          <div v-if="org.inn" class="org-meta">
            <Icon name="hash" size="14" />
            <span>{{ org.inn }}</span>
          </div>
          <div class="org-meta">
            <Icon name="shield" size="14" />
            <span class="badge" :class="getRoleClass(getOrgRole(org))">
          {{ getRoleLabel(getOrgRole(org)) }}
        </span>
          </div>
        </div>

        <div class="org-actions">
          <button
            v-if="organizationStore.currentOrganizationId !== org.id"
            @click="selectOrganization(org.id)"
            class="btn btn-primary btn-sm"
          >
            <Icon name="check" size="14" />
            Выбрать
          </button>
          <button
            v-if="canManage(org)"
            @click="editOrganization(org)"
            class="btn btn-secondary btn-sm"
          >
            <Icon name="edit" size="14" />
            Редактировать
          </button>
          <button
            v-if="isOwner(org)"
            @click="deleteOrganization(org)"
            class="btn btn-danger btn-sm"
          >
            <Icon name="trash" size="14" />
            Удалить
          </button>
          <button
            v-if="!isOwner(org)"
            @click="leaveOrganization(org)"
            class="btn btn-secondary btn-sm"
          >
            <Icon name="log-out" size="14" />
            Выйти
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно создания/редактирования -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon :name="showEditModal ? 'edit' : 'plus'" size="20" />
            {{ showEditModal ? 'Редактировать организацию' : 'Создать организацию' }}
          </h2>
          <button @click="closeModals" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <form @submit.prevent="showEditModal ? updateOrganization() : createOrganization()">
          <div class="form-group">
            <label>Название *</label>
            <input v-model="form.name" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Полное наименование</label>
            <input v-model="form.legal_name" class="form-control" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Тип организации</label>
              <select v-model="form.organization_type_id" class="form-control">
                <option value="">Не указан</option>
                <option v-for="type in orgTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Отрасль</label>
              <select v-model="form.industry_id" class="form-control">
                <option value="">Не указана</option>
                <option v-for="ind in industries" :key="ind.id" :value="ind.id">
                  {{ ind.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>ИНН</label>
            <input v-model="form.inn" class="form-control" pattern="[0-9]{10,12}" />
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModals" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="submitting" class="btn btn-primary">
              <Icon name="check" size="16" />
              {{ submitting ? 'Сохранение...' : (showEditModal ? 'Сохранить' : 'Создать') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import organizationsApi from '@/api/organizations'
import dictionariesApi from '@/api/dictionaries'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()

const organizations = ref([])
const orgTypes = ref([])
const industries = ref([])
const loading = ref(true)
const submitting = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingOrgId = ref(null)

const form = ref({
  name: '',
  legal_name: '',
  organization_type_id: '',
  industry_id: '',
  inn: '',
})

onMounted(async () => {
  await Promise.all([
    fetchOrganizations(),
    fetchOrgTypes(),
    fetchIndustries(),
  ])
})

async function fetchOrganizations() {
  try {
    loading.value = true
    const { data } = await organizationsApi.list()
    organizations.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch organizations:', err)
  } finally {
    loading.value = false
  }
}

async function fetchOrgTypes() {
  try {
    const { data } = await dictionariesApi.organizationTypes()
    orgTypes.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch org types:', err)
  }
}

async function fetchIndustries() {
  try {
    const { data } = await dictionariesApi.industries()
    industries.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch industries:', err)
  }
}
function selectOrganization(orgId) {
  organizationStore.setCurrentOrganization(orgId)
  router.push({ name: 'dashboard' })
}

function editOrganization(org) {
  editingOrgId.value = org.id
  form.value = {
    name: org.name,
    legal_name: org.legal_name || '',
    organization_type_id: org.organization_type_id || '',
    industry_id: org.industry_id || '',
    inn: org.inn || '',
  }
  showEditModal.value = true
}

async function createOrganization() {
  try {
    submitting.value = true

    const payload = {
      name: form.value.name,
      legal_name: form.value.legal_name || null,
      organization_type_id: form.value.organization_type_id ? parseInt(form.value.organization_type_id) : null,
      industry_id: form.value.industry_id ? parseInt(form.value.industry_id) : null,
      inn: form.value.inn || null,
    }

    const { data } = await organizationsApi.create(payload)
    const newOrg = data.data || data

    showCreateModal.value = false
    resetForm()

    await fetchOrganizations()

    organizationStore.setCurrentOrganization(newOrg.id)
    router.push({ name: 'dashboard' })
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка создания организации')
  } finally {
    submitting.value = false
  }
}

async function updateOrganization() {
  try {
    submitting.value = true

    const payload = {
      name: form.value.name,
      legal_name: form.value.legal_name || null,
      organization_type_id: form.value.organization_type_id ? parseInt(form.value.organization_type_id) : null,
      industry_id: form.value.industry_id ? parseInt(form.value.industry_id) : null,
      inn: form.value.inn || null,
    }

    await organizationsApi.update(editingOrgId.value, payload)

    showEditModal.value = false
    editingOrgId.value = null
    resetForm()

    await fetchOrganizations()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка обновления организации')
  } finally {
    submitting.value = false
  }
}

async function leaveOrganization(org) {
  if (!confirm(`Выйти из организации "${org.name}"?`)) return

  try {
    await organizationsApi.leave(org.id)

    if (organizationStore.currentOrganizationId === org.id) {
      await fetchOrganizations()

      if (organizations.value.length > 0) {
        organizationStore.setCurrentOrganization(organizations.value[0].id)
      } else {
        organizationStore.setCurrentOrganization(null)
      }
    } else {
      await fetchOrganizations()
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка выхода из организации')
  }
}

async function deleteOrganization(org) {
  if (!confirm(`Удалить организацию "${org.name}"? Это действие нельзя отменить. Все документы, задачи и данные будут удалены.`)) return

  if (!confirm('Вы уверены? Удаление необратимо.')) return

  try {
    await organizationsApi.delete(org.id)

    if (organizationStore.currentOrganizationId === org.id) {
      await fetchOrganizations()

      if (organizations.value.length > 0) {
        organizationStore.setCurrentOrganization(organizations.value[0].id)
      } else {
        organizationStore.setCurrentOrganization(null)
      }
    } else {
      await fetchOrganizations()
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления организации')
  }
}

function closeModals() {
  showCreateModal.value = false
  showEditModal.value = false
  editingOrgId.value = null
  resetForm()
}

function resetForm() {
  form.value = {
    name: '',
    legal_name: '',
    organization_type_id: '',
    industry_id: '',
    inn: '',
  }
}

function getRoleLabel(role) {
  const map = {
    owner: 'Владелец',
    admin: 'Администратор',
    security_officer: 'Специалист по ИБ',
    legal_officer: 'Юрист',
    auditor: 'Аудитор',
    employee: 'Сотрудник',
    viewer: 'Наблюдатель',
  }
  return map[role] || role
}

function getRoleClass(role) {
  const map = {
    owner: 'badge-danger',
    admin: 'badge-warning',
    security_officer: 'badge-info',
    legal_officer: 'badge-info',
    auditor: 'badge-info',
    employee: 'badge-secondary',
    viewer: 'badge-secondary',
  }
  return map[role] || 'badge-secondary'
}
function getOrgRole(org) {
  // API возвращает role напрямую, не в pivot
  if (org.role) {
    return org.role
  }
  if (org.pivot?.role) {
    return org.pivot.role
  }
  if (org.membership?.role) {
    return org.membership.role
  }
  return 'employee'
}

function isOwner(org) {
  return getOrgRole(org) === 'owner'
}

function canManage(org) {
  const role = getOrgRole(org)
  return role === 'owner' || role === 'admin'
}
</script>

<style scoped>
.organizations-page {
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

/* Organizations grid */
.organizations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.organization-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  border: 2px solid transparent;
  transition: all 0.2s;
}

.organization-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.organization-card.active {
  border-color: #4a90d9;
}

.org-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.org-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

.org-title {
  flex: 1;
}

.org-title h3 {
  margin: 0 0 0.25rem;
}

.org-info {
  margin-bottom: 1rem;
}

.org-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0;
  font-size: 0.9rem;
  color: #666;
}

.org-actions {
  display: flex;
  gap: 0.5rem;
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

.badge-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-danger {
  background: #fee;
  color: #c33;
}

.badge-warning {
  background: #fff8e1;
  color: #f57c00;
}

.badge-info {
  background: #e3f2fd;
  color: #1565c0;
}

.badge-secondary {
  background: #f5f5f5;
  color: #616161;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #999;
  padding: 0.25rem;
}

.close-btn:hover {
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
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
</style>

<template>
  <div class="profile-page">
    <h1>
      <Icon name="building" size="24" />
      Профиль организации
    </h1>

    <!-- Выбор организации -->
    <div class="card">
      <div class="card-header">
        <h2>
          <Icon name="briefcase" size="20" />
          Выбор организации
        </h2>
      </div>

      <div v-if="organizations.length > 0" class="org-selector">
        <div class="form-group">
          <label>Текущая организация</label>
          <select
            :value="organizationStore.currentOrganizationId"
            @change="switchOrganization"
            class="form-control"
          >
            <option v-for="org in organizations" :key="org.id" :value="org.id">
              {{ org.name }}
            </option>
          </select>
        </div>
      </div>

      <div v-else class="empty-state">
        <Icon name="building" size="32" />
        <p>У вас нет организаций</p>
        <router-link to="/organizations" class="btn btn-primary">
          <Icon name="plus" size="16" />
          Создать организацию
        </router-link>
      </div>
    </div>

    <template v-if="currentOrganization">
      <!-- Информация об организации -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="info" size="20" />
            Информация об организации
          </h2>
          <div class="card-actions">
            <button
              v-if="canManage"
              @click="openEditModal"
              class="btn btn-secondary btn-sm"
            >
              <Icon name="edit" size="14" />
              Редактировать
            </button>
            <button
              v-if="isOwner"
              @click="deleteOrganization"
              class="btn btn-danger btn-sm"
            >
              <Icon name="trash" size="14" />
              Удалить
            </button>
            <button
              v-if="!isOwner"
              @click="leaveOrganization"
              class="btn btn-secondary btn-sm"
            >
              <Icon name="log-out" size="14" />
              Выйти
            </button>
          </div>
        </div>

        <div class="org-info-grid">
          <div class="info-item">
            <span class="info-label">
              <Icon name="building" size="14" />
              Название
            </span>
            <span>{{ currentOrganization.name }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="file-text" size="14" />
              Полное наименование
            </span>
            <span>{{ currentOrganization.legal_name || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="briefcase" size="14" />
              Тип
            </span>
            <span>{{ currentOrganization.organization_type?.name || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="globe" size="14" />
              Отрасль
            </span>
            <span>{{ currentOrganization.industry?.name || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="hash" size="14" />
              ИНН
            </span>
            <span>{{ currentOrganization.inn || '—' }}</span>
          </div>

          <div class="info-item">
            <span class="info-label">
              <Icon name="shield" size="14" />
              Ваша роль
            </span>
            <span class="badge" :class="getRoleClass(currentOrganization.role)">
              {{ getRoleLabel(currentOrganization.role) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Профиль организации (параметры для комплаенса) -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="settings" size="20" />
            Параметры организации
          </h2>
        </div>

        <div v-if="loadingProfile" class="loading-state">
          <div class="spinner"></div>
          <span>Загрузка...</span>
        </div>

        <form v-else @submit.prevent="saveProfile">
          <h3>Основные параметры</h3>

          <div class="checkbox-grid">
            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.processes_personal_data" />
              <span>Обрабатывает персональные данные</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_website" />
              <span>Имеет сайт</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_gis" />
              <span>Имеет государственные ИС</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_kii" />
              <span>Имеет объекты КИИ</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_asutp" />
              <span>Имеет АСУ ТП</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.uses_cloud" />
              <span>Использует облачные сервисы</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_contractors" />
              <span>Работает с подрядчиками</span>
            </label>

            <label class="checkbox-item">
              <input type="checkbox" v-model="profileForm.has_cross_border_transfer" />
              <span>Трансграничная передача данных</span>
            </label>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Количество субъектов ПДн</label>
              <input v-model.number="profileForm.subjects_count" type="number" class="form-control" min="0" />
            </div>

            <div class="form-group">
              <label>Уровень защищённости</label>
              <select v-model="profileForm.protection_level" class="form-control">
                <option value="">Не указан</option>
                <option value="УЗ-1">УЗ-1</option>
                <option value="УЗ-2">УЗ-2</option>
                <option value="УЗ-3">УЗ-3</option>
                <option value="УЗ-4">УЗ-4</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Категории данных</label>
            <div class="checkbox-grid">
              <label v-for="cat in dataCategories" :key="cat" class="checkbox-item">
                <input type="checkbox" :value="cat" v-model="profileForm.data_categories" />
                <span>{{ getCategoryLabel(cat) }}</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Специальные категории ПДн</label>
            <div class="checkbox-grid">
              <label v-for="cat in specialCategories" :key="cat" class="checkbox-item">
                <input type="checkbox" :value="cat" v-model="profileForm.special_data_categories" />
                <span>{{ getSpecialCategoryLabel(cat) }}</span>
              </label>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" :disabled="savingProfile" class="btn btn-primary">
              <Icon name="check" size="16" />
              {{ savingProfile ? 'Сохранение...' : 'Сохранить параметры' }}
            </button>
          </div>

          <div v-if="profileSaved" class="success-message">
            <Icon name="check-circle" size="16" />
            Параметры сохранены
          </div>
        </form>
      </div>
    </template>

    <!-- Модальное окно редактирования организации -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="edit" size="20" />
            Редактировать организацию
          </h2>
          <button @click="showEditModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <form @submit.prevent="updateOrganization">
          <div class="form-group">
            <label>Название *</label>
            <input v-model="editForm.name" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Полное наименование</label>
            <input v-model="editForm.legal_name" class="form-control" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Тип организации</label>
              <select v-model="editForm.organization_type_id" class="form-control">
                <option value="">Не указан</option>
                <option v-for="type in orgTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Отрасль</label>
              <select v-model="editForm.industry_id" class="form-control">
                <option value="">Не указана</option>
                <option v-for="ind in industries" :key="ind.id" :value="ind.id">
                  {{ ind.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>ИНН</label>
            <input v-model="editForm.inn" class="form-control" pattern="[0-9]{10,12}" />
          </div>

          <div class="modal-actions">
            <button type="button" @click="showEditModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="submitting" class="btn btn-primary">
              <Icon name="check" size="16" />
              {{ submitting ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
const loadingProfile = ref(true)
const savingProfile = ref(false)
const profileSaved = ref(false)
const showEditModal = ref(false)
const submitting = ref(false)

const profileForm = ref({
  processes_personal_data: false,
  has_website: false,
  has_gis: false,
  has_kii: false,
  has_asutp: false,
  uses_cloud: false,
  has_contractors: false,
  has_cross_border_transfer: false,
  subjects_count: null,
  protection_level: '',
  data_categories: [],
  special_data_categories: [],
})

const editForm = ref({
  name: '',
  legal_name: '',
  organization_type_id: '',
  industry_id: '',
  inn: '',
})

const dataCategories = [
  'employees', 'clients', 'patients', 'students', 'children', 'partners',
]

const specialCategories = [
  'health', 'biometric', 'criminal', 'racial', 'political', 'religious',
]

const currentOrganization = computed(() =>
  organizations.value.find(o => o.id === organizationStore.currentOrganizationId)
)

const isOwner = computed(() => currentOrganization.value?.role === 'owner')

const canManage = computed(() => {
  const role = currentOrganization.value?.role
  return role === 'owner' || role === 'admin'
})

onMounted(async () => {
  await Promise.all([
    fetchOrganizations(),
    fetchOrgTypes(),
    fetchIndustries(),
  ])

  await fetchProfile()
})

watch(() => organizationStore.currentOrganizationId, () => {
  fetchProfile()
})

async function fetchOrganizations() {
  try {
    const { data } = await organizationsApi.list()
    organizations.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch organizations:', err)
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

async function fetchProfile() {
  try {
    loadingProfile.value = true

    if (!organizationStore.currentOrganizationId) {
      return
    }

    const { data } = await organizationsApi.getProfile(
      organizationStore.currentOrganizationId
    )

    const profile = data.data

    if (profile) {
      profileForm.value = {
        processes_personal_data: profile.processes_personal_data || false,
        has_website: profile.has_website || false,
        has_gis: profile.has_gis || false,
        has_kii: profile.has_kii || false,
        has_asutp: profile.has_asutp || false,
        uses_cloud: profile.uses_cloud || false,
        has_contractors: profile.has_contractors || false,
        has_cross_border_transfer: profile.has_cross_border_transfer || false,
        subjects_count: profile.subjects_count,
        protection_level: profile.protection_level || '',
        data_categories: profile.data_categories || [],
        special_data_categories: profile.special_data_categories || [],
      }
    } else {
      // Сбрасываем форму если профиля нет
      profileForm.value = {
        processes_personal_data: false,
        has_website: false,
        has_gis: false,
        has_kii: false,
        has_asutp: false,
        uses_cloud: false,
        has_contractors: false,
        has_cross_border_transfer: false,
        subjects_count: null,
        protection_level: '',
        data_categories: [],
        special_data_categories: [],
      }
    }
  } catch (err) {
    if (err.response?.status === 404) {
      // Профиль не создан, оставляем форму пустой
      console.log('Profile not found, using empty form')
    } else {
      console.error('Failed to fetch profile:', err)
    }
  } finally {
    loadingProfile.value = false
  }
}

function switchOrganization(event) {
  organizationStore.setCurrentOrganization(parseInt(event.target.value))
  router.push({ name: 'dashboard' })
}

function openEditModal() {
  const org = currentOrganization.value

  editForm.value = {
    name: org.name,
    legal_name: org.legal_name || '',
    organization_type_id: org.organization_type?.id || '',
    industry_id: org.industry?.id || '',
    inn: org.inn || '',
  }

  showEditModal.value = true
}

async function updateOrganization() {
  try {
    submitting.value = true

    const payload = {
      name: editForm.value.name,
      legal_name: editForm.value.legal_name || null,
      organization_type_id: editForm.value.organization_type_id
        ? parseInt(editForm.value.organization_type_id)
        : null,
      industry_id: editForm.value.industry_id
        ? parseInt(editForm.value.industry_id)
        : null,
      inn: editForm.value.inn || null,
    }

    await organizationsApi.update(currentOrganization.value.id, payload)

    showEditModal.value = false
    await fetchOrganizations()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка обновления организации')
  } finally {
    submitting.value = false
  }
}

async function deleteOrganization() {
  const org = currentOrganization.value

  if (!confirm(`Удалить организацию "${org.name}"? Это действие нельзя отменить.`)) return
  if (!confirm('Вы уверены? Удаление необратимо.')) return

  try {
    await organizationsApi.delete(org.id)

    await fetchOrganizations()

    if (organizations.value.length > 0) {
      organizationStore.setCurrentOrganization(organizations.value[0].id)
    } else {
      organizationStore.setCurrentOrganization(null)
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления организации')
  }
}

async function leaveOrganization() {
  const org = currentOrganization.value

  if (!confirm(`Выйти из организации "${org.name}"?`)) return

  try {
    await organizationsApi.leave(org.id)

    await fetchOrganizations()

    if (organizations.value.length > 0) {
      organizationStore.setCurrentOrganization(organizations.value[0].id)
      router.push({ name: 'dashboard' })
    } else {
      organizationStore.setCurrentOrganization(null)
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка выхода из организации')
  }
}

async function saveProfile() {
  try {
    savingProfile.value = true
    profileSaved.value = false

    await organizationsApi.updateProfile(
      organizationStore.currentOrganizationId,
      profileForm.value
    )

    profileSaved.value = true
    setTimeout(() => { profileSaved.value = false }, 3000)
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка сохранения профиля')
  } finally {
    savingProfile.value = false
  }
}

function getCategoryLabel(cat) {
  const map = {
    employees: 'Сотрудники',
    clients: 'Клиенты',
    patients: 'Пациенты',
    students: 'Студенты',
    children: 'Дети',
    partners: 'Партнёры',
  }
  return map[cat] || cat
}

function getSpecialCategoryLabel(cat) {
  const map = {
    health: 'Здоровье',
    biometric: 'Биометрия',
    criminal: 'Судимость',
    racial: 'Расовая принадлежность',
    political: 'Политические взгляды',
    religious: 'Религиозные убеждения',
  }
  return map[cat] || cat
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
</script>

<style scoped>
.profile-page {
  max-width: 900px;
}

.profile-page h1 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
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
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-header h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 1.1rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.card h3 {
  margin: 0 0 1rem;
  font-size: 0.95rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  color: #999;
}

.empty-state p {
  margin: 0;
}

/* Organization info */
.org-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

/* Forms */
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
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-item input {
  width: 18px;
  height: 18px;
}

.form-actions {
  margin-top: 1.5rem;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: #e8f5e9;
  color: #2e7d32;
  border-radius: 8px;
}

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
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

.badge-danger { background: #fee; color: #c33; }
.badge-warning { background: #fff8e1; color: #f57c00; }
.badge-info { background: #e3f2fd; color: #1565c0; }
.badge-secondary { background: #f5f5f5; color: #616161; }

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
  text-decoration: none;
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

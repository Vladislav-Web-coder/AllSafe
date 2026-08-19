<template>
  <div class="settings-page">
    <h1>
      <Icon name="settings" size="24" />
      Настройки
    </h1>

    <!-- Навигация по категориям -->
    <div class="settings-nav">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="{ active: activeTab === tab.id }"
        class="tab-btn"
      >
        <Icon :name="tab.icon" size="18" />
        <span>{{ tab.label }}</span>
      </button>
    </div>

    <!-- Профиль -->
    <div v-if="activeTab === 'profile'" class="settings-section">
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="user" size="20" />
            Профиль пользователя
          </h2>
        </div>

        <div class="user-info">
          <div class="user-avatar">
            {{ getInitials(authStore.userName) }}
          </div>
          <div class="user-details">
            <div class="user-name">{{ authStore.userName }}</div>
            <div class="user-email">{{ authStore.userEmail }}</div>
          </div>
        </div>
      </div>

      <!-- Выбор организации -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="building" size="20" />
            Организация
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

          <router-link to="/organizations" class="btn btn-secondary">
            <Icon name="external-link" size="16" />
            Управление организациями
          </router-link>
        </div>

        <div v-else class="no-org">
          <Icon name="alert-circle" size="32" />
          <p>Организация не выбрана</p>
          <router-link to="/organizations" class="btn btn-primary">
            <Icon name="plus" size="16" />
            Выбрать организацию
          </router-link>
        </div>
      </div>
    </div>

    <!-- Безопасность -->
    <div v-if="activeTab === 'security'" class="settings-section">
      <!-- Смена email -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="mail" size="20" />
            Смена email
          </h2>
        </div>

        <template v-if="emailChangeStep === 1">
          <p class="current-email">
            Текущий email: <strong>{{ authStore.userEmail }}</strong>
          </p>

          <form @submit.prevent="requestEmailChange">
            <div class="form-group">
              <label>Новый email</label>
              <input v-model="emailForm.email" type="email" class="form-control" required />
            </div>

            <div class="form-group">
              <label>Текущий пароль</label>
              <input v-model="emailForm.password" type="password" class="form-control" required />
            </div>

            <div v-if="emailChangeError" class="error-message">
              <Icon name="alert-circle" size="16" />
              {{ emailChangeError }}
            </div>

            <button type="submit" :disabled="changingEmail" class="btn btn-primary">
              <Icon name="send" size="16" />
              {{ changingEmail ? 'Отправка...' : 'Изменить email' }}
            </button>
          </form>
        </template>

        <template v-else>
          <p class="verification-hint">
            Код отправлен на вашу текущую почту: <strong>{{ authStore.userEmail }}</strong>
          </p>

          <form @submit.prevent="verifyEmailChange">
            <div class="form-group">
              <label>Код подтверждения</label>
              <input
                v-model="emailVerificationCode"
                type="text"
                class="form-control code-input"
                maxlength="6"
                placeholder="000000"
                required
              />
            </div>

            <div v-if="emailChangeError" class="error-message">
              <Icon name="alert-circle" size="16" />
              {{ emailChangeError }}
            </div>

            <div v-if="emailChangeSuccess" class="success-message">
              <Icon name="check-circle" size="16" />
              {{ emailChangeSuccess }}
            </div>

            <button type="submit" :disabled="changingEmail" class="btn btn-primary">
              <Icon name="check" size="16" />
              {{ changingEmail ? 'Проверка...' : 'Подтвердить' }}
            </button>

            <button type="button" @click="cancelEmailChange" class="btn btn-secondary" style="margin-left: 0.5rem;">
              <Icon name="x" size="16" />
              Отмена
            </button>
          </form>
        </template>
      </div>

      <!-- Смена пароля -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="lock" size="20" />
            Смена пароля
          </h2>
        </div>

        <form @submit.prevent="changePassword">
          <div class="form-group">
            <label>Текущий пароль</label>
            <input v-model="passwordForm.current_password" type="password" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Новый пароль</label>
            <input v-model="passwordForm.password" type="password" class="form-control" required minlength="8" />
          </div>

          <div class="form-group">
            <label>Подтверждение нового пароля</label>
            <input v-model="passwordForm.password_confirmation" type="password" class="form-control" required />
          </div>

          <div v-if="passwordChangeError" class="error-message">
            <Icon name="alert-circle" size="16" />
            {{ passwordChangeError }}
          </div>

          <div v-if="passwordChangeSuccess" class="success-message">
            <Icon name="check-circle" size="16" />
            {{ passwordChangeSuccess }}
          </div>

          <button type="submit" :disabled="changingPassword" class="btn btn-primary">
            <Icon name="check" size="16" />
            {{ changingPassword ? 'Изменение...' : 'Изменить пароль' }}
          </button>
        </form>
      </div>

      <!-- Активные сессии -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="monitor" size="20" />
            Активные сессии
          </h2>
          <button
            v-if="sessions.length > 1"
            @click="showTerminateAllModal = true"
            class="btn btn-danger btn-sm"
          >
            <Icon name="x" size="14" />
            Завершить все
          </button>
        </div>

        <div v-if="loadingSessions" class="loading-state">
          <div class="spinner"></div>
          <span>Загрузка...</span>
        </div>

        <div v-else class="sessions-list">
          <div
            v-for="session in sessions"
            :key="session.id"
            class="session-item"
            :class="{ current: session.is_current }"
          >
            <div class="session-icon">
              <Icon :name="session.icon || 'monitor'" size="24" />
            </div>

            <div class="session-info">
              <div class="session-name">
                {{ session.device_name }}
                <span v-if="session.is_current" class="badge badge-success">Текущая</span>
              </div>
              <div class="session-details">
                <span v-if="session.ip_address">
                  <Icon name="globe" size="12" />
                  {{ session.ip_address }}
                </span>
                <span v-if="session.browser">
                  <Icon name="chrome" size="12" />
                  {{ session.browser }}
                </span>
                <span v-if="session.last_activity_at">
                  <Icon name="clock" size="12" />
                  {{ formatDate(session.last_activity_at) }}
                </span>
              </div>
            </div>

            <button
              v-if="!session.is_current"
              @click="openTerminateModal(session)"
              class="btn btn-danger btn-sm"
            >
              <Icon name="x" size="14" />
              Завершить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Уведомления -->
    <div v-if="activeTab === 'notifications'" class="settings-section">
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="bell" size="20" />
            Настройки уведомлений
          </h2>
        </div>

        <div class="settings-list">
          <label class="setting-item">
            <input type="checkbox" v-model="notificationSettings.email_notifications" />
            <span>Получать email-уведомления</span>
          </label>

          <label class="setting-item">
            <input type="checkbox" v-model="notificationSettings.browser_notifications" />
            <span>Получать браузерные уведомления</span>
          </label>

          <label class="setting-item">
            <input type="checkbox" v-model="notificationSettings.notify_analysis_complete" />
            <span>Уведомлять о завершении анализа</span>
          </label>

          <label class="setting-item">
            <input type="checkbox" v-model="notificationSettings.notify_task_overdue" />
            <span>Уведомлять о просроченных задачах</span>
          </label>

          <label class="setting-item">
            <input type="checkbox" v-model="notificationSettings.notify_issues" />
            <span>Уведомлять о новых замечаниях</span>
          </label>
        </div>

        <button @click="saveNotificationSettings" class="btn btn-primary">
          <Icon name="check" size="16" />
          Сохранить настройки
        </button>
      </div>
    </div>

    <!-- Модальное окно завершения сессии -->
    <div v-if="showTerminateModal" class="modal-overlay" @click.self="showTerminateModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="alert-triangle" size="20" />
            Завершить сессию
          </h2>
          <button @click="showTerminateModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <p>Сессия: <strong>{{ terminatingSession?.device_name }}</strong></p>
        <p v-if="terminatingSession?.ip_address">IP: {{ terminatingSession.ip_address }}</p>

        <form @submit.prevent="terminateSession">
          <div class="form-group">
            <label>Введите пароль для подтверждения</label>
            <input
              v-model="terminatePassword"
              type="password"
              class="form-control"
              required
            />
          </div>

          <div v-if="terminateError" class="error-message">
            <Icon name="alert-circle" size="16" />
            {{ terminateError }}
          </div>

          <div class="modal-actions">
            <button type="button" @click="showTerminateModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="terminating" class="btn btn-danger">
              <Icon name="x" size="16" />
              {{ terminating ? 'Завершение...' : 'Завершить сессию' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно завершения всех сессий -->
    <div v-if="showTerminateAllModal" class="modal-overlay" @click.self="showTerminateAllModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="alert-triangle" size="20" />
            Завершить все сессии
          </h2>
          <button @click="showTerminateAllModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <p>Будут завершены все сессии кроме текущей.</p>

        <form @submit.prevent="terminateAllSessions">
          <div class="form-group">
            <label>Введите пароль для подтверждения</label>
            <input
              v-model="terminateAllPassword"
              type="password"
              class="form-control"
              required
            />
          </div>

          <div v-if="terminateError" class="error-message">
            <Icon name="alert-circle" size="16" />
            {{ terminateError }}
          </div>

          <div class="modal-actions">
            <button type="button" @click="showTerminateAllModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="terminating" class="btn btn-danger">
              <Icon name="x" size="16" />
              {{ terminating ? 'Завершение...' : 'Завершить все' }}
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
import { useAuthStore } from '@/stores/auth'
import { useOrganizationStore } from '@/stores/organization'
import authApi from '@/api/auth'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const authStore = useAuthStore()
const organizationStore = useOrganizationStore()

const tabs = [
  { id: 'profile', label: 'Профиль', icon: 'user' },
  { id: 'security', label: 'Безопасность', icon: 'lock' },
  { id: 'notifications', label: 'Уведомления', icon: 'bell' },
]

const activeTab = ref('profile')
const organizations = ref([])

// Смена email
const emailForm = ref({ email: '', password: '' })
const emailChangeStep = ref(1)
const emailVerificationCode = ref('')
const changingEmail = ref(false)
const emailChangeError = ref(null)
const emailChangeSuccess = ref(null)

// Смена пароля
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const changingPassword = ref(false)
const passwordChangeError = ref(null)
const passwordChangeSuccess = ref(null)

// Сессии
const sessions = ref([])
const loadingSessions = ref(false)
const showTerminateModal = ref(false)
const showTerminateAllModal = ref(false)
const terminatingSession = ref(null)
const terminatePassword = ref('')
const terminateAllPassword = ref('')
const terminating = ref(false)
const terminateError = ref(null)

// Уведомления
const notificationSettings = ref({
  email_notifications: true,
  browser_notifications: false,
  notify_analysis_complete: true,
  notify_task_overdue: true,
  notify_issues: true,
})

onMounted(async () => {
  await organizationStore.fetchOrganizations()
  organizations.value = organizationStore.organizations

  const saved = localStorage.getItem('user_settings')
  if (saved) {
    try {
      notificationSettings.value = { ...notificationSettings.value, ...JSON.parse(saved) }
    } catch (e) {
      console.error('Failed to parse settings:', e)
    }
  }

  await fetchSessions()
})

async function fetchSessions() {
  try {
    loadingSessions.value = true
    const { data } = await authApi.sessions()
    sessions.value = data.data
  } catch (err) {
    console.error('Failed to fetch sessions:', err)
  } finally {
    loadingSessions.value = false
  }
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function switchOrganization(event) {
  organizationStore.setCurrentOrganization(parseInt(event.target.value))
  router.push({ name: 'dashboard' })
}

async function requestEmailChange() {
  try {
    changingEmail.value = true
    emailChangeError.value = null

    await authApi.changeEmail(emailForm.value.email, emailForm.value.password)

    emailChangeStep.value = 2
  } catch (err) {
    emailChangeError.value = err.response?.data?.message
      || err.response?.data?.errors?.email?.[0]
      || err.response?.data?.errors?.password?.[0]
      || 'Ошибка смены email'
  } finally {
    changingEmail.value = false
  }
}

async function verifyEmailChange() {
  try {
    changingEmail.value = true
    emailChangeError.value = null

    const { data } = await authApi.verifyEmailChange(emailVerificationCode.value)

    emailChangeSuccess.value = data.message

    if (authStore.user) {
      authStore.user.email = data.new_email
    }

    setTimeout(() => {
      emailChangeStep.value = 1
      emailForm.value = { email: '', password: '' }
      emailVerificationCode.value = ''
      emailChangeSuccess.value = null
    }, 2000)
  } catch (err) {
    emailChangeError.value = err.response?.data?.message
      || err.response?.data?.errors?.code?.[0]
      || 'Ошибка подтверждения'
  } finally {
    changingEmail.value = false
  }
}

function cancelEmailChange() {
  emailChangeStep.value = 1
  emailVerificationCode.value = ''
  emailChangeError.value = null
  emailChangeSuccess.value = null
}

async function changePassword() {
  try {
    changingPassword.value = true
    passwordChangeError.value = null

    await authApi.changePassword(passwordForm.value)

    passwordChangeSuccess.value = 'Пароль успешно изменён'
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' }

    setTimeout(() => {
      passwordChangeSuccess.value = null
    }, 3000)
  } catch (err) {
    passwordChangeError.value = err.response?.data?.message
      || err.response?.data?.errors?.password?.[0]
      || 'Ошибка смены пароля'
  } finally {
    changingPassword.value = false
  }
}

function openTerminateModal(session) {
  terminatingSession.value = session
  terminatePassword.value = ''
  terminateError.value = null
  showTerminateModal.value = true
}

async function terminateSession() {
  try {
    terminating.value = true
    terminateError.value = null

    await authApi.terminateSession(terminatingSession.value.id, terminatePassword.value)

    showTerminateModal.value = false
    terminatingSession.value = null
    terminatePassword.value = ''

    await fetchSessions()
  } catch (err) {
    terminateError.value = err.response?.data?.message
      || err.response?.data?.errors?.password?.[0]
      || 'Ошибка завершения сессии'
  } finally {
    terminating.value = false
  }
}

async function terminateAllSessions() {
  try {
    terminating.value = true
    terminateError.value = null

    await authApi.terminateAllSessions(terminateAllPassword.value)

    showTerminateAllModal.value = false
    terminateAllPassword.value = ''

    await fetchSessions()
  } catch (err) {
    terminateError.value = err.response?.data?.message
      || err.response?.data?.errors?.password?.[0]
      || 'Ошибка завершения сессий'
  } finally {
    terminating.value = false
  }
}

function saveNotificationSettings() {
  localStorage.setItem('user_settings', JSON.stringify(notificationSettings.value))
  alert('Настройки сохранены')
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
.settings-page {
  max-width: 900px;
}

.settings-page h1 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

/* Navigation */
.settings-nav {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  background: white;
  padding: 0.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  background: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.95rem;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #f0f0f0;
}

.tab-btn.active {
  background: #4a90d9;
  color: white;
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
  margin-bottom: 1rem;
}

.card-header h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
  font-size: 1.1rem;
}

/* Profile */
.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 600;
}

.user-name {
  font-weight: 600;
  font-size: 1.1rem;
}

.user-email {
  color: #666;
  margin-top: 0.25rem;
}

/* Organization selector */
.org-selector {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.no-org {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  color: #999;
}

.no-org p {
  margin: 0;
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

.current-email {
  margin-bottom: 1rem;
  color: #666;
}

.verification-hint {
  margin-bottom: 1rem;
  color: #666;
}

.code-input {
  text-align: center;
  font-size: 1.5rem;
  letter-spacing: 8px;
  font-family: 'Courier New', monospace;
}

.error-message,
.success-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.75rem 0;
  padding: 0.75rem 1rem;
  border-radius: 8px;
}

.error-message {
  background-color: #fee;
  color: #c33;
}

.success-message {
  background-color: #e8f5e9;
  color: #2e7d32;
}

/* Sessions */
.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.session-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.session-item.current {
  background: #e3f2fd;
  border: 1px solid #4a90d9;
}

.session-icon {
  color: #4a90d9;
}

.session-info {
  flex: 1;
}

.session-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.session-details {
  display: flex;
  gap: 1rem;
  font-size: 0.85rem;
  color: #666;
}

.session-details span {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

/* Settings list */
.settings-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.setting-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.setting-item input {
  width: 18px;
  height: 18px;
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
  max-width: 450px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
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

.modal p {
  margin-bottom: 0.5rem;
  color: #666;
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
</style>

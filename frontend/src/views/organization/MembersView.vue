<template>
  <div class="members-page">
    <div class="page-header">
      <h1>Участники организации</h1>
      <button @click="openInviteModal" class="btn btn-primary">
        <Icon name="user" size="16" />
        Пригласить участника
      </button>
    </div>

    <!-- Участники -->
    <div class="section">
      <h2>
        <Icon name="users" size="20" />
        Участники
      </h2>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Загрузка...</span>
      </div>

      <div v-else class="members-table-wrapper">
        <table class="table">
          <thead>
          <tr>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Действия</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="member in members" :key="member.user_id">
            <td>
              <div class="member-name">
                <div class="member-avatar">
                  {{ getInitials(member.name) }}
                </div>
                {{ member.name }}
              </div>
            </td>
            <td>{{ member.email }}</td>
            <td>
              <select
                v-if="member.pivot.role !== 'owner' && canManageMembers"
                v-model="member.pivot.role"
                @change="updateMemberRole(member)"
                class="form-control form-control-sm"
              >
                <option value="admin">Администратор</option>
                <option value="security_officer">Специалист по ИБ</option>
                <option value="legal_officer">Юрист</option>
                <option value="auditor">Аудитор</option>
                <option value="employee">Сотрудник</option>
                <option value="viewer">Наблюдатель</option>
              </select>
              <span v-else class="badge" :class="getRoleClass(member.pivot.role)">
                  <Icon name="shield" size="14" />
                  {{ getRoleLabel(member.pivot.role) }}
                </span>
            </td>
            <td>
              <button
                v-if="member.pivot.role !== 'owner' && canManageMembers"
                @click="removeMember(member.user_id)"
                class="action-btn danger"
                title="Удалить участника"
              >
                <Icon name="trash" size="16" />
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Приглашения -->
    <div class="section">
      <h2>
        <Icon name="mail" size="20" />
        Приглашения
      </h2>

      <div v-if="invitations.length === 0" class="empty-state">
        <Icon name="mail" size="32" />
        <p>Активных приглашений нет</p>
      </div>

      <div v-else class="invitations-table-wrapper">
        <table class="table">
          <thead>
          <tr>
            <th>Email</th>
            <th>Роль</th>
            <th>Статус</th>
            <th>Истекает</th>
            <th>Действия</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="inv in invitations" :key="inv.id">
            <td>{{ inv.email }}</td>
            <td>
              <span class="badge badge-secondary">{{ getRoleLabel(inv.role) }}</span>
            </td>
            <td>
                <span class="badge" :class="getInvitationStatusClass(inv.status)">
                  <Icon :name="getInvitationStatusIcon(inv.status)" size="14" />
                  {{ getInvitationStatusLabel(inv.status) }}
                </span>
            </td>
            <td>{{ formatDate(inv.expires_at) }}</td>
            <td>
              <button
                v-if="inv.status === 'pending'"
                @click="cancelInvitation(inv.id)"
                class="action-btn"
                title="Отменить приглашение"
              >
                <Icon name="x" size="16" />
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модальное окно приглашения -->
    <div v-if="showInviteModal" class="modal-overlay" @click.self="showInviteModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="user" size="20" />
            Пригласить участника
          </h2>
          <button @click="showInviteModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <form @submit.prevent="sendInvitation">
          <div class="form-group">
            <label>Email пользователя</label>
            <input v-model="inviteForm.email" type="email" class="form-control" required />
            <p class="form-hint">
              Пользователь получит приглашение на email и станет участником после принятия.
            </p>
          </div>

          <div class="form-group">
            <label>Роль</label>
            <select v-model="inviteForm.role" class="form-control">
              <option value="admin">Администратор</option>
              <option value="security_officer">Специалист по ИБ</option>
              <option value="legal_officer">Юрист</option>
              <option value="auditor">Аудитор</option>
              <option value="employee">Сотрудник</option>
              <option value="viewer">Наблюдатель</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" @click="showInviteModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="inviting" class="btn btn-primary">
              <Icon name="send" size="16" />
              {{ inviting ? 'Отправка...' : 'Пригласить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useOrganizationStore } from '@/stores/organization'
import { useAuthStore } from '@/stores/auth'
import organizationsApi from '@/api/organizations'
import apiClient from '@/api/client'
import Icon from '@/components/common/Icon.vue'

const organizationStore = useOrganizationStore()
const authStore = useAuthStore()

const members = ref([])
const invitations = ref([])
const loading = ref(true)
const showInviteModal = ref(false)
const inviting = ref(false)

const inviteForm = ref({
  email: '',
  role: 'employee',
})

let pollInterval = null

const canManageMembers = computed(() => {
  const currentMember = members.value.find(m => m.user_id === authStore.user?.id)
  return currentMember && ['owner', 'admin'].includes(currentMember.pivot?.role)
})

onMounted(async () => {
  console.log('MembersView mounted, orgId:', organizationStore.currentOrganizationId)

  if (!organizationStore.currentOrganizationId) {
    console.error('No organization selected')
    loading.value = false
    return
  }

  await loadData()

  // Polling каждые 30 секунд
  pollInterval = setInterval(() => {
    loadData()
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})

async function loadData() {
  try {
    await Promise.all([
      fetchMembers(),
      fetchInvitations(),
    ])
  } catch (err) {
    console.error('Failed to load data:', err)
  } finally {
    loading.value = false
  }
}

async function fetchMembers() {
  try {
    const orgId = organizationStore.currentOrganizationId
    console.log('Fetching members for org:', orgId)

    const { data } = await organizationsApi.listMembers(orgId)
    console.log('Raw response:', data)

    // Нормализуем формат данных
    const rawData = data.data || data
    members.value = rawData.map(member => {
      // Нормализуем pivot
      if (!member.pivot && member.role) {
        member.pivot = { role: member.role }
      }
      return member
    })

    console.log('Members loaded:', members.value)
  } catch (err) {
    console.error('Failed to fetch members:', err)
    console.error('Response:', err.response?.data)
    members.value = []
  }
}

async function fetchInvitations() {
  try {
    const orgId = organizationStore.currentOrganizationId

    const { data } = await apiClient.get(
      `/organizations/${orgId}/invitations`
    )
    invitations.value = data.data || data

    console.log('Invitations loaded:', invitations.value)
  } catch (err) {
    console.error('Failed to fetch invitations:', err)
    invitations.value = []
  }
}

function openInviteModal() {
  showInviteModal.value = true
}

async function sendInvitation() {
  try {
    inviting.value = true

    await apiClient.post(
      `/organizations/${organizationStore.currentOrganizationId}/invitations`,
      inviteForm.value
    )

    showInviteModal.value = false
    inviteForm.value = { email: '', role: 'employee' }

    await loadData()
  } catch (err) {
    const message = err.response?.data?.message
      || err.response?.data?.errors?.email?.[0]
      || 'Ошибка отправки приглашения'

    alert(message)
  } finally {
    inviting.value = false
  }
}

async function cancelInvitation(invitationId) {
  if (!confirm('Отменить приглашение?')) return

  try {
    await apiClient.delete(
      `/organizations/${organizationStore.currentOrganizationId}/invitations/${invitationId}`
    )
    await loadData()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка отмены приглашения')
  }
}

async function updateMemberRole(member) {
  try {
    await organizationsApi.updateMember(
      organizationStore.currentOrganizationId,
      member.user_id,
      { role: member.pivot.role }
    )
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка обновления роли')
    await fetchMembers()
  }
}

async function removeMember(userId) {
  if (!confirm('Удалить участника?')) return

  try {
    await organizationsApi.removeMember(organizationStore.currentOrganizationId, userId)
    await fetchMembers()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления участника')
  }
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
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

function getInvitationStatusLabel(status) {
  const map = {
    pending: 'Ожидает',
    accepted: 'Принято',
    expired: 'Истекло',
    cancelled: 'Отменено',
  }
  return map[status] || status
}

function getInvitationStatusIcon(status) {
  const map = {
    pending: 'clock',
    accepted: 'check-circle',
    expired: 'alert-circle',
    cancelled: 'x-circle',
  }
  return map[status] || 'clock'
}

function getInvitationStatusClass(status) {
  const map = {
    pending: 'badge-warning',
    accepted: 'badge-success',
    expired: 'badge-secondary',
    cancelled: 'badge-secondary',
  }
  return map[status] || 'badge-secondary'
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('ru-RU')
}
</script>

<style scoped>
.members-page {
  max-width: 900px;
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

.section {
  margin-bottom: 2rem;
}

.section h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 1.1rem;
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

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2rem;
  color: #999;
  background: white;
  border-radius: 12px;
}

.empty-state p {
  margin: 0;
}

/* Tables */
.members-table-wrapper,
.invitations-table-wrapper {
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

.member-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.member-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.form-control-sm {
  padding: 0.35rem 0.5rem;
  font-size: 0.85rem;
}

.form-hint {
  font-size: 0.85rem;
  color: #666;
  margin-top: 0.5rem;
}

/* Action buttons */
.action-btn {
  background: none;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f0f0f0;
  border-color: #ccc;
}

.action-btn.danger:hover {
  background: #fee;
  border-color: #dc3545;
  color: #dc3545;
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

.btn-primary {
  background: #4a90d9;
  color: white;
}

.btn-primary:hover {
  background: #357abd;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}
</style>

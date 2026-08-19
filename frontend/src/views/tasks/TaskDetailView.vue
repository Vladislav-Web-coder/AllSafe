<template>
  <div class="task-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/tasks" class="back-link">
          <Icon name="arrow-left" size="16" />
          <span>Задачи</span>
        </router-link>
        <h1>{{ task?.title || 'Задача' }}</h1>
      </div>

      <div class="header-actions">
        <button
          v-if="task && task.status !== 'done' && task.status !== 'cancelled'"
          @click="deleteTask"
          class="btn btn-danger"
        >
          <Icon name="trash" size="16" />
          Удалить
        </button>
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

    <template v-else-if="task">
      <!-- Информация о задаче -->
      <div class="card">
        <div class="task-badges">
          <span class="badge" :class="getPriorityClass(task.priority)">
            <Icon name="zap" size="14" />
            {{ task.priority_label }}
          </span>
          <span class="badge" :class="getStatusClass(task.status)">
            <Icon :name="getStatusIcon(task.status)" size="14" />
            {{ task.status_label }}
          </span>
          <span class="badge badge-secondary">
            <Icon name="file" size="14" />
            {{ task.source_type_label }}
          </span>
          <span v-if="task.is_overdue" class="badge badge-danger">
            <Icon name="alert-triangle" size="14" />
            Просрочена
          </span>
        </div>

        <div v-if="task.description" class="task-section">
          <h3>Описание</h3>
          <p>{{ task.description }}</p>
        </div>

        <div class="task-meta">
          <div class="meta-item">
            <span class="meta-label">
              <Icon name="calendar" size="14" />
              Срок
            </span>
            <span :class="{ overdue: task.is_overdue }">
              {{ task.due_date ? formatDate(task.due_date) : 'Не указан' }}
            </span>
          </div>

          <div class="meta-item">
            <span class="meta-label">
              <Icon name="clock" size="14" />
              Создана
            </span>
            <span>{{ formatDate(task.created_at) }}</span>
          </div>

          <div v-if="task.started_at" class="meta-item">
            <span class="meta-label">
              <Icon name="activity" size="14" />
              Начата
            </span>
            <span>{{ formatDate(task.started_at) }}</span>
          </div>

          <div v-if="task.completed_at" class="meta-item">
            <span class="meta-label">
              <Icon name="check-circle" size="14" />
              Завершена
            </span>
            <span>{{ formatDate(task.completed_at) }}</span>
          </div>

          <div class="meta-item">
            <span class="meta-label">
              <Icon name="user" size="14" />
              Ответственный
            </span>
            <div v-if="task.assigned_user" class="assigned-user">
              <span class="assigned-name">{{ task.assigned_user.name }}</span>
              <span class="assigned-email">{{ task.assigned_user.email }}</span>
              <span class="badge badge-secondary assigned-role">
              {{ getRoleLabel(task.assigned_user.role) }}
            </span>
            </div>
            <span v-else class="unassigned">Не назначен</span>
          </div>
        </div>

        <!-- Связь с замечанием -->
        <div v-if="task.issue" class="task-section">
          <h3>Связанное замечание</h3>
          <router-link
            :to="{ name: 'issue-detail', params: { documentId: task.document_id, issueId: task.issue.id } }"
            class="related-link"
          >
            <Icon name="alert-triangle" size="16" />
            {{ task.issue.title }}
          </router-link>
        </div>

        <!-- Связь с документом -->
        <div v-if="task.document" class="task-section">
          <h3>Связанный документ</h3>
          <router-link
            :to="{ name: 'document-detail', params: { id: task.document.id } }"
            class="related-link"
          >
            <Icon name="file-text" size="16" />
            {{ task.document.title }}
          </router-link>
        </div>

        <!-- Действия -->
        <div class="task-actions">
          <button
            v-for="transition in task.allowed_transitions"
            :key="transition"
            @click="changeStatus(transition)"
            class="btn"
            :class="getTransitionClass(transition)"
          >
            <Icon :name="getTransitionIcon(transition)" size="16" />
            {{ getTransitionLabel(transition) }}
          </button>
        </div>
      </div>

      <!-- Назначение -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="user" size="20" />
            Назначение
          </h2>
        </div>

        <div class="assign-form">
          <select v-model="assignForm.userId" class="form-control">
            <option value="">Не назначен</option>
            <option v-for="member in members" :key="member.user_id" :value="member.user_id">
              {{ member.name }} — {{ member.email }} ({{ getRoleLabel(getMemberRole(member)) }})
            </option>
          </select>

          <button @click="assignTask" :disabled="assigning" class="btn btn-primary">
            <Icon name="user" size="16" />
            {{ assigning ? 'Назначение...' : 'Назначить' }}
          </button>
        </div>
      </div>

      <!-- Параметры -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="settings" size="20" />
            Параметры
          </h2>
        </div>

        <div class="edit-form">
          <div class="form-group">
            <label>Приоритет</label>
            <select v-model="editForm.priority" @change="updatePriority" class="form-control">
              <option value="low">Низкий</option>
              <option value="medium">Средний</option>
              <option value="high">Высокий</option>
              <option value="critical">Критический</option>
            </select>
          </div>

          <div class="form-group">
            <label>Срок выполнения</label>
            <input
              v-model="editForm.due_date"
              type="datetime-local"
              :min="minDueDate"
              @change="updateDueDate"
              class="form-control"
            />
          </div>
        </div>
      </div>

      <!-- Комментарии -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="message-circle" size="20" />
            Комментарии
          </h2>
        </div>

        <form @submit.prevent="addComment" class="comment-form">
          <textarea
            v-model="newComment"
            class="form-control"
            rows="3"
            placeholder="Добавить комментарий..."
            required
          ></textarea>
          <button type="submit" :disabled="submittingComment" class="btn btn-primary">
            <Icon name="send" size="16" />
            {{ submittingComment ? 'Отправка...' : 'Добавить комментарий' }}
          </button>
        </form>

        <div v-if="comments.length === 0" class="empty-state">
          <Icon name="message-circle" size="32" />
          <p>Комментариев пока нет</p>
        </div>

        <div v-else class="comments-list">
          <div v-for="comment in comments" :key="comment.id" class="comment-item">
            <div class="comment-header">
              <div class="comment-author">
                <span class="author-name">{{ comment.user?.name || 'Пользователь' }}</span>
                <span v-if="comment.user?.email" class="author-email">{{ comment.user.email }}</span>
              </div>
              <div class="comment-actions">
                <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
                <button
                  v-if="canDeleteComment(comment)"
                  @click="deleteComment(comment.id)"
                  class="delete-btn"
                >
                  <Icon name="trash" size="14" />
                </button>
              </div>
            </div>
            <div class="comment-content">{{ comment.content }}</div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import { useAuthStore } from '@/stores/auth'
import tasksApi from '@/api/tasks'
import organizationsApi from '@/api/organizations'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const router = useRouter()
const organizationStore = useOrganizationStore()
const authStore = useAuthStore()

const taskId = parseInt(route.params.id)
const orgId = organizationStore.currentOrganizationId

const task = ref(null)
const members = ref([])
const comments = ref([])
const loading = ref(true)
const error = ref(null)
const newComment = ref('')
const submittingComment = ref(false)
const assigning = ref(false)

const assignForm = ref({ userId: '' })
const editForm = ref({ priority: '', due_date: '' })

const minDueDate = computed(() => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
})

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  error.value = null

  try {
    console.log('Loading task:', { orgId, taskId })

    await Promise.all([
      fetchTask(),
      fetchMembers(),
      fetchComments(),
    ])

    // Заполняем формы после загрузки
    if (task.value) {
      assignForm.value.userId = task.value.assigned_to || ''
      editForm.value.priority = task.value.priority
      editForm.value.due_date = task.value.due_date
        ? task.value.due_date.replace(' ', 'T').slice(0, 16)
        : ''
    }
  } catch (err) {
    console.error('Failed to load task:', err)
    error.value = 'Не удалось загрузить данные задачи'
  } finally {
    loading.value = false
  }
}

async function fetchTask() {
  try {
    const { data } = await tasksApi.get(orgId, taskId)
    task.value = data.data
    console.log('Task loaded:', task.value)
  } catch (err) {
    console.error('Failed to fetch task:', err)
    console.error('Response:', err.response?.data)
    throw err
  }
}

async function fetchMembers() {
  try {
    const { data } = await organizationsApi.listMembers(orgId)
    members.value = data.data || []
    console.log('Members loaded:', members.value.length)
  } catch (err) {
    console.error('Failed to fetch members:', err)
    members.value = []
  }
}

async function fetchComments() {
  try {
    const { data } = await tasksApi.listComments(orgId, taskId)
    comments.value = data.data || []
    console.log('Comments loaded:', comments.value.length)
  } catch (err) {
    console.error('Failed to fetch comments:', err)
    comments.value = []
  }
}

async function changeStatus(newStatus) {
  try {
    await tasksApi.updateStatus(orgId, taskId, { status: newStatus })
    await fetchTask()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
  }
}

async function assignTask() {
  try {
    assigning.value = true

    if (assignForm.value.userId) {
      await tasksApi.assign(orgId, taskId, {
        assigned_to: parseInt(assignForm.value.userId),
      })
    } else {
      await tasksApi.assign(orgId, taskId, { assigned_to: null })
    }

    await fetchTask()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка назначения')
  } finally {
    assigning.value = false
  }
}

async function updatePriority() {
  try {
    await tasksApi.update(orgId, taskId, {
      priority: editForm.value.priority,
    })
    await fetchTask()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка обновления приоритета')
    editForm.value.priority = task.value.priority
  }
}

async function updateDueDate() {
  try {
    await tasksApi.update(orgId, taskId, {
      due_date: editForm.value.due_date,
    })
    await fetchTask()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка обновления срока')
    editForm.value.due_date = task.value.due_date
  }
}

async function addComment() {
  try {
    submittingComment.value = true
    await tasksApi.addComment(orgId, taskId, {
      content: newComment.value,
    })
    newComment.value = ''
    await fetchComments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка добавления комментария')
  } finally {
    submittingComment.value = false
  }
}

function canDeleteComment(comment) {
  return comment.user_id === authStore.user?.id
}

async function deleteComment(commentId) {
  if (!confirm('Удалить комментарий?')) return

  try {
    await tasksApi.deleteComment(orgId, taskId, commentId)
    await fetchComments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления комментария')
  }
}

async function deleteTask() {
  if (!confirm('Удалить задачу? Это действие нельзя отменить.')) return

  try {
    await tasksApi.delete(orgId, taskId)
    router.push({ name: 'tasks' })
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления задачи')
  }
}

function getPriorityClass(priority) {
  const map = {
    critical: 'badge-danger',
    high: 'badge-warning',
    medium: 'badge-info',
    low: 'badge-secondary',
  }
  return map[priority] || 'badge-secondary'
}

function getStatusClass(status) {
  const map = {
    new: 'badge-secondary',
    in_progress: 'badge-info',
    blocked: 'badge-warning',
    done: 'badge-success',
    cancelled: 'badge-secondary',
  }
  return map[status] || 'badge-secondary'
}

function getStatusIcon(status) {
  const map = {
    new: 'circle',
    in_progress: 'activity',
    blocked: 'alert-triangle',
    done: 'check-circle',
    cancelled: 'x-circle',
  }
  return map[status] || 'circle'
}

function getTransitionClass(transition) {
  const map = {
    in_progress: 'btn-primary',
    done: 'btn-success',
    blocked: 'btn-secondary',
    cancelled: 'btn-danger',
    new: 'btn-primary',
  }
  return map[transition] || 'btn-secondary'
}

function getTransitionIcon(transition) {
  const map = {
    in_progress: 'activity',
    done: 'check-circle',
    blocked: 'alert-triangle',
    cancelled: 'x',
    new: 'rotate-ccw',
  }
  return map[transition] || 'check'
}

function getTransitionLabel(transition) {
  const map = {
    in_progress: 'Начать',
    done: 'Завершить',
    blocked: 'Заблокировать',
    cancelled: 'Отменить',
    new: 'Переоткрыть',
  }
  return map[transition] || transition
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
function getMemberRole(member) {
  if (member.pivot?.role) {
    return member.pivot.role
  }
  if (member.role) {
    return member.role
  }
  if (member.membership?.role) {
    return member.membership.role
  }
  return 'employee'
}
</script>

<style scoped>
.task-detail {
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

/* Loading & Error states */
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
  margin: 0 0 0.5rem;
  font-size: 0.9rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Badges */
.task-badges {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

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

/* Sections */
.task-section {
  margin-bottom: 1.5rem;
}

.task-section p {
  margin: 0;
  line-height: 1.6;
  color: #333;
}

.related-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a90d9;
  text-decoration: none;
}

.related-link:hover {
  text-decoration: underline;
}

/* Meta */
.task-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meta-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: #666;
}

.overdue {
  color: #dc3545;
  font-weight: 500;
}

.unassigned {
  color: #999;
  font-style: italic;
}

.assigned-user {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.assigned-name {
  font-weight: 500;
}

.assigned-email {
  font-size: 0.85rem;
  color: #666;
}

.assigned-role {
  align-self: flex-start;
  font-size: 0.7rem;
}

/* Actions */
.task-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
  flex-wrap: wrap;
}

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

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover {
  background: #218838;
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

/* Assign form */
.assign-form {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.assign-form .form-control {
  flex: 1;
}

/* Edit form */
.edit-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

/* Comments */
.comment-form {
  margin-bottom: 1.5rem;
}

.comment-form textarea {
  margin-bottom: 0.75rem;
}

.comment-form .btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.comment-item {
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.comment-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.comment-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.author-name {
  font-weight: 500;
}

.author-email {
  color: #666;
  font-size: 0.85rem;
}

.comment-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.comment-date {
  color: #999;
  font-size: 0.8rem;
}

.delete-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #999;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.delete-btn:hover {
  color: #dc3545;
  background: #fee;
}

.comment-content {
  line-height: 1.5;
  color: #333;
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

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}
</style>

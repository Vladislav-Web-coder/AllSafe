<template>
  <div class="tasks-page">
    <div class="page-header">
      <h1>Задачи</h1>

      <div class="header-actions">
        <button @click="showCreateModal = true" class="btn btn-primary">
          <Icon name="plus" size="16" />
          Создать задачу
        </button>
      </div>
    </div>

    <!-- Вкладки -->
    <div class="view-tabs">
      <button
        @click="activeTab = 'active'"
        :class="{ active: activeTab === 'active' }"
        class="tab-btn"
      >
        <Icon name="activity" size="16" />
        Активные
        <span class="tab-count">{{ activeTasks.length }}</span>
      </button>
      <button
        @click="activeTab = 'completed'"
        :class="{ active: activeTab === 'completed' }"
        class="tab-btn"
      >
        <Icon name="check-circle" size="16" />
        Завершённые
        <span class="tab-count">{{ completedTasks.length }}</span>
      </button>
      <button
        @click="activeTab = 'my'"
        :class="{ active: activeTab === 'my' }"
        class="tab-btn"
      >
        <Icon name="user" size="16" />
        Мои задачи
        <span class="tab-count">{{ myTasks.length }}</span>
      </button>
    </div>

    <!-- Статистика -->
    <div v-if="stats" class="stats-row">
      <div class="stat-item">
        <span class="stat-value">{{ stats.new }}</span>
        <span class="stat-label">Новые</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ stats.in_progress }}</span>
        <span class="stat-label">В работе</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ stats.blocked }}</span>
        <span class="stat-label">Заблокированы</span>
      </div>
      <div class="stat-item">
        <span class="stat-value">{{ stats.done }}</span>
        <span class="stat-label">Выполнены</span>
      </div>
      <div class="stat-item" :class="{ 'stat-alert': stats.overdue > 0 }">
        <span class="stat-value">{{ stats.overdue }}</span>
        <span class="stat-label">Просрочены</span>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="displayedTasks.length === 0" class="empty-state">
      <Icon name="check-circle" size="48" />
      <h2>Задач нет</h2>
      <p>Создайте задачу или дождитесь автоматического создания из замечаний</p>
    </div>

    <div v-else class="tasks-table-wrapper">
      <table class="table">
        <thead>
        <tr>
          <th>Название</th>
          <th>Приоритет</th>
          <th>Статус</th>
          <th>Источник</th>
          <th>Срок</th>
          <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="task in displayedTasks"
          :key="task.id"
          @click="goToTask(task.id)"
          class="task-row"
        >
          <td>
            <div class="task-title">{{ task.title }}</div>
            <div v-if="task.issue" class="task-source">
              <Icon name="alert-triangle" size="12" />
              {{ task.issue.title }}
            </div>
          </td>
          <td>
              <span class="badge" :class="getPriorityClass(task.priority)">
                <Icon name="zap" size="14" />
                {{ task.priority_label }}
              </span>
          </td>
          <td>
              <span class="badge" :class="getStatusClass(task.status)">
                <Icon :name="getStatusIcon(task.status)" size="14" />
                {{ task.status_label }}
              </span>
          </td>
          <td>
              <span class="badge badge-secondary">
                <Icon name="file" size="14" />
                {{ task.source_type_label }}
              </span>
          </td>
          <td :class="{ overdue: task.is_overdue }">
            {{ task.due_date ? formatDate(task.due_date) : '—' }}
            <span v-if="task.is_overdue" class="overdue-badge">Просрочена</span>
          </td>
          <td @click.stop>
            <div class="task-actions">
              <button
                v-for="transition in task.allowed_transitions"
                :key="transition"
                @click="changeStatus(task.id, transition)"
                class="btn btn-sm"
                :class="getTransitionClass(transition)"
              >
                <Icon :name="getTransitionIcon(transition)" size="14" />
              </button>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Модальное окно создания -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="plus" size="20" />
            Создать задачу
          </h2>
          <button @click="showCreateModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <form @submit.prevent="createTask">
          <div class="form-group">
            <label>Название</label>
            <input v-model="createForm.title" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Описание</label>
            <textarea v-model="createForm.description" class="form-control" rows="3"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Приоритет</label>
              <select v-model="createForm.priority" class="form-control">
                <option value="low">Низкий</option>
                <option value="medium">Средний</option>
                <option value="high">Высокий</option>
                <option value="critical">Критический</option>
              </select>
            </div>

            <div class="form-group">
              <label>Срок</label>
              <input v-model="createForm.due_date" type="datetime-local" :min="minDueDate" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label>Ответственный</label>
            <select v-model="createForm.assigned_to" class="form-control">
              <option value="">Не назначен</option>
              <option v-for="member in members" :key="member.user_id" :value="member.user_id">
                {{ member.name }} — {{ member.email }}
              </option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" @click="showCreateModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="creating" class="btn btn-primary">
              <Icon name="plus" size="16" />
              {{ creating ? 'Создание...' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import { useAuthStore } from '@/stores/auth'
import tasksApi from '@/api/tasks'
import organizationsApi from '@/api/organizations'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()
const authStore = useAuthStore()

const allTasks = ref([])
const members = ref([])
const stats = ref(null)
const loading = ref(true)
const activeTab = ref('active')
const showCreateModal = ref(false)
const creating = ref(false)

const createForm = ref({
  title: '',
  description: '',
  priority: 'medium',
  due_date: '',
  assigned_to: '',
})

const minDueDate = computed(() => {
  const now = new Date()
  const year = now.getFullYear()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  const hours = String(now.getHours()).padStart(2, '0')
  const minutes = String(now.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
})

const activeTasks = computed(() =>
  allTasks.value.filter(t => ['new', 'in_progress', 'blocked'].includes(t.status))
)

const completedTasks = computed(() =>
  allTasks.value.filter(t => ['done', 'cancelled'].includes(t.status))
)

const myTasks = computed(() =>
  allTasks.value.filter(t => t.assigned_to === authStore.user?.id && !['done', 'cancelled'].includes(t.status))
)

const displayedTasks = computed(() => {
  if (activeTab.value === 'active') return activeTasks.value
  if (activeTab.value === 'completed') return completedTasks.value
  if (activeTab.value === 'my') return myTasks.value
  return allTasks.value
})

onMounted(async () => {
  await Promise.all([
    fetchTasks(),
    fetchStats(),
    fetchMembers(),
  ])
})

async function fetchTasks() {
  try {
    loading.value = true
    const { data } = await tasksApi.list(organizationStore.currentOrganizationId)
    allTasks.value = data.data
  } catch (err) {
    console.error('Failed to fetch tasks:', err)
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const { data } = await tasksApi.stats(organizationStore.currentOrganizationId)
    stats.value = data.data
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

async function fetchMembers() {
  try {
    const { data } = await organizationsApi.listMembers(organizationStore.currentOrganizationId)
    members.value = data.data || []
  } catch (err) {
    console.error('Failed to fetch members:', err)
  }
}

function goToTask(taskId) {
  router.push({ name: 'task-detail', params: { id: taskId } })
}

async function createTask() {
  try {
    creating.value = true

    const payload = {
      title: createForm.value.title,
      description: createForm.value.description,
      priority: createForm.value.priority,
      due_date: createForm.value.due_date || null,
      assigned_to: createForm.value.assigned_to ? parseInt(createForm.value.assigned_to) : null,
    }

    await tasksApi.create(
      organizationStore.currentOrganizationId,
      payload
    )

    showCreateModal.value = false
    createForm.value = { title: '', description: '', priority: 'medium', due_date: '', assigned_to: '' }

    await Promise.all([
      fetchTasks(),
      fetchStats(),
    ])
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка создания задачи')
  } finally {
    creating.value = false
  }
}

async function changeStatus(taskId, newStatus) {
  try {
    await tasksApi.updateStatus(
      organizationStore.currentOrganizationId,
      taskId,
      { status: newStatus }
    )

    await Promise.all([
      fetchTasks(),
      fetchStats(),
    ])
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
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
.tasks-page {
  max-width: 1200px;
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

/* Tabs */
.view-tabs {
  display: flex;
  gap: 0.5rem;
  background: white;
  border-radius: 8px;
  padding: 0.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: #f0f0f0;
}

.tab-btn.active {
  background: #4a90d9;
  color: white;
}

.tab-count {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.1rem 0.5rem;
  border-radius: 10px;
  font-size: 0.75rem;
}

.tab-btn:not(.active) .tab-count {
  background: #e0e0e0;
  color: #666;
}

/* Stats */
.stats-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  padding: 1rem 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.stat-item {
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

.stat-alert .stat-value {
  color: #dc3545;
}

/* Loading & Empty */
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

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  background: white;
  border-radius: 12px;
  color: #28a745;
}

.empty-state h2 {
  color: #333;
  margin: 0;
}

.empty-state p {
  color: #666;
  margin: 0;
}

/* Table */
.tasks-table-wrapper {
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

.task-row {
  cursor: pointer;
}

.task-row:hover td {
  background-color: #f9f9f9;
}

.task-title {
  font-weight: 500;
}

.task-source {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  color: #666;
  margin-top: 0.25rem;
}

.task-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.overdue {
  color: #dc3545;
}

.overdue-badge {
  font-size: 0.7rem;
  background: #f8d7da;
  color: #721c24;
  padding: 0.1rem 0.4rem;
  border-radius: 3px;
  margin-left: 0.5rem;
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
}

.btn-sm {
  padding: 0.35rem 0.5rem;
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
</style>

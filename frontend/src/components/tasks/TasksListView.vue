<template>
  <div class="tasks-page">
    <div class="page-header">
      <h1>Задачи</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        Создать задачу
      </button>
    </div>

    <div v-if="loading" class="loading">Загрузка...</div>

    <div v-else-if="tasks.length === 0" class="empty">
      Задач нет
    </div>

    <table v-else class="table">
      <thead>
      <tr>
        <th>Название</th>
        <th>Приоритет</th>
        <th>Статус</th>
        <th>Срок</th>
        <th>Действия</th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="task in tasks" :key="task.id">
        <td>{{ task.title }}</td>
        <td>
            <span class="badge" :class="getPriorityClass(task.priority)">
              {{ task.priority_label }}
            </span>
        </td>
        <td>
            <span class="badge" :class="getStatusClass(task.status)">
              {{ task.status_label }}
            </span>
        </td>
        <td :class="{ overdue: task.is_overdue }">
          {{ task.due_date || '—' }}
          <span v-if="task.is_overdue" class="overdue-badge">Просрочена</span>
        </td>
        <td>
          <button
            v-if="task.status === 'new'"
            @click="changeStatus(task.id, 'in_progress')"
            class="btn btn-sm btn-primary"
          >
            Начать
          </button>
          <button
            v-if="task.status === 'in_progress'"
            @click="changeStatus(task.id, 'done')"
            class="btn btn-sm btn-success"
          >
            Завершить
          </button>
        </td>
      </tr>
      </tbody>
    </table>

    <!-- Модальное окно создания -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h2>Создать задачу</h2>

        <form @submit.prevent="createTask">
          <div class="form-group">
            <label>Название</label>
            <input v-model="createForm.title" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Описание</label>
            <textarea v-model="createForm.description" class="form-control" rows="3"></textarea>
          </div>

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
            <input v-model="createForm.due_date" type="date" class="form-control" />
          </div>

          <div class="modal-actions">
            <button type="button" @click="showCreateModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" class="btn btn-primary">Создать</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import apiClient from '@/api/client'

const organizationId = 1

const tasks = ref([])
const loading = ref(true)
const showCreateModal = ref(false)
const createForm = ref({
  title: '',
  description: '',
  priority: 'medium',
  due_date: '',
})

onMounted(() => {
  fetchTasks()
})

async function fetchTasks() {
  try {
    loading.value = true
    const { data } = await apiClient.get(`/organizations/${organizationId}/tasks`)
    tasks.value = data.data
  } catch (err) {
    console.error('Failed to fetch tasks:', err)
  } finally {
    loading.value = false
  }
}

async function createTask() {
  try {
    await apiClient.post(`/organizations/${organizationId}/tasks`, createForm.value)
    showCreateModal.value = false
    createForm.value = { title: '', description: '', priority: 'medium', due_date: '' }
    await fetchTasks()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка создания задачи')
  }
}

async function changeStatus(taskId, newStatus) {
  try {
    await apiClient.patch(`/organizations/${organizationId}/tasks/${taskId}/status`, {
      status: newStatus,
    })
    await fetchTasks()
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
</script>

<style scoped>
.tasks-page {
  max-width: 1000px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.overdue {
  color: #c33;
}

.overdue-badge {
  font-size: 0.75rem;
  background: #f8d7da;
  color: #721c24;
  padding: 0.1rem 0.4rem;
  border-radius: 3px;
  margin-left: 0.5rem;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.8rem;
}

.loading, .empty {
  padding: 2rem;
  text-align: center;
  color: #666;
}

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
  border-radius: 8px;
  padding: 2rem;
  width: 100%;
  max-width: 500px;
}

.modal h2 {
  margin-bottom: 1.5rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}
</style>

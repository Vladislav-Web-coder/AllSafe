<template>
  <div class="notifications-page">
    <div class="page-header">
      <h1>
        <Icon name="bell" size="24" />
        Уведомления
      </h1>

      <div class="header-actions">
        <button @click="markAllAsRead" class="btn btn-secondary">
          <Icon name="check" size="16" />
          Отметить все как прочитанные
        </button>
        <button @click="clearAll" class="btn btn-danger">
          <Icon name="trash" size="16" />
          Очистить все
        </button>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="filters">
      <button
        @click="filterUnread = false"
        :class="{ active: !filterUnread }"
        class="filter-btn"
      >
        <Icon name="list" size="16" />
        Все
      </button>
      <button
        @click="filterUnread = true"
        :class="{ active: filterUnread }"
        class="filter-btn"
      >
        <Icon name="bell" size="16" />
        Непрочитанные
        <span v-if="unreadCount > 0" class="unread-badge">{{ unreadCount }}</span>
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="notifications.length === 0" class="empty-state">
      <Icon name="bell" size="48" />
      <h2>Уведомлений нет</h2>
      <p>Здесь будут появляться уведомления о событиях в вашей организации</p>
    </div>

    <div v-else class="notifications-list">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="notification-item"
        :class="{ unread: !notification.read_at }"
        @click="handleNotificationClick(notification)"
      >
        <div class="notification-icon">
          <Icon :name="getNotificationIcon(notification.type)" size="24" />
        </div>

        <div class="notification-content">
          <div class="notification-title">{{ notification.title }}</div>
          <div class="notification-message">{{ notification.message }}</div>
          <div class="notification-time">
            <Icon name="clock" size="12" />
            {{ formatDate(notification.created_at) }}
          </div>
        </div>

        <div class="notification-actions">
          <button
            v-if="!notification.read_at"
            @click.stop="markAsRead(notification.id)"
            class="action-btn"
            title="Отметить как прочитанное"
          >
            <Icon name="check" size="16" />
          </button>
          <button
            @click.stop="deleteNotification(notification.id)"
            class="action-btn delete"
            title="Удалить"
          >
            <Icon name="x" size="16" />
          </button>
        </div>
      </div>
    </div>

    <!-- Пагинация -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page <= 1"
        class="btn btn-sm btn-secondary"
      >
        <Icon name="chevron-left" size="16" />
        Назад
      </button>

      <span class="page-info">
        Страница {{ pagination.current_page }} из {{ pagination.last_page }}
      </span>

      <button
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page >= pagination.last_page"
        class="btn btn-sm btn-secondary"
      >
        Вперёд
        <Icon name="chevron-right" size="16" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import apiClient from '@/api/client'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()

const notifications = ref([])
const loading = ref(true)
const filterUnread = ref(false)
const unreadCount = ref(0)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
})

let pollInterval = null

onMounted(async () => {
  await Promise.all([
    fetchNotifications(),
    fetchUnreadCount(),
  ])

  pollInterval = setInterval(() => {
    fetchUnreadCount()
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})

watch(filterUnread, () => {
  fetchNotifications()
})

async function fetchNotifications(page = 1) {
  try {
    loading.value = true

    const params = {
      page,
      per_page: 20,
      organization_id: organizationStore.currentOrganizationId,
    }

    if (filterUnread.value) {
      params.unread_only = 'true'
    }

    const { data } = await apiClient.get('/notifications', { params })

    notifications.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
  } catch (err) {
    console.error('Failed to fetch notifications:', err)
  } finally {
    loading.value = false
  }
}

async function fetchUnreadCount() {
  try {
    const { data } = await apiClient.get('/notifications/unread-count', {
      params: { organization_id: organizationStore.currentOrganizationId },
    })
    unreadCount.value = data.count
  } catch (err) {
    console.error('Failed to fetch unread count:', err)
  }
}

async function markAsRead(notificationId) {
  try {
    await apiClient.post(`/notifications/${notificationId}/read`)
    await Promise.all([
      fetchNotifications(),
      fetchUnreadCount(),
    ])
  } catch (err) {
    console.error('Failed to mark as read:', err)
  }
}

async function markAllAsRead() {
  try {
    await apiClient.post('/notifications/mark-all-read', null, {
      params: { organization_id: organizationStore.currentOrganizationId },
    })
    await Promise.all([
      fetchNotifications(),
      fetchUnreadCount(),
    ])
  } catch (err) {
    console.error('Failed to mark all as read:', err)
  }
}

async function deleteNotification(notificationId) {
  try {
    await apiClient.delete(`/notifications/${notificationId}`)
    await Promise.all([
      fetchNotifications(),
      fetchUnreadCount(),
    ])
  } catch (err) {
    console.error('Failed to delete notification:', err)
  }
}

async function clearAll() {
  if (!confirm('Удалить все уведомления?')) return

  try {
    await apiClient.delete('/notifications/all', {
      params: { organization_id: organizationStore.currentOrganizationId },
    })
    await Promise.all([
      fetchNotifications(),
      fetchUnreadCount(),
    ])
  } catch (err) {
    console.error('Failed to clear notifications:', err)
  }
}

function handleNotificationClick(notification) {
  if (!notification.read_at) {
    markAsRead(notification.id)
  }

  if (notification.link_type && notification.link_id) {
    if (notification.link_type === 'document') {
      router.push({ name: 'document-detail', params: { id: notification.link_id } })
    } else if (notification.link_type === 'task') {
      router.push({ name: 'task-detail', params: { id: notification.link_id } })
    } else if (notification.link_type === 'issue') {
      router.push({ name: 'documents' })
    }
  }
}

function changePage(page) {
  fetchNotifications(page)
}

function getNotificationIcon(type) {
  const map = {
    analysis_completed: 'check-circle',
    analysis_failed: 'x-circle',
    task_overdue: 'clock',
    task_assigned: 'user',
    issue_added: 'alert-triangle',
    issue_status_changed: 'refresh-cw',
    document_generated: 'file-text',
    invitation: 'mail',
  }
  return map[type] || 'bell'
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
.notifications-page {
  max-width: 800px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

/* Filters */
.filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn:hover {
  background: #f0f0f0;
}

.filter-btn.active {
  background: #4a90d9;
  color: white;
  border-color: #4a90d9;
}

.unread-badge {
  background: #dc3545;
  color: white;
  padding: 0.1rem 0.4rem;
  border-radius: 10px;
  font-size: 0.75rem;
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

/* Notifications list */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notification-item {
  display: flex;
  gap: 1rem;
  padding: 1.25rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  cursor: pointer;
  transition: all 0.2s;
}

.notification-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.notification-item.unread {
  background: #f0f7ff;
  border-left: 4px solid #4a90d9;
}

.notification-icon {
  color: #4a90d9;
  padding-top: 0.25rem;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.notification-message {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.notification-time {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  color: #999;
}

.notification-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

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

.action-btn.delete:hover {
  background: #fee;
  border-color: #dc3545;
  color: #dc3545;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.page-info {
  color: #666;
  font-size: 0.9rem;
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

.btn-sm {
  padding: 0.35rem 0.75rem;
  font-size: 0.85rem;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

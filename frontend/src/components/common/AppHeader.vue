<template>
  <header class="header">
    <div class="header-left">
      <button @click="$emit('toggle-sidebar')" class="toggle-button">
        <Icon name="menu" size="20" />
      </button>
    </div>

    <div class="header-right">
      <!-- Уведомления -->
      <router-link to="/notifications" class="notification-bell">
        <Icon name="bell" size="20" />
        <span v-if="unreadCount > 0" class="notification-count">
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </router-link>

      <div class="user-menu">
        <div class="user-avatar">
          {{ getInitials(authStore.userName) }}
        </div>
        <div class="user-info">
          <span class="user-name">{{ authStore.userName }}</span>
          <span class="user-email">{{ authStore.userEmail }}</span>
        </div>
      </div>

      <button @click="handleLogout" class="logout-button" title="Выйти">
        <Icon name="log-out" size="18" />
      </button>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOrganizationStore } from '@/stores/organization'
import apiClient from '@/api/client'
import Icon from '@/components/common/Icon.vue'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const authStore = useAuthStore()
const organizationStore = useOrganizationStore()

const unreadCount = ref(0)
let pollInterval = null

onMounted(async () => {
  await fetchUnreadCount()

  pollInterval = setInterval(() => {
    fetchUnreadCount()
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
})

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

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 1.5rem;
  background: white;
  border-bottom: 1px solid #eee;
}

.header-left {
  display: flex;
  align-items: center;
}

.toggle-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  color: #666;
  transition: all 0.2s;
}

.toggle-button:hover {
  background-color: #f0f0f0;
  color: #333;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notification-bell {
  position: relative;
  text-decoration: none;
  padding: 0.5rem;
  border-radius: 6px;
  color: #666;
  transition: all 0.2s;
}

.notification-bell:hover {
  background-color: #f0f0f0;
  color: #333;
}

.notification-count {
  position: absolute;
  top: 0;
  right: 0;
  background: #dc3545;
  color: white;
  font-size: 0.6rem;
  padding: 0.15rem 0.35rem;
  border-radius: 10px;
  min-width: 16px;
  text-align: center;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 500;
  font-size: 0.9rem;
}

.user-email {
  font-size: 0.8rem;
  color: #666;
}

.logout-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  color: #666;
  transition: all 0.2s;
}

.logout-button:hover {
  background-color: #fee;
  color: #dc3545;
}
</style>

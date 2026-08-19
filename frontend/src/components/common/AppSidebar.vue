<template>
  <aside class="sidebar" :class="{ collapsed }">
    <div class="sidebar-logo">
      <Icon name="shield" size="24" />
      <span v-if="!collapsed" class="logo-text">ИБ Комплаенс</span>
    </div>

    <nav class="sidebar-nav">
      <router-link to="/" class="nav-item" exact-active-class="active">
        <Icon name="bar-chart" size="20" />
        <span v-if="!collapsed" class="nav-label">Дашборд</span>
      </router-link>

      <router-link to="/documents" class="nav-item" active-class="active">
        <Icon name="file-text" size="20" />
        <span v-if="!collapsed" class="nav-label">Документы</span>
      </router-link>

      <router-link to="/required-documents" class="nav-item" active-class="active">
        <Icon name="clipboard" size="20" />
        <span v-if="!collapsed" class="nav-label">Обязательные документы</span>
      </router-link>

      <router-link to="/generation" class="nav-item" active-class="active">
        <Icon name="zap" size="20" />
        <span v-if="!collapsed" class="nav-label">Генерация</span>
      </router-link>

      <router-link to="/issues" class="nav-item" active-class="active">
        <Icon name="alert-triangle" size="20" />
        <span v-if="!collapsed" class="nav-label">Замечания</span>
      </router-link>

      <router-link to="/tasks" class="nav-item" active-class="active">
        <Icon name="check-circle" size="20" />
        <span v-if="!collapsed" class="nav-label">Задачи</span>
      </router-link>

      <div v-if="!collapsed" class="nav-section">Организация</div>
      <div v-else class="nav-divider"></div>

      <router-link to="/organization/profile" class="nav-item" active-class="active">
        <Icon name="building" size="20" />
        <span v-if="!collapsed" class="nav-label">Профиль</span>
      </router-link>

      <router-link to="/organization/members" class="nav-item" active-class="active">
        <Icon name="users" size="20" />
        <span v-if="!collapsed" class="nav-label">Участники</span>
      </router-link>

      <router-link to="/audit" class="nav-item" active-class="active">
        <Icon name="list" size="20" />
        <span v-if="!collapsed" class="nav-label">Аудит</span>
      </router-link>

      <router-link to="/notifications" class="nav-item" active-class="active">
        <Icon name="bell" size="20" />
        <span v-if="!collapsed" class="nav-label">Уведомления</span>
      </router-link>

      <router-link to="/settings" class="nav-item" active-class="active">
        <Icon name="settings" size="20" />
        <span v-if="!collapsed" class="nav-label">Настройки</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup>
import Icon from '@/components/common/Icon.vue'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 250px;
  height: 100vh;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
  color: white;
  overflow-y: auto;
  transition: width 0.3s;
  z-index: 100;
}

.sidebar.collapsed {
  width: 64px;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  height: 64px;
  padding: 0 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar.collapsed .sidebar-logo {
  justify-content: center;
  padding: 0;
}

.logo-text {
  font-size: 1rem;
  font-weight: 600;
  white-space: nowrap;
}

.sidebar-nav {
  padding: 0.75rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: all 0.2s;
  white-space: nowrap;
  border-left: 3px solid transparent;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 0.75rem;
}

.nav-item:hover {
  color: white;
  background-color: rgba(255, 255, 255, 0.05);
}

.nav-item.active {
  color: white;
  background-color: rgba(74, 144, 217, 0.15);
  border-left-color: #4a90d9;
}

.nav-label {
  font-size: 0.9rem;
}

.nav-section {
  padding: 1.25rem 1.25rem 0.5rem;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: rgba(255, 255, 255, 0.4);
}

.nav-divider {
  margin: 0.75rem 0;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

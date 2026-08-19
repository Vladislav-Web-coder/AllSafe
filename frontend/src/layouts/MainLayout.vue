<template>
  <div class="layout">
    <AppSidebar :collapsed="sidebarCollapsed" />

    <div class="layout-content" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <AppHeader @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />

      <main class="layout-main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppSidebar from '@/components/common/AppSidebar.vue'
import AppHeader from '@/components/common/AppHeader.vue'

const sidebarCollapsed = ref(false)
</script>

<style scoped>
.layout {
  display: flex;
  width: 100%;
  min-height: 100vh;
}

.layout-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-left: 250px;
  transition: margin-left 0.3s;
  min-height: 100vh;
}

.layout-content.sidebar-collapsed {
  margin-left: 60px;
}

.layout-main {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}
</style>

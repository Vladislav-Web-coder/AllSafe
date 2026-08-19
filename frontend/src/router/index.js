import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/auth/RegisterView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/auth/ForgotPasswordView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/accept-invitation',
    name: 'accept-invitation',
    component: () => import('@/views/auth/AcceptInvitationView.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/dashboard/DashboardView.vue'),
      },
      {
        path: 'documents',
        name: 'documents',
        component: () => import('@/views/documents/DocumentsListView.vue'),
      },
      {
        path: 'documents/:id',
        name: 'document-detail',
        component: () => import('@/views/documents/DocumentDetailView.vue'),
        props: true,
      },
      {
        path: 'documents/:documentId/issues/:issueId',
        name: 'issue-detail',
        component: () => import('@/views/issues/IssueDetailView.vue'),
        props: true,
      },
      {
        path: 'required-documents',
        name: 'required-documents',
        component: () => import('@/views/documents/RequiredDocumentsView.vue'),
      },
      {
        path: 'generation',
        name: 'generation',
        component: () => import('@/views/generation/GenerationView.vue'),
      },
      {
        path: 'issues',
        name: 'issues',
        component: () => import('@/views/issues/IssuesListView.vue'),
      },
      {
        path: 'tasks',
        name: 'tasks',
        component: () => import('@/views/tasks/TasksListView.vue'),
      },
      {
        path: 'tasks/:id',
        name: 'task-detail',
        component: () => import('@/views/tasks/TaskDetailView.vue'),
        props: true,
      },
      {
        path: 'organization/profile',
        name: 'organization-profile',
        component: () => import('@/views/organization/ProfileView.vue'),
      },
      {
        path: 'organization/members',
        name: 'organization-members',
        component: () => import('@/views/organization/MembersView.vue'),
      },
      {
        path: 'audit',
        name: 'audit',
        component: () => import('@/views/audit/AuditView.vue'),
      },
      {
        path: 'audit/:auditLogId',
        name: 'audit-detail',
        component: () => import('@/views/audit/AuditDetailView.vue'),
        props: true,
      },
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('@/views/notifications/NotificationsView.vue'),
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/settings/SettingsView.vue'),
      },
      {
        path: '/organizations',
        name: 'organizations',
        component: () => import('@/views/organizations/OrganizationsListView.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (!authStore.initialized) {
    await authStore.initialize()
  }

  if (to.meta.requiresAuth !== false && !authStore.isAuthenticated) {
    next({ name: 'login' })
  } else if (to.name === 'login' && authStore.isAuthenticated) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router

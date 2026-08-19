<template>
  <div class="issue-detail">
    <div class="page-header">
      <div class="header-left">
        <router-link to="/issues" class="back-link">
          <Icon name="arrow-left" size="16" />
          <span>Замечания</span>
        </router-link>
        <h1>{{ issue?.title || 'Замечание' }}</h1>
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

    <template v-else-if="issue">
      <!-- Информация о замечании -->
      <div class="card">
        <div class="issue-badges">
          <span class="badge" :class="getSeverityClass(issue.severity)">
            <Icon :name="getSeverityIcon(issue.severity)" size="14" />
            {{ issue.severity_label }}
          </span>
          <span class="badge" :class="getStatusClass(issue.status)">
            <Icon :name="getStatusIcon(issue.status)" size="14" />
            {{ issue.status_label }}
          </span>
        </div>

        <div v-if="issue.description" class="issue-section">
          <h3>Описание</h3>
          <p>{{ issue.description }}</p>
        </div>

        <div v-if="issue.recommendation" class="issue-section">
          <h3>Рекомендация</h3>
          <p>{{ issue.recommendation }}</p>
        </div>

        <div v-if="issue.legal_basis?.length" class="issue-section">
          <h3>Нормативное основание</h3>
          <div class="legal-tags">
            <span v-for="basis in issue.legal_basis" :key="basis" class="legal-tag">
              <Icon name="book" size="14" />
              {{ basis }}
            </span>
          </div>
        </div>

        <div v-if="issue.document" class="issue-section">
          <h3>Документ</h3>
          <router-link :to="{ name: 'document-detail', params: { id: issue.document.id } }" class="document-link">
            <Icon name="file-text" size="16" />
            {{ issue.document.title }}
          </router-link>
        </div>

        <div class="issue-actions">
          <button
            v-for="transition in issue.allowed_transitions"
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
                <span v-if="comment.user?.role" class="badge badge-secondary author-role">
                  {{ getRoleLabel(comment.user.role) }}
                </span>
              </div>
              <div class="comment-actions">
                <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
                <button
                  v-if="canDeleteComment(comment)"
                  @click="deleteComment(comment.id)"
                  class="delete-btn"
                  title="Удалить комментарий"
                >
                  <Icon name="trash" size="14" />
                </button>
              </div>
            </div>
            <div class="comment-content">{{ comment.content }}</div>
          </div>
        </div>
      </div>

      <!-- История изменений -->
      <div class="card">
        <div class="card-header">
          <h2>
            <Icon name="clock" size="20" />
            История изменений
          </h2>
        </div>

        <div v-if="history.length === 0" class="empty-state">
          <Icon name="clock" size="32" />
          <p>История изменений пуста</p>
        </div>

        <div v-else class="history-list">
          <div v-for="entry in history" :key="entry.id" class="history-item">
            <div class="history-icon">
              <Icon :name="getHistoryIcon(entry.change_type)" size="20" />
            </div>

            <div class="history-content">
              <div class="history-header">
                <div class="history-user">
                  <span class="user-name">{{ entry.user?.name || 'Система' }}</span>
                  <span v-if="entry.user?.email" class="user-email">{{ entry.user.email }}</span>
                  <span v-if="entry.user?.role" class="badge badge-secondary user-role">
                    {{ getRoleLabel(entry.user.role) }}
                  </span>
                </div>
                <span class="history-date">{{ formatDate(entry.created_at) }}</span>
              </div>

              <div v-if="entry.change_type === 'status_changed'" class="history-description">
                Статус изменён с <strong>{{ getStatusLabel(entry.old_value) }}</strong>
                на <strong>{{ getStatusLabel(entry.new_value) }}</strong>
              </div>

              <div v-else-if="entry.change_type === 'comment_added'" class="history-description">
                Добавлен комментарий
              </div>

              <div v-if="entry.comment" class="history-comment">
                "{{ entry.comment }}"
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import { useAuthStore } from '@/stores/auth'
import issuesApi from '@/api/issues'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const organizationStore = useOrganizationStore()
const authStore = useAuthStore()

const issueId = parseInt(route.params.issueId)
const documentId = parseInt(route.params.documentId)
const orgId = organizationStore.currentOrganizationId

const issue = ref(null)
const comments = ref([])
const history = ref([])
const loading = ref(true)
const error = ref(null)
const newComment = ref('')
const submittingComment = ref(false)

onMounted(() => {
  loadData()
})

async function loadData() {
  loading.value = true
  error.value = null

  try {
    console.log('Loading issue:', { orgId, documentId, issueId })

    await Promise.all([
      fetchIssue(),
      fetchComments(),
      fetchHistory(),
    ])
  } catch (err) {
    console.error('Failed to load issue:', err)
    error.value = 'Не удалось загрузить данные замечания'
  } finally {
    loading.value = false
  }
}

async function fetchIssue() {
  try {
    const { data } = await issuesApi.get(orgId, documentId, issueId)
    issue.value = data.data
    console.log('Issue loaded:', issue.value)
  } catch (err) {
    console.error('Failed to fetch issue:', err)
    throw err
  }
}

async function fetchComments() {
  try {
    const { data } = await issuesApi.listComments(orgId, documentId, issueId)
    comments.value = data.data || []
    console.log('Comments loaded:', comments.value.length)
  } catch (err) {
    console.error('Failed to fetch comments:', err)
    // Не бросаем ошибку, комментарии не критичны
    comments.value = []
  }
}

async function fetchHistory() {
  try {
    const { data } = await issuesApi.listHistory(orgId, documentId, issueId)
    history.value = data.data || []
    console.log('History loaded:', history.value.length)
  } catch (err) {
    console.error('Failed to fetch history:', err)
    // Не бросаем ошибку, история не критична
    history.value = []
  }
}

async function changeStatus(newStatus) {
  try {
    await issuesApi.updateStatus(orgId, documentId, issueId, { status: newStatus })
    await Promise.all([
      fetchIssue(),
      fetchHistory(),
    ])
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка смены статуса')
  }
}

async function addComment() {
  try {
    submittingComment.value = true
    await issuesApi.addComment(orgId, documentId, issueId, {
      content: newComment.value,
    })
    newComment.value = ''
    await Promise.all([
      fetchComments(),
      fetchHistory(),
    ])
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
    await issuesApi.deleteComment(orgId, documentId, issueId, commentId)
    await fetchComments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления комментария')
  }
}

function getSeverityClass(severity) {
  const map = {
    critical: 'badge-danger',
    high: 'badge-danger',
    medium: 'badge-warning',
    low: 'badge-info',
    info: 'badge-secondary',
  }
  return map[severity] || 'badge-secondary'
}

function getSeverityIcon(severity) {
  const map = {
    critical: 'alert-octagon',
    high: 'alert-triangle',
    medium: 'alert-circle',
    low: 'info',
    info: 'info',
  }
  return map[severity] || 'info'
}

function getStatusClass(status) {
  const map = {
    open: 'badge-danger',
    accepted: 'badge-warning',
    fixed: 'badge-success',
    rejected: 'badge-secondary',
    deferred: 'badge-info',
  }
  return map[status] || 'badge-secondary'
}

function getStatusIcon(status) {
  const map = {
    open: 'circle',
    accepted: 'check-circle',
    fixed: 'check',
    rejected: 'x-circle',
    deferred: 'clock',
  }
  return map[status] || 'circle'
}

function getStatusLabel(status) {
  const map = {
    open: 'Открыто',
    accepted: 'Принято',
    fixed: 'Исправлено',
    rejected: 'Отклонено',
    deferred: 'Отложено',
  }
  return map[status] || status
}

function getTransitionClass(transition) {
  const map = {
    accepted: 'btn-primary',
    fixed: 'btn-success',
    rejected: 'btn-secondary',
    deferred: 'btn-secondary',
    open: 'btn-primary',
  }
  return map[transition] || 'btn-secondary'
}

function getTransitionIcon(transition) {
  const map = {
    accepted: 'check',
    fixed: 'check-circle',
    rejected: 'x',
    deferred: 'clock',
    open: 'rotate-ccw',
  }
  return map[transition] || 'check'
}

function getTransitionLabel(transition) {
  const map = {
    accepted: 'Принять',
    fixed: 'Исправлено',
    rejected: 'Отклонить',
    deferred: 'Отложить',
    open: 'Переоткрыть',
  }
  return map[transition] || transition
}

function getHistoryIcon(changeType) {
  const map = {
    status_changed: 'refresh-cw',
    comment_added: 'message-circle',
  }
  return map[changeType] || 'file-text'
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
</script>

<style scoped>
.issue-detail {
  max-width: 900px;
}

.page-header {
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
.issue-badges {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
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
.issue-section {
  margin-bottom: 1.5rem;
}

.issue-section p {
  margin: 0;
  line-height: 1.6;
  color: #333;
}

.legal-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.legal-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #e3f2fd;
  color: #1565c0;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
}

.document-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a90d9;
  text-decoration: none;
}

.document-link:hover {
  text-decoration: underline;
}

/* Actions */
.issue-actions {
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

.author-role {
  font-size: 0.7rem;
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

/* History */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-item {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #f9f9f9;
  border-radius: 8px;
}

.history-icon {
  color: #4a90d9;
  padding-top: 0.25rem;
}

.history-content {
  flex: 1;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.history-user {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.user-name {
  font-weight: 500;
}

.user-email {
  color: #666;
  font-size: 0.85rem;
}

.user-role {
  font-size: 0.7rem;
}

.history-date {
  color: #999;
  font-size: 0.8rem;
}

.history-description {
  margin-bottom: 0.5rem;
  color: #333;
}

.history-comment {
  font-style: italic;
  color: #666;
  margin-top: 0.5rem;
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
</style>

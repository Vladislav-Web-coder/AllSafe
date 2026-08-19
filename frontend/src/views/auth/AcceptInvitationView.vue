<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo">
        <Icon name="shield" size="48" />
      </div>

      <h1 class="auth-title">Приглашение в организацию</h1>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Обработка приглашения...</p>
      </div>

      <div v-else-if="success" class="result-block success">
        <div class="result-icon">
          <Icon name="check-circle" size="64" />
        </div>
        <h2>Приглашение принято!</h2>
        <p>Вы успешно присоединились к организации.</p>
        <router-link to="/" class="btn btn-primary btn-lg">
          <Icon name="home" size="16" />
          Перейти в систему
        </router-link>
      </div>

      <div v-else class="result-block error">
        <div class="result-icon">
          <Icon name="x-circle" size="64" />
        </div>
        <h2>Не удалось принять приглашение</h2>
        <p>{{ errorMessage }}</p>
        <div class="result-actions">
          <router-link to="/login" class="btn btn-primary">
            <Icon name="log-in" size="16" />
            Вернуться ко входу
          </router-link>
          <button @click="retry" class="btn btn-secondary">
            <Icon name="rotate-ccw" size="16" />
            Попробовать снова
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient from '@/api/client'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const success = ref(false)
const errorMessage = ref('')

onMounted(() => {
  acceptInvitation()
})

async function acceptInvitation() {
  const token = route.query.token

  if (!token) {
    loading.value = false
    errorMessage.value = 'Токен приглашения не найден в ссылке.'
    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    await apiClient.post(`/invitations/${token}/accept`)
    success.value = true
  } catch (err) {
    errorMessage.value = err.response?.data?.message
      || err.response?.data?.errors?.token?.[0]
      || 'Не удалось принять приглашение. Возможно, оно истекло или было отменено.'
  } finally {
    loading.value = false
  }
}

function retry() {
  acceptInvitation()
}
</script>

<style scoped>
.auth-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  padding: 2rem;
}

.auth-card {
  width: 100%;
  max-width: 480px;
  padding: 2.5rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  text-align: center;
}

.auth-logo {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: #4a90d9;
}

.auth-title {
  margin: 0 0 2rem;
  font-size: 1.5rem;
  color: #1a1a2e;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  color: #666;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 3px solid #e0e0e0;
  border-top-color: #4a90d9;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  margin: 0;
  font-size: 1rem;
}

.result-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.result-icon {
  margin-bottom: 0.5rem;
}

.result-block.success .result-icon {
  color: #28a745;
}

.result-block.error .result-icon {
  color: #dc3545;
}

.result-block h2 {
  margin: 0;
  font-size: 1.3rem;
}

.result-block.success h2 {
  color: #28a745;
}

.result-block.error h2 {
  color: #dc3545;
}

.result-block p {
  color: #666;
  margin: 0;
  line-height: 1.5;
}

.result-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-lg {
  padding: 0.75rem 1.75rem;
  font-size: 1rem;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
}

.btn-primary:hover {
  box-shadow: 0 8px 20px rgba(74, 144, 217, 0.4);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}
</style>

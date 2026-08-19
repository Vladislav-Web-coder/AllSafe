<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo">
        <Icon name="shield" size="48" />
      </div>

      <h1 class="auth-title">Восстановление пароля</h1>

      <!-- Шаг 1: Запрос ссылки -->
      <template v-if="step === 1">
        <p class="auth-subtitle">Введите ваш email и мы отправим ссылку для сброса пароля</p>

        <form @submit.prevent="handleForgot" class="auth-form">
          <div class="form-group">
            <label>Email</label>
            <div class="input-wrapper">
              <Icon name="mail" size="18" class="input-icon" />
              <input
                v-model="email"
                type="email"
                required
                placeholder="ivan@example.com"
              />
            </div>
          </div>

          <div v-if="message" class="success-message">
            <Icon name="check-circle" size="16" />
            {{ message }}
          </div>

          <div v-if="error" class="error-message">
            <Icon name="alert-circle" size="16" />
            {{ error }}
          </div>

          <button type="submit" :disabled="loading" class="auth-button">
            <span v-if="loading" class="btn-loading">
              <div class="spinner-sm"></div>
              Отправка...
            </span>
            <span v-else>
              <Icon name="send" size="16" />
              Отправить ссылку
            </span>
          </button>
        </form>
      </template>

      <!-- Шаг 2: Новый пароль (после перехода по ссылке) -->
      <template v-else>
        <p class="auth-subtitle">Установите новый пароль для вашего аккаунта</p>

        <form @submit.prevent="handleReset" class="auth-form">
          <div class="form-group">
            <label>Новый пароль</label>
            <div class="input-wrapper">
              <Icon name="lock" size="18" class="input-icon" />
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                placeholder="Минимум 8 символов"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Подтверждение пароля</label>
            <div class="input-wrapper">
              <Icon name="lock" size="18" class="input-icon" />
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                placeholder="Повторите пароль"
              />
            </div>
          </div>

          <div v-if="error" class="error-message">
            <Icon name="alert-circle" size="16" />
            {{ error }}
          </div>

          <div v-if="successMessage" class="success-message">
            <Icon name="check-circle" size="16" />
            {{ successMessage }}
          </div>

          <button type="submit" :disabled="loading" class="auth-button">
            <span v-if="loading" class="btn-loading">
              <div class="spinner-sm"></div>
              Сохранение...
            </span>
            <span v-else>
              <Icon name="check" size="16" />
              Сменить пароль
            </span>
          </button>
        </form>
      </template>

      <div class="auth-links">
        <router-link to="/login">
          <Icon name="arrow-left" size="14" />
          Вернуться ко входу
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import authApi from '@/api/auth'
import Icon from '@/components/common/Icon.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const step = ref(1)
const email = ref('')
const token = ref('')
const loading = ref(false)
const message = ref(null)
const error = ref(null)
const successMessage = ref(null)

const form = ref({
  password: '',
  password_confirmation: '',
})

onMounted(() => {
  // Если пришли по ссылке с токеном
  if (route.query.token) {
    token.value = route.query.token
    step.value = 2
  }
})

async function handleForgot() {
  loading.value = true
  message.value = null
  error.value = null

  try {
    await authApi.forgotPassword(email.value)
    message.value = 'Ссылка для сброса пароля отправлена на вашу почту.'
  } catch (err) {
    error.value = err.response?.data?.message || 'Ошибка отправки'
  } finally {
    loading.value = false
  }
}

async function handleReset() {
  loading.value = true
  error.value = null

  try {
    const { data } = await authApi.resetPassword({
      token: token.value,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    })

    successMessage.value = data.message

    // Автологин
    authStore.accessToken = data.access_token
    authStore.refreshToken = data.refresh_token
    authStore.user = data.user

    localStorage.setItem('access_token', data.access_token)
    localStorage.setItem('refresh_token', data.refresh_token)

    setTimeout(() => {
      router.push({ name: 'dashboard' })
    }, 1500)
  } catch (err) {
    error.value = err.response?.data?.message
      || err.response?.data?.errors?.token?.[0]
      || 'Ошибка сброса пароля'

    if (err.response?.data?.errors?.token) {
      setTimeout(() => {
        step.value = 1
        token.value = ''
        error.value = null
      }, 3000)
    }
  } finally {
    loading.value = false
  }
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
  max-width: 420px;
  padding: 2.5rem;
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.auth-logo {
  display: flex;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: #4a90d9;
}

.auth-title {
  margin: 0 0 0.5rem;
  font-size: 1.5rem;
  text-align: center;
  color: #1a1a2e;
}

.auth-subtitle {
  margin: 0 0 2rem;
  color: #666;
  text-align: center;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  color: #999;
}

.input-wrapper input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: all 0.2s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #4a90d9;
  box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.15);
}

.error-message,
.success-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
}

.error-message {
  background-color: #fee;
  color: #c33;
}

.success-message {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.auth-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.auth-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(74, 144, 217, 0.4);
}

.auth-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-loading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spinner-sm {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.auth-links {
  margin-top: 1.5rem;
  text-align: center;
}

.auth-links a {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #4a90d9;
  text-decoration: none;
  font-size: 0.9rem;
}

.auth-links a:hover {
  text-decoration: underline;
}
</style>

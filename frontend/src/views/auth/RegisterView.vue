<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo">
        <Icon name="shield" size="48" />
      </div>

      <h1 class="auth-title">Регистрация</h1>

      <!-- Шаг 1: Регистрация -->
      <template v-if="step === 1">
        <p class="auth-subtitle">Создайте аккаунт для начала работы</p>

        <form @submit.prevent="handleRegister" class="auth-form">
          <div class="form-group">
            <label>Имя</label>
            <div class="input-wrapper">
              <Icon name="user" size="18" class="input-icon" />
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Иван Иванов"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Email</label>
            <div class="input-wrapper">
              <Icon name="mail" size="18" class="input-icon" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="ivan@example.com"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Пароль</label>
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

          <button type="submit" :disabled="loading" class="auth-button">
            <span v-if="loading" class="btn-loading">
              <div class="spinner-sm"></div>
              Отправка...
            </span>
            <span v-else>
              <Icon name="user" size="16" />
              Зарегистрироваться
            </span>
          </button>
        </form>
      </template>

      <!-- Шаг 2: Подтверждение кода -->
      <template v-else>
        <p class="auth-subtitle">Подтвердите ваш email</p>
        <p class="verification-hint">
          Мы отправили 6-значный код на <strong>{{ form.email }}</strong>
        </p>

        <form @submit.prevent="handleVerify" class="auth-form">
          <div class="form-group">
            <label>Код подтверждения</label>
            <div class="input-wrapper">
              <Icon name="hash" size="18" class="input-icon" />
              <input
                v-model="verificationCode"
                type="text"
                required
                maxlength="6"
                pattern="[0-9]{6}"
                placeholder="000000"
                class="code-input"
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
              Проверка...
            </span>
            <span v-else>
              <Icon name="check" size="16" />
              Подтвердить
            </span>
          </button>

          <button
            type="button"
            @click="resendCode"
            :disabled="resendTimer > 0"
            class="resend-button"
          >
            <Icon name="rotate-ccw" size="14" />
            {{ resendTimer > 0 ? `Отправить повторно (${resendTimer}с)` : 'Отправить код повторно' }}
          </button>
        </form>
      </template>

      <div class="auth-links">
        <router-link to="/login">
          <Icon name="arrow-left" size="14" />
          Уже есть аккаунт? Войти
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import authApi from '@/api/auth'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const authStore = useAuthStore()

const step = ref(1)
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const verificationCode = ref('')
const loading = ref(false)
const error = ref(null)
const successMessage = ref(null)
const resendTimer = ref(0)

let timerInterval = null

async function handleRegister() {
  loading.value = true
  error.value = null

  try {
    await authApi.register(form.value)
    step.value = 2
    startResendTimer()
  } catch (err) {
    error.value = err.response?.data?.message
      || err.response?.data?.errors?.email?.[0]
      || 'Ошибка регистрации'
  } finally {
    loading.value = false
  }
}

async function handleVerify() {
  loading.value = true
  error.value = null

  try {
    const { data } = await authApi.verifyRegistration(form.value.email, verificationCode.value)

    authStore.accessToken = data.access_token
    authStore.refreshToken = data.refresh_token
    authStore.user = data.user

    localStorage.setItem('access_token', data.access_token)
    localStorage.setItem('refresh_token', data.refresh_token)

    router.push({ name: 'organizations' })
  } catch (err) {
    error.value = err.response?.data?.message
      || err.response?.data?.errors?.code?.[0]
      || 'Ошибка подтверждения'
  } finally {
    loading.value = false
  }
}

async function resendCode() {
  try {
    await authApi.resendVerification(form.value.email)
    successMessage.value = 'Код отправлен повторно'
    startResendTimer()

    setTimeout(() => {
      successMessage.value = null
    }, 3000)
  } catch (err) {
    error.value = 'Ошибка отправки кода'
  }
}

function startResendTimer() {
  resendTimer.value = 60

  if (timerInterval) clearInterval(timerInterval)

  timerInterval = setInterval(() => {
    resendTimer.value--
    if (resendTimer.value <= 0) {
      clearInterval(timerInterval)
    }
  }, 1000)
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
  margin: 0 0 1.5rem;
  color: #666;
  text-align: center;
}

.verification-hint {
  text-align: center;
  color: #666;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
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

.code-input {
  text-align: center;
  font-size: 1.5rem;
  letter-spacing: 8px;
  font-family: 'Courier New', monospace;
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
  margin-bottom: 0.5rem;
}

.auth-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(74, 144, 217, 0.4);
}

.auth-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.resend-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem;
  background: none;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  cursor: pointer;
  color: #666;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.resend-button:hover:not(:disabled) {
  background-color: #f5f5f5;
  border-color: #ccc;
}

.resend-button:disabled {
  opacity: 0.5;
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

<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-logo">
        <Icon name="shield" size="48" />
      </div>

      <h1 class="auth-title">ИБ Комплаенс</h1>
      <p class="auth-subtitle">Вход в систему</p>

      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <Icon name="mail" size="18" class="input-icon" />
            <input
              id="email"
              v-model="email"
              type="email"
              required
              autocomplete="email"
              placeholder="admin@example.com"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Пароль</label>
          <div class="input-wrapper">
            <Icon name="lock" size="18" class="input-icon" />
            <input
              id="password"
              v-model="password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div v-if="authStore.error" class="error-message">
          <Icon name="alert-circle" size="16" />
          {{ authStore.error }}
        </div>

        <button type="submit" :disabled="authStore.loading" class="auth-button">
          <span v-if="authStore.loading" class="btn-loading">
            <div class="spinner-sm"></div>
            Вход...
          </span>
          <span v-else>Войти</span>
        </button>
      </form>

      <div class="auth-links">
        <router-link to="/register">Регистрация</router-link>
        <span class="separator">•</span>
        <router-link to="/forgot-password">Забыли пароль?</router-link>
      </div>
    </div>

    <div class="auth-footer">
      <p>© 2026 ИБ Комплаенс. Все права защищены.</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')

async function handleLogin() {
  try {
    await authStore.login(email.value, password.value)
    router.push({ name: 'organizations' })
  } catch (error) {
    // Error already handled in store
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
  font-size: 1.75rem;
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

.error-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  background-color: #fee;
  color: #c33;
  border-radius: 8px;
  font-size: 0.9rem;
}

.auth-button {
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
  justify-content: center;
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
  font-size: 0.9rem;
}

.auth-links a {
  color: #4a90d9;
  text-decoration: none;
}

.auth-links a:hover {
  text-decoration: underline;
}

.separator {
  margin: 0 0.75rem;
  color: #ccc;
}

.auth-footer {
  margin-top: 2rem;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.85rem;
}
</style>

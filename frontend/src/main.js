import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Инициализация auth store до монтирования
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()

authStore.initialize().then(() => {
  app.mount('#app')
})

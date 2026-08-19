<template>
  <div class="documents-page">
    <div class="page-header">
      <h1>Документы</h1>
      <button @click="showUploadModal = true" class="btn btn-primary">
        <Icon name="upload" size="16" />
        Загрузить документ
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <span>Загрузка...</span>
    </div>

    <div v-else-if="error" class="error-state">
      <Icon name="alert-circle" size="48" />
      <h2>Ошибка загрузки</h2>
      <p>{{ error }}</p>
      <button @click="fetchDocuments" class="btn btn-primary">Попробовать снова</button>
    </div>

    <div v-else-if="documents.length === 0" class="empty-state">
      <Icon name="file-text" size="48" />
      <h2>Документов пока нет</h2>
      <p>Загрузите первый документ для начала работы</p>
      <button @click="showUploadModal = true" class="btn btn-primary">
        <Icon name="upload" size="16" />
        Загрузить документ
      </button>
    </div>

    <div v-else class="documents-table-wrapper">
      <table class="table">
        <thead>
        <tr>
          <th>Название</th>
          <th>Тип</th>
          <th>Статус</th>
          <th>Создан</th>
          <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="doc in documents" :key="doc.id">
          <td>
            <router-link :to="{ name: 'document-detail', params: { id: doc.id } }" class="doc-link">
              <Icon name="file-text" size="18" />
              {{ doc.title }}
            </router-link>
          </td>
          <td>{{ doc.document_type?.name || '—' }}</td>
          <td>
              <span class="badge" :class="getStatusClass(doc.status)">
                <Icon :name="getStatusIcon(doc.status)" size="14" />
                {{ getStatusLabel(doc.status) }}
              </span>
          </td>
          <td>{{ formatDate(doc.created_at) }}</td>
          <td>
            <div class="row-actions">
              <button
                @click="analyzeDocument(doc.id)"
                class="action-btn"
                title="Запустить анализ"
              >
                <Icon name="zap" size="16" />
              </button>
              <button
                @click="downloadDocument(doc)"
                class="action-btn"
                title="Скачать"
              >
                <Icon name="download" size="16" />
              </button>
              <button
                @click="confirmDelete(doc)"
                class="action-btn danger"
                title="Удалить"
              >
                <Icon name="trash" size="16" />
              </button>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <!-- Модальное окно загрузки -->
    <div v-if="showUploadModal" class="modal-overlay" @click.self="showUploadModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>
            <Icon name="upload" size="20" />
            Загрузить документ
          </h2>
          <button @click="showUploadModal = false" class="close-btn">
            <Icon name="x" size="20" />
          </button>
        </div>

        <form @submit.prevent="uploadDocument">
          <div class="form-group">
            <label>Название документа</label>
            <input v-model="uploadForm.title" class="form-control" required />
          </div>

          <div class="form-group">
            <label>Тип документа</label>
            <select v-model="uploadForm.document_type_id" class="form-control" required>
              <option value="" disabled>Выберите тип</option>
              <option v-for="type in documentTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <!-- Drag & Drop зона -->
          <div
            class="drop-zone"
            :class="{ 'drop-zone-active': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="openFilePicker"
          >
            <input
              ref="fileInput"
              type="file"
              @change="handleFileSelect"
              style="display: none"
              accept=".pdf,.docx,.txt,.md"
            />

            <div v-if="!uploadForm.file" class="drop-zone-content">
              <Icon name="upload" size="32" />
              <p>Перетащите файл сюда или кликните для выбора</p>
              <p class="drop-hint">PDF, DOCX, TXT, MD (до 50 МБ)</p>
            </div>

            <div v-else class="drop-zone-selected">
              <Icon name="file-text" size="24" />
              <div class="file-info">
                <span class="file-name">{{ uploadForm.file.name }}</span>
                <span class="file-size">{{ formatFileSize(uploadForm.file.size) }}</span>
              </div>
              <button type="button" @click.stop="removeFile" class="remove-file">
                <Icon name="x" size="16" />
              </button>
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="showUploadModal = false" class="btn btn-secondary">
              Отмена
            </button>
            <button type="submit" :disabled="uploading || !uploadForm.file" class="btn btn-primary">
              <Icon name="upload" size="16" />
              {{ uploading ? 'Загрузка...' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOrganizationStore } from '@/stores/organization'
import documentsApi from '@/api/documents'
import dictionariesApi from '@/api/dictionaries'
import { downloadFile } from '@/utils/download'
import Icon from '@/components/common/Icon.vue'

const router = useRouter()
const organizationStore = useOrganizationStore()

const documents = ref([])
const documentTypes = ref([])
const loading = ref(true)
const error = ref(null)

const showUploadModal = ref(false)
const uploading = ref(false)
const fileInput = ref(null)
const isDragging = ref(false)

const uploadForm = ref({
  title: '',
  document_type_id: '',
  file: null,
})

onMounted(async () => {
  if (!organizationStore.hasOrganization) {
    error.value = 'Организация не выбрана'
    loading.value = false
    return
  }

  await Promise.all([
    fetchDocuments(),
    fetchDocumentTypes(),
  ])
})

async function fetchDocuments() {
  try {
    loading.value = true
    error.value = null
    const { data } = await documentsApi.list(organizationStore.currentOrganizationId)
    documents.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch documents:', err)
    error.value = 'Ошибка загрузки документов'
  } finally {
    loading.value = false
  }
}

async function fetchDocumentTypes() {
  try {
    const { data } = await dictionariesApi.documentTypes()
    documentTypes.value = data.data || data
  } catch (err) {
    console.error('Failed to fetch document types:', err)
  }
}

function openFilePicker() {
  fileInput.value?.click()
}

function handleDrop(event) {
  isDragging.value = false
  const files = event.dataTransfer.files

  if (files.length > 0) {
    uploadForm.value.file = files[0]
  }
}

function handleFileSelect(event) {
  uploadForm.value.file = event.target.files[0]
}

function removeFile() {
  uploadForm.value.file = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function formatFileSize(bytes) {
  if (!bytes) return ''
  const sizes = ['Б', 'КБ', 'МБ', 'ГБ']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

async function uploadDocument() {
  try {
    uploading.value = true

    const orgId = organizationStore.currentOrganizationId

    const { data: docData } = await documentsApi.create(orgId, {
      title: uploadForm.value.title,
      document_type_id: parseInt(uploadForm.value.document_type_id),
    })

    const documentId = docData.data.id

    const formData = new FormData()
    formData.append('file', uploadForm.value.file)

    await documentsApi.upload(orgId, documentId, formData)

    showUploadModal.value = false
    uploadForm.value = { title: '', document_type_id: '', file: null }

    await fetchDocuments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка загрузки документа')
  } finally {
    uploading.value = false
  }
}

async function analyzeDocument(documentId) {
  try {
    await documentsApi.analyze(organizationStore.currentOrganizationId, documentId)
    alert('Анализ запущен')
    await fetchDocuments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка запуска анализа')
  }
}

async function downloadDocument(doc) {
  try {
    const { data } = await documentsApi.download(organizationStore.currentOrganizationId, doc.id)
    downloadFile(data.download_url, doc.title)
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка скачивания')
  }
}

function confirmDelete(doc) {
  if (!confirm(`Удалить документ "${doc.title}"?`)) return
  deleteDocument(doc.id)
}

async function deleteDocument(documentId) {
  try {
    await documentsApi.delete(organizationStore.currentOrganizationId, documentId)
    await fetchDocuments()
  } catch (err) {
    alert(err.response?.data?.message || 'Ошибка удаления документа')
  }
}

function getStatusClass(status) {
  const map = {
    completed: 'badge-success',
    uploaded: 'badge-info',
    analyzing: 'badge-warning',
    failed: 'badge-danger',
    draft: 'badge-secondary',
  }
  return map[status] || 'badge-secondary'
}

function getStatusIcon(status) {
  const map = {
    completed: 'check-circle',
    uploaded: 'upload',
    analyzing: 'activity',
    failed: 'x-circle',
    draft: 'file',
  }
  return map[status] || 'file'
}

function getStatusLabel(status) {
  const map = {
    completed: 'Завершён',
    uploaded: 'Загружен',
    analyzing: 'Анализируется',
    failed: 'Ошибка',
    draft: 'Черновик',
  }
  return map[status] || status
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('ru-RU')
}
</script>

<style scoped>
.documents-page {
  max-width: 1200px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-header h1 {
  margin: 0;
}

/* Loading & Error */
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

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 4rem 2rem;
  text-align: center;
  background: white;
  border-radius: 12px;
  color: #999;
}

.empty-state h2 {
  color: #333;
  margin: 0;
}

.empty-state p {
  color: #666;
  margin: 0;
}

/* Table */
.documents-table-wrapper {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 1rem 1.25rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table th {
  font-weight: 600;
  color: #666;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: #f9f9f9;
}

.table tr:hover td {
  background-color: #f9f9f9;
}

.doc-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a90d9;
  text-decoration: none;
  font-weight: 500;
}

.doc-link:hover {
  text-decoration: underline;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}

.badge-success {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-info {
  background: #e3f2fd;
  color: #1565c0;
}

.badge-warning {
  background: #fff8e1;
  color: #f57c00;
}

.badge-danger {
  background: #fee;
  color: #c33;
}

.badge-secondary {
  background: #f5f5f5;
  color: #616161;
}

/* Row actions */
.row-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  background: none;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #666;
  transition: all 0.2s;
}

.action-btn:hover {
  background: #f0f0f0;
  border-color: #ccc;
}

.action-btn.danger:hover {
  background: #fee;
  border-color: #dc3545;
  color: #dc3545;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #999;
  padding: 0.25rem;
}

.close-btn:hover {
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

/* Drop zone */
.drop-zone {
  border: 2px dashed #ccc;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  margin-bottom: 1.5rem;
}

.drop-zone:hover {
  border-color: #4a90d9;
  background-color: #f0f7ff;
}

.drop-zone-active {
  border-color: #4a90d9;
  background-color: #e8f4fd;
}

.drop-zone-content {
  color: #666;
}

.drop-zone-content p {
  margin: 0.5rem 0 0;
}

.drop-hint {
  font-size: 0.85rem;
  color: #999;
}

.drop-zone-selected {
  display: flex;
  align-items: center;
  gap: 1rem;
  justify-content: center;
}

.file-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.file-name {
  font-weight: 500;
}

.file-size {
  color: #666;
  font-size: 0.85rem;
}

.remove-file {
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  padding: 0.25rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-primary {
  background: linear-gradient(135deg, #4a90d9 0%, #357abd 100%);
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}
</style>

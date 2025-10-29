<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Folders</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.folders" @click="fetchFolders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.folders" type="error" variant="tonal" class="mb-4">
                {{ errors.folders }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.folders" @submit.prevent="createFolder">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.folder.name" label="Folder Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.folder.code" label="Code (optional)" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.folder.parent_id"
                      :items="folderOptions"
                      item-title="name"
                      item-value="id"
                      label="Parent Folder"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.folder.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.folders">
                      <v-icon start>mdi-folder-plus</v-icon>
                      Add Folder
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="folderHeaders"
                :items="folders"
                :loading="loading.folders"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.parent="{ item }">
                  {{ folderLookup[item.parent_id] ?? '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Documents</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.documents" @click="fetchDocuments">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.documents" type="error" variant="tonal" class="mb-4">
                {{ errors.documents }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.documents" @submit.prevent="createDocument">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.document.folder_id"
                      :items="folderOptions"
                      item-title="name"
                      item-value="id"
                      label="Folder"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.document.reference" label="Reference" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.document.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.document.document_type" label="Type" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.document.status"
                      :items="documentStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.document.description"
                      label="Summary"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.documents">
                      <v-icon start>mdi-file-document-plus</v-icon>
                      Register Document
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="documentHeaders"
                :items="documents"
                :loading="loading.documents"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.folder="{ item }">
                  {{ folderLookup[item.folder_id] ?? 'Unfiled' }}
                </template>
                <template #item.status="{ item }">
                  <v-chip size="small" variant="flat" :color="statusColor(item.status)">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Document Versions</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.versions" @click="fetchVersions">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.versions" type="error" variant="tonal" class="mb-4">
                {{ errors.versions }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.versions" @submit.prevent="createVersion">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.version.document_id"
                      :items="documentOptions"
                      item-title="title"
                      item-value="id"
                      label="Document"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.version.file_path" label="File Path" dense required />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="forms.version.version_number"
                      label="Version"
                      type="number"
                      dense
                      min="1"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="forms.version.checksum" label="Checksum" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.version.notes"
                      label="Notes"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.versions">
                      <v-icon start>mdi-file-plus</v-icon>
                      Publish Version
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="versionHeaders"
                :items="versions"
                :loading="loading.versions"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.document="{ item }">
                  {{ documentLookup[item.document_id] ?? '—' }}
                </template>
                <template #item.created_at="{ item }">
                  {{ formatDate(item.created_at) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const folders = ref([])
const documents = ref([])
const versions = ref([])

const loading = reactive({
  folders: false,
  documents: false,
  versions: false
})

const errors = reactive({
  folders: null,
  documents: null,
  versions: null
})

const forms = reactive({
  folder: {
    name: '',
    code: '',
    parent_id: null,
    description: ''
  },
  document: {
    folder_id: null,
    reference: '',
    title: '',
    document_type: '',
    status: 'draft',
    description: ''
  },
  version: {
    document_id: null,
    file_path: '',
    version_number: null,
    checksum: '',
    notes: ''
  }
})

const folderHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Parent', key: 'parent' }
]

const documentHeaders = [
  { title: 'Title', key: 'title' },
  { title: 'Reference', key: 'reference' },
  { title: 'Folder', key: 'folder' },
  { title: 'Type', key: 'document_type' },
  { title: 'Status', key: 'status' },
  { title: 'Versions', key: 'latest_version_number' }
]

const versionHeaders = [
  { title: 'Document', key: 'document' },
  { title: 'Version', key: 'version_number' },
  { title: 'File Path', key: 'file_path' },
  { title: 'Checksum', key: 'checksum' },
  { title: 'Published', key: 'created_at' }
]

const documentStatuses = ['draft', 'in_review', 'approved', 'archived']

const folderOptions = computed(() => folders.value ?? [])
const documentOptions = computed(() => documents.value ?? [])

const folderLookup = computed(() => {
  const map = {}
  for (const folder of folders.value ?? []) {
    map[folder.id] = folder.name
  }
  return map
})

const documentLookup = computed(() => {
  const map = {}
  for (const document of documents.value ?? []) {
    map[document.id] = document.title
  }
  return map
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return {
    Authorization: token ? `Bearer ${token}` : undefined
  }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

function statusColor(status) {
  const colors = {
    draft: 'grey',
    in_review: 'warning',
    approved: 'success',
    archived: 'secondary'
  }
  return colors[status] || 'primary'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

async function fetchFolders() {
  loading.folders = true
  errors.folders = null
  try {
    const { data } = await axios.get(`${API_BASE}/dms/folders`, { headers: authHeaders() })
    folders.value = data.data || data
  } catch (error) {
    errors.folders = parseError(error)
  } finally {
    loading.folders = false
  }
}

async function createFolder() {
  loading.folders = true
  errors.folders = null
  try {
    await axios.post(`${API_BASE}/dms/folders`, forms.folder, { headers: authHeaders() })
    Object.assign(forms.folder, { name: '', code: '', parent_id: null, description: '' })
    await fetchFolders()
  } catch (error) {
    errors.folders = parseError(error)
  } finally {
    loading.folders = false
  }
}

async function fetchDocuments() {
  loading.documents = true
  errors.documents = null
  try {
    const { data } = await axios.get(`${API_BASE}/dms/documents`, { headers: authHeaders() })
    documents.value = data.data || data
  } catch (error) {
    errors.documents = parseError(error)
  } finally {
    loading.documents = false
  }
}

async function createDocument() {
  loading.documents = true
  errors.documents = null
  try {
    await axios.post(`${API_BASE}/dms/documents`, forms.document, { headers: authHeaders() })
    Object.assign(forms.document, {
      folder_id: null,
      reference: '',
      title: '',
      document_type: '',
      status: 'draft',
      description: ''
    })
    await fetchDocuments()
  } catch (error) {
    errors.documents = parseError(error)
  } finally {
    loading.documents = false
  }
}

async function fetchVersions() {
  loading.versions = true
  errors.versions = null
  try {
    const { data } = await axios.get(`${API_BASE}/dms/document-versions`, { headers: authHeaders() })
    versions.value = data.data || data
  } catch (error) {
    errors.versions = parseError(error)
  } finally {
    loading.versions = false
  }
}

async function createVersion() {
  if (!forms.version.document_id) {
    errors.versions = 'Select a document before creating a version.'
    return
  }

  loading.versions = true
  errors.versions = null
  try {
    const payload = {
      document_id: forms.version.document_id,
      file_path: forms.version.file_path,
      version_number: forms.version.version_number || undefined,
      checksum: forms.version.checksum || undefined,
      notes: forms.version.notes || undefined
    }
    await axios.post(`${API_BASE}/dms/document-versions`, payload, { headers: authHeaders() })
    Object.assign(forms.version, {
      document_id: forms.version.document_id,
      file_path: '',
      version_number: null,
      checksum: '',
      notes: ''
    })
    await Promise.all([fetchVersions(), fetchDocuments()])
  } catch (error) {
    errors.versions = parseError(error)
  } finally {
    loading.versions = false
  }
}

onMounted(async () => {
  await Promise.all([fetchFolders(), fetchDocuments(), fetchVersions()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


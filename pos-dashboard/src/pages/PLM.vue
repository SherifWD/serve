<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Product Designs</span>
              <v-spacer />
              <v-btn icon color="primary" class="rounded-pill" @click="loadDesigns" :loading="loading.designs">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.designs" type="error" variant="tonal" class="mb-4">
                {{ errors.designs }}
              </v-alert>
              <v-form @submit.prevent="createDesign" class="mb-4" :disabled="loading.designs">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.design.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.design.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.design.item_id"
                      :items="items"
                      item-value="id"
                      item-title="name"
                      label="Item"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.design.version" label="Version" dense />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.design.lifecycle_state"
                      :items="lifecycleStates"
                      label="State"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.designs">
                      <v-icon start>mdi-plus</v-icon>
                      Add Design
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="designHeaders"
                :items="designs"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.lifecycle_state="{ item }">
                  <v-chip size="small">{{ item.lifecycle_state }}</v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Engineering Changes</span>
              <v-spacer />
              <v-btn icon color="primary" class="rounded-pill" @click="loadChanges" :loading="loading.changes">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.changes" type="error" variant="tonal" class="mb-4">
                {{ errors.changes }}
              </v-alert>
              <v-form @submit.prevent="createChange" class="mb-4" :disabled="loading.changes">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.change.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.change.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.change.product_design_id"
                      :items="designs"
                      item-title="name"
                      item-value="id"
                      label="Product Design"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.change.status"
                      :items="changeStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.change.effectivity_date"
                      label="Effectivity"
                      type="date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.changes">
                      <v-icon start>mdi-plus</v-icon>
                      Submit Change
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="changeHeaders"
                :items="changes"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card elevation="3" class="pa-4 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Design Documents</span>
              <v-spacer />
              <v-btn icon color="primary" class="rounded-pill" @click="loadDocuments" :loading="loading.documents">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.documents" type="error" variant="tonal" class="mb-4">
                {{ errors.documents }}
              </v-alert>
              <v-form @submit.prevent="createDocument" class="mb-4" :disabled="loading.documents">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.document.product_design_id"
                      :items="designs"
                      item-value="id"
                      item-title="name"
                      label="Product Design"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.document.document_type"
                      :items="documentTypes"
                      label="Type"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.document.file_name" label="File Name" dense required />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="forms.document.version" label="Version" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.document.file_path" label="File Path / URL" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.documents">
                      <v-icon start>mdi-plus</v-icon>
                      Attach Document
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="documentHeaders"
                :items="documents"
                :items-per-page="8"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const items = ref([])
const designs = ref([])
const changes = ref([])
const documents = ref([])

const loading = reactive({
  items: false,
  designs: false,
  changes: false,
  documents: false
})

const errors = reactive({
  designs: null,
  changes: null,
  documents: null
})

const forms = reactive({
  design: {
    code: '',
    name: '',
    item_id: null,
    version: '1.0',
    lifecycle_state: 'in_design'
  },
  change: {
    code: '',
    title: '',
    product_design_id: null,
    status: 'draft',
    effectivity_date: null
  },
  document: {
    product_design_id: null,
    document_type: 'specification',
    file_name: '',
    file_path: '',
    version: '1.0'
  }
})

const lifecycleStates = [
  { title: 'In Design', value: 'in_design' },
  { title: 'Prototype', value: 'prototype' },
  { title: 'Released', value: 'released' },
  { title: 'Retired', value: 'retired' }
]

const changeStatuses = [
  { title: 'Draft', value: 'draft' },
  { title: 'Submitted', value: 'submitted' },
  { title: 'Approved', value: 'approved' },
  { title: 'Implemented', value: 'implemented' },
  { title: 'Rejected', value: 'rejected' }
]

const documentTypes = [
  { title: 'Specification', value: 'specification' },
  { title: 'Drawing', value: 'drawing' },
  { title: 'Test Report', value: 'test_report' },
  { title: 'Other', value: 'other' }
]

const designHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Version', key: 'version' },
  { title: 'Lifecycle', key: 'lifecycle_state' }
]

const changeHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Title', key: 'title' },
  { title: 'Status', key: 'status' },
  { title: 'Effectivity', key: 'effectivity_date' }
]

const documentHeaders = [
  { title: 'Design', key: 'product_design_id' },
  { title: 'Type', key: 'document_type' },
  { title: 'File Name', key: 'file_name' },
  { title: 'Version', key: 'version' }
]

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

async function loadItems() {
  loading.items = true
  try {
    const { data } = await axios.get(`${API_BASE}/erp/items`, { headers: authHeaders() })
    items.value = data.data || data
  } catch (error) {
    console.error(error)
  } finally {
    loading.items = false
  }
}

async function loadDesigns() {
  loading.designs = true
  errors.designs = null
  try {
    const { data } = await axios.get(`${API_BASE}/plm/product-designs`, { headers: authHeaders() })
    designs.value = data.data || data
  } catch (error) {
    errors.designs = parseError(error)
  } finally {
    loading.designs = false
  }
}

async function loadChanges() {
  loading.changes = true
  errors.changes = null
  try {
    const { data } = await axios.get(`${API_BASE}/plm/engineering-changes`, { headers: authHeaders() })
    changes.value = data.data || data
  } catch (error) {
    errors.changes = parseError(error)
  } finally {
    loading.changes = false
  }
}

async function loadDocuments() {
  loading.documents = true
  errors.documents = null
  try {
    const { data } = await axios.get(`${API_BASE}/plm/design-documents`, { headers: authHeaders() })
    documents.value = data.data || data
  } catch (error) {
    errors.documents = parseError(error)
  } finally {
    loading.documents = false
  }
}

async function createDesign() {
  loading.designs = true
  errors.designs = null
  try {
    await axios.post(`${API_BASE}/plm/product-designs`, forms.design, { headers: authHeaders() })
    Object.assign(forms.design, {
      code: '',
      name: '',
      item_id: null,
      version: '1.0',
      lifecycle_state: 'in_design'
    })
    await loadDesigns()
  } catch (error) {
    errors.designs = parseError(error)
  } finally {
    loading.designs = false
  }
}

async function createChange() {
  loading.changes = true
  errors.changes = null
  try {
    await axios.post(`${API_BASE}/plm/engineering-changes`, forms.change, { headers: authHeaders() })
    Object.assign(forms.change, {
      code: '',
      title: '',
      product_design_id: null,
      status: 'draft',
      effectivity_date: null
    })
    await loadChanges()
  } catch (error) {
    errors.changes = parseError(error)
  } finally {
    loading.changes = false
  }
}

async function createDocument() {
  loading.documents = true
  errors.documents = null
  try {
    await axios.post(`${API_BASE}/plm/design-documents`, forms.document, { headers: authHeaders() })
    Object.assign(forms.document, {
      product_design_id: null,
      document_type: 'specification',
      file_name: '',
      file_path: '',
      version: '1.0'
    })
    await loadDocuments()
  } catch (error) {
    errors.documents = parseError(error)
  } finally {
    loading.documents = false
  }
}

onMounted(async () => {
  await loadItems()
  await loadDesigns()
  await loadChanges()
  await loadDocuments()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


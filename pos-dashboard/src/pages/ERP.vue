<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Sites</span>
              <v-spacer />
              <v-btn color="primary" class="rounded-pill" @click="fetchSites" :loading="loading.sites" icon>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.sites" type="error" variant="tonal" class="mb-4">
                {{ errors.sites }}
              </v-alert>
              <v-form @submit.prevent="createSite" class="mb-4" :disabled="loading.sites">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.site.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.site.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.site.timezone" label="Timezone" dense />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.site.address.line1" label="Address" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.site.status"
                      :items="statusOptions"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.sites">
                      <v-icon start>mdi-plus</v-icon>
                      Add Site
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="siteHeaders"
                :items="sites"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.address="{ item }">
                  {{ item.address?.line1 || '—' }}
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon size="small" @click="deleteSite(item)" color="error" :loading="loading.deleteSite === item.id">
                    <v-icon size="18">mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Departments</span>
              <v-spacer />
              <v-btn color="primary" class="rounded-pill" @click="fetchDepartments" :loading="loading.departments" icon>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.departments" type="error" variant="tonal" class="mb-4">
                {{ errors.departments }}
              </v-alert>
              <v-form @submit.prevent="createDepartment" class="mb-4" :disabled="loading.departments">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.department.site_id"
                      :items="sites"
                      item-title="name"
                      item-value="id"
                      label="Site"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.department.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.department.status"
                      :items="statusOptions"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.department.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.departments">
                      <v-icon start>mdi-plus</v-icon>
                      Add Department
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="departmentHeaders"
                :items="departments"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.site="{ item }">
                  {{ item.site?.name || '—' }}
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon size="small" @click="deleteDepartment(item)" color="error" :loading="loading.deleteDepartment === item.id">
                    <v-icon size="18">mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Items</span>
              <v-spacer />
              <v-btn color="primary" class="rounded-pill" @click="fetchItems" :loading="loading.items" icon>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.items" type="error" variant="tonal" class="mb-4">
                {{ errors.items }}
              </v-alert>
              <v-form @submit.prevent="createItem" class="mb-4" :disabled="loading.items">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.item.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.item.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.item.type"
                      :items="itemTypes"
                      label="Type"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="forms.item.standard_cost" label="Std Cost" dense type="number" min="0" step="0.01" />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model.number="forms.item.list_price" label="List Price" dense type="number" min="0" step="0.01" />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.item.uom" label="UOM" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.items">
                      <v-icon start>mdi-plus</v-icon>
                      Add Item
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="itemHeaders"
                :items="items"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Bill of Materials</span>
              <v-spacer />
              <v-btn color="primary" class="rounded-pill" @click="fetchBoms" :loading="loading.boms" icon>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.boms" type="error" variant="tonal" class="mb-4">
                {{ errors.boms }}
              </v-alert>

              <v-form @submit.prevent="createBom" class="mb-4" :disabled="loading.boms">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.bom.item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Parent Item"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.bom.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="forms.bom.revision" label="Rev" dense />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.bom.status"
                      :items="bomStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.bom.components[0].component_item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Component"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.bom.components[0].quantity"
                      label="Quantity"
                      dense
                      type="number"
                      min="0.0001"
                      step="0.0001"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.bom.components[0].uom" label="UOM" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.boms">
                      <v-icon start>mdi-plus</v-icon>
                      Create BOM
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="bomHeaders"
                :items="boms"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.item="{ item }">
                  {{ item.item?.name ?? '—' }}
                </template>
                <template #item.components="{ item }">
                  <ul class="pl-4">
                    <li v-for="component in item.components" :key="component.id">
                      {{ component.component?.name ?? 'Component' }} — {{ component.quantity }} {{ component.uom }}
                    </li>
                  </ul>
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
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const sites = ref([])
const departments = ref([])
const items = ref([])
const boms = ref([])

const loading = reactive({
  sites: false,
  departments: false,
  items: false,
  boms: false,
  deleteSite: null,
  deleteDepartment: null
})

const errors = reactive({
  sites: null,
  departments: null,
  items: null,
  boms: null
})

const statusOptions = [
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' }
]

const itemTypes = [
  { title: 'Manufactured', value: 'manufactured' },
  { title: 'Purchased', value: 'purchased' },
  { title: 'Service', value: 'service' }
]

const bomStatuses = [
  { title: 'Draft', value: 'draft' },
  { title: 'Active', value: 'active' },
  { title: 'Archived', value: 'archived' }
]

const forms = reactive({
  site: {
    code: '',
    name: '',
    status: 'active',
    timezone: 'UTC',
    address: {
      line1: ''
    }
  },
  department: {
    site_id: null,
    code: '',
    name: '',
    status: 'active'
  },
  item: {
    code: '',
    name: '',
    type: 'manufactured',
    standard_cost: 0,
    list_price: 0,
    uom: 'EA'
  },
  bom: {
    item_id: null,
    code: '',
    revision: 'A',
    status: 'active',
    components: [
      { component_item_id: null, quantity: 1, uom: 'EA' }
    ]
  }
})

const siteHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Status', key: 'status' },
  { title: 'Timezone', key: 'timezone' },
  { title: 'Address', key: 'address' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const departmentHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Site', key: 'site' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const itemHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'type' },
  { title: 'UOM', key: 'uom' },
  { title: 'Std Cost', key: 'standard_cost' },
  { title: 'List Price', key: 'list_price' }
]

const bomHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Revision', key: 'revision' },
  { title: 'Status', key: 'status' },
  { title: 'Item', key: 'item' },
  { title: 'Components', key: 'components' }
]

function authHeaders() {
  const token = localStorage.getItem('token')
  return {
    Authorization: token ? `Bearer ${token}` : undefined
  }
}

async function fetchSites() {
  loading.sites = true
  errors.sites = null
  try {
    const { data } = await axios.get(`${API_BASE}/erp/sites`, { headers: authHeaders() })
    sites.value = data.data || data
  } catch (error) {
    errors.sites = parseError(error)
  } finally {
    loading.sites = false
  }
}

async function fetchDepartments() {
  loading.departments = true
  errors.departments = null
  try {
    const { data } = await axios.get(`${API_BASE}/erp/departments`, { headers: authHeaders() })
    departments.value = data.data || data
  } catch (error) {
    errors.departments = parseError(error)
  } finally {
    loading.departments = false
  }
}

async function fetchItems() {
  loading.items = true
  errors.items = null
  try {
    const { data } = await axios.get(`${API_BASE}/erp/items`, { headers: authHeaders() })
    items.value = (data.data || data).map(item => ({
      ...item,
      standard_cost: Number(item.standard_cost ?? 0),
      list_price: Number(item.list_price ?? 0)
    }))
  } catch (error) {
    errors.items = parseError(error)
  } finally {
    loading.items = false
  }
}

async function fetchBoms() {
  loading.boms = true
  errors.boms = null
  try {
    const { data } = await axios.get(`${API_BASE}/erp/boms`, { headers: authHeaders() })
    boms.value = data.data || data
  } catch (error) {
    errors.boms = parseError(error)
  } finally {
    loading.boms = false
  }
}

async function createSite() {
  loading.sites = true
  errors.sites = null
  try {
    await axios.post(`${API_BASE}/erp/sites`, forms.site, { headers: authHeaders() })
    forms.site.code = ''
    forms.site.name = ''
    forms.site.address.line1 = ''
    await fetchSites()
  } catch (error) {
    errors.sites = parseError(error)
  } finally {
    loading.sites = false
  }
}

async function deleteSite(site) {
  loading.deleteSite = site.id
  errors.sites = null
  try {
    await axios.delete(`${API_BASE}/erp/sites/${site.id}`, { headers: authHeaders() })
    await fetchSites()
  } catch (error) {
    errors.sites = parseError(error)
  } finally {
    loading.deleteSite = null
  }
}

async function createDepartment() {
  loading.departments = true
  errors.departments = null
  try {
    await axios.post(`${API_BASE}/erp/departments`, forms.department, { headers: authHeaders() })
    forms.department.code = ''
    forms.department.name = ''
    await fetchDepartments()
  } catch (error) {
    errors.departments = parseError(error)
  } finally {
    loading.departments = false
  }
}

async function deleteDepartment(department) {
  loading.deleteDepartment = department.id
  errors.departments = null
  try {
    await axios.delete(`${API_BASE}/erp/departments/${department.id}`, { headers: authHeaders() })
    await fetchDepartments()
  } catch (error) {
    errors.departments = parseError(error)
  } finally {
    loading.deleteDepartment = null
  }
}

async function createItem() {
  loading.items = true
  errors.items = null
  try {
    await axios.post(`${API_BASE}/erp/items`, forms.item, { headers: authHeaders() })
    Object.assign(forms.item, { code: '', name: '', standard_cost: 0, list_price: 0 })
    await fetchItems()
  } catch (error) {
    errors.items = parseError(error)
  } finally {
    loading.items = false
  }
}

async function createBom() {
  loading.boms = true
  errors.boms = null
  try {
    await axios.post(`${API_BASE}/erp/boms`, forms.bom, { headers: authHeaders() })
    Object.assign(forms.bom, {
      item_id: null,
      code: '',
      revision: 'A',
      status: 'active',
      components: [{ component_item_id: null, quantity: 1, uom: 'EA' }]
    })
    await fetchBoms()
  } catch (error) {
    errors.boms = parseError(error)
  } finally {
    loading.boms = false
  }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

onMounted(async () => {
  await Promise.all([fetchSites(), fetchDepartments(), fetchItems()])
  await fetchBoms()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


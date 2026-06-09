<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="8">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">Locations</div>
              <h1 class="page-title">Manage branches, map them to restaurants, and keep each location organized.</h1>
              <p class="page-copy">
                Admin users can create branches for any venue. Restaurant owners only see and
                manage the branches inside their own brand.
              </p>
            </div>

            <v-btn
              v-if="auth.can('branches.manage')"
              color="primary"
              size="large"
              class="rs-pill"
              @click="openCreate"
            >
              <v-icon start icon="mdi-plus" />
              Add branch
            </v-btn>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Branches</div>
            <div class="metric-value">{{ branches.length }}</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Venues</div>
            <div class="metric-value">
              {{ auth.isAdmin ? restaurants.length : (auth.user?.restaurant?.name ? 1 : 0) }}
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="rs-panel filter-panel">
        <v-row>
          <v-col v-if="auth.isAdmin" cols="12" md="6">
            <v-select
              v-model="selectedRestaurant"
              :items="restaurants"
              item-title="name"
              item-value="id"
              label="Restaurant"
              variant="outlined"
              clearable
              @update:modelValue="loadBranches"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              label="Search branches..."
              prepend-inner-icon="mdi-magnify"
              clearable
              variant="outlined"
            />
          </v-col>
        </v-row>
      </div>

      <div class="rs-panel table-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Branch Directory</div>
            <div class="panel-subtitle">Branch location records available to the current user.</div>
          </div>
        </div>

        <v-data-table
          :headers="headers"
          :items="filteredBranches"
          item-value="id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.restaurant="{ item }">
            <v-chip variant="tonal" color="primary" class="rs-pill">
              {{ item.restaurant?.name || 'Unassigned' }}
            </v-chip>
          </template>

          <template #item.actions="{ item }">
            <div class="table-actions">
              <v-btn
                v-if="auth.can('branches.manage')"
                size="small"
                icon="mdi-pencil-outline"
                variant="text"
                @click="openEdit(item)"
              />
              <v-btn
                v-if="auth.can('branches.manage')"
                size="small"
                icon="mdi-delete-outline"
                variant="text"
                color="error"
                @click="removeBranch(item.id)"
              />
            </div>
          </template>
        </v-data-table>
      </div>

      <v-dialog v-model="dialog" max-width="560">
        <v-card class="dialog-card">
          <v-card-title class="dialog-title">
            {{ editing ? 'Edit branch' : 'Create branch' }}
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col v-if="auth.isAdmin" cols="12">
                <v-select
                  v-model="form.restaurant_id"
                  :items="restaurants"
                  item-title="name"
                  item-value="id"
                  label="Restaurant"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="form.name" label="Branch name" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="form.location" label="Location" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="justify-end px-6 pb-6">
            <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
            <v-btn color="primary" class="rs-pill" @click="saveBranch">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { missingField, showValidationAlert } from '../lib/validationAlert'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const restaurants = ref([])
const branches = ref([])
const dialog = ref(false)
const editing = ref(false)
const selectedRestaurant = ref(auth.user?.restaurant_id ?? null)
const search = ref('')
const form = ref(createEmptyForm())

const headers = [
  { title: 'ID', key: 'id', width: 70 },
  { title: 'Branch', key: 'name' },
  { title: 'Restaurant', key: 'restaurant' },
  { title: 'Location', key: 'location' },
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
]

const filteredBranches = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return branches.value

  return branches.value.filter(branch =>
    (branch.name || '').toLowerCase().includes(q) ||
    (branch.location || '').toLowerCase().includes(q) ||
    (branch.restaurant?.name || '').toLowerCase().includes(q)
  )
})

function createEmptyForm() {
  return {
    id: null,
    restaurant_id: auth.isAdmin ? selectedRestaurant.value : auth.user?.restaurant_id,
    name: '',
    location: '',
  }
}

function authHeaders() {
  return { Authorization: `Bearer ${auth.token}` }
}

async function loadRestaurants() {
  if (!auth.isAdmin) return

  const { data } = await axios.get(`${API_BASE_URL}/restaurants`, {
    headers: authHeaders(),
  })
  restaurants.value = data
}

async function loadBranches() {
  const params = {}

  if (auth.isAdmin && selectedRestaurant.value) {
    params.restaurant_id = selectedRestaurant.value
  }

  const { data } = await axios.get(`${API_BASE_URL}/branches`, {
    headers: authHeaders(),
    params,
  })

  branches.value = data.data || data
}

function openCreate() {
  editing.value = false
  form.value = createEmptyForm()
  dialog.value = true
}

function openEdit(branch) {
  editing.value = true
  form.value = {
    id: branch.id,
    restaurant_id: branch.restaurant_id ?? branch.restaurant?.id ?? auth.user?.restaurant_id,
    name: branch.name,
    location: branch.location,
  }
  dialog.value = true
}

async function saveBranch() {
  const validationFields = branchValidationFields()
  if (validationFields.length) {
    await showValidationAlert(validationFields, { title: 'Complete branch details' })
    return
  }

  const payload = {
    name: form.value.name,
    location: form.value.location,
  }

  if (auth.isAdmin) {
    payload.restaurant_id = form.value.restaurant_id
  }

  if (editing.value) {
    await axios.put(`${API_BASE_URL}/branches/${form.value.id}`, payload, {
      headers: authHeaders(),
    })
  } else {
    await axios.post(`${API_BASE_URL}/branches`, payload, {
      headers: authHeaders(),
    })
  }

  dialog.value = false
  await loadBranches()
}

function branchValidationFields() {
  return [
    missingField('Restaurant', !auth.isAdmin || Boolean(form.value.restaurant_id)),
    missingField('Branch name', Boolean(form.value.name.trim())),
  ].filter(Boolean)
}

async function removeBranch(id) {
  await axios.delete(`${API_BASE_URL}/branches/${id}`, {
    headers: authHeaders(),
  })
  await loadBranches()
}

onMounted(async () => {
  await loadRestaurants()
  await loadBranches()
})
</script>

<style scoped>
.hero-panel,
.metric-card,
.filter-panel,
.table-panel,
.dialog-card {
  padding: 24px;
}

.hero-panel {
  min-height: 220px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-kicker,
.metric-label,
.panel-subtitle {
  color: var(--rs-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.76rem;
}

.page-title {
  max-width: 700px;
  margin-top: 0.8rem;
  color: var(--rs-text);
  font-size: 2.35rem;
  line-height: 1.05;
  letter-spacing: -0.04em;
}

.page-copy {
  max-width: 620px;
  margin-top: 0.8rem;
  color: var(--rs-text-soft);
  line-height: 1.7;
}

.metric-card {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.metric-value {
  color: var(--rs-text);
  font-size: 3rem;
  line-height: 1;
  letter-spacing: -0.05em;
  margin-top: 1rem;
}

.filter-panel,
.table-panel {
  margin-top: 8px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.panel-title,
.dialog-title {
  color: var(--rs-text);
  font-size: 1.2rem;
  font-weight: 700;
}

.table-actions {
  display: flex;
  justify-content: flex-end;
}

.dialog-card {
  border: 1px solid var(--rs-border);
  border-radius: 24px;
  background: #101926;
}

@media (max-width: 960px) {
  .hero-panel {
    min-height: auto;
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 2rem;
  }
}
</style>

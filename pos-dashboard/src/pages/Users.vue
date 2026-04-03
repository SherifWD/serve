<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="8">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">Identity & Access</div>
              <h1 class="page-title">Manage owners, supervisors, waiters, cashiers, and kitchen users.</h1>
              <p class="page-copy">
                Filter by restaurant, branch, or role, then create employee access with the
                correct branch assignment and workspace types.
              </p>
            </div>

            <v-btn
              v-if="auth.can('users.manage')"
              color="primary"
              size="large"
              class="rs-pill"
              @click="openCreate"
            >
              <v-icon start icon="mdi-plus" />
              Add user
            </v-btn>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Users</div>
            <div class="metric-value">{{ users.length }}</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Roles</div>
            <div class="metric-value">{{ roleOptions.length }}</div>
          </div>
        </v-col>
      </v-row>

      <v-alert
        v-if="temporaryPassword"
        type="success"
        variant="tonal"
        class="mb-4"
      >
        User created successfully. Temporary password: <strong>{{ temporaryPassword }}</strong>
      </v-alert>

      <div class="rs-panel filter-panel">
        <v-row>
          <v-col v-if="auth.isAdmin" cols="12" md="4">
            <v-select
              v-model="filters.restaurant_id"
              :items="restaurants"
              item-title="name"
              item-value="id"
              label="Restaurant"
              variant="outlined"
              clearable
              @update:modelValue="handleRestaurantFilterChange"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="filters.branch_id"
              :items="branches"
              item-title="name"
              item-value="id"
              label="Branch"
              variant="outlined"
              clearable
              @update:modelValue="loadUsers"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="filters.role"
              :items="roleOptions"
              label="Role"
              variant="outlined"
              clearable
              @update:modelValue="loadUsers"
            />
          </v-col>
        </v-row>
      </div>

      <div class="rs-panel table-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">User Directory</div>
            <div class="panel-subtitle">Every authenticated dashboard or workspace account.</div>
          </div>
        </div>

        <v-data-table
          :headers="headers"
          :items="users"
          item-value="id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.role="{ item }">
            <v-chip color="primary" variant="tonal" class="rs-pill">
              {{ item.role }}
            </v-chip>
          </template>

          <template #item.types="{ item }">
            <div class="chip-stack">
              <v-chip
                v-for="type in item.types"
                :key="type"
                size="small"
                variant="tonal"
                color="info"
                class="rs-pill"
              >
                {{ type }}
              </v-chip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <div class="table-actions">
              <v-btn
                v-if="auth.can('users.manage')"
                size="small"
                icon="mdi-pencil-outline"
                variant="text"
                @click="openEdit(item)"
              />
              <v-btn
                v-if="auth.can('users.manage')"
                size="small"
                icon="mdi-delete-outline"
                variant="text"
                color="error"
                @click="removeUser(item.id)"
              />
            </div>
          </template>
        </v-data-table>
      </div>

      <v-dialog v-model="dialog" max-width="760">
        <v-card class="dialog-card">
          <v-card-title class="dialog-title">
            {{ editing ? 'Edit user' : 'Create user' }}
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.name" label="Full name" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.email" label="Email" variant="outlined" />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.role"
                  :items="roleOptions"
                  label="Role"
                  variant="outlined"
                  @update:modelValue="handleRoleChange"
                />
              </v-col>

              <v-col v-if="auth.isAdmin" cols="12" md="6">
                <v-select
                  v-model="form.restaurant_id"
                  :items="restaurants"
                  item-title="name"
                  item-value="id"
                  label="Restaurant"
                  variant="outlined"
                  clearable
                  @update:modelValue="handleFormRestaurantChange"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.branch_id"
                  :items="formBranches"
                  item-title="name"
                  item-value="id"
                  label="Branch"
                  variant="outlined"
                  clearable
                  :disabled="!requiresBranch"
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.types"
                  :items="typeOptions"
                  label="Workspace types"
                  variant="outlined"
                  multiple
                  chips
                  clearable
                />
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.password"
                  :label="editing ? 'New password (optional)' : 'Password (optional)'"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="justify-end px-6 pb-6">
            <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
            <v-btn color="primary" class="rs-pill" @click="saveUser">Save</v-btn>
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
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const users = ref([])
const restaurants = ref([])
const branches = ref([])
const roles = ref([])
const dialog = ref(false)
const editing = ref(false)
const temporaryPassword = ref('')
const formBranches = ref([])

const filters = ref({
  restaurant_id: auth.user?.restaurant_id ?? null,
  branch_id: null,
  role: null,
})

const form = ref(createEmptyForm())

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Restaurant', key: 'restaurant.name' },
  { title: 'Branch', key: 'branch.name' },
  { title: 'Types', key: 'types', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
]

const typeOptions = ['owner', 'waiter', 'cashier', 'kitchen']

const roleOptions = computed(() => {
  const dynamic = roles.value.map((role) => role.name)
  return dynamic.length ? dynamic : ['admin', 'owner', 'supervisor', 'staff', 'employee']
})

const requiresBranch = computed(() => !['admin', 'owner'].includes(form.value.role))

function createEmptyForm() {
  return {
    id: null,
    name: '',
    email: '',
    role: 'staff',
    restaurant_id: auth.isAdmin ? filters.value.restaurant_id : auth.user?.restaurant_id,
    branch_id: null,
    types: [],
    password: '',
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

async function loadBranches(restaurantId = filters.value.restaurant_id) {
  const params = {}

  if (auth.isAdmin && restaurantId) {
    params.restaurant_id = restaurantId
  }

  const { data } = await axios.get(`${API_BASE_URL}/branches`, {
    headers: authHeaders(),
    params,
  })

  const collection = data.data || data
  branches.value = collection
  formBranches.value = collection
}

async function loadRoles() {
  const { data } = await axios.get(`${API_BASE_URL}/roles`, {
    headers: authHeaders(),
  })

  roles.value = data.roles || []
}

async function loadUsers() {
  const params = {}

  if (auth.isAdmin && filters.value.restaurant_id) {
    params.restaurant_id = filters.value.restaurant_id
  }
  if (filters.value.branch_id) {
    params.branch_id = filters.value.branch_id
  }
  if (filters.value.role) {
    params.role = filters.value.role
  }

  const { data } = await axios.get(`${API_BASE_URL}/users`, {
    headers: authHeaders(),
    params,
  })

  users.value = data
}

async function handleRestaurantFilterChange() {
  filters.value.branch_id = null
  await loadBranches(filters.value.restaurant_id)
  await loadUsers()
}

function handleRoleChange() {
  if (!requiresBranch.value) {
    form.value.branch_id = null
  }

  if (form.value.role === 'owner') {
    form.value.types = ['owner']
  }
}

async function handleFormRestaurantChange() {
  form.value.branch_id = null
  await loadBranches(form.value.restaurant_id)
  formBranches.value = branches.value
}

function openCreate() {
  editing.value = false
  temporaryPassword.value = ''
  form.value = createEmptyForm()
  formBranches.value = [...branches.value]
  dialog.value = true
}

function openEdit(user) {
  editing.value = true
  temporaryPassword.value = ''
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
    restaurant_id: user.restaurant_id,
    branch_id: user.branch_id,
    types: [...(user.types || [])],
    password: '',
  }
  formBranches.value = branches.value.filter((branch) =>
    !form.value.restaurant_id || branch.restaurant_id === form.value.restaurant_id,
  )
  dialog.value = true
}

async function saveUser() {
  const payload = {
    name: form.value.name,
    email: form.value.email,
    role: form.value.role,
    restaurant_id: form.value.restaurant_id,
    branch_id: requiresBranch.value ? form.value.branch_id : null,
    types: form.value.types,
  }

  if (form.value.password) {
    payload.password = form.value.password
  }

  if (!auth.isAdmin) {
    delete payload.restaurant_id
  }

  if (editing.value) {
    await axios.put(`${API_BASE_URL}/users/${form.value.id}`, payload, {
      headers: authHeaders(),
    })
    temporaryPassword.value = ''
  } else {
    const { data } = await axios.post(`${API_BASE_URL}/users`, payload, {
      headers: authHeaders(),
    })
    temporaryPassword.value = data.temporary_password || ''
  }

  dialog.value = false
  await loadUsers()
}

async function removeUser(id) {
  await axios.delete(`${API_BASE_URL}/users/${id}`, {
    headers: authHeaders(),
  })
  await loadUsers()
}

onMounted(async () => {
  await loadRestaurants()
  await loadBranches(filters.value.restaurant_id)
  await loadRoles()
  await loadUsers()
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
.page-copy,
.panel-subtitle {
  color: var(--rs-text-muted);
}

.page-kicker,
.metric-label,
.panel-subtitle {
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

.chip-stack {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
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

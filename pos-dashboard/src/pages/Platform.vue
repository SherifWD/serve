<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-card class="rounded-xl mb-6" elevation="3">
        <v-card-title class="d-flex align-center">
          <div>
            <div class="text-h6 font-weight-bold">Platform Control Centre</div>
            <div class="text-body-2 text-grey">Manage tenant module access, discounts, and seat allocations.</div>
          </div>
          <v-spacer />
          <v-btn
            color="secondary"
            class="me-2"
            size="small"
            variant="tonal"
            @click="openCreateFactory"
          >
            <v-icon start>mdi-domain-plus</v-icon>
            Add Factory
          </v-btn>
          <v-btn icon color="primary" :loading="loading.tenants" @click="reloadTenants">
            <v-icon>mdi-refresh</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-row dense class="mb-4">
            <v-col cols="12" md="3" v-for="metric in tenantMetrics" :key="metric.label">
              <v-sheet class="pa-4 rounded-lg" color="#232a2e" elevation="1">
                <div class="text-caption text-grey">{{ metric.label }}</div>
                <div class="text-h5 font-weight-bold text-white">{{ metric.value }}</div>
              </v-sheet>
            </v-col>
          </v-row>
          <v-alert v-if="feedback.message" type="success" variant="tonal" class="mb-3">
            {{ feedback.message }}
          </v-alert>
          <v-alert v-if="errors.platform" type="error" variant="tonal" class="mb-3">
            {{ errors.platform }}
          </v-alert>

          <v-expansion-panels variant="accordion">
            <v-expansion-panel v-for="tenant in tenants" :key="tenant.id">
              <v-expansion-panel-title>
                <div class="d-flex flex-column w-100">
                  <div class="d-flex align-center">
                    <span class="text-subtitle-1 font-weight-medium me-2">{{ tenant.name }}</span>
                    <v-chip size="small" color="primary" variant="tonal" class="me-2">
                      {{ tenant.status }}
                    </v-chip>
                    <v-chip
                      v-if="tenant.subscription?.plan"
                      size="small"
                      color="secondary"
                      variant="tonal"
                    >
                      {{ tenant.subscription.plan }}
                    </v-chip>
                    <v-spacer />
                    <v-btn
                      icon
                      size="small"
                      variant="text"
                      color="primary"
                      class="me-1"
                      @click.stop="openEditFactory(tenant)"
                    >
                      <v-icon>mdi-pencil</v-icon>
                    </v-btn>
                    <v-btn
                      size="small"
                      color="secondary"
                      variant="tonal"
                      class="me-3"
                      @click.stop="openEmployeeManager(tenant)"
                    >
                      <v-icon start>mdi-account-group</v-icon>
                      Employees
                    </v-btn>
                    <span class="text-caption text-grey">
                      {{ tenant.modules?.length || 0 }} / {{ modules.length }} modules enabled
                    </span>
                  </div>
                  <div class="text-caption text-grey mt-1">
                    {{ tenant.industry || 'General manufacturing' }} • {{ tenant.timezone || 'UTC' }}
                  </div>
                </div>
              </v-expansion-panel-title>
              <v-expansion-panel-text>
                <v-row dense class="mb-3">
                  <v-col cols="12" md="4">
                    <v-list density="compact" class="rounded-lg" style="background:#232a2e;">
                      <v-list-subheader class="text-grey">Active Modules</v-list-subheader>
                      <v-list-item
                        v-for="module in activeModules(tenant)"
                        :key="`active-${tenant.id}-${module.key}`"
                        :title="module.name"
                        :subtitle="module.status"
                        prepend-icon="mdi-check-circle"
                      />
                    </v-list>
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-alert type="info" variant="tonal" density="compact">
                      Toggle modules to enable/disable access. Adjust status for discounting (pending, active, suspended) and optionally cap seats.
                    </v-alert>
                  </v-col>
                </v-row>
                <v-divider class="mb-4" />
                <v-row class="mb-2 font-weight-medium text-caption text-grey">
                  <v-col cols="12" md="3">Module</v-col>
                  <v-col cols="12" md="2">Enabled</v-col>
                  <v-col cols="12" md="3">Status</v-col>
                  <v-col cols="12" md="2">Seat Limit</v-col>
                  <v-col cols="12" md="2" class="text-right">Actions</v-col>
                </v-row>
                <v-divider />
                <v-row
                  v-for="module in modules"
                  :key="`module-${tenant.id}-${module.id}`"
                  class="align-center py-2"
                >
                  <v-col cols="12" md="3">
                    <div class="text-subtitle-2 text-white">{{ module.name }}</div>
                    <div class="text-caption text-grey">
                      {{ module.category }} • {{ module.has_mobile_app ? 'Web + Mobile' : 'Web' }}
                    </div>
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-switch
                      :model-value="moduleState(tenant.id, module.id)?.enabled ?? false"
                      color="primary"
                      inset
                      :disabled="loading.tenants"
                      @update:model-value="value => setModuleEnabled(tenant.id, module.id, value)"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      :model-value="moduleState(tenant.id, module.id)?.status ?? 'pending'"
                      :items="moduleStatuses"
                      label="Module Status"
                      density="compact"
                      :disabled="!isModuleEnabled(tenant.id, module.id)"
                      @update:model-value="value => setModuleStatus(tenant.id, module.id, value)"
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      :model-value="moduleState(tenant.id, module.id)?.seat_limit"
                      type="number"
                      min="0"
                      density="compact"
                      label="Seats"
                      :disabled="!isModuleEnabled(tenant.id, module.id)"
                      @update:model-value="value => setSeatLimit(tenant.id, module.id, value)"
                    />
                  </v-col>
                  <v-col cols="12" md="2" class="text-right">
                    <v-btn
                      color="primary"
                      variant="tonal"
                      size="small"
                      :loading="isUpdating(tenant.id, module.id)"
                      @click="applyModuleChange(tenant, module)"
                    >
                      Save
                    </v-btn>
                  </v-col>
                  <v-col cols="12" class="pt-0" v-if="module.features?.length">
                    <div class="feature-panel px-4 py-3 mt-2 rounded-lg">
                      <v-row dense>
                        <v-col
                          v-for="feature in module.features"
                          :key="`feature-${tenant.id}-${module.id}-${feature.id}`"
                          cols="12"
                          md="4"
                        >
                          <div class="d-flex align-center justify-space-between">
                            <div class="me-3">
                              <div class="text-subtitle-2 text-white">{{ feature.name }}</div>
                              <div class="text-caption text-grey">
                                {{ feature.description || 'Feature toggle' }}
                              </div>
                              <v-chip
                                size="x-small"
                                :color="tenantStates[tenant.id]?.[module.id]?.features?.[feature.id]?.status === 'enabled' ? 'success' : 'grey'"
                                variant="flat"
                                class="mt-1"
                              >
                                {{ tenantStates[tenant.id]?.[module.id]?.features?.[feature.id]?.status || 'disabled' }}
                              </v-chip>
                            </div>
                            <v-switch
                              :model-value="tenantStates[tenant.id]?.[module.id]?.features?.[feature.id]?.enabled || false"
                              :disabled="!isModuleEnabled(tenant.id, module.id)"
                              :loading="isFeatureUpdating(tenant.id, feature.id)"
                              color="secondary"
                              inset
                              @update:model-value="value => applyFeatureChange(tenant, module, feature, value)"
                            />
                          </div>
                        </v-col>
                      </v-row>
                    </div>
                  </v-col>
                </v-row>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>

          <div class="d-flex justify-end mt-4">
            <v-pagination
              v-model="pagination.page"
              :length="pagination.lastPage"
              :total-visible="5"
              @update:model-value="fetchTenants"
            />
          </div>
        </v-card-text>
      </v-card>

      <v-dialog v-model="factoryDialog.open" max-width="520">
        <v-card>
          <v-card-title class="d-flex align-center">
            <span class="text-h6 font-weight-bold">
              {{ factoryDialog.mode === 'create' ? 'Add Factory' : 'Edit Factory' }}
            </span>
            <v-spacer />
            <v-btn icon variant="text" @click="closeFactoryDialog">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-alert v-if="factoryDialog.errors" type="error" variant="tonal" class="mb-3">
              {{ factoryDialog.errors }}
            </v-alert>
            <v-form @submit.prevent="saveFactory">
              <v-row dense>
                <v-col cols="12">
                  <v-text-field
                    v-model="factoryDialog.form.name"
                    label="Factory Name"
                    density="compact"
                    required
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="factoryDialog.form.industry"
                    label="Industry"
                    density="compact"
                  />
                </v-col>
                <v-col cols="12">
                  <v-text-field
                    v-model="factoryDialog.form.billing_email"
                    label="Billing Email"
                    density="compact"
                    type="email"
                    required
                  />
                </v-col>
                <v-col cols="12">
                  <v-select
                    v-model="factoryDialog.form.status"
                    :items="factoryStatuses"
                    label="Status"
                    density="compact"
                  />
                </v-col>
                <template v-if="factoryDialog.mode === 'create'">
                  <v-col cols="12">
                    <v-text-field
                      v-model="factoryDialog.form.owner_name"
                      label="Owner Name"
                      density="compact"
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="factoryDialog.form.owner_email"
                      label="Owner Email"
                      density="compact"
                      type="email"
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="factoryDialog.form.owner_password"
                      label="Owner Password"
                      density="compact"
                      type="password"
                      required
                    />
                  </v-col>
                </template>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions class="justify-end">
            <v-btn variant="text" @click="closeFactoryDialog">Cancel</v-btn>
            <v-btn
              color="primary"
              :loading="factoryDialog.loading"
              @click="saveFactory"
            >
              {{ factoryDialog.mode === 'create' ? 'Create' : 'Save Changes' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="employeeDialog.open" max-width="900">
        <v-card>
          <v-card-title class="d-flex align-center">
            <div>
              <div class="text-h6 font-weight-bold">
                Manage Employees - {{ employeeDialog.tenant?.name }}
              </div>
              <div class="text-caption text-grey">
                Assign benefits and invite key staff members to the suite.
              </div>
            </div>
            <v-spacer />
            <v-btn icon variant="text" @click="closeEmployeeManager">
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-alert v-if="employeeDialog.errors" type="error" variant="tonal" class="mb-3">
              {{ employeeDialog.errors }}
            </v-alert>
            <v-sheet class="pa-4 mb-4 rounded-lg" color="#232a2e">
              <div class="text-subtitle-2 text-white mb-2">Add New Employee</div>
              <v-form @submit.prevent="createEmployee">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="employeeDialog.form.name"
                      label="Name"
                      density="compact"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="employeeDialog.form.email"
                      label="Email"
                      density="compact"
                      type="email"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="employeeDialog.form.password"
                      label="Temporary Password"
                      density="compact"
                      type="password"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="employeeDialog.form.role"
                      label="Role Key (optional)"
                      density="compact"
                      hint="Matches predefined platform roles, e.g. supervisor"
                      persistent-hint
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="employeeDialog.form.benefitsText"
                      label="Benefits (comma separated)"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" class="text-right">
                    <v-btn
                      color="secondary"
                      :loading="employeeDialog.loading"
                      @click="createEmployee"
                    >
                      <v-icon start>mdi-account-plus</v-icon>
                      Add Employee
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
            </v-sheet>
            <v-data-table
              :headers="employeeHeaders"
              :items="employeeDialog.list"
              :loading="employeeDialog.loading"
              class="elevation-0"
              density="compact"
              item-key="id"
            >
              <template #item.status="{ item }">
                <v-select
                  :model-value="item.status || 'active'"
                  :items="employeeStatusOptions"
                  density="compact"
                  :disabled="item._updating"
                  @update:model-value="value => updateEmployeeStatus(item, value)"
                />
              </template>
              <template #item.benefits="{ item }">
                <v-text-field
                  v-model="item.benefitsText"
                  density="compact"
                  :disabled="item._updating"
                  @blur="updateEmployeeBenefits(item)"
                />
              </template>
              <template #item.name="{ item }">
                <div class="d-flex align-center">
                  <div>
                    <div class="text-body-2 text-white">{{ item.name }}</div>
                    <div class="text-caption text-grey">
                      {{ item.roles?.join(', ') || 'No role assigned' }}
                    </div>
                  </div>
                  <v-progress-circular
                    v-if="item._updating"
                    indeterminate
                    size="20"
                    width="2"
                    color="primary"
                    class="ms-2"
                  />
                </div>
              </template>
            </v-data-table>
          </v-card-text>
          <v-card-actions class="justify-end">
            <v-btn variant="text" @click="closeEmployeeManager">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const modules = ref([])
const tenants = ref([])
const tenantStates = reactive({})
const updating = reactive({})
const featureUpdating = reactive({})

const loading = reactive({
  modules: false,
  tenants: false
})

const errors = reactive({
  platform: null
})

const feedback = reactive({
  message: null
})

const pagination = reactive({
  page: 1,
  perPage: 10,
  lastPage: 1,
  total: 0
})

const moduleStatuses = ['pending', 'active', 'suspended']
const employeeStatusOptions = ['active', 'invited', 'suspended']

const factoryDialog = reactive({
  open: false,
  mode: 'create',
  loading: false,
  errors: null,
  form: {
    id: null,
    name: '',
    industry: '',
    billing_email: '',
    status: 'active',
    owner_name: '',
    owner_email: '',
    owner_password: ''
  }
})

const employeeDialog = reactive({
  open: false,
  loading: false,
  errors: null,
  tenant: null,
  list: [],
  form: {
    name: '',
    email: '',
    password: '',
    role: null,
    benefitsText: ''
  }
})

const factoryStatuses = ['trial', 'active', 'suspended', 'canceled']
const employeeHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Status', key: 'status' },
  { title: 'Benefits', key: 'benefits' }
]

function resetFactoryForm() {
  Object.assign(factoryDialog.form, {
    id: null,
    name: '',
    industry: '',
    billing_email: '',
    status: 'active',
    owner_name: '',
    owner_email: '',
    owner_password: ''
  })
  factoryDialog.errors = null
}

function openCreateFactory() {
  factoryDialog.mode = 'create'
  resetFactoryForm()
  factoryDialog.open = true
}

function openEditFactory(tenant) {
  factoryDialog.mode = 'edit'
  factoryDialog.errors = null
  Object.assign(factoryDialog.form, {
    id: tenant.id,
    name: tenant.name,
    industry: tenant.industry || '',
    billing_email: tenant.billing_email || '',
    status: tenant.status || 'active',
    owner_name: '',
    owner_email: '',
    owner_password: ''
  })
  factoryDialog.open = true
}

async function saveFactory() {
  factoryDialog.loading = true
  factoryDialog.errors = null
  try {
    if (factoryDialog.mode === 'create') {
      const payload = {
        name: factoryDialog.form.name,
        billing_email: factoryDialog.form.billing_email,
        status: factoryDialog.form.status,
        industry: factoryDialog.form.industry || null,
        owner_name: factoryDialog.form.owner_name,
        owner_email: factoryDialog.form.owner_email,
        owner_password: factoryDialog.form.owner_password,
      }
      await axios.post(`${API_BASE}/platform/tenants`, payload, { headers: authHeaders() })
    } else if (factoryDialog.form.id) {
      const payload = {
        name: factoryDialog.form.name,
        billing_email: factoryDialog.form.billing_email,
        status: factoryDialog.form.status,
        industry: factoryDialog.form.industry || null,
      }
      await axios.put(`${API_BASE}/platform/tenants/${factoryDialog.form.id}`, payload, { headers: authHeaders() })
    }
    factoryDialog.open = false
    await reloadTenants()
    resetFactoryForm()
    feedback.message = factoryDialog.mode === 'create' ? 'Factory created successfully.' : 'Factory updated successfully.'
  } catch (error) {
    factoryDialog.errors = parseError(error)
  } finally {
    factoryDialog.loading = false
  }
}

function closeFactoryDialog() {
  factoryDialog.open = false
  resetFactoryForm()
}

function resetEmployeeForm() {
  Object.assign(employeeDialog.form, {
    name: '',
    email: '',
    password: '',
    role: null,
    benefitsText: ''
  })
  employeeDialog.errors = null
}

function mapEmployees(list) {
  return (list || []).map(user => ({
    ...user,
    benefits: user.benefits || [],
    benefitsText: benefitsToString(user.benefits || []),
    _updating: false,
  }))
}

async function loadEmployees(tenantId) {
  employeeDialog.loading = true
  employeeDialog.errors = null
  try {
    const { data } = await axios.get(`${API_BASE}/platform/tenants/${tenantId}/users`, { headers: authHeaders() })
    employeeDialog.list = mapEmployees(data.data || data)
  } catch (error) {
    employeeDialog.errors = parseError(error)
  } finally {
    employeeDialog.loading = false
  }
}

function openEmployeeManager(tenant) {
  employeeDialog.tenant = tenant
  employeeDialog.open = true
  employeeDialog.list = []
  resetEmployeeForm()
  loadEmployees(tenant.id)
}

function closeEmployeeManager() {
  employeeDialog.open = false
  employeeDialog.tenant = null
  employeeDialog.list = []
  resetEmployeeForm()
}

function splitBenefits(text) {
  if (!text) return []
  return text
    .split(',')
    .map(value => value.trim())
    .filter(value => value.length > 0)
}

function benefitsToString(list) {
  return (list || []).join(', ')
}

async function createEmployee() {
  if (!employeeDialog.tenant) return
  employeeDialog.loading = true
  employeeDialog.errors = null
  try {
    const payload = {
      name: employeeDialog.form.name,
      email: employeeDialog.form.email,
      password: employeeDialog.form.password,
      role: employeeDialog.form.role || null,
      benefits: splitBenefits(employeeDialog.form.benefitsText),
    }
    await axios.post(
      `${API_BASE}/platform/tenants/${employeeDialog.tenant.id}/users`,
      payload,
      { headers: authHeaders() }
    )
    resetEmployeeForm()
    await loadEmployees(employeeDialog.tenant.id)
    await reloadTenants()
    feedback.message = 'Employee added successfully.'
  } catch (error) {
    employeeDialog.errors = parseError(error)
  } finally {
    employeeDialog.loading = false
  }
}

async function syncEmployee(user, payload) {
  if (!employeeDialog.tenant) return
  user._updating = true
  employeeDialog.errors = null
  try {
    const { data } = await axios.put(
      `${API_BASE}/platform/tenants/${employeeDialog.tenant.id}/users/${user.id}`,
      payload,
      { headers: authHeaders() }
    )
    employeeDialog.list = mapEmployees(data.data || data)
    await reloadTenants()
    feedback.message = 'Employee settings updated.'
  } catch (error) {
    employeeDialog.errors = parseError(error)
  } finally {
    user._updating = false
  }
}

function updateEmployeeBenefits(user) {
  const benefits = splitBenefits(user.benefitsText)
  syncEmployee(user, { benefits })
}

function updateEmployeeStatus(user, status) {
  syncEmployee(user, { status })
}

const tenantMetrics = computed(() => {
  const tenantCount = tenants.value.length
  const activeTenants = tenants.value.filter(tenant => tenant.status === 'active').length
  const totalEnabledModules = tenants.value.reduce((sum, tenant) => sum + (tenant.modules?.length || 0), 0)
  const totalEnabledFeatures = tenants.value.reduce((sum, tenant) => {
    const moduleFeatures = (tenant.modules || []).reduce((featureSum, module) => {
      const enabledFeatures = (module.features || []).filter(feature => feature.status === 'enabled').length
      return featureSum + enabledFeatures
    }, 0)
    return sum + moduleFeatures
  }, 0)
  return [
    { label: 'Tenants Loaded', value: tenantCount },
    { label: 'Active Tenants', value: activeTenants },
    { label: 'Modules Enabled', value: totalEnabledModules },
    { label: 'Features Enabled', value: totalEnabledFeatures },
    { label: 'Available Modules', value: modules.value.length }
  ]
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return { Authorization: token ? `Bearer ${token}` : undefined }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

function keyFor(tenantId, moduleId) {
  return `${tenantId}-${moduleId}`
}

function isUpdating(tenantId, moduleId) {
  return !!updating[keyFor(tenantId, moduleId)]
}

function activeModules(tenant) {
  return tenant.modules || []
}

function ensureModuleState(tenantId, moduleId) {
  if (!tenantStates[tenantId]) {
    tenantStates[tenantId] = {}
  }

  if (!tenantStates[tenantId][moduleId]) {
    tenantStates[tenantId][moduleId] = {
      enabled: false,
      status: 'pending',
      seat_limit: null,
      features: {}
    }
  }

  if (!tenantStates[tenantId][moduleId].features) {
    tenantStates[tenantId][moduleId].features = {}
  }

  return tenantStates[tenantId][moduleId]
}

function moduleState(tenantId, moduleId) {
  return tenantStates[tenantId]?.[moduleId] ?? null
}

function ensureFeatureState(tenantId, moduleId, featureId) {
  const state = ensureModuleState(tenantId, moduleId)
  if (!state.features[featureId]) {
    state.features[featureId] = {
      enabled: false,
      status: 'disabled',
      settings: null
    }
  }
  return state.features[featureId]
}

function isModuleEnabled(tenantId, moduleId) {
  return !!moduleState(tenantId, moduleId)?.enabled
}

function setModuleEnabled(tenantId, moduleId, value) {
  const state = ensureModuleState(tenantId, moduleId)
  state.enabled = !!value

  if (!state.enabled && state.features) {
    Object.values(state.features).forEach(feature => {
      feature.enabled = false
      feature.status = 'disabled'
    })
  }
}

function setModuleStatus(tenantId, moduleId, value) {
  const state = ensureModuleState(tenantId, moduleId)
  state.status = value || 'pending'
}

function setSeatLimit(tenantId, moduleId, value) {
  const state = ensureModuleState(tenantId, moduleId)
  if (value === '' || value === null || typeof value === 'undefined') {
    state.seat_limit = null
    return
  }
  const parsed = Number(value)
  state.seat_limit = Number.isNaN(parsed) ? null : parsed
}

function prepareTenantState(tenant) {
  if (!tenantStates[tenant.id]) {
    tenantStates[tenant.id] = {}
  }

  modules.value.forEach(module => {
    const assigned = tenant.modules?.find(item => item.id === module.id)
    const featureStates = {}
    const moduleFeatures = module.features || []

    const moduleEnabled = !!assigned
    const state = ensureModuleState(tenant.id, module.id)

    moduleFeatures.forEach(feature => {
      const tenantFeature = assigned?.features?.find(f => f.id === feature.id)
      const defaultEnabled = feature.is_default ?? false
      featureStates[feature.id] = {
        enabled: moduleEnabled ? (tenantFeature?.enabled ?? defaultEnabled) : false,
        status: moduleEnabled
          ? (tenantFeature?.status || (defaultEnabled ? 'enabled' : 'disabled'))
          : 'disabled',
        settings: moduleEnabled ? tenantFeature?.settings || null : null
      }
    })

    state.enabled = moduleEnabled
    state.status = assigned?.status || 'pending'
    state.seat_limit = assigned?.seat_limit ?? null
    state.features = featureStates
  })
}

async function fetchModules() {
  loading.modules = true
  try {
    const { data } = await axios.get(`${API_BASE}/platform/modules`, { headers: authHeaders() })
    modules.value = data.data || data
  } catch (error) {
    errors.platform = parseError(error)
  } finally {
    loading.modules = false
  }
}

async function fetchTenants(page = pagination.page) {
  loading.tenants = true
  errors.platform = null
  try {
    const { data } = await axios.get(`${API_BASE}/platform/tenants`, {
      headers: authHeaders(),
      params: { page, per_page: pagination.perPage }
    })
    tenants.value = data.data || []
    pagination.page = data.meta?.current_page || page
    pagination.lastPage = data.meta?.last_page || 1
    pagination.total = data.meta?.total || tenants.value.length
    tenants.value.forEach(prepareTenantState)
  } catch (error) {
    errors.platform = parseError(error)
  } finally {
    loading.tenants = false
  }
}

async function reloadTenants() {
  await fetchTenants(pagination.page)
  if (employeeDialog.open && employeeDialog.tenant) {
    const freshTenant = tenants.value.find(t => t.id === employeeDialog.tenant.id)
    if (freshTenant) {
      employeeDialog.tenant = freshTenant
    }
  }
}

async function applyModuleChange(tenant, module) {
  const state = moduleState(tenant.id, module.id) || ensureModuleState(tenant.id, module.id)

  const key = keyFor(tenant.id, module.id)
  updating[key] = true
  errors.platform = null
  feedback.message = null

  const payload = state.enabled
    ? {
        enabled: true,
        status: state.status,
        seat_limit: state.seat_limit ?? null
      }
    : { enabled: false }

  try {
    const { data } = await axios.put(
      `${API_BASE}/platform/tenants/${tenant.id}/modules/${module.id}`,
      payload,
      { headers: authHeaders() }
    )
    const updatedTenant = data.data
    const index = tenants.value.findIndex(item => item.id === updatedTenant.id)
    if (index !== -1) {
      tenants.value.splice(index, 1, updatedTenant)
      prepareTenantState(updatedTenant)
    }
    feedback.message = `${module.name} updated for ${updatedTenant.name}`
    setTimeout(() => {
      if (feedback.message === `${module.name} updated for ${updatedTenant.name}`) {
        feedback.message = null
      }
    }, 4000)
  } catch (error) {
    errors.platform = parseError(error)
    await fetchTenants(pagination.page)
  } finally {
    updating[key] = false
  }
}

function featureKey(tenantId, featureId) {
  return `${tenantId}-${featureId}`
}

function isFeatureUpdating(tenantId, featureId) {
  return !!featureUpdating[featureKey(tenantId, featureId)]
}

async function applyFeatureChange(tenant, module, feature, enabled) {
  if (!isModuleEnabled(tenant.id, module.id)) {
    return
  }

  const key = featureKey(tenant.id, feature.id)
  featureUpdating[key] = true
  errors.platform = null
  feedback.message = null

  const featureState = ensureFeatureState(tenant.id, module.id, feature.id)
  featureState.enabled = !!enabled
  featureState.status = enabled ? 'enabled' : 'disabled'

  const payload = {
    status: enabled ? 'enabled' : 'disabled'
  }

  try {
    const { data } = await axios.put(
      `${API_BASE}/platform/tenants/${tenant.id}/modules/${module.id}/features/${feature.id}`,
      payload,
      { headers: authHeaders() }
    )
    const updatedTenant = data.data
    const index = tenants.value.findIndex(item => item.id === updatedTenant.id)
    if (index !== -1) {
      tenants.value.splice(index, 1, updatedTenant)
      prepareTenantState(updatedTenant)
    }
    feedback.message = `${feature.name} ${enabled ? 'enabled' : 'disabled'} for ${updatedTenant.name}`
    setTimeout(() => {
      if (feedback.message === `${feature.name} ${enabled ? 'enabled' : 'disabled'} for ${updatedTenant.name}`) {
        feedback.message = null
      }
    }, 4000)
  } catch (error) {
    errors.platform = parseError(error)
    await fetchTenants(pagination.page)
  } finally {
    Reflect.deleteProperty(featureUpdating, key)
  }
}

onMounted(async () => {
  await fetchModules()
  await fetchTenants()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}

.feature-panel {
  background: #1e2428;
  border: 1px solid #2a343a;
}
</style>

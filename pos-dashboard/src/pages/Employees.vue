<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col>
          <v-card elevation="3" color="card" class="mb-8" style="border-radius:2rem;">
            <v-card-title class="d-flex align-center" style="border-radius:2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Employees</span>
              <v-spacer />
              <v-btn color="primary" variant="elevated" class="rounded-pill" size="large"
                     style="color:#181818;font-weight:bold;" @click="openAdd">
                <v-icon start>mdi-plus</v-icon>Add Employee
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="search"
                label="Quick Search (all columns)"
                prepend-inner-icon="mdi-magnify"
                dense
                clearable
                rounded
                class="mb-3"
              />
              <!-- Filter Bar (advanced search) -->
              <v-row class="mb-4" dense>
                <v-col cols="12" md="2">
                  <v-text-field v-model="filters.name" label="Name" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model="filters.position" label="Position" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select
                    v-model="filters.restaurant_id"
                    :items="restaurants"
                    item-title="name"
                    item-value="id"
                    label="Restaurant"
                    dense
                    clearable
                    rounded
                    @update:model-value="filters.branch_id = null"
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select
                    v-model="filters.branch_id"
                    :items="branchFilterItems"
                    item-title="name"
                    item-value="id"
                    label="Branch"
                    dense
                    clearable
                    rounded
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field
                    v-model="filters.hired_at"
                    label="Hired Date"
                    type="date"
                    dense
                    clearable
                    rounded
                  />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model="filters.salary" label="Min Salary" type="number" dense clearable rounded />
                </v-col>
              </v-row>
              <v-data-table
                :headers="headers"
                :items="filteredEmployees"
                :items-per-page="10"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
              >
                <template #item.name="{ item }">
                  <v-avatar color="#e0eee7" size="30" class="mr-2 text-primary" v-if="item.name">
                    {{ item.name[0] || '?' }}
                  </v-avatar>
                  {{ item.name }}
                </template>
                <template #item.branch="{ item }">
                  <v-chip color="primary" size="small" variant="tonal" v-if="item.branch">{{ item.branch.name }}</v-chip>
                  <span v-else>—</span>
                </template>
                <template #item.actions="{ item }">
                  <v-btn size="small" icon color="primary" class="rounded-xl" @click="openEdit(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <!-- <v-btn size="small" icon color="accent" class="rounded-xl" @click="openPerformance(item)">
                    <v-icon>mdi-chart-line</v-icon>
                  </v-btn> -->
                  <v-btn size="small" icon color="red" class="rounded-xl" @click="deleteEmployee(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Add/Edit Drawer (with tabs) -->
      <v-navigation-drawer
        v-model="dialog"
        location="right"
        temporary
        width="440"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ editing ? 'Edit Employee' : 'Add Employee' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="dialog = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
          <v-tab value="general">General Info</v-tab>
          <v-tab value="performance" :disabled="!editing">Performance</v-tab>
        </v-tabs>
        <v-window v-model="tab">
          <!-- General Info Tab -->
          <v-window-item value="general">
            <v-form @submit.prevent="saveEmployee" class="pa-6">
              <v-text-field label="Name" v-model="form.name" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select
                label="Restaurant"
                :items="restaurants"
                item-title="name"
                item-value="id"
                v-model="form.restaurant_id"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
                @update:model-value="syncEmployeeBranch"
              />
              <v-select
                label="Position"
                :items="roleOptions"
                v-model="form.position"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-text-field label="Base Salary" v-model="form.base_salary" type="number" min="0" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select label="Branch" :items="filteredFormBranches" item-title="name" item-value="id" v-model="form.branch_id" required variant="outlined" color="primary" class="mb-4 rounded-xl" no-data-text="Choose a restaurant with branches" />
              <v-text-field label="Hired At" v-model="form.hired_at" type="date" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-btn color="primary" class="rounded-pill" block size="large" style="color:#181818;font-weight:bold;" type="submit">
                <v-icon start>mdi-check</v-icon>{{ editing ? 'Update' : 'Add' }}
              </v-btn>
            </v-form>
          </v-window-item>
          <!-- Performance Tab (view only, for editing employees) -->
          <v-window-item value="performance" v-if="editing">
            <div class="pa-6">
              <h3 class="mb-3" style="color:#2a9d8f;">Recent Performance</h3>
              <div class="metric-bubbles mb-6" style="display:flex;flex-wrap:wrap;gap:1rem;">
                <div
                  v-for="(perf, i) in performanceRecords"
                  :key="i"
                  class="bubble-card"
                >
                  <div class="metric-title">{{ perf.metric }}</div>
                  <div class="metric-value">{{ perf.value }}</div>
                  <div class="metric-date">{{ perf.recorded_at }}</div>
                </div>
                <div v-if="!performanceRecords.length" class="text-center text-muted py-4">No performance records yet.</div>
              </div>
              <!-- <div v-show="performanceRecords.length" class="mb-6" style="width:100%;max-width:340px;">
                <h4 class="mb-2">Performance Trend</h4>
                <canvas id="perfChart" width="320" height="110" style="max-width:100%;"></canvas>
              </div>
              <v-divider class="my-2" />
              <div class="text-caption text-grey-darken-1">Showing up to 10 most recent records</div> -->
            </div>
          </v-window-item>
        </v-window>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import { confirmDelete } from '../lib/confirmDelete'
import { missingField, showValidationAlert } from '../lib/validationAlert'
import Chart from 'chart.js/auto'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const employees = ref([])
const branches = ref([])
const restaurants = ref([])
const roles = ref([])
const dialog = ref(false)
const editing = ref(false)
const performanceRecords = ref([])
const chartInstance = ref(null)
const form = ref({
  id: null, name: '', restaurant_id: null, position: '', base_salary: '', branch_id: null, hired_at: ''
})
const tab = ref('general')
const search = ref('')

const filters = ref({
  name: '',
  restaurant_id: null,
  position: '',
  branch_id: null,
  hired_at: '',
  salary: ''
})

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Position', value: 'position' },
  { title: 'Base Salary', value: 'base_salary' },
  { title: 'Branch', value: 'branch' },
  { title: 'Hired At', value: 'hired_at' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const roleOptions = computed(() => {
  const dynamic = roles.value.map(role => role.name)
  return Array.from(new Set([...dynamic, 'owner', 'supervisor', 'staff', 'employee']))
})

const branchFilterItems = computed(() => {
  if (!filters.value.restaurant_id) return branches.value
  return branches.value.filter(branch =>
    Number(branchRestaurantId(branch)) === Number(filters.value.restaurant_id)
  )
})

const filteredFormBranches = computed(() => {
  if (!form.value.restaurant_id) return branches.value
  return branches.value.filter(branch =>
    Number(branchRestaurantId(branch)) === Number(form.value.restaurant_id)
  )
})

const filteredEmployees = computed(() => {
  return employees.value.filter(emp => {
    // Quick search
    if (search.value) {
      const text = `${emp.name} ${emp.position} ${emp.base_salary} ${(emp.branch?.name || '')} ${emp.hired_at}`.toLowerCase()
      if (!text.includes(search.value.toLowerCase())) return false
    }
    // Individual column filters
    if (filters.value.name && !emp.name?.toLowerCase().includes(filters.value.name.toLowerCase())) return false
    if (filters.value.restaurant_id && Number(branchRestaurantId(emp.branch)) !== Number(filters.value.restaurant_id)) return false
    if (filters.value.position && !emp.position?.toLowerCase().includes(filters.value.position.toLowerCase())) return false
    if (filters.value.branch_id && Number(emp.branch_id) !== Number(filters.value.branch_id)) return false
    if (filters.value.hired_at && emp.hired_at !== filters.value.hired_at) return false
    if (filters.value.salary && parseFloat(emp.base_salary) < parseFloat(filters.value.salary)) return false
    return true
  })
})

async function loadEmployees() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/employees`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  employees.value = res.data.data || res.data
}
async function loadRestaurants() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/restaurants`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  restaurants.value = res.data.data || res.data
}

async function loadBranches() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/branches`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = res.data.data || res.data
}

async function loadRoles() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/roles`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  roles.value = res.data.roles || []
}

function branchRestaurantId(branch) {
  return branch?.restaurant_id ?? branch?.restaurant?.id ?? null
}

function defaultRestaurantId() {
  return auth.user?.restaurant_id ??
    auth.user?.branch?.restaurant_id ??
    branchRestaurantId(branches.value[0]) ??
    restaurants.value[0]?.id ??
    null
}

function syncEmployeeBranch() {
  const selectedStillAvailable = filteredFormBranches.value.some(branch =>
    Number(branch.id) === Number(form.value.branch_id)
  )
  if (!selectedStillAvailable) {
    form.value.branch_id = filteredFormBranches.value[0]?.id ?? null
  }
}

function openAdd() {
  editing.value = false
  tab.value = 'general'
  form.value = {
    id: null,
    name: '',
    restaurant_id: defaultRestaurantId(),
    position: roleOptions.value.includes('staff') ? 'staff' : (roleOptions.value[0] || ''),
    base_salary: '',
    branch_id: null,
    hired_at: '',
  }
  syncEmployeeBranch()
  dialog.value = true
  performanceRecords.value = []
}
function openEdit(emp) {
  editing.value = true
  tab.value = 'general'
  form.value = {
    ...emp,
    restaurant_id: branchRestaurantId(emp.branch) ?? defaultRestaurantId(),
  }
  syncEmployeeBranch()
  dialog.value = true
  loadPerformance(emp.id)
}

async function saveEmployee() {
  const validationFields = employeeValidationFields()
  if (validationFields.length) {
    await showValidationAlert(validationFields, { title: 'Complete employee details' })
    return
  }

  const token = localStorage.getItem('token')
  const payload = {
    name: form.value.name,
    position: form.value.position,
    base_salary: form.value.base_salary,
    branch_id: form.value.branch_id,
    hired_at: form.value.hired_at,
  }
  if (editing.value) {
    await axios.put(`${API_BASE_URL}/employees/${form.value.id}`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    })
  } else {
    await axios.post(`${API_BASE_URL}/employees`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    })
  }
  dialog.value = false
  loadEmployees()
}

function employeeValidationFields() {
  return [
    missingField('Name', Boolean(form.value.name.trim())),
    missingField('Restaurant', Boolean(form.value.restaurant_id)),
    missingField('Position', Boolean(form.value.position)),
    missingField('Base Salary', hasNumberAtLeast(form.value.base_salary, 0)),
    missingField('Branch', Boolean(form.value.branch_id)),
    missingField('Hired At', Boolean(form.value.hired_at)),
  ].filter(Boolean)
}

function hasNumberAtLeast(value, min) {
  if (value === '' || value === null || value === undefined) return false
  const numeric = Number(value)
  return Number.isFinite(numeric) && numeric >= min
}

async function deleteEmployee(emp) {
  if (!await confirmDelete(emp.name)) return
  const token = localStorage.getItem('token')
  await axios.delete(`${API_BASE_URL}/employees/${emp.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadEmployees()
}

async function loadPerformance(employeeId) {
  // Call this in openEdit
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/employees/${employeeId}/performance`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  performanceRecords.value = (res.data.data || res.data).slice(0, 10).reverse()
  await nextTick()
  renderChart()
}

function renderChart() {
  // Destroy previous chart if any
  if (chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
  if (performanceRecords.value.length) {
    const canvas = document.getElementById('perfChart')
    if (canvas) {
      const ctx = canvas.getContext('2d')
      // Group by metric
      const grouped = {}
      for (const rec of performanceRecords.value) {
        if (!grouped[rec.metric]) grouped[rec.metric] = []
        grouped[rec.metric].push(rec)
      }
      const dateSet = new Set()
      for (const arr of Object.values(grouped)) for (const rec of arr) dateSet.add(rec.recorded_at)
      const allDates = Array.from(dateSet).sort()
      const colors = ['#f97233', '#009ffd', '#ffc300', '#43aa8b', '#ff6f61', '#7367f0']
      const datasets = Object.entries(grouped).map(([metric, arr], i) => {
        const valueByDate = Object.fromEntries(arr.map(rec => [rec.recorded_at, rec.value]))
        return {
          label: metric,
          data: allDates.map(d => valueByDate[d] ?? null),
          borderColor: colors[i % colors.length],
          backgroundColor: colors[i % colors.length] + "40",
          fill: false,
          tension: 0.3,
          pointRadius: 4,
          spanGaps: true,
        }
      })
      chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: {
          labels: allDates,
          datasets,
        },
        options: {
          plugins: { legend: { display: true } },
          scales: {
            x: { ticks: { font: { size: 11 } }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { font: { size: 11 } } }
          },
          responsive: true,
          maintainAspectRatio: true,
        }
      })
    }
  }
}

// Clean up chart when drawer is closed
watch(dialog, (val) => {
  if (!val && chartInstance.value) {
    chartInstance.value.destroy()
    chartInstance.value = null
  }
})

onMounted(() => {
  loadRestaurants()
  loadEmployees()
  loadBranches()
  loadRoles()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.metric-bubbles {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.bubble-card {
  background: #2a9d8f20;
  border-radius: 1.3rem;
  padding: 1rem 1.4rem;
  box-shadow: 0 4px 24px 0 #0002;
  min-width: 105px;
  min-height: 85px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
}
.metric-title {
  font-weight: bold;
  font-size: 1.1em;
  color: #f97233;
}
.metric-value {
  font-size: 1.4em;
  font-weight: 900;
  color: #222;
  letter-spacing: 0.02em;
  margin: 2px 0 2px 0;
}
.metric-date {
  font-size: 0.88em;
  color: #888;
}
</style>

<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Cost Centres</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.costCenters" @click="fetchCostCenters">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.costCenters" type="error" variant="tonal" class="mb-3">
                {{ errors.costCenters }}
              </v-alert>
              <v-form :disabled="loading.costCenters" @submit.prevent="createCostCenter">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.costCenter.code"
                      label="Code"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.costCenter.name"
                      label="Name"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.costCenter.department" label="Department" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.costCenter.manager_name" label="Manager" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.costCenter.status"
                      :items="costCenterStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.costCenters">
                      <v-icon start>mdi-plus</v-icon>
                      Add Cost Centre
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-select
                v-model="selectedCostCenter"
                :items="costCenters"
                item-title="name"
                item-value="id"
                label="Filter by Cost Centre"
                dense
                clearable
              />
              <v-data-table
                :headers="costCenterHeaders"
                :items="costCenters"
                :loading="loading.costCenters"
                class="elevation-0"
                density="compact"
              >
                <template #item.status="{ item }">
                  <v-chip :color="item.status === 'active' ? 'success' : 'grey'" size="small" variant="flat">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Budgets</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.budgets" @click="fetchBudgets">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.budgets" type="error" variant="tonal" class="mb-3">
                {{ errors.budgets }}
              </v-alert>
              <v-form :disabled="loading.budgets" @submit.prevent="createBudget">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.budget.cost_center_id"
                      :items="costCenters"
                      item-title="name"
                      item-value="id"
                      label="Cost Centre"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.budget.fiscal_year"
                      label="Fiscal Year"
                      dense
                      placeholder="FY25"
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.budget.period"
                      label="Period"
                      dense
                      placeholder="FY25-Q4"
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.budget.status"
                      :items="budgetStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.budget.planned_amount"
                      type="number"
                      min="0"
                      label="Planned"
                      dense
                      required
                      :rules="[requiredNumberRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.budget.approved_amount"
                      type="number"
                      min="0"
                      label="Approved"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.budget.forecast_amount"
                      type="number"
                      min="0"
                      label="Forecast"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="forms.budget.currency"
                      label="Currency"
                      dense
                      maxlength="3"
                    />
                  </v-col>
                  <v-col cols="12" md="10">
                    <v-textarea
                      v-model="forms.budget.assumptionsText"
                      label="Assumptions (JSON or comma separated)"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.budgets">
                      <v-icon start>mdi-finance</v-icon>
                      Save Budget
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="budgetHeaders"
                :items="filteredBudgets"
                :loading="loading.budgets"
                class="elevation-0"
                density="compact"
              >
                <template #item.cost_center="{ item }">
                  {{ item.cost_center?.name || '—' }}
                </template>
                <template #item.planned_amount="{ item }">
                  {{ formatCurrency(item.planned_amount, item.currency) }}
                </template>
                <template #item.approved_amount="{ item }">
                  {{ formatCurrency(item.approved_amount, item.currency) }}
                </template>
                <template #item.forecast_amount="{ item }">
                  {{ formatCurrency(item.forecast_amount, item.currency) }}
                </template>
                <template #item.status="{ item }">
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ item.status || 'draft' }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Actuals & Variance</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.actuals" @click="fetchActuals">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.actuals" type="error" variant="tonal" class="mb-3">
                {{ errors.actuals }}
              </v-alert>
              <v-form :disabled="loading.actuals" @submit.prevent="createActual">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.actual.cost_center_id"
                      :items="costCenters"
                      item-title="name"
                      item-value="id"
                      label="Cost Centre"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.actual.fiscal_year"
                      label="Fiscal Year"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.actual.period"
                      label="Period"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.actual.actual_amount"
                      type="number"
                      min="0"
                      label="Actual Amount"
                      dense
                      required
                      :rules="[requiredNumberRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.actual.currency"
                      label="Currency"
                      dense
                      maxlength="3"
                    />
                  </v-col>
                  <v-col cols="12" md="5">
                    <v-text-field
                      v-model="forms.actual.source_reference"
                      label="Source Reference"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.actual.notes"
                      label="Notes"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.actuals">
                      <v-icon start>mdi-file-chart</v-icon>
                      Record Actual
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="actualHeaders"
                :items="filteredActuals"
                :loading="loading.actuals"
                class="elevation-0"
                density="compact"
              >
                <template #item.cost_center="{ item }">
                  {{ item.cost_center?.name || '—' }}
                </template>
                <template #item.actual_amount="{ item }">
                  {{ formatCurrency(item.actual_amount, item.currency) }}
                </template>
              </v-data-table>

              <v-divider class="my-4" />
              <v-data-table
                :headers="varianceHeaders"
                :items="varianceRows"
                class="elevation-0"
                density="compact"
              >
                <template #item.planned="{ item }">
                  {{ formatCurrency(item.planned, item.currency) }}
                </template>
                <template #item.actual="{ item }">
                  {{ formatCurrency(item.actual, item.currency) }}
                </template>
                <template #item.variance="{ item }">
                  <v-chip :color="item.variance >= 0 ? 'success' : 'error'" size="small" variant="tonal">
                    {{ formatCurrency(item.variance, item.currency) }}
                  </v-chip>
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const costCenters = ref([])
const budgets = ref([])
const actuals = ref([])
const selectedCostCenter = ref(null)

const loading = reactive({
  costCenters: false,
  budgets: false,
  actuals: false
})

const errors = reactive({
  costCenters: null,
  budgets: null,
  actuals: null
})

const forms = reactive({
  costCenter: {
    code: '',
    name: '',
    department: '',
    manager_name: '',
    status: 'active'
  },
  budget: {
    cost_center_id: null,
    fiscal_year: '',
    period: '',
    status: 'draft',
    planned_amount: 0,
    approved_amount: null,
    forecast_amount: null,
    currency: 'USD',
    assumptionsText: ''
  },
  actual: {
    cost_center_id: null,
    fiscal_year: '',
    period: '',
    actual_amount: 0,
    currency: 'USD',
    source_reference: '',
    notes: ''
  }
})

const costCenterStatuses = ['active', 'inactive']
const budgetStatuses = ['draft', 'submitted', 'approved', 'locked']

const costCenterHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Department', key: 'department' },
  { title: 'Manager', key: 'manager_name' },
  { title: 'Status', key: 'status' }
]

const budgetHeaders = [
  { title: 'Period', key: 'period' },
  { title: 'Cost Centre', key: 'cost_center' },
  { title: 'Planned', key: 'planned_amount' },
  { title: 'Approved', key: 'approved_amount' },
  { title: 'Forecast', key: 'forecast_amount' },
  { title: 'Status', key: 'status' }
]

const actualHeaders = [
  { title: 'Period', key: 'period' },
  { title: 'Cost Centre', key: 'cost_center' },
  { title: 'Actual', key: 'actual_amount' },
  { title: 'Source', key: 'source_reference' }
]

const varianceHeaders = [
  { title: 'Period', key: 'period' },
  { title: 'Planned', key: 'planned' },
  { title: 'Actual', key: 'actual' },
  { title: 'Variance', key: 'variance' },
  { title: 'Currency', key: 'currency' }
]

const requiredRule = value => (!!value && value !== '') || 'Required'
const requiredNumberRule = value => (value !== null && value !== '' && !Number.isNaN(Number(value))) || 'Required'

const filteredBudgets = computed(() => {
  if (!selectedCostCenter.value) return budgets.value
  return budgets.value.filter(b => b.cost_center_id === selectedCostCenter.value)
})

const filteredActuals = computed(() => {
  if (!selectedCostCenter.value) return actuals.value
  return actuals.value.filter(a => a.cost_center_id === selectedCostCenter.value)
})

const varianceRows = computed(() => {
  const rows = []
  const costCenterId = selectedCostCenter.value
  const relevantBudgets = costCenterId ? budgets.value.filter(b => b.cost_center_id === costCenterId) : budgets.value
  relevantBudgets.forEach(budget => {
    const matchingActual = actuals.value.find(actual =>
      actual.cost_center_id === budget.cost_center_id &&
      actual.period === budget.period &&
      (!costCenterId || actual.cost_center_id === costCenterId)
    )
    const planned = Number(budget.approved_amount ?? budget.planned_amount ?? 0)
    const actualValue = Number(matchingActual?.actual_amount ?? 0)
    rows.push({
      period: budget.period,
      planned,
      actual: actualValue,
      variance: planned - actualValue,
      currency: budget.currency || 'USD'
    })
  })
  return rows
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

function parseAssumptions(text) {
  if (!text) return null
  try {
    const parsed = JSON.parse(text)
    return typeof parsed === 'object' ? parsed : null
  } catch (error) {
    return text.split(',').map(item => item.trim()).filter(Boolean)
  }
}

function formatCurrency(value, currency = 'USD') {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency,
    minimumFractionDigits: 2
  }).format(amount)
}

async function fetchCostCenters() {
  loading.costCenters = true
  errors.costCenters = null
  try {
    const { data } = await axios.get(`${API_BASE}/budgeting/cost-centers`, { headers: authHeaders() })
    costCenters.value = data.data || data
  } catch (error) {
    errors.costCenters = parseError(error)
  } finally {
    loading.costCenters = false
  }
}

async function fetchBudgets() {
  loading.budgets = true
  errors.budgets = null
  try {
    const params = selectedCostCenter.value ? { cost_center_id: selectedCostCenter.value, per_page: 100 } : { per_page: 100 }
    const { data } = await axios.get(`${API_BASE}/budgeting/budgets`, { headers: authHeaders(), params })
    budgets.value = (data.data || data)
  } catch (error) {
    errors.budgets = parseError(error)
  } finally {
    loading.budgets = false
  }
}

async function fetchActuals() {
  loading.actuals = true
  errors.actuals = null
  try {
    const params = selectedCostCenter.value ? { cost_center_id: selectedCostCenter.value, per_page: 100 } : { per_page: 100 }
    const { data } = await axios.get(`${API_BASE}/budgeting/actuals`, { headers: authHeaders(), params })
    actuals.value = data.data || data
  } catch (error) {
    errors.actuals = parseError(error)
  } finally {
    loading.actuals = false
  }
}

async function createCostCenter() {
  loading.costCenters = true
  errors.costCenters = null
  try {
    await axios.post(`${API_BASE}/budgeting/cost-centers`, forms.costCenter, { headers: authHeaders() })
    Object.assign(forms.costCenter, {
      code: '',
      name: '',
      department: '',
      manager_name: '',
      status: 'active'
    })
    await fetchCostCenters()
  } catch (error) {
    errors.costCenters = parseError(error)
  } finally {
    loading.costCenters = false
  }
}

async function createBudget() {
  loading.budgets = true
  errors.budgets = null
  try {
    const payload = {
      cost_center_id: forms.budget.cost_center_id,
      fiscal_year: forms.budget.fiscal_year,
      period: forms.budget.period,
      status: forms.budget.status,
      planned_amount: forms.budget.planned_amount,
      approved_amount: forms.budget.approved_amount,
      forecast_amount: forms.budget.forecast_amount,
      currency: forms.budget.currency,
      assumptions: parseAssumptions(forms.budget.assumptionsText)
    }
    await axios.post(`${API_BASE}/budgeting/budgets`, payload, { headers: authHeaders() })
    Object.assign(forms.budget, {
      cost_center_id: null,
      fiscal_year: '',
      period: '',
      status: 'draft',
      planned_amount: 0,
      approved_amount: null,
      forecast_amount: null,
      currency: 'USD',
      assumptionsText: ''
    })
    await fetchBudgets()
  } catch (error) {
    errors.budgets = parseError(error)
  } finally {
    loading.budgets = false
  }
}

async function createActual() {
  loading.actuals = true
  errors.actuals = null
  try {
    await axios.post(`${API_BASE}/budgeting/actuals`, forms.actual, { headers: authHeaders() })
    Object.assign(forms.actual, {
      cost_center_id: null,
      fiscal_year: '',
      period: '',
      actual_amount: 0,
      currency: 'USD',
      source_reference: '',
      notes: ''
    })
    await fetchActuals()
  } catch (error) {
    errors.actuals = parseError(error)
  } finally {
    loading.actuals = false
  }
}

watch(selectedCostCenter, async () => {
  await Promise.all([fetchBudgets(), fetchActuals()])
})

onMounted(async () => {
  await fetchCostCenters()
  await Promise.all([fetchBudgets(), fetchActuals()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


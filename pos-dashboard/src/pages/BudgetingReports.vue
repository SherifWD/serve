<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="text-h6 font-weight-bold">Filters</v-card-title>
            <v-card-text>
              <v-form @submit.prevent="fetchSummary">
                <v-select
                  v-model="filters.cost_center_id"
                  :items="costCenters"
                  item-title="name"
                  item-value="id"
                  label="Cost Centre"
                  density="compact"
                  :loading="loading.costCenters"
                  clearable
                />
                <v-text-field
                  v-model="filters.fiscal_year"
                  label="Fiscal Year"
                  density="compact"
                  placeholder="FY25"
                />
                <v-select
                  v-model="filters.status"
                  :items="budgetStatuses"
                  label="Budget Status"
                  density="compact"
                  clearable
                />
                <v-text-field
                  v-model="filters.period"
                  label="Period"
                  density="compact"
                  placeholder="FY25-Q4"
                />
                <v-btn
                  type="submit"
                  color="primary"
                  class="rounded-pill mt-3"
                  :loading="loading.summary"
                  block
                >
                  Apply Filters
                </v-btn>
                <v-btn
                  color="secondary"
                  class="rounded-pill mt-2"
                  block
                  :loading="loading.export"
                  @click="downloadReport"
                >
                  <v-icon start>mdi-download</v-icon>
                  Export CSV
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="text-h6 font-weight-bold">Summary</v-card-title>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" v-if="errors.summary">
                  <v-alert type="error" variant="tonal">{{ errors.summary }}</v-alert>
                </v-col>
                <v-col cols="12" v-else-if="summary">
                  <v-row dense>
                    <v-col cols="12" md="6" v-for="metric in summaryCards" :key="metric.label">
                      <v-sheet class="pa-4 rounded-lg" color="#232a2e" elevation="1">
                        <div class="text-caption text-grey">{{ metric.label }}</div>
                        <div class="text-h5 font-weight-bold text-white">{{ metric.value }}</div>
                      </v-sheet>
                    </v-col>
                  </v-row>
                </v-col>
                <v-col cols="12" v-else>
                  <v-skeleton-loader type="card" class="rounded-lg" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Cost Centre Performance</span>
              <v-spacer />
              <v-chip size="small" color="primary" variant="tonal">
                {{ costCenterChart.categories.length }} cost centres
              </v-chip>
            </v-card-title>
            <v-card-text>
              <apexchart
                type="bar"
                height="320"
                :options="costCenterChart.options"
                :series="costCenterChart.series"
              />
              <v-data-table
                :headers="costCenterHeaders"
                :items="summary?.cost_centers || []"
                class="mt-4 elevation-0"
                dense
              >
                <template #item.variance="{ item }">
                  <v-chip size="small" :color="Number(item.variance) >= 0 ? 'success' : 'error'" variant="tonal">
                    {{ formatCurrency(item.variance) }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Period Trend</span>
              <v-spacer />
              <v-chip size="small" color="secondary" variant="tonal">
                {{ periodChart.categories.length }} periods
              </v-chip>
            </v-card-title>
            <v-card-text>
              <apexchart
                type="line"
                height="320"
                :options="periodChart.options"
                :series="periodChart.series"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const costCenters = ref([])
const summary = ref(null)

const filters = reactive({
  cost_center_id: null,
  fiscal_year: '',
  status: '',
  period: ''
})

const loading = reactive({
  summary: false,
  costCenters: false,
  export: false
})

const errors = reactive({
  summary: null
})

const budgetStatuses = ['draft', 'submitted', 'approved', 'locked']

const costCenterHeaders = [
  { title: 'Cost Centre', key: 'cost_center' },
  { title: 'Planned', key: 'planned' },
  { title: 'Approved', key: 'approved' },
  { title: 'Forecast', key: 'forecast' },
  { title: 'Actual', key: 'actual' },
  { title: 'Variance', key: 'variance' }
]

const summaryCards = computed(() => {
  if (!summary.value?.totals) return []
  return [
    { label: 'Planned', value: formatCurrency(summary.value.totals.planned) },
    { label: 'Approved', value: formatCurrency(summary.value.totals.approved) },
    { label: 'Actual', value: formatCurrency(summary.value.totals.actual) },
    { label: 'Forecast', value: formatCurrency(summary.value.totals.forecast) }
  ]
})

const costCenterChart = computed(() => {
  const data = summary.value?.cost_centers || []
  const categories = data.map(item => item.cost_center || 'N/A')

  return {
    categories,
    series: [
      {
        name: 'Approved',
        data: data.map(item => Number(item.approved))
      },
      {
        name: 'Actual',
        data: data.map(item => Number(item.actual))
      }
    ],
    options: {
      chart: { stacked: false, toolbar: { show: false } },
      theme: { mode: 'dark' },
      plotOptions: { bar: { horizontal: false, borderRadius: 6 } },
      dataLabels: { enabled: false },
      xaxis: { categories },
      yaxis: { labels: { formatter: val => formatCurrency(val) } },
      tooltip: { y: { formatter: val => formatCurrency(val) } },
      legend: { position: 'top', horizontalAlign: 'center' }
    }
  }
})

const periodChart = computed(() => {
  const data = summary.value?.periods || []
  const categories = data.map(item => item.period || 'N/A')

  return {
    categories,
    series: [
      { name: 'Approved', data: data.map(item => Number(item.approved)) },
      { name: 'Actual', data: data.map(item => Number(item.actual)) },
      { name: 'Forecast', data: data.map(item => Number(item.forecast)) }
    ],
    options: {
      chart: { toolbar: { show: false } },
      theme: { mode: 'dark' },
      stroke: { width: 2, curve: 'smooth' },
      dataLabels: { enabled: false },
      xaxis: { categories },
      yaxis: { labels: { formatter: val => formatCurrency(val) } },
      tooltip: { y: { formatter: val => formatCurrency(val) } },
      legend: { position: 'top', horizontalAlign: 'center' }
    }
  }
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return { Authorization: token ? `Bearer ${token}` : undefined }
}

function formatCurrency(value) {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount)
}

async function fetchCostCenters() {
  loading.costCenters = true
  try {
    const { data } = await axios.get(`${API_BASE}/budgeting/cost-centers`, {
      headers: authHeaders(),
      params: { per_page: 200 }
    })
    costCenters.value = data.data || data
  } catch (error) {
    // non-blocking
  } finally {
    loading.costCenters = false
  }
}

async function fetchSummary() {
  loading.summary = true
  errors.summary = null
  try {
    const { data } = await axios.get(`${API_BASE}/budgeting/reports/summary`, {
      headers: authHeaders(),
      params: cleanFilters(filters)
    })
    summary.value = data
  } catch (error) {
    errors.summary = parseError(error)
  } finally {
    loading.summary = false
  }
}

async function downloadReport() {
  loading.export = true
  try {
    const response = await axios.get(`${API_BASE}/budgeting/reports/export`, {
      headers: authHeaders(),
      params: cleanFilters(filters),
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `budgeting-report-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    errors.summary = parseError(error)
  } finally {
    loading.export = false
  }
}

function cleanFilters(source) {
  return Object.entries(source).reduce((result, [key, value]) => {
    if (value !== null && value !== '') result[key] = value
    return result
  }, {})
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

onMounted(async () => {
  await fetchCostCenters()
  await fetchSummary()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


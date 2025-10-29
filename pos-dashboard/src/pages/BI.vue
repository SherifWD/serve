<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">BI Overview</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.summary" @click="fetchSummary">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
              <v-btn
                color="secondary"
                class="rounded-pill ms-2"
                size="small"
                :loading="loading.summaryExport"
                @click="downloadSummary"
              >
                <v-icon start>mdi-download</v-icon>
                Export
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.summary" type="error" variant="tonal" class="mb-4">
                {{ errors.summary }}
              </v-alert>
              <template v-else>
                <v-row v-if="biSummary" dense>
                  <v-col cols="12" md="3" v-for="metric in biSummaryCards" :key="metric.label">
                    <v-sheet class="pa-4 rounded-lg" color="#232a2e" elevation="1">
                      <div class="text-caption text-grey">{{ metric.label }}</div>
                      <div class="text-h5 font-weight-bold text-white">{{ metric.value }}</div>
                    </v-sheet>
                  </v-col>
                  <v-col cols="12" md="6">
                    <apexchart
                      type="donut"
                      height="260"
                      :options="categoryChart.options"
                      :series="categoryChart.series"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <apexchart
                      type="line"
                      height="260"
                      :options="trendChart.options"
                      :series="trendChart.series"
                    />
                  </v-col>
                </v-row>
                <v-skeleton-loader v-else type="card,card" class="rounded-lg" />
              </template>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="5">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Key Performance Indicators</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.kpis" @click="fetchKpis">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.kpis" type="error" variant="tonal" class="mb-4">
                {{ errors.kpis }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.kpis" @submit.prevent="createKpi">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.kpi.code" label="KPI Code" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.kpi.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.kpi.category" label="Category" dense />
                  </v-col>
                  <v-col cols="6">
                    <v-text-field v-model="forms.kpi.unit" label="Unit" dense />
                  </v-col>
                  <v-col cols="6">
                    <v-text-field
                      v-model.number="forms.kpi.target"
                      type="number"
                      step="0.01"
                      label="Target"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.kpis">
                      <v-icon start>mdi-chart-bell-curve</v-icon>
                      Add KPI
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="kpiHeaders"
                :items="kpis"
                :loading="loading.kpis"
                class="elevation-0"
              >
                <template #item.config="{ item }">
                  <span v-if="item.config?.target">
                    Target: {{ item.config.target }}
                  </span>
                  <span v-else>—</span>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Dashboards</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.dashboards" @click="fetchDashboards">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.dashboards" type="error" variant="tonal" class="mb-4">
                {{ errors.dashboards }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.dashboards" @submit.prevent="createDashboard">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.dashboard.title" label="Dashboard Title" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.dashboard.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-switch
                      v-model="forms.dashboard.is_default"
                      hide-details
                      inset
                      color="primary"
                      label="Mark as default dashboard"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.dashboards">
                      <v-icon start>mdi-view-dashboard</v-icon>
                      Create Dashboard
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="dashboardHeaders"
                :items="dashboards"
                :loading="loading.dashboards"
                class="elevation-0"
              >
                <template #item.is_default="{ item }">
                  <v-chip size="small" :color="item.is_default ? 'success' : 'grey'" variant="flat">
                    {{ item.is_default ? 'Default' : 'Optional' }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="7">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Dashboard Widgets</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.widgets" @click="fetchWidgets">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.widgets" type="error" variant="tonal" class="mb-4">
                {{ errors.widgets }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.widgets" @submit.prevent="createWidget">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.widget.dashboard_id"
                      :items="dashboards"
                      item-title="title"
                      item-value="id"
                      label="Dashboard"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.widget.kpi_id"
                      :items="kpis"
                      item-title="name"
                      item-value="id"
                      label="KPI"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.widget.type"
                      :items="widgetTypes"
                      label="Widget Type"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.widget.position"
                      type="number"
                      min="0"
                      label="Position"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.widget.title"
                      label="Display Title"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.widgets">
                      <v-icon start>mdi-widget</v-icon>
                      Attach Widget
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="widgetHeaders"
                :items="widgets"
                :loading="loading.widgets"
                class="elevation-0"
              >
                <template #item.dashboard="{ item }">
                  {{ dashboards.find(d => d.id === item.dashboard_id)?.title ?? '—' }}
                </template>
                <template #item.kpi="{ item }">
                  {{ kpis.find(k => k.id === item.kpi_id)?.name ?? '—' }}
                </template>
                <template #item.options="{ item }">
                  {{ item.options?.title ?? '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">KPI Snapshots</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.snapshots" @click="fetchSnapshots">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.snapshots" type="error" variant="tonal" class="mb-4">
                {{ errors.snapshots }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.snapshots" @submit.prevent="createSnapshot">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.snapshot.kpi_id"
                      :items="kpis"
                      item-title="name"
                      item-value="id"
                      label="KPI"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.snapshot.snapshot_date"
                      type="date"
                      label="Snapshot Date"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="forms.snapshot.value"
                      type="number"
                      step="0.0001"
                      label="Value"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.snapshots">
                      <v-icon start>mdi-chart-line</v-icon>
                      Capture Snapshot
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="snapshotHeaders"
                :items="snapshots"
                :loading="loading.snapshots"
                class="elevation-0"
              >
                <template #item.kpi="{ item }">
                  {{ kpis.find(k => k.id === item.kpi_id)?.name ?? '—' }}
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
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const biSummary = ref(null)
const kpis = ref([])
const dashboards = ref([])
const widgets = ref([])
const snapshots = ref([])

const loading = reactive({
  summary: false,
  summaryExport: false,
  kpis: false,
  dashboards: false,
  widgets: false,
  snapshots: false
})

const errors = reactive({
  summary: null,
  kpis: null,
  dashboards: null,
  widgets: null,
  snapshots: null
})

const forms = reactive({
  kpi: {
    code: '',
    name: '',
    category: '',
    unit: '',
    target: null
  },
  dashboard: {
    title: '',
    description: '',
    is_default: false
  },
  widget: {
    dashboard_id: null,
    kpi_id: null,
    type: 'metric',
    position: null,
    title: ''
  },
  snapshot: {
    kpi_id: null,
    snapshot_date: '',
    value: null
  }
})

const biSummaryCards = computed(() => {
  if (!biSummary.value) return []
  const totalDashboards = biSummary.value.dashboards?.total ?? 0
  const widgetCounts = biSummary.value.dashboards?.widget_counts || []
  const averageWidgets = widgetCounts.length
    ? widgetCounts.reduce((sum, item) => sum + Number(item.widgets ?? 0), 0) / widgetCounts.length
    : 0

  return [
    { label: 'KPIs', value: formatNumber(biSummary.value.kpis?.total ?? 0) },
    { label: 'Dashboards', value: formatNumber(totalDashboards) },
    { label: 'Default Dashboards', value: formatNumber(biSummary.value.dashboards?.with_default ?? 0) },
    { label: 'Avg Widgets / Dashboard', value: formatNumber(averageWidgets, 1) }
  ]
})

const categoryChart = computed(() => {
  const buckets = biSummary.value?.kpis?.by_category || []
  return {
    series: buckets.map(bucket => bucket.count),
    options: {
      chart: { type: 'donut' },
      theme: { mode: 'dark' },
      labels: buckets.map(bucket => bucket.category ?? 'uncategorised'),
      legend: { position: 'bottom' },
      dataLabels: { enabled: true }
    }
  }
})

const trendChart = computed(() => {
  const trends = biSummary.value?.trends || []
  const categories = trends.map(entry => entry.date)

  return {
    series: [
      { name: 'Average', data: trends.map(entry => Number(entry.average_value ?? 0)) },
      { name: 'Min', data: trends.map(entry => Number(entry.min_value ?? 0)) },
      { name: 'Max', data: trends.map(entry => Number(entry.max_value ?? 0)) }
    ],
    options: {
      chart: { type: 'line', toolbar: { show: false } },
      theme: { mode: 'dark' },
      stroke: { width: 2, curve: 'smooth' },
      xaxis: { categories },
      dataLabels: { enabled: false },
      yaxis: {
        labels: {
          formatter: val => formatNumber(val, 1)
        }
      },
      legend: { position: 'top', horizontalAlign: 'center' }
    }
  }
})

const widgetTypes = ['metric', 'chart', 'table']

const kpiHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Category', key: 'category' },
  { title: 'Unit', key: 'unit' },
  { title: 'Config', key: 'config' }
]

const dashboardHeaders = [
  { title: 'Title', key: 'title' },
  { title: 'Description', key: 'description' },
  { title: 'Default', key: 'is_default' },
  { title: 'Widgets', key: 'widgets_count' }
]

const widgetHeaders = [
  { title: 'Dashboard', key: 'dashboard' },
  { title: 'KPI', key: 'kpi' },
  { title: 'Type', key: 'type' },
  { title: 'Position', key: 'position' },
  { title: 'Options', key: 'options' }
]

const snapshotHeaders = [
  { title: 'KPI', key: 'kpi' },
  { title: 'Date', key: 'snapshot_date' },
  { title: 'Value', key: 'value' }
]

function formatNumber(value, decimals = 0) {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(amount)
}

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

async function fetchSummary() {
  loading.summary = true
  errors.summary = null
  try {
    const { data } = await axios.get(`${API_BASE}/bi/reports/summary`, { headers: authHeaders() })
    biSummary.value = data
  } catch (error) {
    errors.summary = parseError(error)
  } finally {
    loading.summary = false
  }
}

async function downloadSummary() {
  loading.summaryExport = true
  try {
    const response = await axios.get(`${API_BASE}/bi/reports/export`, {
      headers: authHeaders(),
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bi-report-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    errors.summary = parseError(error)
  } finally {
    loading.summaryExport = false
  }
}

async function fetchKpis() {
  loading.kpis = true
  errors.kpis = null
  try {
    const { data } = await axios.get(`${API_BASE}/bi/kpis`, { headers: authHeaders() })
    kpis.value = data.data || data
  } catch (error) {
    errors.kpis = parseError(error)
  } finally {
    loading.kpis = false
  }
}

async function fetchDashboards() {
  loading.dashboards = true
  errors.dashboards = null
  try {
    const { data } = await axios.get(`${API_BASE}/bi/dashboards`, { headers: authHeaders() })
    dashboards.value = data.data || data
  } catch (error) {
    errors.dashboards = parseError(error)
  } finally {
    loading.dashboards = false
  }
}

async function fetchWidgets() {
  loading.widgets = true
  errors.widgets = null
  try {
    const { data } = await axios.get(`${API_BASE}/bi/dashboard-widgets`, { headers: authHeaders() })
    widgets.value = data.data || data
  } catch (error) {
    errors.widgets = parseError(error)
  } finally {
    loading.widgets = false
  }
}

async function fetchSnapshots() {
  loading.snapshots = true
  errors.snapshots = null
  try {
    const { data } = await axios.get(`${API_BASE}/bi/data-snapshots`, { headers: authHeaders() })
    snapshots.value = data.data || data
  } catch (error) {
    errors.snapshots = parseError(error)
  } finally {
    loading.snapshots = false
  }
}

async function createKpi() {
  loading.kpis = true
  errors.kpis = null
  try {
    const payload = {
      code: forms.kpi.code,
      name: forms.kpi.name,
      category: forms.kpi.category || null,
      unit: forms.kpi.unit || null,
      config: forms.kpi.target !== null && forms.kpi.target !== '' ? { target: forms.kpi.target } : null
    }
    await axios.post(`${API_BASE}/bi/kpis`, payload, { headers: authHeaders() })
    Object.assign(forms.kpi, { code: '', name: '', category: '', unit: '', target: null })
    await fetchKpis()
  } catch (error) {
    errors.kpis = parseError(error)
  } finally {
    loading.kpis = false
  }
}

async function createDashboard() {
  loading.dashboards = true
  errors.dashboards = null
  try {
    await axios.post(`${API_BASE}/bi/dashboards`, forms.dashboard, { headers: authHeaders() })
    Object.assign(forms.dashboard, { title: '', description: '', is_default: false })
    await fetchDashboards()
  } catch (error) {
    errors.dashboards = parseError(error)
  } finally {
    loading.dashboards = false
  }
}

async function createWidget() {
  loading.widgets = true
  errors.widgets = null
  try {
    const payload = {
      dashboard_id: forms.widget.dashboard_id,
      kpi_id: forms.widget.kpi_id,
      type: forms.widget.type,
      position: forms.widget.position,
      options: forms.widget.title ? { title: forms.widget.title } : null
    }
    await axios.post(`${API_BASE}/bi/dashboard-widgets`, payload, { headers: authHeaders() })
    Object.assign(forms.widget, {
      dashboard_id: null,
      kpi_id: null,
      type: 'metric',
      position: null,
      title: ''
    })
    await fetchWidgets()
  } catch (error) {
    errors.widgets = parseError(error)
  } finally {
    loading.widgets = false
  }
}

async function createSnapshot() {
  loading.snapshots = true
  errors.snapshots = null
  try {
    await axios.post(`${API_BASE}/bi/data-snapshots`, forms.snapshot, { headers: authHeaders() })
    Object.assign(forms.snapshot, { kpi_id: null, snapshot_date: '', value: null })
    await fetchSnapshots()
  } catch (error) {
    errors.snapshots = parseError(error)
  } finally {
    loading.snapshots = false
  }
}

onMounted(async () => {
  await Promise.all([fetchSummary(), fetchKpis(), fetchDashboards(), fetchWidgets(), fetchSnapshots()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

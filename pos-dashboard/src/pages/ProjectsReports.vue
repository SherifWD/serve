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
                  v-model="filters.status"
                  :items="projectStatuses"
                  label="Project Status"
                  density="compact"
                  clearable
                />
                <v-text-field
                  v-model="filters.stage"
                  label="Project Stage"
                  density="compact"
                  placeholder="execution"
                />
                <v-select
                  v-model="filters.task_status"
                  :items="taskStatuses"
                  label="Task Status"
                  density="compact"
                  clearable
                />
                <v-select
                  v-model="filters.change_status"
                  :items="changeStatuses"
                  label="Change Status"
                  density="compact"
                  clearable
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
            <v-card-title class="text-h6 font-weight-bold">Snapshot</v-card-title>
            <v-card-text>
              <v-alert v-if="errors.summary" type="error" variant="tonal">
                {{ errors.summary }}
              </v-alert>
              <v-row v-else-if="summary" dense>
                <v-col cols="12" md="6" v-for="metric in summaryCards" :key="metric.label">
                  <v-sheet class="pa-4 rounded-lg" color="#232a2e" elevation="1">
                    <div class="text-caption text-grey">{{ metric.label }}</div>
                    <div class="text-h5 font-weight-bold text-white">{{ metric.value }}</div>
                  </v-sheet>
                </v-col>
              </v-row>
              <v-skeleton-loader v-else type="card" class="rounded-lg" />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-row>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Projects by Status</v-card-title>
                <v-card-text>
                  <apexchart
                    type="donut"
                    height="280"
                    :options="projectChart.options"
                    :series="projectChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Tasks by Status</v-card-title>
                <v-card-text>
                  <apexchart
                    type="bar"
                    height="280"
                    :options="taskStatusChart.options"
                    :series="taskStatusChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Tasks by Priority</v-card-title>
                <v-card-text>
                  <apexchart
                    type="bar"
                    height="280"
                    :options="taskPriorityChart.options"
                    :series="taskPriorityChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Change Requests</v-card-title>
                <v-card-text>
                  <apexchart
                    type="pie"
                    height="280"
                    :options="changeChart.options"
                    :series="changeChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="text-h6 font-weight-bold">Timeline Overview</v-card-title>
            <v-card-text>
              <v-data-table
                :headers="timelineHeaders"
                :items="summary?.timeline || []"
                class="elevation-0"
                density="compact"
              >
                <template #item.days_remaining="{ item }">
                  <v-chip
                    size="small"
                    :color="item.days_remaining >= 0 ? 'success' : 'error'"
                    variant="tonal"
                  >
                    {{ formatDays(item.days_remaining) }}
                  </v-chip>
                </template>
                <template #item.average_progress="{ item }">
                  <v-progress-linear
                    :model-value="Number(item.average_progress)"
                    height="8"
                    rounded
                    color="primary"
                  />
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

const summary = ref(null)

const filters = reactive({
  status: '',
  stage: '',
  task_status: '',
  change_status: ''
})

const loading = reactive({
  summary: false,
  export: false
})

const errors = reactive({
  summary: null
})

const projectStatuses = ['draft', 'active', 'on_hold', 'completed', 'archived']
const taskStatuses = ['not_started', 'in_progress', 'completed', 'blocked']
const changeStatuses = ['draft', 'submitted', 'in_review', 'approved', 'rejected', 'implemented']

const timelineHeaders = [
  { title: 'Project', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Status', key: 'status' },
  { title: 'Start', key: 'start_date' },
  { title: 'Due', key: 'due_date' },
  { title: 'Days Remaining', key: 'days_remaining' },
  { title: 'Progress', key: 'average_progress' }
]

const summaryCards = computed(() => {
  if (!summary.value) return []
  return [
    { label: 'Projects', value: summary.value.projects?.total ?? 0 },
    { label: 'Tasks', value: summary.value.tasks?.total ?? 0 },
    { label: 'Change Requests', value: summary.value.change_requests?.total ?? 0 },
    {
      label: 'Avg Task Progress',
      value: `${Number(summary.value.tasks?.average_progress ?? 0).toFixed(1)} %`
    }
  ]
})

const projectChart = computed(() => {
  const data = summary.value?.projects?.status_breakdown || []
  return {
    series: data.map(item => item.count),
    options: {
      chart: { type: 'donut' },
      theme: { mode: 'dark' },
      labels: data.map(item => item.status),
      legend: { position: 'bottom' },
      dataLabels: { enabled: true }
    }
  }
})

const taskStatusChart = computed(() => {
  const data = summary.value?.tasks?.status_breakdown || []
  return {
    series: [
      {
        name: 'Tasks',
        data: data.map(item => item.count)
      }
    ],
    options: {
      chart: { type: 'bar', toolbar: { show: false } },
      theme: { mode: 'dark' },
      plotOptions: { bar: { horizontal: false, borderRadius: 6 } },
      xaxis: { categories: data.map(item => item.status) },
      dataLabels: { enabled: false }
    }
  }
})

const taskPriorityChart = computed(() => {
  const data = summary.value?.tasks?.priority_breakdown || []
  return {
    series: [
      {
        name: 'Tasks',
        data: data.map(item => item.count)
      }
    ],
    options: {
      chart: { type: 'bar', toolbar: { show: false } },
      theme: { mode: 'dark' },
      plotOptions: { bar: { horizontal: false, borderRadius: 6 } },
      xaxis: { categories: data.map(item => item.priority) },
      dataLabels: { enabled: false },
      colors: ['#37b37e']
    }
  }
})

const changeChart = computed(() => {
  const data = summary.value?.change_requests?.status_breakdown || []
  return {
    series: data.map(item => item.count),
    options: {
      chart: { type: 'pie' },
      theme: { mode: 'dark' },
      labels: data.map(item => item.status),
      legend: { position: 'bottom' },
      dataLabels: { enabled: true }
    }
  }
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return { Authorization: token ? `Bearer ${token}` : undefined }
}

function formatDays(value) {
  if (value === null || value === undefined) return 'N/A'
  const days = Number(value)
  if (Number.isNaN(days)) return 'N/A'
  return days >= 0 ? `${days} days` : `${Math.abs(days)} overdue`
}

async function fetchSummary() {
  loading.summary = true
  errors.summary = null
  try {
    const { data } = await axios.get(`${API_BASE}/projects/reports/summary`, {
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
    const response = await axios.get(`${API_BASE}/projects/reports/export`, {
      headers: authHeaders(),
      params: cleanFilters(filters),
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `projects-report-${new Date().toISOString().slice(0, 10)}.csv`)
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
  await fetchSummary()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


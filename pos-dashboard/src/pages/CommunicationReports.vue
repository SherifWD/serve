++ pos-dashboard/src/pages/CommunicationReports.vue
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
                  :items="workflowStatuses"
                  label="Workflow Status"
                  density="compact"
                  clearable
                />
                <v-select
                  v-model="filters.priority"
                  :items="workflowPriorities"
                  label="Priority"
                  density="compact"
                  clearable
                />
                <v-text-field
                  v-model="filters.request_type"
                  label="Request Type"
                  density="compact"
                  placeholder="Access Request"
                />
                <v-text-field
                  v-model="filters.requested_from"
                  label="Requested From"
                  type="date"
                  density="compact"
                />
                <v-text-field
                  v-model="filters.requested_to"
                  label="Requested To"
                  type="date"
                  density="compact"
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
                <v-card-title class="text-h6 font-weight-bold">Announcements by Status</v-card-title>
                <v-card-text>
                  <apexchart
                    type="pie"
                    height="280"
                    :options="announcementStatusChart.options"
                    :series="announcementStatusChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Announcements by Priority</v-card-title>
                <v-card-text>
                  <apexchart
                    type="bar"
                    height="280"
                    :options="announcementPriorityChart.options"
                    :series="announcementPriorityChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Workflow Requests</v-card-title>
                <v-card-text>
                  <apexchart
                    type="bar"
                    height="280"
                    :options="workflowStatusChart.options"
                    :series="workflowStatusChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" lg="6">
              <v-card class="rounded-xl mb-6" elevation="3">
                <v-card-title class="text-h6 font-weight-bold">Workflow Actions</v-card-title>
                <v-card-text>
                  <apexchart
                    type="donut"
                    height="280"
                    :options="workflowActionChart.options"
                    :series="workflowActionChart.series"
                  />
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="text-h6 font-weight-bold">Workflow Requests</v-card-title>
            <v-card-text>
              <v-data-table
                :headers="workflowHeaders"
                :items="summary?.workflow_requests?.details || []"
                class="elevation-0"
                density="compact"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" color="primary" variant="tonal">
                    {{ item.status }}
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
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const summary = ref(null)

const filters = reactive({
  status: '',
  priority: '',
  request_type: '',
  requested_from: '',
  requested_to: ''
})

const loading = reactive({
  summary: false,
  export: false
})

const errors = reactive({
  summary: null
})

const workflowStatuses = ['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled']
const workflowPriorities = ['low', 'medium', 'high', 'urgent']

const workflowHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Title', key: 'title' },
  { title: 'Status', key: 'status' },
  { title: 'Priority', key: 'priority' },
  { title: 'Requested', key: 'requested_at' },
  { title: 'Due', key: 'due_at' }
]

const summaryCards = computed(() => {
  if (!summary.value) return []
  return [
    { label: 'Announcements', value: summary.value.announcements?.total ?? 0 },
    { label: 'Workflow Requests', value: summary.value.workflow_requests?.total ?? 0 },
    { label: 'Workflow Actions', value: summary.value.workflow_actions?.total ?? 0 },
    { label: 'SLA Breaches', value: summary.value.workflow_requests?.sla_breaches ?? 0 }
  ]
})

const announcementStatusChart = computed(() => {
  const data = summary.value?.announcements?.status_breakdown || []
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

const announcementPriorityChart = computed(() => {
  const data = summary.value?.announcements?.priority_breakdown || []
  return {
    series: [
      {
        name: 'Announcements',
        data: data.map(item => item.count)
      }
    ],
    options: {
      chart: { type: 'bar', toolbar: { show: false } },
      theme: { mode: 'dark' },
      plotOptions: { bar: { horizontal: false, borderRadius: 6 } },
      xaxis: { categories: data.map(item => item.priority) },
      dataLabels: { enabled: false }
    }
  }
})

const workflowStatusChart = computed(() => {
  const data = summary.value?.workflow_requests?.status_breakdown || []
  return {
    series: [
      {
        name: 'Requests',
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

const workflowActionChart = computed(() => {
  const data = summary.value?.workflow_actions?.by_type || []
  return {
    series: data.map(item => item.count),
    options: {
      chart: { type: 'donut' },
      theme: { mode: 'dark' },
      labels: data.map(item => item.action),
      legend: { position: 'bottom' },
      dataLabels: { enabled: true }
    }
  }
})

function authHeaders() {
  const token = localStorage.getItem('token')
  return { Authorization: token ? `Bearer ${token}` : undefined }
}

async function fetchSummary() {
  loading.summary = true
  errors.summary = null
  try {
    const { data } = await axios.get(`${API_BASE}/communication/reports/summary`, {
      headers: authHeaders(),
      params: cleanFilters(filters)
    })
    summary.value = {
      ...data,
      workflow_requests: {
        ...(data.workflow_requests || {}),
        details: (data.workflow_requests?.details) || []
      }
    }
  } catch (error) {
    errors.summary = parseError(error)
  } finally {
    loading.summary = false
  }
}

async function downloadReport() {
  loading.export = true
  try {
    const response = await axios.get(`${API_BASE}/communication/reports/export`, {
      headers: authHeaders(),
      params: cleanFilters(filters),
      responseType: 'blob'
    })
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `communication-report-${new Date().toISOString().slice(0, 10)}.csv`)
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


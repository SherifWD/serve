<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Visitors</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.visitors" @click="fetchVisitors">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.visitors" type="error" variant="tonal" class="mb-4">
                {{ errors.visitors }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.visitors" @submit.prevent="createVisitor">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.visitor.full_name" label="Full Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.visitor.company" label="Company" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.visitor.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.visitor.phone" label="Phone" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.visitor.status"
                      :items="visitorStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.visitor.notes"
                      label="Notes"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.visitors">
                      <v-icon start>mdi-account-plus</v-icon>
                      Register Visitor
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="visitorHeaders"
                :items="visitors"
                :loading="loading.visitors"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" :color="statusColor(item.status)" variant="flat">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Visitor Entries</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.entries" @click="fetchEntries">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.entries" type="error" variant="tonal" class="mb-4">
                {{ errors.entries }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.entries" @submit.prevent="createEntry">
                <v-row dense>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.entry.visitor_id"
                      :items="visitors"
                      item-title="full_name"
                      item-value="id"
                      label="Visitor"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.entry.host_name" label="Host" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.entry.host_department" label="Department" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.entry.purpose" label="Purpose" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.entry.scheduled_start"
                      type="datetime-local"
                      label="Scheduled Start"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.entry.scheduled_end"
                      type="datetime-local"
                      label="Scheduled End"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.entry.status"
                      :items="entryStatuses"
                      label="Visit Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.entries">
                      <v-icon start>mdi-gate</v-icon>
                      Schedule Entry
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="entryHeaders"
                :items="entries"
                :loading="loading.entries"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.visitor="{ item }">
                  {{ visitorLookup[item.visitor_id] ?? '—' }}
                </template>
                <template #item.scheduled_start="{ item }">
                  {{ formatDate(item.scheduled_start) }}
                </template>
                <template #item.check_in_at="{ item }">
                  {{ formatDate(item.check_in_at) }}
                </template>
                <template #item.status="{ item }">
                  <v-chip size="small" variant="flat" :color="entryColor(item.status)">
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
import axios from 'axios'
import { computed, onMounted, reactive, ref } from 'vue'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const visitors = ref([])
const entries = ref([])

const loading = reactive({
  visitors: false,
  entries: false
})

const errors = reactive({
  visitors: null,
  entries: null
})

const forms = reactive({
  visitor: {
    full_name: '',
    company: '',
    email: '',
    phone: '',
    status: 'invited',
    notes: ''
  },
  entry: {
    visitor_id: null,
    host_name: '',
    host_department: '',
    purpose: '',
    scheduled_start: '',
    scheduled_end: '',
    status: 'scheduled'
  }
})

const visitorHeaders = [
  { title: 'Name', key: 'full_name' },
  { title: 'Company', key: 'company' },
  { title: 'Email', key: 'email' },
  { title: 'Status', key: 'status' }
]

const entryHeaders = [
  { title: 'Visitor', key: 'visitor' },
  { title: 'Host', key: 'host_name' },
  { title: 'Purpose', key: 'purpose' },
  { title: 'Scheduled', key: 'scheduled_start' },
  { title: 'Check-in', key: 'check_in_at' },
  { title: 'Status', key: 'status' }
]

const visitorStatuses = ['invited', 'checked_in', 'checked_out', 'blocked']
const entryStatuses = ['scheduled', 'onsite', 'completed', 'cancelled', 'denied']

const visitorLookup = computed(() => {
  const map = {}
  for (const visitor of visitors.value ?? []) {
    map[visitor.id] = visitor.full_name
  }
  return map
})

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

function statusColor(status) {
  const colors = {
    invited: 'primary',
    checked_in: 'success',
    checked_out: 'secondary',
    blocked: 'error'
  }
  return colors[status] || 'primary'
}

function entryColor(status) {
  const colors = {
    scheduled: 'info',
    onsite: 'success',
    completed: 'secondary',
    cancelled: 'grey',
    denied: 'error'
  }
  return colors[status] || 'primary'
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

async function fetchVisitors() {
  loading.visitors = true
  errors.visitors = null
  try {
    const { data } = await axios.get(`${API_BASE}/visitor/visitors`, { headers: authHeaders() })
    visitors.value = data.data || data
  } catch (error) {
    errors.visitors = parseError(error)
  } finally {
    loading.visitors = false
  }
}

async function createVisitor() {
  loading.visitors = true
  errors.visitors = null
  try {
    await axios.post(`${API_BASE}/visitor/visitors`, forms.visitor, { headers: authHeaders() })
    Object.assign(forms.visitor, {
      full_name: '',
      company: '',
      email: '',
      phone: '',
      status: 'invited',
      notes: ''
    })
    await fetchVisitors()
  } catch (error) {
    errors.visitors = parseError(error)
  } finally {
    loading.visitors = false
  }
}

async function fetchEntries() {
  loading.entries = true
  errors.entries = null
  try {
    const { data } = await axios.get(`${API_BASE}/visitor/entries`, { headers: authHeaders() })
    entries.value = data.data || data
  } catch (error) {
    errors.entries = parseError(error)
  } finally {
    loading.entries = false
  }
}

async function createEntry() {
  if (!forms.entry.visitor_id) {
    errors.entries = 'Select a visitor before scheduling an entry.'
    return
  }

  loading.entries = true
  errors.entries = null
  try {
    await axios.post(`${API_BASE}/visitor/entries`, forms.entry, { headers: authHeaders() })
    Object.assign(forms.entry, {
      visitor_id: null,
      host_name: '',
      host_department: '',
      purpose: '',
      scheduled_start: '',
      scheduled_end: '',
      status: 'scheduled'
    })
    await fetchEntries()
  } catch (error) {
    errors.entries = parseError(error)
  } finally {
    loading.entries = false
  }
}

onMounted(async () => {
  await Promise.all([fetchVisitors(), fetchEntries()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


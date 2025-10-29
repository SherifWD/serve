<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Safety Incidents</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.incidents" @click="fetchIncidents">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.incidents" type="error" variant="tonal" class="mb-4">
                {{ errors.incidents }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.incidents" @submit.prevent="createIncident">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.incident.reference" label="Incident Ref" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.incident.incident_date" type="date" label="Incident Date" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.incident.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.incident.severity"
                      :items="incidentSeverities"
                      label="Severity"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.incident.status"
                      :items="incidentStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.incident.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.incidents">
                      <v-icon start>mdi-alert</v-icon>
                      Log Incident
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="incidentHeaders"
                :items="incidents"
                :loading="loading.incidents"
                class="elevation-0"
              >
                <template #item.actions_count="{ item }">
                  {{ item.actions?.length ?? 0 }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">HSE Audits</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.audits" @click="fetchAudits">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.audits" type="error" variant="tonal" class="mb-4">
                {{ errors.audits }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.audits" @submit.prevent="createAudit">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.audit.code" label="Audit Code" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.audit.name" label="Audit Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.audit.scheduled_date" type="date" label="Scheduled Date" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.audit.status"
                      :items="auditStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.audits">
                      <v-icon start>mdi-clipboard-text-search</v-icon>
                      Schedule Audit
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="auditHeaders"
                :items="audits"
                :loading="loading.audits"
                class="elevation-0"
              >
                <template #item.actions_count="{ item }">
                  {{ item.actions?.length ?? 0 }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Corrective / Preventive Actions</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.actions" @click="fetchActions">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.actions" type="error" variant="tonal" class="mb-4">
                {{ errors.actions }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.actions" @submit.prevent="createAction">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.incident_id"
                      :items="incidents"
                      item-title="reference"
                      item-value="id"
                      label="Incident"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.audit_id"
                      :items="audits"
                      item-title="code"
                      item-value="id"
                      label="Audit"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.action_type"
                      :items="actionTypes"
                      label="Action Type"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.status"
                      :items="actionStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.action.due_date" type="date" label="Due Date" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.action.description"
                      label="Description"
                      rows="2"
                      density="compact"
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.actions">
                      <v-icon start>mdi-check-decagram</v-icon>
                      Assign Action
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="actionHeaders"
                :items="actions"
                :loading="loading.actions"
                class="elevation-0"
              >
                <template #item.source="{ item }">
                  <span v-if="item.incident_id">Incident {{ incidents.find(i => i.id === item.incident_id)?.reference }}</span>
                  <span v-else-if="item.audit_id">Audit {{ audits.find(a => a.id === item.audit_id)?.code }}</span>
                  <span v-else>—</span>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Training Records</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.training" @click="fetchTrainingRecords">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.training" type="error" variant="tonal" class="mb-4">
                {{ errors.training }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.training" @submit.prevent="createTrainingRecord">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.training.title" label="Training Title" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.training.session_date" type="date" label="Session Date" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.training.trainer" label="Trainer" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.training">
                      <v-icon start>mdi-school</v-icon>
                      Record Training
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="trainingHeaders"
                :items="trainingRecords"
                :loading="loading.training"
                class="elevation-0"
              >
                <template #item.attendees="{ item }">
                  {{ (item.attendees ?? []).length }} attendees
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
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const incidents = ref([])
const audits = ref([])
const actions = ref([])
const trainingRecords = ref([])

const loading = reactive({
  incidents: false,
  audits: false,
  actions: false,
  training: false
})

const errors = reactive({
  incidents: null,
  audits: null,
  actions: null,
  training: null
})

const forms = reactive({
  incident: {
    reference: '',
    title: '',
    incident_date: '',
    severity: 'medium',
    status: 'open',
    description: ''
  },
  audit: {
    code: '',
    name: '',
    scheduled_date: '',
    status: 'scheduled'
  },
  action: {
    incident_id: null,
    audit_id: null,
    action_type: 'corrective',
    status: 'open',
    due_date: '',
    description: ''
  },
  training: {
    title: '',
    session_date: '',
    trainer: ''
  }
})

const incidentSeverities = ['low', 'medium', 'high', 'critical']
const incidentStatuses = ['open', 'investigating', 'resolved', 'closed']
const auditStatuses = ['scheduled', 'in_progress', 'completed', 'cancelled']
const actionTypes = ['corrective', 'preventive']
const actionStatuses = ['open', 'in_progress', 'verified', 'closed']

const incidentHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Title', key: 'title' },
  { title: 'Date', key: 'incident_date' },
  { title: 'Severity', key: 'severity' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions_count' }
]

const auditHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Scheduled', key: 'scheduled_date' },
  { title: 'Status', key: 'status' },
  { title: 'Actions', key: 'actions_count' }
]

const actionHeaders = [
  { title: 'Type', key: 'action_type' },
  { title: 'Source', key: 'source' },
  { title: 'Status', key: 'status' },
  { title: 'Due', key: 'due_date' }
]

const trainingHeaders = [
  { title: 'Title', key: 'title' },
  { title: 'Date', key: 'session_date' },
  { title: 'Trainer', key: 'trainer' },
  { title: 'Attendees', key: 'attendees' }
]

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

async function fetchIncidents() {
  loading.incidents = true
  errors.incidents = null
  try {
    const { data } = await axios.get(`${API_BASE}/hse/incidents`, { headers: authHeaders() })
    incidents.value = data.data || data
  } catch (error) {
    errors.incidents = parseError(error)
  } finally {
    loading.incidents = false
  }
}

async function fetchAudits() {
  loading.audits = true
  errors.audits = null
  try {
    const { data } = await axios.get(`${API_BASE}/hse/audits`, { headers: authHeaders() })
    audits.value = data.data || data
  } catch (error) {
    errors.audits = parseError(error)
  } finally {
    loading.audits = false
  }
}

async function fetchActions() {
  loading.actions = true
  errors.actions = null
  try {
    const { data } = await axios.get(`${API_BASE}/hse/actions`, { headers: authHeaders() })
    actions.value = data.data || data
  } catch (error) {
    errors.actions = parseError(error)
  } finally {
    loading.actions = false
  }
}

async function fetchTrainingRecords() {
  loading.training = true
  errors.training = null
  try {
    const { data } = await axios.get(`${API_BASE}/hse/training-records`, { headers: authHeaders() })
    trainingRecords.value = data.data || data
  } catch (error) {
    errors.training = parseError(error)
  } finally {
    loading.training = false
  }
}

async function createIncident() {
  loading.incidents = true
  errors.incidents = null
  try {
    await axios.post(`${API_BASE}/hse/incidents`, forms.incident, { headers: authHeaders() })
    Object.assign(forms.incident, {
      reference: '',
      title: '',
      incident_date: '',
      severity: 'medium',
      status: 'open',
      description: ''
    })
    await fetchIncidents()
  } catch (error) {
    errors.incidents = parseError(error)
  } finally {
    loading.incidents = false
  }
}

async function createAudit() {
  loading.audits = true
  errors.audits = null
  try {
    await axios.post(`${API_BASE}/hse/audits`, forms.audit, { headers: authHeaders() })
    Object.assign(forms.audit, {
      code: '',
      name: '',
      scheduled_date: '',
      status: 'scheduled'
    })
    await fetchAudits()
  } catch (error) {
    errors.audits = parseError(error)
  } finally {
    loading.audits = false
  }
}

async function createAction() {
  loading.actions = true
  errors.actions = null
  if (!forms.action.incident_id && !forms.action.audit_id) {
    loading.actions = false
    errors.actions = 'Select an incident or audit before assigning an action.'
    return
  }
  try {
    await axios.post(`${API_BASE}/hse/actions`, forms.action, { headers: authHeaders() })
    Object.assign(forms.action, {
      incident_id: null,
      audit_id: null,
      action_type: 'corrective',
      status: 'open',
      due_date: '',
      description: ''
    })
    await fetchActions()
  } catch (error) {
    errors.actions = parseError(error)
  } finally {
    loading.actions = false
  }
}

async function createTrainingRecord() {
  loading.training = true
  errors.training = null
  try {
    await axios.post(`${API_BASE}/hse/training-records`, forms.training, { headers: authHeaders() })
    Object.assign(forms.training, {
      title: '',
      session_date: '',
      trainer: ''
    })
    await fetchTrainingRecords()
  } catch (error) {
    errors.training = parseError(error)
  } finally {
    loading.training = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchIncidents(),
    fetchAudits(),
    fetchActions(),
    fetchTrainingRecords()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

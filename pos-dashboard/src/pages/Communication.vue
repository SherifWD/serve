<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="5">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Announcements</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.announcements" @click="fetchAnnouncements">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.announcements" type="error" variant="tonal" class="mb-3">
                {{ errors.announcements }}
              </v-alert>
              <v-form :disabled="loading.announcements" @submit.prevent="createAnnouncement">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.announcement.title"
                      label="Title"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.announcement.body"
                      label="Message"
                      rows="3"
                      density="compact"
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.announcement.priority"
                      :items="announcementPriorities"
                      label="Priority"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.announcement.status"
                      :items="announcementStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.announcement.publish_at" type="datetime-local" label="Publish At" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.announcement.expires_at" type="datetime-local" label="Expires At" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.announcement.audiencesText"
                      label="Audiences (comma separated)"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.announcement.attachmentsText"
                      label="Attachments (name:path)"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.announcements">
                      <v-icon start>mdi-bullhorn-outline</v-icon>
                      Publish Announcement
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="announcementHeaders"
                :items="announcements"
                :loading="loading.announcements"
                class="elevation-0"
                density="compact"
              >
                <template #item.status="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Engagement Snapshot</span>
            </v-card-title>
            <v-card-text>
              <v-row dense>
                <v-col cols="12" md="6" v-for="metric in announcementMetrics" :key="metric.label">
                  <v-sheet class="pa-4 rounded-lg" color="#232a2e" elevation="1">
                    <div class="text-caption text-grey">{{ metric.label }}</div>
                    <div class="text-h5 font-weight-bold text-white">{{ metric.value }}</div>
                  </v-sheet>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="7">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Workflow Requests</span>
              <v-spacer />
              <v-select
                v-model="filters.requestStatus"
                :items="workflowStatuses"
                label="Filter Status"
                density="compact"
                clearable
                style="max-width: 220px;"
              />
              <v-btn icon color="primary" class="ms-2" :loading="loading.workflowRequests" @click="fetchWorkflowRequests">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.workflowRequests" type="error" variant="tonal" class="mb-3">
                {{ errors.workflowRequests }}
              </v-alert>
              <v-form :disabled="loading.workflowRequests" @submit.prevent="createWorkflowRequest">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.workflow.reference"
                      label="Reference"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.workflow.request_type"
                      label="Request Type"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.workflow.priority"
                      :items="workflowPriorities"
                      label="Priority"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.workflow.title"
                      label="Title"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.workflow.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.workflow.status"
                      :items="workflowStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.workflow.requested_at" type="date" label="Requested" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.workflow.due_at" type="date" label="Due" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.workflow.payloadText"
                      label="Payload (JSON)"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.workflowRequests">
                      <v-icon start>mdi-clipboard-text-clock</v-icon>
                      Register Request
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="workflowHeaders"
                :items="filteredWorkflowRequests"
                :loading="loading.workflowRequests"
                class="elevation-0"
                density="compact"
              >
                <template #item.status="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #item.priority="{ item }">
                  <v-chip size="small" color="secondary" variant="tonal">
                    {{ item.priority }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Workflow Actions</span>
              <v-spacer />
              <v-select
                v-model="filters.actionRequest"
                :items="workflowRequests"
                item-title="reference"
                item-value="id"
                label="Filter by Request"
                density="compact"
                clearable
                style="max-width: 220px;"
              />
              <v-btn icon color="primary" class="ms-2" :loading="loading.workflowActions" @click="fetchWorkflowActions">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.workflowActions" type="error" variant="tonal" class="mb-3">
                {{ errors.workflowActions }}
              </v-alert>
              <v-form :disabled="loading.workflowActions" @submit.prevent="createWorkflowAction">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.workflow_request_id"
                      :items="workflowRequests"
                      item-title="reference"
                      item-value="id"
                      label="Workflow Request"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.action"
                      :items="workflowActionTypes"
                      label="Action"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.action.status"
                      :items="workflowActionStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.action.acted_at" type="datetime-local" label="Acted At" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.action.comments"
                      label="Comments"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.workflowActions">
                      <v-icon start>mdi-check-decagram</v-icon>
                      Log Action
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="workflowActionHeaders"
                :items="filteredWorkflowActions"
                :loading="loading.workflowActions"
                class="elevation-0"
                density="compact"
              >
                <template #item.workflow_request="{ item }">
                  {{ lookupRequestReference(item.workflow_request_id) }}
                </template>
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

const announcements = ref([])
const workflowRequests = ref([])
const workflowActions = ref([])

const loading = reactive({
  announcements: false,
  workflowRequests: false,
  workflowActions: false
})

const errors = reactive({
  announcements: null,
  workflowRequests: null,
  workflowActions: null
})

const filters = reactive({
  requestStatus: null,
  actionRequest: null
})

const forms = reactive({
  announcement: {
    title: '',
    body: '',
    priority: 'normal',
    status: 'draft',
    publish_at: '',
    expires_at: '',
    audiencesText: '',
    attachmentsText: ''
  },
  workflow: {
    reference: generateReference(),
    request_type: '',
    title: '',
    description: '',
    status: 'pending',
    priority: 'medium',
    requested_at: '',
    due_at: '',
    payloadText: ''
  },
  action: {
    workflow_request_id: null,
    action: 'approve',
    status: 'recorded',
    acted_at: '',
    comments: ''
  }
})

const announcementStatuses = ['draft', 'scheduled', 'published', 'expired']
const announcementPriorities = ['low', 'normal', 'high', 'critical']
const workflowStatuses = ['pending', 'in_progress', 'approved', 'rejected', 'completed', 'cancelled']
const workflowPriorities = ['low', 'medium', 'high', 'urgent']
const workflowActionTypes = ['approve', 'reject', 'comment', 'reassign', 'escalate', 'assign']
const workflowActionStatuses = ['recorded', 'pending', 'completed']

const announcementHeaders = [
  { title: 'Title', key: 'title' },
  { title: 'Priority', key: 'priority' },
  { title: 'Status', key: 'status' },
  { title: 'Publish At', key: 'publish_at' }
]

const workflowHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Type', key: 'request_type' },
  { title: 'Priority', key: 'priority' },
  { title: 'Status', key: 'status' },
  { title: 'Due', key: 'due_at' }
]

const workflowActionHeaders = [
  { title: 'Request', key: 'workflow_request' },
  { title: 'Action', key: 'action' },
  { title: 'Status', key: 'status' },
  { title: 'Acted At', key: 'acted_at' }
]

const requiredRule = value => (!!value && value !== '') || 'Required'

const filteredWorkflowRequests = computed(() => {
  if (!filters.requestStatus) return workflowRequests.value
  return workflowRequests.value.filter(request => request.status === filters.requestStatus)
})

const filteredWorkflowActions = computed(() => {
  if (!filters.actionRequest) return workflowActions.value
  return workflowActions.value.filter(action => action.workflow_request_id === filters.actionRequest)
})

const announcementMetrics = computed(() => {
  const total = announcements.value.length
  const published = announcements.value.filter(item => item.status === 'published').length
  const scheduled = announcements.value.filter(item => item.status === 'scheduled').length
  return [
    { label: 'Total Announcements', value: total },
    { label: 'Published', value: published },
    { label: 'Scheduled', value: scheduled },
    { label: 'Active Requests', value: workflowRequests.value.filter(item => item.status === 'in_progress').length }
  ]
})

function generateReference() {
  return `WRK-${Date.now()}`
}

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

function parseListInput(text) {
  if (!text) return null
  return text.split(',').map(item => item.trim()).filter(Boolean)
}

function parseAttachmentsInput(text) {
  if (!text) return null
  return text.split(',').map(entry => {
    const [name, path] = entry.split(':').map(value => value?.trim())
    return name ? { name, path: path || '' } : null
  }).filter(Boolean)
}

function parsePayload(text) {
  if (!text) return null
  try {
    const parsed = JSON.parse(text)
    return typeof parsed === 'object' ? parsed : null
  } catch (error) {
    return null
  }
}

function statusColor(status) {
  switch (status) {
    case 'published':
    case 'approved':
    case 'completed':
      return 'success'
    case 'scheduled':
    case 'in_progress':
      return 'primary'
    case 'rejected':
    case 'expired':
    case 'cancelled':
      return 'error'
    default:
      return 'grey'
  }
}

function lookupRequestReference(requestId) {
  return workflowRequests.value.find(request => request.id === requestId)?.reference || '—'
}

async function fetchAnnouncements() {
  loading.announcements = true
  errors.announcements = null
  try {
    const { data } = await axios.get(`${API_BASE}/communication/announcements`, {
      headers: authHeaders(),
      params: { per_page: 100 }
    })
    announcements.value = data.data || data
  } catch (error) {
    errors.announcements = parseError(error)
  } finally {
    loading.announcements = false
  }
}

async function fetchWorkflowRequests() {
  loading.workflowRequests = true
  errors.workflowRequests = null
  try {
    const params = { per_page: 100 }
    if (filters.requestStatus) params.status = filters.requestStatus
    const { data } = await axios.get(`${API_BASE}/communication/workflows`, {
      headers: authHeaders(),
      params
    })
    workflowRequests.value = data.data || data
  } catch (error) {
    errors.workflowRequests = parseError(error)
  } finally {
    loading.workflowRequests = false
  }
}

async function fetchWorkflowActions() {
  loading.workflowActions = true
  errors.workflowActions = null
  try {
    const params = { per_page: 100 }
    if (filters.actionRequest) params.workflow_request_id = filters.actionRequest
    const { data } = await axios.get(`${API_BASE}/communication/workflow-actions`, {
      headers: authHeaders(),
      params
    })
    workflowActions.value = data.data || data
  } catch (error) {
    errors.workflowActions = parseError(error)
  } finally {
    loading.workflowActions = false
  }
}

async function createAnnouncement() {
  loading.announcements = true
  errors.announcements = null
  try {
    await axios.post(`${API_BASE}/communication/announcements`, {
      title: forms.announcement.title,
      body: forms.announcement.body,
      priority: forms.announcement.priority,
      status: forms.announcement.status,
      publish_at: forms.announcement.publish_at || null,
      expires_at: forms.announcement.expires_at || null,
      audiences: parseListInput(forms.announcement.audiencesText),
      attachments: parseAttachmentsInput(forms.announcement.attachmentsText)
    }, { headers: authHeaders() })
    Object.assign(forms.announcement, {
      title: '',
      body: '',
      priority: 'normal',
      status: 'draft',
      publish_at: '',
      expires_at: '',
      audiencesText: '',
      attachmentsText: ''
    })
    await fetchAnnouncements()
  } catch (error) {
    errors.announcements = parseError(error)
  } finally {
    loading.announcements = false
  }
}

async function createWorkflowRequest() {
  loading.workflowRequests = true
  errors.workflowRequests = null
  try {
    await axios.post(`${API_BASE}/communication/workflows`, {
      reference: forms.workflow.reference,
      request_type: forms.workflow.request_type,
      title: forms.workflow.title,
      description: forms.workflow.description || null,
      status: forms.workflow.status,
      priority: forms.workflow.priority,
      requested_at: forms.workflow.requested_at || null,
      due_at: forms.workflow.due_at || null,
      payload: parsePayload(forms.workflow.payloadText)
    }, { headers: authHeaders() })
    Object.assign(forms.workflow, {
      reference: generateReference(),
      request_type: '',
      title: '',
      description: '',
      status: 'pending',
      priority: 'medium',
      requested_at: '',
      due_at: '',
      payloadText: ''
    })
    await fetchWorkflowRequests()
  } catch (error) {
    errors.workflowRequests = parseError(error)
  } finally {
    loading.workflowRequests = false
  }
}

async function createWorkflowAction() {
  loading.workflowActions = true
  errors.workflowActions = null
  try {
    await axios.post(`${API_BASE}/communication/workflow-actions`, {
      workflow_request_id: forms.action.workflow_request_id,
      action: forms.action.action,
      status: forms.action.status,
      acted_at: forms.action.acted_at || null,
      comments: forms.action.comments || null
    }, { headers: authHeaders() })
    Object.assign(forms.action, {
      workflow_request_id: null,
      action: 'approve',
      status: 'recorded',
      acted_at: '',
      comments: ''
    })
    await fetchWorkflowActions()
  } catch (error) {
    errors.workflowActions = parseError(error)
  } finally {
    loading.workflowActions = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchAnnouncements(),
    fetchWorkflowRequests(),
    fetchWorkflowActions()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


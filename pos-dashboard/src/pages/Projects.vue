<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="5">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Projects</span>
              <v-spacer />
              <v-text-field
                v-model="filters.projectSearch"
                density="compact"
                append-inner-icon="mdi-magnify"
                variant="solo"
                flat
                hide-details
                placeholder="Search code or name"
                class="me-2"
                style="max-width: 220px;"
              />
              <v-btn icon color="primary" :loading="loading.projects" @click="fetchProjects">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.projects" type="error" variant="tonal" class="mb-3">
                {{ errors.projects }}
              </v-alert>
              <v-form :disabled="loading.projects" @submit.prevent="createProject">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.project.code"
                      label="Code"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.project.name"
                      label="Name"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.project.status"
                      :items="projectStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.project.stage" label="Stage" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.project.start_date" type="date" label="Start" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.project.due_date" type="date" label="Due" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.project.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model.number="forms.project.budget_amount"
                      type="number"
                      min="0"
                      label="Budget Amount"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.projects">
                      <v-icon start>mdi-clipboard-flow</v-icon>
                      Create Project
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="projectHeaders"
                :items="filteredProjects"
                :loading="loading.projects"
                class="elevation-0"
                density="compact"
                item-key="id"
                @click:row="selectProject"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" color="primary" variant="tonal">{{ item.status }}</v-chip>
                </template>
                <template #item.budget_amount="{ item }">
                  {{ formatCurrency(item.budget_amount) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Change Requests</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.changeRequests" @click="fetchChangeRequests">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.changeRequests" type="error" variant="tonal" class="mb-3">
                {{ errors.changeRequests }}
              </v-alert>
              <v-form :disabled="loading.changeRequests" @submit.prevent="createChangeRequest">
                <v-row dense>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.changeRequest.project_id"
                      :items="projects"
                      item-title="name"
                      item-value="id"
                      label="Project"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.changeRequest.reference"
                      label="Reference"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.changeRequest.change_type"
                      :items="changeTypes"
                      label="Change Type"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.changeRequest.title"
                      label="Title"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.changeRequest.impact_summary"
                      label="Impact Summary"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.changeRequest.status"
                      :items="changeStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.changeRequest.risk_level"
                      :items="riskLevels"
                      label="Risk Level"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.changeRequest.requested_at" type="date" label="Requested" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.changeRequest.target_date" type="date" label="Target" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.changeRequests">
                      <v-icon start>mdi-file-compare</v-icon>
                      Log Change Request
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="changeRequestHeaders"
                :items="changeRequests"
                :loading="loading.changeRequests"
                class="elevation-0"
                density="compact"
              >
                <template #item.project="{ item }">
                  {{ lookupProjectName(item.project_id) }}
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

        <v-col cols="12" md="7">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Project Tasks</span>
              <v-spacer />
              <v-select
                v-model="filters.taskProject"
                :items="projects"
                item-title="name"
                item-value="id"
                label="Filter by Project"
                density="compact"
                clearable
                style="max-width: 280px;"
              />
              <v-btn icon color="primary" :loading="loading.tasks" class="ms-2" @click="fetchTasks">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.tasks" type="error" variant="tonal" class="mb-3">
                {{ errors.tasks }}
              </v-alert>
              <v-form :disabled="loading.tasks" @submit.prevent="createTask">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.task.project_id"
                      :items="projects"
                      item-title="name"
                      item-value="id"
                      label="Project"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.task.depends_on_task_id"
                      :items="dependencyOptions"
                      item-title="title"
                      item-value="id"
                      label="Depends On"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field
                      v-model="forms.task.title"
                      label="Task Title"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.task.status"
                      :items="taskStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.task.priority"
                      :items="taskPriorities"
                      label="Priority"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.task.progress"
                      type="number"
                      min="0"
                      max="100"
                      label="Progress %"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.task.start_date" type="date" label="Start" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.task.due_date" type="date" label="Due" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.task.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.tasks">
                      <v-icon start>mdi-format-list-checks</v-icon>
                      Add Task
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="taskHeaders"
                :items="filteredTasks"
                :loading="loading.tasks"
                class="elevation-0"
                density="compact"
              >
                <template #item.project="{ item }">
                  {{ lookupProjectName(item.project_id) }}
                </template>
                <template #item.status="{ item }">
                  <v-chip size="small" color="primary" variant="tonal">
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
              <span class="text-h6 font-weight-bold">Change Approvals</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.changeApprovals" @click="fetchChangeApprovals">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.changeApprovals" type="error" variant="tonal" class="mb-3">
                {{ errors.changeApprovals }}
              </v-alert>
              <v-form :disabled="loading.changeApprovals" @submit.prevent="createChangeApproval">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.approval.change_request_id"
                      :items="changeRequests"
                      item-title="reference"
                      item-value="id"
                      label="Change Request"
                      dense
                      required
                      :rules="[requiredRule]"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.approval.role" label="Approver Role" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.approval.status"
                      :items="approvalStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.approval.acted_at" type="datetime-local" label="Acted At" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.approval.comments"
                      label="Comments"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.changeApprovals">
                      <v-icon start>mdi-account-check</v-icon>
                      Record Approval
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-divider class="my-4" />
              <v-data-table
                :headers="approvalHeaders"
                :items="changeApprovals"
                :loading="loading.changeApprovals"
                class="elevation-0"
                density="compact"
              >
                <template #item.change_request="{ item }">
                  {{ lookupChangeReference(item.change_request_id) }}
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const projects = ref([])
const tasks = ref([])
const changeRequests = ref([])
const changeApprovals = ref([])
const selectedProjectId = ref(null)

const loading = reactive({
  projects: false,
  tasks: false,
  changeRequests: false,
  changeApprovals: false
})

const errors = reactive({
  projects: null,
  tasks: null,
  changeRequests: null,
  changeApprovals: null
})

const filters = reactive({
  projectSearch: '',
  taskProject: null
})

const forms = reactive({
  project: {
    code: '',
    name: '',
    status: 'draft',
    stage: '',
    description: '',
    start_date: '',
    due_date: '',
    budget_amount: null
  },
  task: {
    project_id: null,
    title: '',
    description: '',
    status: 'not_started',
    priority: 'medium',
    start_date: '',
    due_date: '',
    progress: 0,
    depends_on_task_id: null
  },
  changeRequest: {
    project_id: null,
    reference: '',
    title: '',
    change_type: 'scope',
    status: 'draft',
    requested_at: '',
    target_date: '',
    risk_level: 'medium',
    impact_summary: ''
  },
  approval: {
    change_request_id: null,
    role: '',
    status: 'pending',
    acted_at: '',
    comments: ''
  }
})

const projectStatuses = ['draft', 'active', 'on_hold', 'completed', 'archived']
const taskStatuses = ['not_started', 'in_progress', 'completed', 'blocked']
const taskPriorities = ['low', 'medium', 'high']
const changeTypes = ['scope', 'design', 'schedule', 'cost', 'quality']
const changeStatuses = ['draft', 'submitted', 'in_review', 'approved', 'rejected', 'implemented']
const riskLevels = ['low', 'medium', 'high']
const approvalStatuses = ['pending', 'approved', 'rejected']

const projectHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Status', key: 'status' },
  { title: 'Stage', key: 'stage' },
  { title: 'Budget', key: 'budget_amount' }
]

const taskHeaders = [
  { title: 'Project', key: 'project' },
  { title: 'Title', key: 'title' },
  { title: 'Status', key: 'status' },
  { title: 'Priority', key: 'priority' },
  { title: 'Progress %', key: 'progress' }
]

const changeRequestHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Project', key: 'project' },
  { title: 'Type', key: 'change_type' },
  { title: 'Status', key: 'status' },
  { title: 'Risk', key: 'risk_level' }
]

const approvalHeaders = [
  { title: 'Change Request', key: 'change_request' },
  { title: 'Role', key: 'role' },
  { title: 'Status', key: 'status' },
  { title: 'Acted At', key: 'acted_at' }
]

const requiredRule = value => (!!value && value !== '') || 'Required'

const filteredProjects = computed(() => {
  const term = filters.projectSearch?.toLowerCase().trim()
  if (!term) return projects.value
  return projects.value.filter(project =>
    project.name?.toLowerCase().includes(term) ||
    project.code?.toLowerCase().includes(term)
  )
})

const filteredTasks = computed(() => {
  if (!filters.taskProject) return tasks.value
  return tasks.value.filter(task => task.project_id === filters.taskProject)
})

const dependencyOptions = computed(() => {
  if (!forms.task.project_id) return []
  return tasks.value.filter(task => task.project_id === forms.task.project_id)
})

function lookupProjectName(projectId) {
  return projects.value.find(project => project.id === projectId)?.name || '—'
}

function lookupChangeReference(changeRequestId) {
  return changeRequests.value.find(change => change.id === changeRequestId)?.reference || '—'
}

function formatCurrency(value) {
  const amount = Number(value ?? 0)
  return amount
    ? new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(amount)
    : '—'
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

async function fetchProjects() {
  loading.projects = true
  errors.projects = null
  try {
    const params = {
      per_page: 100,
      search: filters.projectSearch || undefined
    }
    const { data } = await axios.get(`${API_BASE}/projects`, { headers: authHeaders(), params })
    projects.value = data.data || data
  } catch (error) {
    errors.projects = parseError(error)
  } finally {
    loading.projects = false
  }
}

async function fetchTasks() {
  loading.tasks = true
  errors.tasks = null
  try {
    const params = { per_page: 100 }
    if (filters.taskProject) params.project_id = filters.taskProject
    const { data } = await axios.get(`${API_BASE}/project-tasks`, { headers: authHeaders(), params })
    tasks.value = data.data || data
  } catch (error) {
    errors.tasks = parseError(error)
  } finally {
    loading.tasks = false
  }
}

async function fetchChangeRequests() {
  loading.changeRequests = true
  errors.changeRequests = null
  try {
    const { data } = await axios.get(`${API_BASE}/project-change-requests`, {
      headers: authHeaders(),
      params: { per_page: 100 }
    })
    changeRequests.value = data.data || data
  } catch (error) {
    errors.changeRequests = parseError(error)
  } finally {
    loading.changeRequests = false
  }
}

async function fetchChangeApprovals() {
  loading.changeApprovals = true
  errors.changeApprovals = null
  try {
    const { data } = await axios.get(`${API_BASE}/project-change-approvals`, {
      headers: authHeaders(),
      params: { per_page: 100 }
    })
    changeApprovals.value = data.data || data
  } catch (error) {
    errors.changeApprovals = parseError(error)
  } finally {
    loading.changeApprovals = false
  }
}

async function createProject() {
  loading.projects = true
  errors.projects = null
  try {
    await axios.post(`${API_BASE}/projects`, forms.project, { headers: authHeaders() })
    Object.assign(forms.project, {
      code: '',
      name: '',
      status: 'draft',
      stage: '',
      description: '',
      start_date: '',
      due_date: '',
      budget_amount: null
    })
    await fetchProjects()
  } catch (error) {
    errors.projects = parseError(error)
  } finally {
    loading.projects = false
  }
}

async function createTask() {
  loading.tasks = true
  errors.tasks = null
  try {
    const payload = {
      project_id: forms.task.project_id,
      title: forms.task.title,
      description: forms.task.description || null,
      status: forms.task.status,
      priority: forms.task.priority,
      start_date: forms.task.start_date || null,
      due_date: forms.task.due_date || null,
      progress: forms.task.progress ?? null,
      depends_on_task_id: forms.task.depends_on_task_id
    }
    await axios.post(`${API_BASE}/project-tasks`, payload, { headers: authHeaders() })
    Object.assign(forms.task, {
      project_id: null,
      title: '',
      description: '',
      status: 'not_started',
      priority: 'medium',
      start_date: '',
      due_date: '',
      progress: 0,
      depends_on_task_id: null
    })
    await fetchTasks()
  } catch (error) {
    errors.tasks = parseError(error)
  } finally {
    loading.tasks = false
  }
}

async function createChangeRequest() {
  loading.changeRequests = true
  errors.changeRequests = null
  try {
    await axios.post(`${API_BASE}/project-change-requests`, {
      ...forms.changeRequest,
      impact_summary: forms.changeRequest.impact_summary || null
    }, { headers: authHeaders() })
    Object.assign(forms.changeRequest, {
      project_id: null,
      reference: '',
      title: '',
      change_type: 'scope',
      status: 'draft',
      requested_at: '',
      target_date: '',
      risk_level: 'medium',
      impact_summary: ''
    })
    await fetchChangeRequests()
  } catch (error) {
    errors.changeRequests = parseError(error)
  } finally {
    loading.changeRequests = false
  }
}

async function createChangeApproval() {
  loading.changeApprovals = true
  errors.changeApprovals = null
  try {
    await axios.post(`${API_BASE}/project-change-approvals`, {
      ...forms.approval,
      comments: forms.approval.comments || null
    }, { headers: authHeaders() })
    Object.assign(forms.approval, {
      change_request_id: null,
      role: '',
      status: 'pending',
      acted_at: '',
      comments: ''
    })
    await fetchChangeApprovals()
  } catch (error) {
    errors.changeApprovals = parseError(error)
  } finally {
    loading.changeApprovals = false
  }
}

function selectProject(event, { item }) {
  selectedProjectId.value = item.id
  forms.task.project_id = item.id
  filters.taskProject = item.id
}

watch(() => forms.task.project_id, () => {
  if (!dependencyOptions.value.some(option => option.id === forms.task.depends_on_task_id)) {
    forms.task.depends_on_task_id = null
  }
})

onMounted(async () => {
  await Promise.all([
    fetchProjects(),
    fetchTasks(),
    fetchChangeRequests(),
    fetchChangeApprovals()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


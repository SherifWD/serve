<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Workers</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchWorkers" :loading="loading.workers">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.workers" type="error" variant="tonal" class="mb-4">
                {{ errors.workers }}
              </v-alert>
              <v-form @submit.prevent="createWorker" :disabled="loading.workers" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.worker.employee_number" label="Employee #" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.worker.first_name" label="First Name" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.worker.last_name" label="Last Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.workers">
                      <v-icon start>mdi-plus</v-icon>
                      Add Worker
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="workerHeaders"
                :items="workers"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Attendance</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchAttendance" :loading="loading.attendance">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.attendance" type="error" variant="tonal" class="mb-4">
                {{ errors.attendance }}
              </v-alert>
              <v-form @submit.prevent="createAttendance" :disabled="loading.attendance" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.attendance.worker_id"
                      :items="workers"
                      item-title="full_name"
                      item-value="id"
                      label="Worker"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.attendance.attendance_date" type="date" label="Date" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.attendance.status"
                      :items="attendanceStatuses"
                      label="Status"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.attendance">
                      <v-icon start>mdi-plus</v-icon>
                      Record Attendance
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="attendanceHeaders"
                :items="attendance"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.worker="{ item }">
                  {{ item.worker?.full_name || '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Payroll Runs</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchPayrollRuns" :loading="loading.payroll">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.payroll" type="error" variant="tonal" class="mb-4">
                {{ errors.payroll }}
              </v-alert>
              <v-form @submit.prevent="createPayrollRun" :disabled="loading.payroll" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.payroll.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.payroll.period" label="Period (YYYY-MM)" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.payroll.pay_date" type="date" label="Pay Date" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.payroll">
                      <v-icon start>mdi-plus</v-icon>
                      Create Payroll Run
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="payrollHeaders"
                :items="payrollRuns"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Leave Requests</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchLeaves" :loading="loading.leaves">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.leaves" type="error" variant="tonal" class="mb-4">
                {{ errors.leaves }}
              </v-alert>
              <v-form @submit.prevent="createLeave" :disabled="loading.leaves" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.leave.worker_id"
                      :items="workers"
                      item-title="full_name"
                      item-value="id"
                      label="Worker"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.leave.start_date" type="date" label="Start" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.leave.end_date" type="date" label="End" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.leaves">
                      <v-icon start>mdi-plus</v-icon>
                      Submit Leave
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="leaveHeaders"
                :items="leaveRequests"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.worker="{ item }">
                  {{ item.worker?.full_name || '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Training Sessions</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchTrainingSessions" :loading="loading.training">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.training" type="error" variant="tonal" class="mb-4">
                {{ errors.training }}
              </v-alert>
              <v-form @submit.prevent="createTrainingSession" :disabled="loading.training" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="5">
                    <v-text-field v-model="forms.trainingSession.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.trainingSession.scheduled_date" type="date" label="Date" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.training">
                      <v-icon start>mdi-plus</v-icon>
                      Schedule Training
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="trainingHeaders"
                :items="trainingSessions"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.assignments="{ item }">
                  {{ item.assignments?.length || 0 }}
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

const workers = ref([])
const attendance = ref([])
const payrollRuns = ref([])
const leaveRequests = ref([])
const trainingSessions = ref([])

const loading = reactive({
  workers: false,
  attendance: false,
  payroll: false,
  leaves: false,
  training: false
})

const errors = reactive({
  workers: null,
  attendance: null,
  payroll: null,
  leaves: null,
  training: null
})

const forms = reactive({
  worker: {
    employee_number: '',
    first_name: '',
    last_name: ''
  },
  attendance: {
    worker_id: null,
    attendance_date: new Date().toISOString().slice(0, 10),
    status: 'present'
  },
  payroll: {
    reference: '',
    period: new Date().toISOString().slice(0, 7),
    pay_date: new Date().toISOString().slice(0, 10)
  },
  leave: {
    worker_id: null,
    start_date: '',
    end_date: ''
  },
  trainingSession: {
    title: '',
    scheduled_date: ''
  }
})

const attendanceStatuses = [
  { title: 'Present', value: 'present' },
  { title: 'Absent', value: 'absent' },
  { title: 'Remote', value: 'remote' },
  { title: 'Leave', value: 'leave' }
]

const workerHeaders = [
  { title: 'Employee #', key: 'employee_number' },
  { title: 'Name', key: 'full_name' },
  { title: 'Status', key: 'employment_status' }
]

const attendanceHeaders = [
  { title: 'Date', key: 'attendance_date' },
  { title: 'Worker', key: 'worker' },
  { title: 'Status', key: 'status' }
]

const payrollHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Period', key: 'period' },
  { title: 'Gross', key: 'gross_total' },
  { title: 'Net', key: 'net_total' },
  { title: 'Status', key: 'status' }
]

const leaveHeaders = [
  { title: 'Worker', key: 'worker' },
  { title: 'Type', key: 'leave_type' },
  { title: 'Start', key: 'start_date' },
  { title: 'End', key: 'end_date' },
  { title: 'Status', key: 'status' }
]

const trainingHeaders = [
  { title: 'Title', key: 'title' },
  { title: 'Date', key: 'scheduled_date' },
  { title: 'Status', key: 'status' },
  { title: 'Assignments', key: 'assignments' }
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

async function fetchWorkers() {
  loading.workers = true
  errors.workers = null
  try {
    const { data } = await axios.get(`${API_BASE}/hrms/workers`, { headers: authHeaders() })
    workers.value = (data.data || data).map(worker => ({
      ...worker,
      full_name: `${worker.first_name} ${worker.last_name}`
    }))
  } catch (error) {
    errors.workers = parseError(error)
  } finally {
    loading.workers = false
  }
}

async function createWorker() {
  loading.workers = true
  errors.workers = null
  try {
    await axios.post(`${API_BASE}/hrms/workers`, forms.worker, { headers: authHeaders() })
    Object.assign(forms.worker, { employee_number: '', first_name: '', last_name: '' })
    await fetchWorkers()
  } catch (error) {
    errors.workers = parseError(error)
  } finally {
    loading.workers = false
  }
}

async function fetchAttendance() {
  loading.attendance = true
  errors.attendance = null
  try {
    const { data } = await axios.get(`${API_BASE}/hrms/attendance-records`, { headers: authHeaders() })
    attendance.value = data.data || data
  } catch (error) {
    errors.attendance = parseError(error)
  } finally {
    loading.attendance = false
  }
}

async function createAttendance() {
  loading.attendance = true
  errors.attendance = null
  try {
    await axios.post(`${API_BASE}/hrms/attendance-records`, forms.attendance, { headers: authHeaders() })
    await fetchAttendance()
  } catch (error) {
    errors.attendance = parseError(error)
  } finally {
    loading.attendance = false
  }
}

async function fetchPayrollRuns() {
  loading.payroll = true
  errors.payroll = null
  try {
    const { data } = await axios.get(`${API_BASE}/hrms/payroll-runs`, { headers: authHeaders() })
    payrollRuns.value = data.data || data
  } catch (error) {
    errors.payroll = parseError(error)
  } finally {
    loading.payroll = false
  }
}

async function createPayrollRun() {
  loading.payroll = true
  errors.payroll = null
  try {
    await axios.post(`${API_BASE}/hrms/payroll-runs`, { ...forms.payroll, entries: [] }, { headers: authHeaders() })
    Object.assign(forms.payroll, { reference: '', period: new Date().toISOString().slice(0, 7), pay_date: new Date().toISOString().slice(0, 10) })
    await fetchPayrollRuns()
  } catch (error) {
    errors.payroll = parseError(error)
  } finally {
    loading.payroll = false
  }
}

async function fetchLeaves() {
  loading.leaves = true
  errors.leaves = null
  try {
    const { data } = await axios.get(`${API_BASE}/hrms/leave-requests`, { headers: authHeaders() })
    leaveRequests.value = data.data || data
  } catch (error) {
    errors.leaves = parseError(error)
  } finally {
    loading.leaves = false
  }
}

async function createLeave() {
  loading.leaves = true
  errors.leaves = null
  try {
    await axios.post(`${API_BASE}/hrms/leave-requests`, {
      ...forms.leave,
      leave_type: 'vacation'
    }, { headers: authHeaders() })
    Object.assign(forms.leave, { worker_id: null, start_date: '', end_date: '' })
    await fetchLeaves()
  } catch (error) {
    errors.leaves = parseError(error)
  } finally {
    loading.leaves = false
  }
}

async function fetchTrainingSessions() {
  loading.training = true
  errors.training = null
  try {
    const { data } = await axios.get(`${API_BASE}/hrms/training-sessions`, { headers: authHeaders() })
    trainingSessions.value = data.data || data
  } catch (error) {
    errors.training = parseError(error)
  } finally {
    loading.training = false
  }
}

async function createTrainingSession() {
  loading.training = true
  errors.training = null
  try {
    await axios.post(`${API_BASE}/hrms/training-sessions`, forms.trainingSession, { headers: authHeaders() })
    Object.assign(forms.trainingSession, { title: '', scheduled_date: '' })
    await fetchTrainingSessions()
  } catch (error) {
    errors.training = parseError(error)
  } finally {
    loading.training = false
  }
}

onMounted(async () => {
  await fetchWorkers()
  forms.attendance.worker_id = workers.value[0]?.id ?? null
  forms.leave.worker_id = workers.value[0]?.id ?? null
  await Promise.all([
    fetchAttendance(),
    fetchPayrollRuns(),
    fetchLeaves(),
    fetchTrainingSessions()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

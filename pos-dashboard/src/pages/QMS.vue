<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="5">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Inspection Plans</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchPlans" :loading="loading.plans">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.plans" type="error" variant="tonal" class="mb-4">
                {{ errors.plans }}
              </v-alert>
              <v-form @submit.prevent="createPlan" :disabled="loading.plans" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.plan.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.plan.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.plans">
                      <v-icon start>mdi-plus</v-icon>
                      Add Plan
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="planHeaders"
                :items="plans"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="7">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Inspections</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchInspections" :loading="loading.inspections">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.inspections" type="error" variant="tonal" class="mb-4">
                {{ errors.inspections }}
              </v-alert>
              <v-form @submit.prevent="createInspection" :disabled="loading.inspections" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.inspection.inspection_plan_id"
                      :items="plans"
                      item-title="name"
                      item-value="id"
                      label="Plan"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.inspection.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.inspections">
                      <v-icon start>mdi-plus</v-icon>
                      Log Inspection
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="inspectionHeaders"
                :items="inspections"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.plan="{ item }">
                  {{ item.plan?.name || '—' }}
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
              <span class="text-h6 font-weight-bold">Non-Conformities</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchNonConformities" :loading="loading.nonConformities">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.nonConformities" type="error" variant="tonal" class="mb-4">
                {{ errors.nonConformities }}
              </v-alert>
              <v-form @submit.prevent="createNonConformity" :disabled="loading.nonConformities" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.nonConformity.inspection_id"
                      :items="inspections"
                      item-title="reference"
                      item-value="id"
                      label="Inspection"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.nonConformity.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.nonConformities">
                      <v-icon start>mdi-plus</v-icon>
                      Record Issue
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="nonConformityHeaders"
                :items="nonConformities"
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
              <span class="text-h6 font-weight-bold">CAPA Actions</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchCapaActions" :loading="loading.capaActions">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.capaActions" type="error" variant="tonal" class="mb-4">
                {{ errors.capaActions }}
              </v-alert>
              <v-form @submit.prevent="createCapaAction" :disabled="loading.capaActions" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.capaAction.non_conformity_id"
                      :items="nonConformities"
                      item-title="code"
                      item-value="id"
                      label="Non-Conformity"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.capaAction.description" label="Action" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.capaActions">
                      <v-icon start>mdi-plus</v-icon>
                      Add CAPA
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="capaHeaders"
                :items="capaActions"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Audits</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchAudits" :loading="loading.audits">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.audits" type="error" variant="tonal" class="mb-4">
                {{ errors.audits }}
              </v-alert>
              <v-form @submit.prevent="createAudit" :disabled="loading.audits" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.audit.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.audit.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field v-model="forms.audit.scheduled_date" type="date" label="Date" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.audits">
                      <v-icon start>mdi-plus</v-icon>
                      Schedule Audit
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="auditHeaders"
                :items="audits"
                :items-per-page="6"
                density="comfortable"
                class="rounded-lg"
              />
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

const plans = ref([])
const inspections = ref([])
const nonConformities = ref([])
const capaActions = ref([])
const audits = ref([])

const loading = reactive({
  plans: false,
  inspections: false,
  nonConformities: false,
  capaActions: false,
  audits: false
})

const errors = reactive({
  plans: null,
  inspections: null,
  nonConformities: null,
  capaActions: null,
  audits: null
})

const forms = reactive({
  plan: {
    code: '',
    name: ''
  },
  inspection: {
    inspection_plan_id: null,
    reference: ''
  },
  nonConformity: {
    inspection_id: null,
    code: ''
  },
  capaAction: {
    non_conformity_id: null,
    description: ''
  },
  audit: {
    code: '',
    name: '',
    scheduled_date: ''
  }
})

const planHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'inspection_type' }
]

const inspectionHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Plan', key: 'plan' },
  { title: 'Status', key: 'status' }
]

const nonConformityHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Severity', key: 'severity' },
  { title: 'Status', key: 'status' }
]

const capaHeaders = [
  { title: 'Description', key: 'description' },
  { title: 'Type', key: 'action_type' },
  { title: 'Status', key: 'status' }
]

const auditHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'audit_type' },
  { title: 'Status', key: 'status' },
  { title: 'Scheduled', key: 'scheduled_date' }
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

async function fetchPlans() {
  loading.plans = true
  errors.plans = null
  try {
    const { data } = await axios.get(`${API_BASE}/qms/inspection-plans`, { headers: authHeaders() })
    plans.value = data.data || data
  } catch (error) {
    errors.plans = parseError(error)
  } finally {
    loading.plans = false
  }
}

async function createPlan() {
  loading.plans = true
  errors.plans = null
  try {
    await axios.post(`${API_BASE}/qms/inspection-plans`, forms.plan, { headers: authHeaders() })
    Object.assign(forms.plan, { code: '', name: '' })
    await fetchPlans()
  } catch (error) {
    errors.plans = parseError(error)
  } finally {
    loading.plans = false
  }
}

async function fetchInspections() {
  loading.inspections = true
  errors.inspections = null
  try {
    const { data } = await axios.get(`${API_BASE}/qms/inspections`, { headers: authHeaders() })
    inspections.value = data.data || data
  } catch (error) {
    errors.inspections = parseError(error)
  } finally {
    loading.inspections = false
  }
}

async function createInspection() {
  loading.inspections = true
  errors.inspections = null
  try {
    await axios.post(`${API_BASE}/qms/inspections`, forms.inspection, { headers: authHeaders() })
    Object.assign(forms.inspection, { inspection_plan_id: null, reference: '' })
    await fetchInspections()
  } catch (error) {
    errors.inspections = parseError(error)
  } finally {
    loading.inspections = false
  }
}

async function fetchNonConformities() {
  loading.nonConformities = true
  errors.nonConformities = null
  try {
    const { data } = await axios.get(`${API_BASE}/qms/non-conformities`, { headers: authHeaders() })
    nonConformities.value = data.data || data
  } catch (error) {
    errors.nonConformities = parseError(error)
  } finally {
    loading.nonConformities = false
  }
}

async function createNonConformity() {
  loading.nonConformities = true
  errors.nonConformities = null
  try {
    await axios.post(`${API_BASE}/qms/non-conformities`, forms.nonConformity, { headers: authHeaders() })
    Object.assign(forms.nonConformity, { inspection_id: null, code: '' })
    await fetchNonConformities()
  } catch (error) {
    errors.nonConformities = parseError(error)
  } finally {
    loading.nonConformities = false
  }
}

async function fetchCapaActions() {
  loading.capaActions = true
  errors.capaActions = null
  try {
    const { data } = await axios.get(`${API_BASE}/qms/capa-actions`, { headers: authHeaders() })
    capaActions.value = data.data || data
  } catch (error) {
    errors.capaActions = parseError(error)
  } finally {
    loading.capaActions = false
  }
}

async function createCapaAction() {
  loading.capaActions = true
  errors.capaActions = null
  try {
    await axios.post(`${API_BASE}/qms/capa-actions`, forms.capaAction, { headers: authHeaders() })
    Object.assign(forms.capaAction, { non_conformity_id: null, description: '' })
    await fetchCapaActions()
  } catch (error) {
    errors.capaActions = parseError(error)
  } finally {
    loading.capaActions = false
  }
}

async function fetchAudits() {
  loading.audits = true
  errors.audits = null
  try {
    const { data } = await axios.get(`${API_BASE}/qms/audits`, { headers: authHeaders() })
    audits.value = data.data || data
  } catch (error) {
    errors.audits = parseError(error)
  } finally {
    loading.audits = false
  }
}

async function createAudit() {
  loading.audits = true
  errors.audits = null
  try {
    await axios.post(`${API_BASE}/qms/audits`, forms.audit, { headers: authHeaders() })
    Object.assign(forms.audit, { code: '', name: '', scheduled_date: '' })
    await fetchAudits()
  } catch (error) {
    errors.audits = parseError(error)
  } finally {
    loading.audits = false
  }
}

onMounted(async () => {
  await fetchPlans()
  await Promise.all([
    fetchInspections(),
    fetchNonConformities(),
    fetchCapaActions(),
    fetchAudits()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

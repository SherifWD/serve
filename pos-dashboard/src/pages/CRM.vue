<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Customer Accounts</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.accounts" @click="fetchAccounts">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.accounts" type="error" variant="tonal" class="mb-4">
                {{ errors.accounts }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.accounts" @submit.prevent="createAccount">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.account.name" label="Account Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.account.industry" label="Industry" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.account.status"
                      :items="accountStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.account.city" label="City" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.accounts">
                      <v-icon start>mdi-account-plus</v-icon>
                      Add Account
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="accountHeaders"
                :items="accounts"
                :loading="loading.accounts"
                class="elevation-0"
              >
                <template #item.address="{ item }">
                  {{ item.address?.city ?? '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Contacts</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.contacts" @click="fetchContacts">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.contacts" type="error" variant="tonal" class="mb-4">
                {{ errors.contacts }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.contacts" @submit.prevent="createContact">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.contact.first_name" label="First Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.contact.last_name" label="Last Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.contact.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.contact.phone" label="Phone" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.contact.account_id"
                      :items="accounts"
                      item-title="name"
                      item-value="id"
                      label="Account"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.contacts">
                      <v-icon start>mdi-account-tie</v-icon>
                      Add Contact
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="contactHeaders"
                :items="contacts"
                :loading="loading.contacts"
                class="elevation-0"
              >
                <template #item.name="{ item }">
                  {{ [item.first_name, item.last_name].filter(Boolean).join(' ') || '—' }}
                </template>
                <template #item.account="{ item }">
                  {{ item.account?.name ?? '—' }}
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
              <span class="text-h6 font-weight-bold">Sales Opportunities</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.opportunities" @click="fetchOpportunities">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.opportunities" type="error" variant="tonal" class="mb-4">
                {{ errors.opportunities }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.opportunities" @submit.prevent="createOpportunity">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.opportunity.name" label="Opportunity Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.opportunity.account_id"
                      :items="accounts"
                      item-title="name"
                      item-value="id"
                      label="Account"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.opportunity.stage"
                      :items="opportunityStages"
                      label="Stage"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="forms.opportunity.amount"
                      type="number"
                      min="0"
                      step="0.01"
                      label="Amount"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.opportunity.close_date"
                      type="date"
                      label="Expected Close"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.opportunities">
                      <v-icon start>mdi-target</v-icon>
                      Create Opportunity
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="opportunityHeaders"
                :items="opportunities"
                :loading="loading.opportunities"
                class="elevation-0"
              >
                <template #item.account="{ item }">
                  {{ item.account?.name ?? '—' }}
                </template>
                <template #item.amount="{ item }">
                  {{ formatCurrency(item.amount) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Service Cases</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.serviceCases" @click="fetchServiceCases">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.serviceCases" type="error" variant="tonal" class="mb-4">
                {{ errors.serviceCases }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.serviceCases" @submit.prevent="createServiceCase">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.serviceCase.case_number" label="Case #" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.serviceCase.account_id"
                      :items="accounts"
                      item-title="name"
                      item-value="id"
                      label="Account"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.serviceCase.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.serviceCase.status"
                      :items="serviceStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.serviceCase.priority"
                      :items="servicePriorities"
                      label="Priority"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.serviceCase.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.serviceCases">
                      <v-icon start>mdi-lifebuoy</v-icon>
                      Log Service Case
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="serviceCaseHeaders"
                :items="serviceCases"
                :loading="loading.serviceCases"
                class="elevation-0"
              >
                <template #item.account="{ item }">
                  {{ item.account?.name ?? '—' }}
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
              <span class="text-h6 font-weight-bold">Marketing Leads</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.leads" @click="fetchLeads">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.leads" type="error" variant="tonal" class="mb-4">
                {{ errors.leads }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.leads" @submit.prevent="createLead">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.lead.company_name" label="Company Name" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.lead.contact_name" label="Contact Name" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.lead.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.lead.phone" label="Phone" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.lead.status"
                      :items="leadStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.lead.source" label="Source" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.leads">
                      <v-icon start>mdi-bullhorn</v-icon>
                      Capture Lead
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="leadHeaders"
                :items="leads"
                :loading="loading.leads"
                class="elevation-0"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" color="primary" variant="flat">
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
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const accounts = ref([])
const contacts = ref([])
const opportunities = ref([])
const serviceCases = ref([])
const leads = ref([])

const loading = reactive({
  accounts: false,
  contacts: false,
  opportunities: false,
  serviceCases: false,
  leads: false
})

const errors = reactive({
  accounts: null,
  contacts: null,
  opportunities: null,
  serviceCases: null,
  leads: null
})

const forms = reactive({
  account: {
    name: '',
    industry: '',
    status: 'active',
    city: ''
  },
  contact: {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    account_id: null
  },
  opportunity: {
    name: '',
    account_id: null,
    stage: 'prospecting',
    amount: 0,
    close_date: ''
  },
  serviceCase: {
    case_number: '',
    account_id: null,
    title: '',
    status: 'open',
    priority: 'medium',
    description: ''
  },
  lead: {
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    status: 'new',
    source: ''
  }
})

const accountStatuses = ['active', 'inactive']
const opportunityStages = ['prospecting', 'proposal', 'negotiation', 'closed_won', 'closed_lost']
const serviceStatuses = ['open', 'working', 'resolved', 'closed']
const servicePriorities = ['low', 'medium', 'high']
const leadStatuses = ['new', 'qualified', 'converted', 'lost']

const accountHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Industry', key: 'industry' },
  { title: 'Status', key: 'status' },
  { title: 'City', key: 'address' }
]

const contactHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Phone', key: 'phone' },
  { title: 'Account', key: 'account' }
]

const opportunityHeaders = [
  { title: 'Name', key: 'name' },
  { title: 'Account', key: 'account' },
  { title: 'Stage', key: 'stage' },
  { title: 'Amount', key: 'amount' },
  { title: 'Close Date', key: 'close_date' }
]

const serviceCaseHeaders = [
  { title: 'Case #', key: 'case_number' },
  { title: 'Title', key: 'title' },
  { title: 'Account', key: 'account' },
  { title: 'Status', key: 'status' },
  { title: 'Priority', key: 'priority' }
]

const leadHeaders = [
  { title: 'Company', key: 'company_name' },
  { title: 'Contact', key: 'contact_name' },
  { title: 'Email', key: 'email' },
  { title: 'Phone', key: 'phone' },
  { title: 'Status', key: 'status' },
  { title: 'Source', key: 'source' }
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

function formatCurrency(value) {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(Number(value ?? 0))
}

async function fetchAccounts() {
  loading.accounts = true
  errors.accounts = null
  try {
    const { data } = await axios.get(`${API_BASE}/crm/accounts`, { headers: authHeaders() })
    accounts.value = data.data || data
  } catch (error) {
    errors.accounts = parseError(error)
  } finally {
    loading.accounts = false
  }
}

async function fetchContacts() {
  loading.contacts = true
  errors.contacts = null
  try {
    const { data } = await axios.get(`${API_BASE}/crm/contacts`, { headers: authHeaders() })
    contacts.value = data.data || data
  } catch (error) {
    errors.contacts = parseError(error)
  } finally {
    loading.contacts = false
  }
}

async function fetchOpportunities() {
  loading.opportunities = true
  errors.opportunities = null
  try {
    const { data } = await axios.get(`${API_BASE}/crm/opportunities`, { headers: authHeaders() })
    opportunities.value = data.data || data
  } catch (error) {
    errors.opportunities = parseError(error)
  } finally {
    loading.opportunities = false
  }
}

async function fetchServiceCases() {
  loading.serviceCases = true
  errors.serviceCases = null
  try {
    const { data } = await axios.get(`${API_BASE}/crm/service-cases`, { headers: authHeaders() })
    serviceCases.value = data.data || data
  } catch (error) {
    errors.serviceCases = parseError(error)
  } finally {
    loading.serviceCases = false
  }
}

async function fetchLeads() {
  loading.leads = true
  errors.leads = null
  try {
    const { data } = await axios.get(`${API_BASE}/crm/leads`, { headers: authHeaders() })
    leads.value = data.data || data
  } catch (error) {
    errors.leads = parseError(error)
  } finally {
    loading.leads = false
  }
}

async function createAccount() {
  loading.accounts = true
  errors.accounts = null
  try {
    const payload = {
      name: forms.account.name,
      industry: forms.account.industry || null,
      status: forms.account.status,
      address: forms.account.city ? { city: forms.account.city } : null
    }
    await axios.post(`${API_BASE}/crm/accounts`, payload, { headers: authHeaders() })
    Object.assign(forms.account, { name: '', industry: '', status: 'active', city: '' })
    await fetchAccounts()
  } catch (error) {
    errors.accounts = parseError(error)
  } finally {
    loading.accounts = false
  }
}

async function createContact() {
  loading.contacts = true
  errors.contacts = null
  try {
    await axios.post(`${API_BASE}/crm/contacts`, forms.contact, { headers: authHeaders() })
    Object.assign(forms.contact, {
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      account_id: null
    })
    await fetchContacts()
  } catch (error) {
    errors.contacts = parseError(error)
  } finally {
    loading.contacts = false
  }
}

async function createOpportunity() {
  loading.opportunities = true
  errors.opportunities = null
  try {
    await axios.post(`${API_BASE}/crm/opportunities`, forms.opportunity, { headers: authHeaders() })
    Object.assign(forms.opportunity, {
      name: '',
      account_id: null,
      stage: 'prospecting',
      amount: 0,
      close_date: ''
    })
    await fetchOpportunities()
  } catch (error) {
    errors.opportunities = parseError(error)
  } finally {
    loading.opportunities = false
  }
}

async function createServiceCase() {
  loading.serviceCases = true
  errors.serviceCases = null
  try {
    await axios.post(`${API_BASE}/crm/service-cases`, forms.serviceCase, { headers: authHeaders() })
    Object.assign(forms.serviceCase, {
      case_number: '',
      account_id: null,
      title: '',
      status: 'open',
      priority: 'medium',
      description: ''
    })
    await fetchServiceCases()
  } catch (error) {
    errors.serviceCases = parseError(error)
  } finally {
    loading.serviceCases = false
  }
}

async function createLead() {
  loading.leads = true
  errors.leads = null
  try {
    await axios.post(`${API_BASE}/crm/leads`, forms.lead, { headers: authHeaders() })
    Object.assign(forms.lead, {
      company_name: '',
      contact_name: '',
      email: '',
      phone: '',
      status: 'new',
      source: ''
    })
    await fetchLeads()
  } catch (error) {
    errors.leads = parseError(error)
  } finally {
    loading.leads = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchAccounts(),
    fetchContacts(),
    fetchOpportunities(),
    fetchServiceCases(),
    fetchLeads()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

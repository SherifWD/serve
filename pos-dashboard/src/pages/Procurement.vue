<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Vendors</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.vendors" @click="fetchVendors">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.vendors" type="error" variant="tonal" class="mb-4">
                {{ errors.vendors }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.vendors" @submit.prevent="createVendor">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.vendor.code" label="Vendor Code" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.vendor.name" label="Vendor Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.vendor.category" label="Category" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.vendor.status"
                      :items="vendorStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.vendor.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.vendor.phone" label="Phone" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.vendors">
                      <v-icon start>mdi-domain-plus</v-icon>
                      Add Vendor
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="vendorHeaders"
                :items="vendors"
                :loading="loading.vendors"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" variant="flat" :color="vendorColor(item.status)">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="8">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Tenders & Responses</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.tenders" @click="fetchTenders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.tenders" type="error" variant="tonal" class="mb-4">
                {{ errors.tenders }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.tenders" @submit.prevent="createTender">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.tender.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.tender.title" label="Title" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.tender.status"
                      :items="tenderStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.tender.opening_date"
                      type="date"
                      label="Opening Date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.tender.closing_date"
                      type="date"
                      label="Closing Date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.tender.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.tenders">
                      <v-icon start>mdi-briefcase-plus</v-icon>
                      Publish Tender
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-divider class="my-4" />

              <v-card-subtitle class="px-0 pb-4">Submit Response</v-card-subtitle>
              <v-form class="mb-4" :disabled="loading.tenders" @submit.prevent="createTenderResponse">
                <v-row dense>
                  <v-col cols="12" md="5">
                    <v-select
                      v-model="forms.response.tender_id"
                      :items="tenders"
                      item-title="title"
                      item-value="id"
                      label="Tender"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.response.vendor_id"
                      :items="vendors"
                      item-title="name"
                      item-value="id"
                      label="Vendor"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.response.amount"
                      label="Quote Amount"
                      type="number"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.response.response_date"
                      type="date"
                      label="Response Date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.response.status"
                      :items="responseStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.tenders">
                      <v-icon start>mdi-file-sign</v-icon>
                      Submit Response
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="tenderHeaders"
                :items="tenders"
                :loading="loading.tenders"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" variant="flat" :color="tenderColor(item.status)">
                    {{ item.status }}
                  </v-chip>
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
              <span class="text-h6 font-weight-bold">Purchase Requests</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.purchaseRequests" @click="fetchPurchaseRequests">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.purchaseRequests" type="error" variant="tonal" class="mb-4">
                {{ errors.purchaseRequests }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.purchaseRequests" @submit.prevent="createPurchaseRequest">
                <v-row dense>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.pr.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.pr.requester_name" label="Requester" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.pr.department" label="Department" dense />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.pr.status"
                      :items="prStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.pr.needed_by"
                      type="date"
                      label="Needed By"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.pr.total_amount"
                      label="Amount"
                      type="number"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-textarea
                      v-model="forms.pr.justification"
                      label="Justification"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.purchaseRequests">
                      <v-icon start>mdi-file-check</v-icon>
                      Submit Request
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="prHeaders"
                :items="purchaseRequests"
                :loading="loading.purchaseRequests"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" variant="flat" :color="prColor(item.status)">
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
import { onMounted, reactive, ref } from 'vue'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const vendors = ref([])
const tenders = ref([])
const purchaseRequests = ref([])

const loading = reactive({
  vendors: false,
  tenders: false,
  purchaseRequests: false
})

const errors = reactive({
  vendors: null,
  tenders: null,
  purchaseRequests: null
})

const forms = reactive({
  vendor: {
    code: '',
    name: '',
    category: '',
    status: 'active',
    email: '',
    phone: ''
  },
  tender: {
    reference: '',
    title: '',
    status: 'draft',
    opening_date: '',
    closing_date: '',
    description: ''
  },
  response: {
    tender_id: null,
    vendor_id: null,
    amount: '',
    response_date: '',
    status: 'submitted'
  },
  pr: {
    reference: '',
    requester_name: '',
    department: '',
    status: 'draft',
    needed_by: '',
    total_amount: '',
    justification: ''
  }
})

const vendorHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Category', key: 'category' },
  { title: 'Status', key: 'status' }
]

const tenderHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Title', key: 'title' },
  { title: 'Status', key: 'status' },
  { title: 'Closes', key: 'closing_date' }
]

const prHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Requester', key: 'requester_name' },
  { title: 'Department', key: 'department' },
  { title: 'Status', key: 'status' },
  { title: 'Needed By', key: 'needed_by' },
  { title: 'Amount', key: 'total_amount' }
]

const vendorStatuses = ['active', 'suspended', 'blacklisted']
const tenderStatuses = ['draft', 'open', 'closed', 'awarded', 'cancelled']
const responseStatuses = ['submitted', 'shortlisted', 'awarded', 'rejected']
const prStatuses = ['draft', 'pending_approval', 'approved', 'rejected', 'fulfilled']

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

function vendorColor(status) {
  const colors = {
    active: 'success',
    suspended: 'warning',
    blacklisted: 'error'
  }
  return colors[status] || 'primary'
}

function tenderColor(status) {
  const colors = {
    draft: 'grey',
    open: 'primary',
    closed: 'secondary',
    awarded: 'success',
    cancelled: 'error'
  }
  return colors[status] || 'primary'
}

function prColor(status) {
  const colors = {
    draft: 'grey',
    pending_approval: 'warning',
    approved: 'success',
    rejected: 'error',
    fulfilled: 'secondary'
  }
  return colors[status] || 'primary'
}

async function fetchVendors() {
  loading.vendors = true
  errors.vendors = null
  try {
    const { data } = await axios.get(`${API_BASE}/procurement/vendors`, { headers: authHeaders() })
    vendors.value = data.data || data
  } catch (error) {
    errors.vendors = parseError(error)
  } finally {
    loading.vendors = false
  }
}

async function createVendor() {
  loading.vendors = true
  errors.vendors = null
  try {
    await axios.post(`${API_BASE}/procurement/vendors`, forms.vendor, { headers: authHeaders() })
    Object.assign(forms.vendor, {
      code: '',
      name: '',
      category: '',
      status: 'active',
      email: '',
      phone: ''
    })
    await fetchVendors()
  } catch (error) {
    errors.vendors = parseError(error)
  } finally {
    loading.vendors = false
  }
}

async function fetchTenders() {
  loading.tenders = true
  errors.tenders = null
  try {
    const { data } = await axios.get(`${API_BASE}/procurement/tenders`, { headers: authHeaders() })
    tenders.value = data.data || data
  } catch (error) {
    errors.tenders = parseError(error)
  } finally {
    loading.tenders = false
  }
}

async function createTender() {
  loading.tenders = true
  errors.tenders = null
  try {
    await axios.post(`${API_BASE}/procurement/tenders`, forms.tender, { headers: authHeaders() })
    Object.assign(forms.tender, {
      reference: '',
      title: '',
      status: 'draft',
      opening_date: '',
      closing_date: '',
      description: ''
    })
    await fetchTenders()
  } catch (error) {
    errors.tenders = parseError(error)
  } finally {
    loading.tenders = false
  }
}

async function createTenderResponse() {
  if (!forms.response.tender_id || !forms.response.vendor_id) {
    errors.tenders = 'Select both tender and vendor before submitting a response.'
    return
  }

  loading.tenders = true
  errors.tenders = null
  try {
    const payload = {
      ...forms.response,
      amount: forms.response.amount !== '' ? Number(forms.response.amount) : undefined
    }
    await axios.post(`${API_BASE}/procurement/tender-responses`, payload, { headers: authHeaders() })
    Object.assign(forms.response, {
      tender_id: null,
      vendor_id: null,
      amount: '',
      response_date: '',
      status: 'submitted'
    })
    await fetchTenders()
  } catch (error) {
    errors.tenders = parseError(error)
  } finally {
    loading.tenders = false
  }
}

async function fetchPurchaseRequests() {
  loading.purchaseRequests = true
  errors.purchaseRequests = null
  try {
    const { data } = await axios.get(`${API_BASE}/procurement/purchase-requests`, { headers: authHeaders() })
    purchaseRequests.value = data.data || data
  } catch (error) {
    errors.purchaseRequests = parseError(error)
  } finally {
    loading.purchaseRequests = false
  }
}

async function createPurchaseRequest() {
  loading.purchaseRequests = true
  errors.purchaseRequests = null
  try {
    const payload = {
      ...forms.pr,
      total_amount: forms.pr.total_amount !== '' ? Number(forms.pr.total_amount) : undefined
    }
    await axios.post(`${API_BASE}/procurement/purchase-requests`, payload, { headers: authHeaders() })
    Object.assign(forms.pr, {
      reference: '',
      requester_name: '',
      department: '',
      status: 'draft',
      needed_by: '',
      total_amount: '',
      justification: ''
    })
    await fetchPurchaseRequests()
  } catch (error) {
    errors.purchaseRequests = parseError(error)
  } finally {
    loading.purchaseRequests = false
  }
}

onMounted(async () => {
  await Promise.all([fetchVendors(), fetchTenders(), fetchPurchaseRequests()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


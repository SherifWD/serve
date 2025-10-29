<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="5">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Ledger Accounts</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.ledgerAccounts" @click="fetchLedgerAccounts">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.ledgerAccounts" type="error" variant="tonal" class="mb-4">
                {{ errors.ledgerAccounts }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.ledgerAccounts" @submit.prevent="createLedgerAccount">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.ledger.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.ledger.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.ledger.account_type"
                      :items="accountTypes"
                      label="Account Type"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.ledger.parent_code" label="Parent Code" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.ledgerAccounts">
                      <v-icon start>mdi-plus</v-icon>
                      Add Ledger Account
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="ledgerHeaders"
                :items="ledgerAccounts"
                :loading="loading.ledgerAccounts"
                class="elevation-0"
              >
                <template #item.is_active="{ item }">
                  <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" variant="flat">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Post Journal Entry</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.journalEntries" @click="fetchJournalEntries">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.journalEntries" type="error" variant="tonal" class="mb-4">
                {{ errors.journalEntries }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.journalEntries" @submit.prevent="createJournalEntry">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.journal.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.journal.entry_date" type="date" label="Entry Date" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.journal.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.journal.debit_account_id"
                      :items="ledgerAccounts"
                      item-title="name"
                      item-value="id"
                      label="Debit Account"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.journal.credit_account_id"
                      :items="ledgerAccounts"
                      item-title="name"
                      item-value="id"
                      label="Credit Account"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model.number="forms.journal.amount"
                      type="number"
                      min="0"
                      step="0.01"
                      label="Amount"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.journalEntries">
                      <v-icon start>mdi-book-open-variant</v-icon>
                      Post Entry
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="journalHeaders"
                :items="journalEntries"
                :loading="loading.journalEntries"
                item-value="id"
                class="elevation-0"
              >
                <template #item.total="{ item }">
                  {{ formatCurrency(item.lines?.reduce((total, line) => total + Number(line.debit ?? 0), 0)) }}
                </template>
                <template #item.status="{ item }">
                  <v-chip size="small" :color="item.status === 'posted' ? 'success' : 'info'" variant="flat">
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
              <span class="text-h6 font-weight-bold">Accounts Payable</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.payables" @click="fetchPayables">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.payables" type="error" variant="tonal" class="mb-4">
                {{ errors.payables }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.payables" @submit.prevent="createPayable">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.payable.vendor_name" label="Vendor" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.payable.invoice_number" label="Invoice #" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.payable.invoice_date" type="date" label="Invoice Date" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.payable.due_date" type="date" label="Due Date" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.payable.amount"
                      type="number"
                      min="0"
                      step="0.01"
                      label="Amount"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.payable.status"
                      :items="invoiceStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.payables">
                      <v-icon start>mdi-file-document</v-icon>
                      Record Payable
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="payableHeaders"
                :items="accountsPayable"
                :loading="loading.payables"
                class="elevation-0"
              >
                <template #item.amount="{ item }">
                  {{ formatCurrency(item.amount) }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>

          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Accounts Receivable</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.receivables" @click="fetchReceivables">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.receivables" type="error" variant="tonal" class="mb-4">
                {{ errors.receivables }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.receivables" @submit.prevent="createReceivable">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.receivable.customer_name" label="Customer" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.receivable.invoice_number" label="Invoice #" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.receivable.invoice_date" type="date" label="Invoice Date" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.receivable.due_date" type="date" label="Due Date" dense />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.receivable.amount"
                      type="number"
                      min="0"
                      step="0.01"
                      label="Amount"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.receivable.status"
                      :items="invoiceStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.receivables">
                      <v-icon start>mdi-file-chart</v-icon>
                      Record Receivable
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="receivableHeaders"
                :items="accountsReceivable"
                :loading="loading.receivables"
                class="elevation-0"
              >
                <template #item.amount="{ item }">
                  {{ formatCurrency(item.amount) }}
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

const ledgerAccounts = ref([])
const journalEntries = ref([])
const accountsPayable = ref([])
const accountsReceivable = ref([])

const loading = reactive({
  ledgerAccounts: false,
  journalEntries: false,
  payables: false,
  receivables: false
})

const errors = reactive({
  ledgerAccounts: null,
  journalEntries: null,
  payables: null,
  receivables: null
})

const forms = reactive({
  ledger: {
    code: '',
    name: '',
    account_type: 'asset',
    parent_code: ''
  },
  journal: {
    reference: '',
    entry_date: '',
    description: '',
    debit_account_id: null,
    credit_account_id: null,
    amount: 0
  },
  payable: {
    vendor_name: '',
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    amount: 0,
    status: 'open'
  },
  receivable: {
    customer_name: '',
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    amount: 0,
    status: 'open'
  }
})

const accountTypes = ['asset', 'liability', 'equity', 'revenue', 'expense']
const invoiceStatuses = ['open', 'paid', 'cancelled']

const ledgerHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'account_type' },
  { title: 'Parent', key: 'parent_code' },
  { title: 'Active', key: 'is_active' }
]

const journalHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Date', key: 'entry_date' },
  { title: 'Status', key: 'status' },
  { title: 'Total', key: 'total' }
]

const payableHeaders = [
  { title: 'Vendor', key: 'vendor_name' },
  { title: 'Invoice #', key: 'invoice_number' },
  { title: 'Invoice Date', key: 'invoice_date' },
  { title: 'Due Date', key: 'due_date' },
  { title: 'Status', key: 'status' },
  { title: 'Amount', key: 'amount' }
]

const receivableHeaders = [
  { title: 'Customer', key: 'customer_name' },
  { title: 'Invoice #', key: 'invoice_number' },
  { title: 'Invoice Date', key: 'invoice_date' },
  { title: 'Due Date', key: 'due_date' },
  { title: 'Status', key: 'status' },
  { title: 'Amount', key: 'amount' }
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
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
  }).format(amount)
}

async function fetchLedgerAccounts() {
  loading.ledgerAccounts = true
  errors.ledgerAccounts = null
  try {
    const { data } = await axios.get(`${API_BASE}/finance/ledger-accounts`, { headers: authHeaders() })
    ledgerAccounts.value = data.data || data
  } catch (error) {
    errors.ledgerAccounts = parseError(error)
  } finally {
    loading.ledgerAccounts = false
  }
}

async function fetchJournalEntries() {
  loading.journalEntries = true
  errors.journalEntries = null
  try {
    const { data } = await axios.get(`${API_BASE}/finance/journal-entries`, { headers: authHeaders() })
    journalEntries.value = data.data || data
  } catch (error) {
    errors.journalEntries = parseError(error)
  } finally {
    loading.journalEntries = false
  }
}

async function fetchPayables() {
  loading.payables = true
  errors.payables = null
  try {
    const { data } = await axios.get(`${API_BASE}/finance/accounts-payable`, { headers: authHeaders() })
    accountsPayable.value = data.data || data
  } catch (error) {
    errors.payables = parseError(error)
  } finally {
    loading.payables = false
  }
}

async function fetchReceivables() {
  loading.receivables = true
  errors.receivables = null
  try {
    const { data } = await axios.get(`${API_BASE}/finance/accounts-receivable`, { headers: authHeaders() })
    accountsReceivable.value = data.data || data
  } catch (error) {
    errors.receivables = parseError(error)
  } finally {
    loading.receivables = false
  }
}

async function createLedgerAccount() {
  loading.ledgerAccounts = true
  errors.ledgerAccounts = null
  try {
    await axios.post(`${API_BASE}/finance/ledger-accounts`, forms.ledger, { headers: authHeaders() })
    Object.assign(forms.ledger, { code: '', name: '', account_type: 'asset', parent_code: '' })
    await fetchLedgerAccounts()
  } catch (error) {
    errors.ledgerAccounts = parseError(error)
  } finally {
    loading.ledgerAccounts = false
  }
}

async function createJournalEntry() {
  loading.journalEntries = true
  errors.journalEntries = null
  try {
    const payload = {
      reference: forms.journal.reference,
      entry_date: forms.journal.entry_date,
      description: forms.journal.description || null,
      status: 'posted',
      lines: [
        {
          ledger_account_id: forms.journal.debit_account_id,
          debit: forms.journal.amount,
          credit: 0,
          memo: 'Debit line'
        },
        {
          ledger_account_id: forms.journal.credit_account_id,
          debit: 0,
          credit: forms.journal.amount,
          memo: 'Credit line'
        }
      ]
    }
    await axios.post(`${API_BASE}/finance/journal-entries`, payload, { headers: authHeaders() })
    Object.assign(forms.journal, {
      reference: '',
      entry_date: '',
      description: '',
      debit_account_id: null,
      credit_account_id: null,
      amount: 0
    })
    await fetchJournalEntries()
  } catch (error) {
    errors.journalEntries = parseError(error)
  } finally {
    loading.journalEntries = false
  }
}

async function createPayable() {
  loading.payables = true
  errors.payables = null
  try {
    await axios.post(`${API_BASE}/finance/accounts-payable`, forms.payable, { headers: authHeaders() })
    Object.assign(forms.payable, {
      vendor_name: '',
      invoice_number: '',
      invoice_date: '',
      due_date: '',
      amount: 0,
      status: 'open'
    })
    await fetchPayables()
  } catch (error) {
    errors.payables = parseError(error)
  } finally {
    loading.payables = false
  }
}

async function createReceivable() {
  loading.receivables = true
  errors.receivables = null
  try {
    await axios.post(`${API_BASE}/finance/accounts-receivable`, forms.receivable, { headers: authHeaders() })
    Object.assign(forms.receivable, {
      customer_name: '',
      invoice_number: '',
      invoice_date: '',
      due_date: '',
      amount: 0,
      status: 'open'
    })
    await fetchReceivables()
  } catch (error) {
    errors.receivables = parseError(error)
  } finally {
    loading.receivables = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchLedgerAccounts(),
    fetchJournalEntries(),
    fetchPayables(),
    fetchReceivables()
  ])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


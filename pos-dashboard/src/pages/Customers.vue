<template>
  <OwnerLayout>
    <v-container class="rs-page customers-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="6">
          <div class="rs-panel page-panel">
            <div>
              <div class="page-kicker">People</div>
              <h1 class="page-title">Customers</h1>
              <p class="page-copy">Purchases, loyalty, and visit history across accessible branches.</p>
            </div>
          </div>
        </v-col>

        <v-col cols="12" sm="4" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Customers</div>
            <div class="metric-value">{{ meta.total || 0 }}</div>
          </div>
        </v-col>

        <v-col cols="12" sm="4" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Purchases</div>
            <div class="metric-value">{{ meta.total_purchases || 0 }}</div>
          </div>
        </v-col>

        <v-col cols="12" sm="4" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Total Spend</div>
            <div class="metric-value compact">{{ money(meta.total_spent) }}</div>
          </div>
        </v-col>
      </v-row>

      <div class="rs-panel filter-panel">
        <v-row dense>
          <v-col cols="12" md="6" lg="3">
            <v-text-field
              v-model="filters.search"
              label="Search"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              clearable
              @keyup.enter="loadCustomers(1)"
            />
          </v-col>

          <v-col v-if="auth.isAdmin" cols="12" md="6" lg="3">
            <v-select
              v-model="filters.restaurant_id"
              :items="restaurants"
              item-title="name"
              item-value="id"
              label="Restaurant"
              variant="outlined"
              clearable
              @update:modelValue="handleRestaurantChange"
            />
          </v-col>

          <v-col cols="12" md="6" lg="3">
            <v-select
              v-model="filters.branch_id"
              :items="branchOptions"
              item-title="name"
              item-value="id"
              label="Branch"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="3">
            <v-select
              v-model="filters.payment_status"
              :items="paymentStatuses"
              label="Payment status"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <v-text-field
              v-model.number="filters.min_bill"
              label="Min bill"
              type="number"
              min="0"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <v-text-field
              v-model.number="filters.max_bill"
              label="Max bill"
              type="number"
              min="0"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <v-text-field
              v-model="filters.from_date"
              label="From date"
              type="date"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <v-text-field
              v-model="filters.to_date"
              label="To date"
              type="date"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <v-select
              v-model="filters.order_status"
              :items="orderStatuses"
              label="Order status"
              variant="outlined"
              clearable
            />
          </v-col>

          <v-col cols="12" md="6" lg="2">
            <div class="filter-actions">
              <v-btn color="primary" class="rs-pill" @click="loadCustomers(1)">
                <v-icon start icon="mdi-filter-check-outline" />
                Apply
              </v-btn>
              <v-btn variant="text" icon="mdi-filter-remove-outline" @click="resetFilters" />
            </div>
          </v-col>
        </v-row>
      </div>

      <div class="rs-panel table-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Customer Directory</div>
            <div class="panel-subtitle">
              {{ auth.isAdmin ? 'All platform customers and matched purchases.' : 'Customers served by your restaurant or cafe.' }}
            </div>
          </div>

          <v-select
            v-model="filters.per_page"
            :items="[10, 20, 50, 100]"
            label="Rows"
            density="compact"
            variant="outlined"
            hide-details
            class="rows-select"
            @update:modelValue="loadCustomers(1)"
          />
        </div>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">
          {{ error }}
        </v-alert>

        <v-data-table
          :headers="headers"
          :items="customers"
          :items-per-page="customers.length || 1"
          :loading="loading"
          item-value="id"
          class="rs-table customers-table"
          hide-default-footer
        >
          <template #item.customer="{ item }">
            <div class="customer-cell">
              <div class="customer-name">{{ item.name }}</div>
              <div class="customer-id">#{{ item.id }}</div>
            </div>
          </template>

          <template #item.contact="{ item }">
            <div class="contact-cell">
              <span>{{ item.phone || 'No phone' }}</span>
              <span>{{ item.email || 'No email' }}</span>
            </div>
          </template>

          <template #item.branches="{ item }">
            <div class="chip-stack">
              <v-chip
                v-for="branch in item.branches.slice(0, 2)"
                :key="branch.id"
                size="small"
                color="info"
                variant="tonal"
                class="rs-pill"
              >
                {{ branch.name }}
              </v-chip>
              <v-chip
                v-if="item.branches.length > 2"
                size="small"
                color="secondary"
                class="rs-pill"
              >
                +{{ item.branches.length - 2 }}
              </v-chip>
            </div>
          </template>

          <template #item.purchases_count="{ item }">
            <v-chip color="primary" variant="tonal" class="rs-pill">
              {{ item.purchases_count }}
            </v-chip>
          </template>

          <template #item.total_spent="{ item }">
            {{ money(item.total_spent) }}
          </template>

          <template #item.average_bill="{ item }">
            {{ money(item.average_bill) }}
          </template>

          <template #item.last_visit_at="{ item }">
            {{ dateLabel(item.last_visit_at) }}
          </template>

          <template #item.actions="{ item }">
            <v-btn
              size="small"
              variant="text"
              icon="mdi-eye-outline"
              @click="openCustomer(item)"
            />
          </template>
        </v-data-table>

        <div class="pagination-row">
          <v-pagination
            v-model="page"
            :length="meta.last_page || 1"
            total-visible="7"
            density="comfortable"
            @update:modelValue="loadCustomers"
          />
        </div>
      </div>

      <v-navigation-drawer
        v-model="drawer"
        location="right"
        temporary
        width="520"
        color="surface"
        class="detail-drawer"
      >
        <div v-if="selectedCustomer" class="drawer-content">
          <div class="drawer-header">
            <div>
              <div class="page-kicker">Customer</div>
              <div class="drawer-title">{{ selectedCustomer.name }}</div>
              <div class="drawer-subtitle">{{ selectedCustomer.phone || selectedCustomer.email || `#${selectedCustomer.id}` }}</div>
            </div>
            <v-btn variant="text" icon="mdi-close" @click="drawer = false" />
          </div>

          <v-row dense class="mb-4">
            <v-col cols="4">
              <div class="drawer-metric">
                <span>Orders</span>
                <strong>{{ selectedCustomer.purchases_count }}</strong>
              </div>
            </v-col>
            <v-col cols="4">
              <div class="drawer-metric">
                <span>Spent</span>
                <strong>{{ money(selectedCustomer.total_spent) }}</strong>
              </div>
            </v-col>
            <v-col cols="4">
              <div class="drawer-metric">
                <span>Points</span>
                <strong>{{ selectedCustomer.loyalty_points }}</strong>
              </div>
            </v-col>
          </v-row>

          <div class="detail-section">
            <div class="section-label">Details</div>
            <div class="detail-grid">
              <span>Customer ID</span>
              <strong>#{{ selectedCustomer.id }}</strong>
              <span>Email</span>
              <strong>{{ selectedCustomer.email || 'No email' }}</strong>
              <span>Phone</span>
              <strong>{{ selectedCustomer.phone || 'No phone' }}</strong>
              <span>Joined</span>
              <strong>{{ dateLabel(selectedCustomer.created_at) }}</strong>
              <span>Email verified</span>
              <strong>{{ verifiedLabel(selectedCustomer.email_verified_at) }}</strong>
              <span>Phone verified</span>
              <strong>{{ verifiedLabel(selectedCustomer.phone_verified_at) }}</strong>
              <span>Last visit</span>
              <strong>{{ dateLabel(selectedCustomer.last_visit_at) }}</strong>
              <span>Highest bill</span>
              <strong>{{ money(selectedCustomer.highest_bill) }}</strong>
            </div>
          </div>

          <div class="detail-section">
            <div class="section-label">Purchases</div>
            <div v-if="selectedCustomer.orders.length" class="purchase-list">
              <div v-for="order in selectedCustomer.orders" :key="order.id" class="purchase-row">
                <div>
                  <div class="purchase-title">Order #{{ order.id }}</div>
                  <div class="purchase-meta">
                    {{ dateLabel(order.order_date) }} · {{ order.branch?.name || 'No branch' }}
                  </div>
                </div>
                <div class="purchase-side">
                  <strong>{{ money(order.total) }}</strong>
                  <v-chip size="x-small" :color="statusColor(order.payment_status)" variant="tonal" class="rs-pill">
                    {{ order.payment_status || order.status }}
                  </v-chip>
                </div>
              </div>
            </div>
            <v-alert v-else type="info" variant="tonal">No purchases match the current filters.</v-alert>
          </div>
        </div>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const auth = useAuthStore()
const customers = ref([])
const restaurants = ref([])
const branches = ref([])
const selectedCustomer = ref(null)
const drawer = ref(false)
const loading = ref(false)
const error = ref('')
const page = ref(1)

const filters = ref({
  search: '',
  restaurant_id: null,
  branch_id: null,
  min_bill: null,
  max_bill: null,
  from_date: '',
  to_date: '',
  payment_status: null,
  order_status: null,
  per_page: 20,
})

const headers = [
  { title: 'Customer', key: 'customer' },
  { title: 'Contact', key: 'contact', sortable: false },
  { title: 'Branches', key: 'branches', sortable: false },
  { title: 'Orders', key: 'purchases_count' },
  { title: 'Total Spent', key: 'total_spent' },
  { title: 'Avg Bill', key: 'average_bill' },
  { title: 'Last Visit', key: 'last_visit_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const paymentStatuses = [
  { title: 'Paid', value: 'paid' },
  { title: 'Partial', value: 'partial' },
  { title: 'Unpaid', value: 'unpaid' },
]

const orderStatuses = ['pending', 'open', 'running', 'cashier', 'preparing', 'prepared', 'paid', 'closed', 'cancelled', 'refunded']

const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  total_purchases: 0,
  total_spent: 0,
  average_bill: 0,
})

const branchOptions = computed(() => {
  if (!auth.isAdmin || !filters.value.restaurant_id) {
    return branches.value
  }

  return branches.value.filter((branch) => branch.restaurant_id === filters.value.restaurant_id)
})

function authHeaders() {
  return { Authorization: `Bearer ${auth.token || localStorage.getItem('token')}` }
}

function requestParams(targetPage = page.value) {
  const params = {
    ...filters.value,
    page: targetPage,
  }

  Object.keys(params).forEach((key) => {
    if (params[key] === null || params[key] === undefined || params[key] === '') {
      delete params[key]
    }
  })

  return params
}

async function loadCustomers(targetPage = page.value) {
  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.get(`${API_BASE_URL}/customers`, {
      headers: authHeaders(),
      params: requestParams(targetPage),
    })

    customers.value = data.data || []
    meta.value = data.meta || meta.value
    page.value = meta.value.current_page || targetPage

    if (selectedCustomer.value) {
      selectedCustomer.value = customers.value.find((customer) => customer.id === selectedCustomer.value.id) || selectedCustomer.value
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load customers.'
  } finally {
    loading.value = false
  }
}

async function loadLookups() {
  const branchRequest = axios.get(`${API_BASE_URL}/branches`, { headers: authHeaders() })
  const restaurantRequest = auth.isAdmin
    ? axios.get(`${API_BASE_URL}/restaurants`, { headers: authHeaders() })
    : Promise.resolve({ data: [] })

  const [branchResponse, restaurantResponse] = await Promise.all([branchRequest, restaurantRequest])
  branches.value = branchResponse.data.data || branchResponse.data || []
  restaurants.value = restaurantResponse.data.data || restaurantResponse.data || []
}

function handleRestaurantChange() {
  filters.value.branch_id = null
}

function resetFilters() {
  filters.value = {
    search: '',
    restaurant_id: null,
    branch_id: null,
    min_bill: null,
    max_bill: null,
    from_date: '',
    to_date: '',
    payment_status: null,
    order_status: null,
    per_page: filters.value.per_page,
  }
  loadCustomers(1)
}

function openCustomer(customer) {
  selectedCustomer.value = customer
  drawer.value = true
}

function money(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EGP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function dateLabel(value) {
  if (!value) return 'No visits'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

function verifiedLabel(value) {
  return value ? dateLabel(value) : 'Not verified'
}

function statusColor(status) {
  if (status === 'paid') return 'success'
  if (status === 'partial') return 'warning'
  if (status === 'unpaid') return 'error'
  return 'info'
}

onMounted(async () => {
  await loadLookups()
  await loadCustomers(1)
})
</script>

<style scoped>
.customers-page {
  color: var(--rs-text);
}

.page-panel,
.metric-card,
.filter-panel,
.table-panel {
  padding: 22px;
  height: 100%;
}

.page-title {
  margin: 0;
  color: var(--rs-text);
  font-size: 2rem;
  font-weight: 800;
}

.page-kicker,
.metric-label,
.panel-subtitle,
.customer-id,
.drawer-subtitle,
.section-label,
.purchase-meta {
  color: var(--rs-text-muted);
}

.page-copy {
  margin: 8px 0 0;
  color: var(--rs-text-soft);
}

.metric-card {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
}

.metric-value {
  color: var(--rs-text);
  font-size: 2rem;
  font-weight: 800;
}

.metric-value.compact {
  font-size: 1.35rem;
}

.filter-actions {
  min-height: 56px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-panel {
  margin-top: 16px;
}

.panel-header,
.pagination-row,
.drawer-header,
.purchase-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.panel-header {
  margin-bottom: 16px;
}

.panel-title {
  color: var(--rs-text);
  font-size: 1.2rem;
  font-weight: 800;
}

.rows-select {
  max-width: 120px;
}

.customer-cell,
.contact-cell,
.purchase-side {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.customer-name,
.purchase-title {
  color: var(--rs-text);
  font-weight: 700;
}

.contact-cell {
  color: var(--rs-text-soft);
}

.chip-stack {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pagination-row {
  justify-content: center;
  padding-top: 18px;
}

.detail-drawer {
  border-left: 1px solid var(--rs-border);
}

.drawer-content {
  padding: 24px;
}

.drawer-header {
  margin-bottom: 22px;
}

.drawer-title {
  color: var(--rs-text);
  font-size: 1.45rem;
  font-weight: 800;
}

.drawer-metric,
.detail-section {
  border: 1px solid var(--rs-border);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.03);
}

.drawer-metric {
  min-height: 82px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 6px;
}

.drawer-metric span {
  color: var(--rs-text-muted);
  font-size: 0.8rem;
}

.drawer-metric strong {
  color: var(--rs-text);
}

.detail-section {
  padding: 16px;
  margin-top: 14px;
}

.section-label {
  margin-bottom: 12px;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(100px, 0.5fr) 1fr;
  gap: 10px 14px;
}

.detail-grid span {
  color: var(--rs-text-muted);
}

.detail-grid strong {
  color: var(--rs-text-soft);
  min-width: 0;
  overflow-wrap: anywhere;
}

.purchase-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.purchase-row {
  padding: 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.04);
}

.purchase-side {
  align-items: flex-end;
  color: var(--rs-text);
}

@media (max-width: 720px) {
  .panel-header,
  .purchase-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .rows-select {
    max-width: none;
    width: 100%;
  }
}
</style>

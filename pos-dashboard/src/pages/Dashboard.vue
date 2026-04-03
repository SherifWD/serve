<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="8">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">
                {{ auth.isAdmin ? 'Platform Overview' : 'Restaurant Operations' }}
              </div>
              <h1 class="page-title">
                {{ auth.isAdmin ? 'Control every restaurant from one live board.' : 'Track live service, branch health, and cash flow.' }}
              </h1>
              <p class="page-copy">
                Monitor restaurant performance, filter by branch, and stay ahead of cashier
                pressure, kitchen backlog, low stock, and loyalty activity.
              </p>
            </div>

            <div class="filter-stack">
              <v-select
                v-if="auth.isAdmin"
                v-model="selectedRestaurant"
                :items="restaurants"
                item-title="name"
                item-value="id"
                label="Restaurant"
                variant="outlined"
                clearable
                @update:modelValue="handleRestaurantChange"
              />

              <v-select
                v-model="selectedBranch"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Branch"
                variant="outlined"
                clearable
                @update:modelValue="fetchDashboard"
              />
            </div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Revenue</div>
            <div class="metric-value">{{ formatCompactCurrency(summary.total_sales) }}</div>
            <div class="metric-note">Paid orders only</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Orders</div>
            <div class="metric-value">{{ summary.orders_count || 0 }}</div>
            <div class="metric-note">{{ formatCompactCurrency(summary.avg_order_value) }} average ticket</div>
          </div>
        </v-col>
      </v-row>

      <v-row class="mb-4">
        <v-col
          v-for="card in overviewCards"
          :key="card.label"
          cols="12"
          sm="6"
          lg="3"
        >
          <div class="rs-panel stat-card">
            <div class="stat-label">{{ card.label }}</div>
            <div class="stat-value">{{ card.value }}</div>
            <div class="stat-note">{{ card.note }}</div>
          </div>
        </v-col>
      </v-row>

      <v-row class="mb-4">
        <v-col cols="12" lg="7">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Branch Leaderboard</div>
                <div class="panel-subtitle">Top branches by paid sales in the current filter window.</div>
              </div>
            </div>

            <div v-if="summary.branch_performance?.length" class="leaderboard">
              <div
                v-for="branch in summary.branch_performance"
                :key="branch.id"
                class="leader-item"
              >
                <div class="leader-main">
                  <div>
                    <div class="leader-name">{{ branch.name }}</div>
                    <div class="leader-meta">{{ branch.location || 'No location' }}</div>
                  </div>
                  <div class="leader-values">
                    <span>{{ formatCompactCurrency(branch.sales) }}</span>
                    <span class="leader-orders">{{ branch.orders_count }} orders</span>
                  </div>
                </div>
                <v-progress-linear
                  :model-value="progressForBranch(branch.sales)"
                  color="primary"
                  height="8"
                  rounded
                />
              </div>
            </div>

            <v-alert v-else type="info" variant="tonal">
              No branch performance data available for this filter.
            </v-alert>
          </div>
        </v-col>

        <v-col cols="12" lg="5">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Payment Mix</div>
                <div class="panel-subtitle">How paid orders were settled.</div>
              </div>
            </div>

            <div v-if="summary.payment_mix?.length" class="payment-list">
              <div
                v-for="payment in summary.payment_mix"
                :key="payment.method"
                class="payment-item"
              >
                <div class="payment-head">
                  <span class="payment-method">{{ payment.method }}</span>
                  <span class="payment-total">{{ formatCurrency(payment.total) }}</span>
                </div>
                <v-progress-linear
                  :model-value="paymentProgress(payment.total)"
                  color="info"
                  height="8"
                  rounded
                />
              </div>
            </div>

            <v-alert v-else type="info" variant="tonal">
              Payment records will appear after paid transactions exist.
            </v-alert>
          </div>
        </v-col>
      </v-row>

      <v-row class="mb-4">
        <v-col cols="12" lg="4">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Top Products</div>
                <div class="panel-subtitle">Most sold items in paid orders.</div>
              </div>
            </div>

            <v-data-table
              :headers="topProductHeaders"
              :items="summary.top_products || []"
              item-value="name"
              class="rs-table"
              hide-default-footer
            />
          </div>
        </v-col>

        <v-col cols="12" lg="4">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Low Stock Alerts</div>
                <div class="panel-subtitle">Ingredients near or below the minimum stock line.</div>
              </div>
            </div>

            <v-data-table
              :headers="lowStockHeaders"
              :items="summary.low_stock_items || []"
              item-value="id"
              class="rs-table"
              hide-default-footer
            />
          </div>
        </v-col>

        <v-col cols="12" lg="4">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Service Radar</div>
                <div class="panel-subtitle">Live queues that need the owner or admin eye.</div>
              </div>
            </div>

            <div class="radar-grid">
              <div v-for="card in radarCards" :key="card.label" class="radar-card">
                <div class="radar-label">{{ card.label }}</div>
                <div class="radar-value">{{ card.value }}</div>
                <div class="radar-note">{{ card.note }}</div>
              </div>
            </div>
          </div>
        </v-col>
      </v-row>

      <div class="rs-panel section-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Recent Orders</div>
            <div class="panel-subtitle">Latest ticket activity in the filtered restaurant or branch.</div>
          </div>
        </div>

        <v-data-table
          :headers="orderHeaders"
          :items="summary.recent_orders || []"
          item-value="id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.total="{ item }">
            {{ formatCurrency(item.total) }}
          </template>

          <template #item.status="{ item }">
            <v-chip :color="statusColor(item.status)" variant="tonal" class="rs-pill">
              {{ item.status }}
            </v-chip>
          </template>

          <template #item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
        </v-data-table>
      </div>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const loading = ref(false)
const restaurants = ref([])
const branches = ref([])
const selectedRestaurant = ref(null)
const selectedBranch = ref(null)
const summary = ref({
  total_sales: 0,
  orders_count: 0,
  avg_order_value: 0,
  restaurant_count: 0,
  branch_count: 0,
  product_count: 0,
  employee_count: 0,
  active_tables: 0,
  cashier_queue: 0,
  kds_backlog: 0,
  loyalty_members: 0,
  payment_mix: [],
  branch_performance: [],
  top_products: [],
  low_stock_items: [],
  recent_orders: [],
})

const orderHeaders = [
  { title: 'Order', key: 'id', width: 90 },
  { title: 'Branch', key: 'branch.name' },
  { title: 'Total', key: 'total' },
  { title: 'Status', key: 'status' },
  { title: 'Created', key: 'created_at' },
]

const topProductHeaders = [
  { title: 'Product', key: 'name' },
  { title: 'Sold', key: 'quantity', width: 90 },
]

const lowStockHeaders = [
  { title: 'Ingredient', key: 'name' },
  { title: 'Qty', key: 'stock', width: 80 },
  { title: 'Unit', key: 'unit', width: 80 },
]

const overviewCards = computed(() => [
  {
    label: 'Restaurants',
    value: auth.isAdmin ? summary.value.restaurant_count || restaurants.value.length : auth.user?.restaurant?.name || '1',
    note: auth.isAdmin ? 'Brands under platform control' : 'Current owner workspace',
  },
  {
    label: 'Branches',
    value: summary.value.branch_count || 0,
    note: 'Active locations inside the current scope',
  },
  {
    label: 'Products',
    value: summary.value.product_count || 0,
    note: 'Menu items available to sell',
  },
  {
    label: 'Employees',
    value: summary.value.employee_count || 0,
    note: 'Staff records inside this scope',
  },
])

const radarCards = computed(() => [
  {
    label: 'Active Tables',
    value: summary.value.active_tables || 0,
    note: 'Occupied dine-in tables right now',
  },
  {
    label: 'Cashier Queue',
    value: summary.value.cashier_queue || 0,
    note: 'Orders waiting for settlement',
  },
  {
    label: 'Kitchen Backlog',
    value: summary.value.kds_backlog || 0,
    note: 'Items queued or preparing in KDS',
  },
  {
    label: 'Loyalty Members',
    value: summary.value.loyalty_members || 0,
    note: 'Customers with loyalty activity',
  },
])

const maxBranchSales = computed(() =>
  Math.max(...(summary.value.branch_performance || []).map((branch) => Number(branch.sales || 0)), 1),
)

const paymentMixTotal = computed(() =>
  (summary.value.payment_mix || []).reduce((sum, item) => sum + Number(item.total || 0), 0),
)

function authHeaders() {
  return { Authorization: `Bearer ${auth.token}` }
}

function formatCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-EG', {
    style: 'currency',
    currency: 'EGP',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatCompactCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-EG', {
    style: 'currency',
    currency: 'EGP',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(amount)
}

function formatDate(value) {
  if (!value) return 'N/A'

  return new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function progressForBranch(value) {
  return (Number(value || 0) / maxBranchSales.value) * 100
}

function paymentProgress(value) {
  if (!paymentMixTotal.value) return 0
  return (Number(value || 0) / paymentMixTotal.value) * 100
}

function statusColor(status) {
  switch (status) {
    case 'paid':
      return 'success'
    case 'cashier':
      return 'warning'
    case 'open':
      return 'info'
    default:
      return 'secondary'
  }
}

async function loadRestaurants() {
  if (!auth.isAdmin) return

  const { data } = await axios.get(`${API_BASE_URL}/restaurants`, {
    headers: authHeaders(),
  })

  restaurants.value = data
}

async function loadBranches() {
  const params = {}
  if (auth.isAdmin && selectedRestaurant.value) {
    params.restaurant_id = selectedRestaurant.value
  }

  const { data } = await axios.get(`${API_BASE_URL}/branches`, {
    headers: authHeaders(),
    params,
  })

  branches.value = data.data || data
}

async function fetchDashboard() {
  loading.value = true
  const params = {}

  if (auth.isAdmin && selectedRestaurant.value) {
    params.restaurant_id = selectedRestaurant.value
  }

  if (selectedBranch.value) {
    params.branch_id = selectedBranch.value
  }

  try {
    const { data } = await axios.get(`${API_BASE_URL}/dashboard/summary`, {
      headers: authHeaders(),
      params,
    })

    summary.value = data
  } finally {
    loading.value = false
  }
}

async function handleRestaurantChange() {
  selectedBranch.value = null
  await loadBranches()
  await fetchDashboard()
}

onMounted(async () => {
  await loadRestaurants()
  await loadBranches()
  await fetchDashboard()
})
</script>

<style scoped>
.hero-panel,
.metric-card,
.section-panel {
  padding: 24px;
}

.hero-panel {
  min-height: 220px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-kicker,
.metric-label,
.stat-label,
.panel-subtitle,
.radar-label,
.metric-note {
  color: var(--rs-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.76rem;
}

.page-title {
  max-width: 740px;
  margin-top: 0.8rem;
  color: var(--rs-text);
  font-size: 2.45rem;
  line-height: 1.02;
  letter-spacing: -0.04em;
}

.page-copy {
  max-width: 620px;
  margin-top: 0.8rem;
  color: var(--rs-text-soft);
  line-height: 1.7;
}

.filter-stack {
  width: min(320px, 100%);
  display: grid;
  gap: 14px;
}

.metric-card,
.stat-card {
  min-height: 180px;
}

.metric-card {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.metric-value,
.stat-value,
.radar-value {
  color: var(--rs-text);
  font-size: 2.7rem;
  line-height: 1;
  letter-spacing: -0.05em;
  margin-top: 1rem;
}

.stat-card {
  padding: 22px;
}

.stat-note,
.radar-note {
  margin-top: 0.75rem;
  color: var(--rs-text-soft);
}

.section-panel {
  height: 100%;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.panel-title,
.leader-name,
.payment-method {
  color: var(--rs-text);
  font-weight: 700;
}

.leaderboard,
.payment-list {
  display: grid;
  gap: 16px;
}

.leader-item,
.payment-item {
  padding: 14px 16px;
  border: 1px solid var(--rs-border);
  border-radius: 18px;
  background: rgba(15, 24, 39, 0.72);
}

.leader-main,
.payment-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 10px;
}

.leader-meta,
.leader-orders,
.payment-total {
  color: var(--rs-text-soft);
}

.leader-values {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.radar-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.radar-card {
  padding: 16px;
  border: 1px solid var(--rs-border);
  border-radius: 18px;
  background: rgba(15, 24, 39, 0.72);
}

@media (max-width: 1100px) {
  .hero-panel {
    min-height: auto;
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 680px) {
  .page-title {
    font-size: 2rem;
  }

  .radar-grid {
    grid-template-columns: 1fr;
  }
}
</style>

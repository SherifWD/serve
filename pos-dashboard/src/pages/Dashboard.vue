<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="6">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">
                {{ auth.isAdmin ? 'Business Overview' : 'Operations' }}
              </div>
              <h1 class="page-title">
                {{ auth.isAdmin ? 'Manage restaurants, branches, and service performance.' : 'Track live service, branch health, and cash flow.' }}
              </h1>
              <p class="page-copy">
                Review restaurant performance, filter by branch, and stay ahead of cashier
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
            <div class="metric-label">Expenses</div>
            <div class="metric-value">{{ formatCompactCurrency(summary.total_expenses) }}</div>
            <div class="metric-note">Logged branch costs</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Net</div>
            <div class="metric-value">{{ formatCompactCurrency(summary.net_revenue) }}</div>
            <div class="metric-note">Revenue minus expenses</div>
          </div>
        </v-col>
      </v-row>

      <div class="rs-panel section-panel management-panel mb-4">
        <div class="panel-header">
          <div>
            <div class="panel-title">Owner Control Center</div>
            <div class="panel-subtitle">
              Jump straight into the records owners edit most often.
            </div>
          </div>
        </div>

        <div class="management-grid">
          <div
            v-for="card in managementCards"
            :key="card.title"
            class="management-card"
            :class="{ 'management-card-alert': card.alert }"
          >
            <div class="management-icon">
              <v-icon :icon="card.icon" />
            </div>
            <div class="management-content">
              <div class="management-title">{{ card.title }}</div>
              <div class="management-copy">{{ card.copy }}</div>
              <div class="management-meta">{{ card.meta }}</div>
            </div>
            <v-btn
              :to="card.to"
              color="primary"
              variant="tonal"
              class="rs-pill management-action"
            >
              Open
            </v-btn>
          </div>
        </div>
      </div>

      <v-row class="mb-4">
        <v-col cols="12" lg="5">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Add Expense</div>
                <div class="panel-subtitle">Record branch costs inside the current admin or owner scope.</div>
              </div>
            </div>

            <v-alert
              v-if="expenseError"
              type="error"
              variant="tonal"
              density="compact"
              class="mb-3"
              closable
              @click:close="expenseError = ''"
            >
              {{ expenseError }}
            </v-alert>

            <v-form class="expense-form" @submit.prevent="saveExpense">
              <v-select
                v-model="expenseForm.branch_id"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Branch"
                variant="outlined"
                density="comfortable"
                no-data-text="No branch available in this scope"
              />
              <v-row dense>
                <v-col cols="12" sm="6">
                  <v-select
                    v-model="expenseForm.category"
                    :items="expenseCategoryOptions"
                    label="Category"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-model.number="expenseForm.amount"
                    label="Amount"
                    type="number"
                    min="0"
                    step="0.01"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
              </v-row>
              <v-text-field
                v-model="expenseForm.expense_date"
                label="Expense date"
                type="date"
                variant="outlined"
                density="comfortable"
              />
              <v-textarea
                v-model="expenseForm.description"
                label="Description"
                rows="2"
                auto-grow
                variant="outlined"
                density="comfortable"
              />
              <div class="expense-actions">
                <v-btn
                  color="primary"
                  prepend-icon="mdi-cash-minus"
                  :loading="savingExpense"
                  type="submit"
                >
                  Save expense
                </v-btn>
              </div>
            </v-form>
          </div>
        </v-col>

        <v-col cols="12" lg="3">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Expense Categories</div>
                <div class="panel-subtitle">Costs grouped for the active filter.</div>
              </div>
            </div>

            <v-data-table
              :headers="expenseCategoryHeaders"
              :items="summary.expense_by_category || []"
              item-value="category"
              class="rs-table"
              hide-default-footer
            >
              <template #item.total="{ item }">
                {{ formatCurrency(item.total) }}
              </template>
            </v-data-table>
          </div>
        </v-col>

        <v-col cols="12" lg="4">
          <div class="rs-panel section-panel">
            <div class="panel-header">
              <div>
                <div class="panel-title">Recent Expenses</div>
                <div class="panel-subtitle">Latest expense entries in the active scope.</div>
              </div>
            </div>

            <v-data-table
              :headers="recentExpenseHeaders"
              :items="summary.recent_expenses || []"
              item-value="id"
              class="rs-table"
              hide-default-footer
            >
              <template #item.amount="{ item }">
                {{ formatCurrency(item.amount) }}
              </template>
              <template #item.expense_date="{ item }">
                {{ formatDateOnly(item.expense_date) }}
              </template>
            </v-data-table>
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
                    <span class="leader-orders">
                      {{ branch.orders_count }} orders / {{ formatCompactCurrency(branch.net_revenue) }} net
                    </span>
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

      <div class="rs-panel section-panel mb-4">
        <div class="panel-header">
          <div>
            <div class="panel-title">Employee Revenue</div>
            <div class="panel-subtitle">
              Paid revenue grouped by employee and branch in the current filter.
            </div>
          </div>
        </div>

        <v-data-table
          :headers="employeeRevenueHeaders"
          :items="summary.employee_revenue || []"
          item-value="employee_id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.employee_name="{ item }">
            <div class="employee-cell">
              <strong>{{ item.employee_name }}</strong>
              <span>{{ item.position || 'No position' }}</span>
            </div>
          </template>
          <template #item.revenue="{ item }">
            {{ formatCurrency(item.revenue) }}
          </template>
          <template #item.average_order="{ item }">
            {{ formatCurrency(item.average_order) }}
          </template>
        </v-data-table>
      </div>

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
const savingExpense = ref(false)
const expenseError = ref('')
const expenseForm = ref({
  branch_id: null,
  category: 'Operations',
  amount: null,
  expense_date: todayInput(),
  description: '',
})
const summary = ref({
  total_sales: 0,
  total_expenses: 0,
  net_revenue: 0,
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
  expense_by_category: [],
  recent_expenses: [],
  employee_revenue: [],
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

const expenseCategoryHeaders = [
  { title: 'Category', key: 'category' },
  { title: 'Total', key: 'total', align: 'end' },
]

const recentExpenseHeaders = [
  { title: 'Date', key: 'expense_date' },
  { title: 'Branch', key: 'branch_name' },
  { title: 'Category', key: 'category' },
  { title: 'Amount', key: 'amount', align: 'end' },
]

const employeeRevenueHeaders = [
  { title: 'Employee', key: 'employee_name' },
  { title: 'Restaurant', key: 'restaurant_name' },
  { title: 'Branch', key: 'branch_name' },
  { title: 'Orders', key: 'orders_count', align: 'end' },
  { title: 'Revenue', key: 'revenue', align: 'end' },
  { title: 'Average', key: 'average_order', align: 'end' },
]

const expenseCategoryOptions = [
  'Operations',
  'Supplies',
  'Ingredients',
  'Utilities',
  'Rent',
  'Salaries',
  'Maintenance',
  'Marketing',
  'Delivery',
  'Other',
]

const overviewCards = computed(() => [
  {
    label: 'Orders',
    value: summary.value.orders_count || 0,
    note: `${formatCompactCurrency(summary.value.avg_order_value)} average ticket`,
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

const managementCards = computed(() => [
  {
    title: 'Products',
    copy: 'Prices, availability, stock limits, recipes, and images.',
    meta: `${summary.value.product_count || 0} active items`,
    to: '/products',
    icon: 'mdi-hamburger',
    permission: 'products.view',
  },
  {
    title: 'Ingredients',
    copy: 'Ingredient catalog with branch stock and recipe usage.',
    meta: 'Branch stock aware',
    to: '/ingredients',
    icon: 'mdi-leaf-circle-outline',
    permission: 'ingredients.view',
  },
  {
    title: 'Recipes',
    copy: 'Ingredient quantities behind each sellable product.',
    meta: 'Cost and prep control',
    to: '/recipe',
    icon: 'mdi-silverware-fork-knife',
    permission: 'recipes.view',
  },
  {
    title: 'Menus',
    copy: 'Menu/category assignment and available modifiers.',
    meta: 'Customer and waiter menu flow',
    to: '/menus',
    icon: 'mdi-food-outline',
    permission: 'menu.view',
  },
  {
    title: 'Categories',
    copy: 'Groups, questions, and options used by menu items.',
    meta: 'Catalog structure',
    to: '/categories',
    icon: 'mdi-shape-outline',
    permission: 'categories.view',
  },
  {
    title: 'Inventory',
    copy: 'Counts, stock floors, product inventory, and movements.',
    meta: `${summary.value.low_stock_items?.length || 0} stock alerts`,
    to: '/inventory',
    icon: 'mdi-warehouse',
    permission: 'inventory.view',
    alert: (summary.value.low_stock_items?.length || 0) > 0,
  },
  {
    title: 'Employees',
    copy: 'Staff records, branch assignments, salaries, and performance.',
    meta: `${summary.value.employee_count || 0} employees`,
    to: '/employees',
    icon: 'mdi-badge-account-outline',
    permission: 'employees.view',
  },
  {
    title: 'Stock Alerts',
    copy: 'Low-stock ingredients that need purchasing or transfers.',
    meta: `${summary.value.low_stock_items?.length || 0} below minimum`,
    to: '/inventory',
    icon: 'mdi-alert-circle-outline',
    permission: 'inventory.view',
    alert: true,
  },
].filter((card) => auth.can(card.permission)))

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
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatCompactCurrency(value) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
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

function formatDateOnly(value) {
  if (!value) return 'N/A'

  return new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
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
  syncExpenseBranchSelection()
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

  syncExpenseBranchSelection()

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

function todayInput() {
  const now = new Date()
  const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
  return local.toISOString().slice(0, 10)
}

function syncExpenseBranchSelection() {
  const branchIds = branches.value.map((branch) => Number(branch.id))

  if (selectedBranch.value && branchIds.includes(Number(selectedBranch.value))) {
    expenseForm.value.branch_id = selectedBranch.value
    return
  }

  if (!branchIds.includes(Number(expenseForm.value.branch_id))) {
    expenseForm.value.branch_id = branches.value.length === 1 ? branches.value[0].id : null
  }
}

function resetExpenseFormAfterSave() {
  expenseForm.value = {
    branch_id: selectedBranch.value || expenseForm.value.branch_id,
    category: expenseForm.value.category || 'Operations',
    amount: null,
    expense_date: todayInput(),
    description: '',
  }
  syncExpenseBranchSelection()
}

function expenseValidationError() {
  if (!expenseForm.value.branch_id) return 'Choose a branch for this expense.'
  if (!expenseForm.value.category) return 'Choose an expense category.'
  if (Number(expenseForm.value.amount || 0) <= 0) return 'Expense amount must be greater than zero.'
  if (!expenseForm.value.expense_date) return 'Choose the expense date.'

  return ''
}

async function saveExpense() {
  const validationError = expenseValidationError()
  if (validationError) {
    expenseError.value = validationError
    return
  }

  if (savingExpense.value) return

  try {
    savingExpense.value = true
    expenseError.value = ''
    await axios.post(`${API_BASE_URL}/expenses`, {
      branch_id: expenseForm.value.branch_id,
      category: expenseForm.value.category,
      amount: Number(expenseForm.value.amount),
      expense_date: expenseForm.value.expense_date,
      description: expenseForm.value.description || null,
    }, {
      headers: authHeaders(),
    })
    resetExpenseFormAfterSave()
    await fetchDashboard()
  } catch (error) {
    expenseError.value = error?.response?.data?.message || 'Could not save expense.'
  } finally {
    savingExpense.value = false
  }
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

.management-panel {
  padding: 24px;
}

.management-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 14px;
}

.management-card {
  min-height: 172px;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: start;
  gap: 14px;
  padding: 16px;
  border: 1px solid var(--rs-border);
  border-radius: 18px;
  background: rgba(15, 24, 39, 0.72);
}

.management-card-alert {
  border-color: rgba(245, 158, 11, 0.36);
  background: linear-gradient(180deg, rgba(245, 158, 11, 0.09), rgba(15, 24, 39, 0.72));
}

.management-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  color: var(--rs-accent);
  background: var(--rs-accent-soft);
}

.management-title {
  color: var(--rs-text);
  font-weight: 800;
}

.management-copy {
  margin-top: 5px;
  color: var(--rs-text-soft);
  line-height: 1.45;
}

.management-meta {
  margin-top: 10px;
  color: var(--rs-text-muted);
  font-size: 0.85rem;
  font-weight: 700;
}

.management-action {
  grid-column: 1 / -1;
  justify-self: start;
  align-self: end;
}

.expense-form {
  display: grid;
  gap: 12px;
}

.expense-actions {
  display: flex;
  justify-content: flex-end;
}

.employee-cell {
  display: grid;
  gap: 4px;
}

.employee-cell strong {
  color: var(--rs-text);
}

.employee-cell span {
  color: var(--rs-text-soft);
  font-size: 0.86rem;
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

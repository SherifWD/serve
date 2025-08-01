<template>
  <OwnerLayout>
    <v-container class="py-8" fluid>
      <!-- Branch Filter -->
      <v-row>
        <v-col cols="12" md="3">
          <v-select
            v-model="selectedBranch"
            :items="branches"
            item-title="name"
            item-value="id"
            label="Select Branch"
            color="primary"
            dense
            clearable
            :menu-props="{ maxHeight: '250px' }"
            class="mb-4 branch-filter"
            @update:modelValue="fetchDashboard"
          />
        </v-col>
      </v-row>

      <!-- Stat Cards -->
      <v-row>
        <v-col cols="12" md="3" v-for="(card, i) in statsCards" :key="i">
          <v-card :class="['stat-card', card.class]" elevation="2">
            <v-card-title class="stat-title">{{ card.label }}</v-card-title>
            <v-card-text class="stat-value">
              <span v-if="!loading">{{ card.value }}</span>
              <v-skeleton-loader v-else type="heading" />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Data Tables Row -->
      <v-row class="mt-3" dense>
        <v-col cols="12" md="6" lg="8">
          <v-card class="main-card rounded-2xl" elevation="2">
            <v-card-title class="section-title">Recent Orders</v-card-title>
            <v-card-text>
              <v-data-table
                :headers="orderHeaders"
                :items="recentOrders"
                :items-per-page="5"
                class="elevation-0"
                dense
                style="border-radius:1rem;overflow:hidden;"
                item-value="id"
                :footer-props="{ 'items-per-page-options': [5, 10, 20] }"
                :loading="loading"
                loading-text="Loading orders..."
                no-data-text="No recent orders found."
              />
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6" lg="4">
          <v-card class="main-card rounded-2xl mb-4">
            <v-card-title class="section-title">Top Products</v-card-title>
            <v-card-text>
              <v-data-table
                :headers="topProductHeaders"
                :items="topProducts"
                :items-per-page="5"
                class="elevation-0"
                dense
                style="border-radius:1rem;overflow:hidden;"
                item-value="name"
                :footer-props="{ 'items-per-page-options': [5, 10] }"
                :loading="loading"
                loading-text="Loading top products..."
                no-data-text="No sales data yet."
              />
            </v-card-text>
          </v-card>

          <v-card class="main-card rounded-2xl">
            <v-card-title class="section-title">Low Inventory</v-card-title>
            <v-card-text>
              <v-data-table
                :headers="lowStockHeaders"
                :items="lowStockItems"
                :items-per-page="5"
                class="elevation-0"
                dense
                style="border-radius:1rem;overflow:hidden;"
                item-value="name"
                :footer-props="{ 'items-per-page-options': [5, 10] }"
                :loading="loading"
                loading-text="Loading inventory..."
                no-data-text="All stocks are healthy!"
              />
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const loading = ref(true)
const branches = ref([])
const selectedBranch = ref(null)
const stats = ref({
  total_sales: 0,
  orders_count: 0,
  avg_order_value: 0,
  branch_count: 0,
  product_count: 0,
})
const topProducts = ref([])
const lowStockItems = ref([])
const recentOrders = ref([])

const statsCards = computed(() => [
  {
    label: 'Total Revenue',
    value: 'EGP ' + (stats.value.total_sales ?? '--'),
    class: 'stat-green'
  },
  {
    label: 'Total Orders',
    value: stats.value.orders_count ?? '--',
    class: 'stat-slate'
  },
  {
    label: 'Avg Order Value',
    value: 'EGP ' + (stats.value.avg_order_value ?? '--'),
    class: 'stat-sage'
  },
  {
    label: 'Products',
    value: stats.value.product_count ?? '--',
    class: 'stat-slate'
  }
])

const orderHeaders = [
  { title: 'ID', key: 'id', align: 'start', width: '70px' },
  { title: 'Branch', key: 'branch.name', width: '140px' },
  { title: 'Total', key: 'total', width: '90px' },
  { title: 'Status', key: 'status', width: '100px' },
  { title: 'Date', key: 'created_at', width: '140px' }
]
const topProductHeaders = [
  { title: 'Product', key: 'name', align: 'start' },
  { title: 'Sold', key: 'quantity', width: '90px' }
]
const lowStockHeaders = [
  { title: 'Ingredient', key: 'name', align: 'start' },
  { title: 'Qty', key: 'stock', width: '80px' },
  { title: 'Unit', key: 'unit', width: '70px' }
]

async function fetchBranches() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = data.data || data
}

async function fetchDashboard() {
  loading.value = true
  try {
    const params = selectedBranch.value ? { branch_id: selectedBranch.value } : {}
    const { data } = await axios.get('http://localhost:8000/api/dashboard/summary', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
      params
    })
    stats.value = data
    topProducts.value = data.top_products || []
    lowStockItems.value = data.low_stock_items || []
    recentOrders.value = data.recent_orders || []
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchBranches()
  await fetchDashboard()
})
</script>

<style scoped>
body, .v-application { background: #232a2e !important; }
.v-container { max-width: 1600px; }
.branch-filter { min-width: 220px; }
.stat-card {
  border-radius: 2rem !important;
  padding: 1.1rem;
  min-height: 120px;
  display: flex; flex-direction: column; align-items: flex-start;
  background: #eef3ee !important;
  color: #232a2e;
  box-shadow: 0 4px 24px 0 #0001;
}
.stat-green { background: linear-gradient(90deg,#79bfa1 0%,#eef3ee 100%) !important; color: #1a4223; }
.stat-sage { background: linear-gradient(90deg,#eef3ee 0%,#b7dbcc 100%) !important; color: #24423c; }
.stat-slate { background: #eef3ee !important; color: #2a323c; }
.stat-title { font-size: 1.05rem; font-weight: bold; color: #2a9d8f; margin-bottom: 0.5em; letter-spacing: 0.03em; }
.stat-value { font-size: 2.2rem; font-weight: bold; margin-top: 0.1em; color: #232a2e; }
.section-title { color: #2a9d8f; font-size: 1.25rem; font-weight: bold; letter-spacing: 0.02em; }
.main-card { background: #232a2e !important; color: #eef3ee !important; border-radius: 2rem !important; }
.text-caption { color: #aaa; font-size: .95em; margin-top: .8em; }
</style>

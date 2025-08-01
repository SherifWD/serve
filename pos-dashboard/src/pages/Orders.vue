<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col>
          <v-card elevation="3" color="card" class="mb-8" style="border-radius: 2rem;">
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Orders</span>
              <v-spacer />
              <v-btn color="primary" variant="elevated" class="rounded-pill" size="large"
                     style="color:#181818;font-weight:bold;" @click="openAdd">
                <v-icon start>mdi-plus</v-icon>New Order
              </v-btn>
            </v-card-title>
            <v-card-text>
              <!-- Filter Bar -->
              <v-row class="mb-4" align="center" dense>
                <v-col cols="12" md="2">
                  <v-select v-model="filters.branch_id" :items="branches" item-title="name" item-value="id" label="Branch" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select v-model="filters.table_id" :items="tables" item-title="name" item-value="id" label="Table" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select v-model="filters.status" :items="statuses" label="Status" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-select v-model="filters.order_type" :items="orderTypes" label="Order Type" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-text-field v-model="filters.search" label="Search" dense clearable rounded />
                </v-col>
                <v-col cols="12" md="2">
                  <v-menu v-model="filters.dateMenu" :close-on-content-click="false" transition="scale-transition" offset-y min-width="auto">
                    <template #activator="{ on, attrs }">
                      <v-text-field
                        v-model="filters.date"
                        label="Order Date"
                        readonly
                        v-bind="attrs"
                        v-on="on"
                        dense
                        clearable
                        rounded
                        :value="filters.date ? filters.date : ''"
                        @click="filters.dateMenu = true"
                      />
                    </template>
                    <v-date-picker
                      v-model="filters.date"
                      @input="filters.dateMenu = false"
                      color="primary"
                      scrollable
                      no-title
                      :max="new Date().toISOString().substr(0, 10)"
                    />
                  </v-menu>
                </v-col>
              </v-row>

              <v-data-table
                :headers="headers"
                :items="filteredOrders"
                :items-per-page="10"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
              >
                <template #item.status="{ item }">
                  <v-chip :color="statusColor(item.status)" size="small" class="status-chip" text-color="white">{{ item.status }}</v-chip>
                </template>
                <template #item.order_type="{ item }">
                  <v-chip color="primary" size="small" class="order-type-chip" text-color="white">{{ item.order_type }}</v-chip>
                </template>
                <template #item.actions="{ item }">
                  <v-btn size="small" icon color="primary" class="rounded-xl" @click="viewOrder(item)">
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Add Order Drawer (with tabs) -->
      <v-navigation-drawer
        v-model="dialog"
        location="right"
        temporary
        width="430"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">New Order</v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="dialog = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
          <v-tab value="general">General Info</v-tab>
          <v-tab value="items">Items</v-tab>
        </v-tabs>
        <v-window v-model="tab">
          <!-- General Info Tab -->
          <v-window-item value="general">
            <v-form class="pa-6">
              <v-select label="Branch" :items="branches" item-title="name" item-value="id" v-model="form.branch_id" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select label="Table" :items="tables" item-title="name" item-value="id" v-model="form.table_id" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select label="Order Type" :items="orderTypes" v-model="form.order_type" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select label="Status" :items="statuses" v-model="form.status" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
            </v-form>
          </v-window-item>
          <!-- Items Tab -->
          <v-window-item value="items">
            <div class="pa-6">
              <h4 class="text-h6 mb-2">Items</h4>
              <div v-for="(item, i) in form.order_items" :key="i" class="d-flex align-center mb-2">
                <v-select
                  :items="products"
                  item-title="name"
                  item-value="id"
                  v-model="item.product_id"
                  label="Product"
                  class="mr-2 rounded-xl"
                  dense
                  style="min-width:120px;"
                />
                <v-text-field
                  v-model="item.quantity"
                  type="number"
                  min="1"
                  label="Qty"
                  class="mr-2 rounded-xl"
                  dense
                  style="width:70px;"
                />
                <v-text-field
                  v-model="item.price"
                  type="number"
                  min="0"
                  label="Price"
                  class="mr-2 rounded-xl"
                  dense
                  style="width:100px;"
                />
                <v-btn size="x-small" icon color="red" class="rounded-xl" @click="form.order_items.splice(i,1)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </div>
              <v-btn
                size="small"
                class="mt-2 mb-4 rounded-xl"
                color="success"
                @click="form.order_items.push({product_id:null, quantity:1, price:0})"
              >Add Item</v-btn>
              <v-btn
                color="primary"
                class="rounded-pill mt-2"
                block
                size="large"
                style="color:#181818;font-weight:bold;"
                @click="addOrder"
              >
                <v-icon start>mdi-check</v-icon>Save
              </v-btn>
            </div>
          </v-window-item>
        </v-window>
      </v-navigation-drawer>

      <!-- Order Detail Drawer -->
      <v-navigation-drawer
        v-model="detailDialog"
        location="right"
        temporary
        width="430"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            Order #{{ selectedOrder?.id }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="detailDialog = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6" v-if="selectedOrder">
          <div class="mb-3">
            <div>Status: <v-chip :color="statusColor(selectedOrder.status)" size="small" text-color="white" class="mr-2">{{ selectedOrder.status }}</v-chip></div>
            <div>Order Type: <v-chip color="primary" size="small" text-color="white">{{ selectedOrder.order_type }}</v-chip></div>
            <div>Table: <v-chip v-if="selectedOrder.table" color="success" size="small" text-color="white">{{ selectedOrder.table?.name }}</v-chip></div>
            <v-divider class="my-2"/>
            <h4 class="mb-2">Order Items</h4>
            <v-list>
              <v-list-item v-for="item in selectedOrder.items" :key="item.id">
                <v-list-item-title>
                  <v-chip color="success" size="small" class="mr-1" text-color="white">{{ item.product?.name }}</v-chip>
                  <span>Qty: <b>{{ item.quantity }}</b></span>
                  <span class="ml-2">Price: <b>{{ item.price }}</b></span>
                </v-list-item-title>
              </v-list-item>
            </v-list>
            <div class="order-summary-box mt-4">
              <div>Total: <b>{{ selectedOrder.total }}</b></div>
              <div v-if="selectedOrder.discount">Discount: <b>{{ selectedOrder.discount }}</b></div>
            </div>
          </div>
        </div>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const orders = ref([])
const branches = ref([])
const tables = ref([])
const products = ref([])
const dialog = ref(false)
const detailDialog = ref(false)
const selectedOrder = ref(null)
const tab = ref('general')
const statuses = ['pending', 'paid', 'cancelled', 'refunded']
const orderTypes = ['dine-in', 'takeaway', 'delivery']
const form = ref({
  branch_id: null,
  table_id: null,
  status: 'pending',
  order_type: 'dine-in',
  order_items: [{ product_id: null, quantity: 1, price: 0 }]
})

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Branch', key: 'branch.name' },
  { title: 'Table', key: 'table.name' },
  { title: 'Status', key: 'status' },
  { title: 'Type', key: 'order_type' },
  { title: 'Discount', key: 'discount' },
  { title: 'Total', key: 'total' },
  { title: 'Created At', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false }
]
const filters = ref({
  branch_id: null,
  table_id: null,
  status: null,
  order_type: null,
  date: null,
  dateMenu: false,
  search: ''
})
function dateToYMD(date) {
  if (!date) return null;
  const d = typeof date === 'string' ? new Date(date) : date;
  if (isNaN(d)) return null;
  return d.toISOString().slice(0, 10);
}

const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    if (filters.value.branch_id && order.branch_id !== filters.value.branch_id) return false
    if (filters.value.table_id && order.table_id !== filters.value.table_id) return false
    if (filters.value.status && order.status !== filters.value.status) return false
    if (filters.value.order_type && order.order_type !== filters.value.order_type) return false
    if (filters.value.date) {
      const filterDate = dateToYMD(filters.value.date);
      const orderDate = order.created_at?.slice(0, 10);
      if (filterDate !== orderDate) return false;
    }
    if (filters.value.search) {
      const text = `${order.id} ${order.total} ${order.status} ${(order.branch?.name || '')} ${(order.table?.name || '')}`.toLowerCase()
      if (!text.includes(filters.value.search.toLowerCase())) return false
    }
    return true
  })
})

function statusColor(status) {
  switch (status) {
    case 'pending': return 'orange';
    case 'paid': return 'success';
    case 'cancelled': return 'red';
    case 'refunded': return 'blue-grey';
    default: return 'grey';
  }
}

async function loadOrders() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/orders', {
    headers: { Authorization: `Bearer ${token}` }
  })
  orders.value = res.data.data || res.data
}

async function loadBranches() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = res.data.data || res.data
}
async function loadTables() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/tables', {
    headers: { Authorization: `Bearer ${token}` }
  })
  tables.value = res.data.data || res.data
}
async function loadProducts() {
  const token = localStorage.getItem('token')
  const res = await axios.get('http://localhost:8000/api/products', {
    headers: { Authorization: `Bearer ${token}` }
  })
  products.value = res.data.data || res.data
}

function openAdd() {
  form.value = { branch_id: null, table_id: null, status: 'pending', order_type: 'dine-in', order_items: [{ product_id: null, quantity: 1, price: 0 }] }
  tab.value = 'general'
  dialog.value = true
}
async function addOrder() {
  const token = localStorage.getItem('token')
  await axios.post('http://localhost:8000/api/orders', form.value, {
    headers: { Authorization: `Bearer ${token}` }
  })
  dialog.value = false
  loadOrders()
}

function viewOrder(order) {
  selectedOrder.value = order
  detailDialog.value = true
}

onMounted(() => {
  loadOrders()
  loadBranches()
  loadTables()
  loadProducts()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.status-chip { text-transform: capitalize; font-weight: bold; }
.order-type-chip { text-transform: capitalize; font-weight: bold; }
.order-summary-box {
  padding: 1rem;
  border-radius: 1.3rem;
  background: #f6f8f7;
  border: 1px solid #e0eee7;
  margin-top: 10px;
  color: #222;
  font-size: 1.1em;
}
</style>

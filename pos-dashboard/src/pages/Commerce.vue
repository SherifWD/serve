<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="4">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Customers</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.customers" @click="fetchCustomers">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.customers" type="error" variant="tonal" class="mb-4">
                {{ errors.customers }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.customers" @submit.prevent="createCustomer">
                <v-row dense>
                  <v-col cols="12">
                    <v-text-field v-model="forms.customer.code" label="Customer Code" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.customer.name" label="Customer Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.customer.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.customer.phone" label="Phone" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-select
                      v-model="forms.customer.status"
                      :items="customerStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.customers">
                      <v-icon start>mdi-account-plus</v-icon>
                      Add Customer
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="customerHeaders"
                :items="customers"
                :loading="loading.customers"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" :color="customerColor(item.status)" variant="flat">
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
              <span class="text-h6 font-weight-bold">Orders</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.orders" @click="fetchOrders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.orders" type="error" variant="tonal" class="mb-4">
                {{ errors.orders }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.orders" @submit.prevent="createOrder">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.order.order_number" label="Order Number" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.order.customer_id"
                      :items="customers"
                      item-title="name"
                      item-value="id"
                      label="Customer"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.order.status"
                      :items="orderStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.order.placed_at"
                      type="datetime-local"
                      label="Placed At"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.order.shipping_fee"
                      type="number"
                      label="Shipping Fee"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="forms.order.tax"
                      type="number"
                      label="Tax"
                      dense
                    />
                  </v-col>
                </v-row>

                <v-divider class="my-3" />
                <v-card-subtitle class="px-0 pb-2">Line Item</v-card-subtitle>
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.orderItem.name" label="Item Name" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.orderItem.sku" label="SKU" dense />
                  </v-col>
                  <v-col cols="12" md="2">
                    <v-text-field
                      v-model="forms.orderItem.quantity"
                      type="number"
                      label="Qty"
                      dense
                      min="0.001"
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field
                      v-model="forms.orderItem.unit_price"
                      type="number"
                      label="Unit Price"
                      dense
                      min="0"
                    />
                  </v-col>
                </v-row>
                <v-row dense>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.orders">
                      <v-icon start>mdi-cart-arrow-down</v-icon>
                      Create Order
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="orderHeaders"
                :items="orders"
                :loading="loading.orders"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.customer="{ item }">
                  {{ customerLookup[item.customer_id] ?? 'Guest Account' }}
                </template>
                <template #item.status="{ item }">
                  <v-chip size="small" :color="orderColor(item.status)" variant="flat">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #item.total="{ item }">
                  {{ formatCurrency(item.total) }}
                </template>
                <template #item.placed_at="{ item }">
                  {{ formatDate(item.placed_at) }}
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
import { computed, onMounted, reactive, ref } from 'vue'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const customers = ref([])
const orders = ref([])

const loading = reactive({
  customers: false,
  orders: false
})

const errors = reactive({
  customers: null,
  orders: null
})

const forms = reactive({
  customer: {
    code: '',
    name: '',
    email: '',
    phone: '',
    status: 'active'
  },
  order: {
    order_number: '',
    customer_id: null,
    status: 'pending',
    placed_at: '',
    shipping_fee: '',
    tax: ''
  },
  orderItem: {
    sku: '',
    name: '',
    quantity: '1',
    unit_price: ''
  }
})

const customerHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Status', key: 'status' }
]

const orderHeaders = [
  { title: 'Order #', key: 'order_number' },
  { title: 'Customer', key: 'customer' },
  { title: 'Status', key: 'status' },
  { title: 'Placed', key: 'placed_at' },
  { title: 'Total', key: 'total' }
]

const customerStatuses = ['active', 'inactive', 'prospect']
const orderStatuses = ['pending', 'paid', 'fulfilled', 'cancelled', 'refunded']

const customerLookup = computed(() => {
  const map = {}
  for (const customer of customers.value ?? []) {
    map[customer.id] = customer.name
  }
  return map
})

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

function customerColor(status) {
  const colors = {
    active: 'success',
    inactive: 'grey',
    prospect: 'info'
  }
  return colors[status] || 'primary'
}

function orderColor(status) {
  const colors = {
    pending: 'warning',
    paid: 'success',
    fulfilled: 'secondary',
    cancelled: 'grey',
    refunded: 'info'
  }
  return colors[status] || 'primary'
}

function formatCurrency(value) {
  const amount = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(amount)
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString()
}

async function fetchCustomers() {
  loading.customers = true
  errors.customers = null
  try {
    const { data } = await axios.get(`${API_BASE}/commerce/customers`, { headers: authHeaders() })
    customers.value = data.data || data
  } catch (error) {
    errors.customers = parseError(error)
  } finally {
    loading.customers = false
  }
}

async function createCustomer() {
  loading.customers = true
  errors.customers = null
  try {
    await axios.post(`${API_BASE}/commerce/customers`, forms.customer, { headers: authHeaders() })
    Object.assign(forms.customer, {
      code: '',
      name: '',
      email: '',
      phone: '',
      status: 'active'
    })
    await fetchCustomers()
  } catch (error) {
    errors.customers = parseError(error)
  } finally {
    loading.customers = false
  }
}

async function fetchOrders() {
  loading.orders = true
  errors.orders = null
  try {
    const { data } = await axios.get(`${API_BASE}/commerce/orders`, { headers: authHeaders() })
    orders.value = data.data || data
  } catch (error) {
    errors.orders = parseError(error)
  } finally {
    loading.orders = false
  }
}

async function createOrder() {
  if (!forms.order.order_number) {
    errors.orders = 'Provide an order number to create an order.'
    return
  }

  if (!forms.orderItem.name) {
    errors.orders = 'Define at least one line item.'
    return
  }

  loading.orders = true
  errors.orders = null

  try {
    const quantity = Number(forms.orderItem.quantity || 1)
    const unitPrice = Number(forms.orderItem.unit_price || 0)
    const lineTotal = quantity * unitPrice
    const shipping = forms.order.shipping_fee !== '' ? Number(forms.order.shipping_fee) : 0
    const tax = forms.order.tax !== '' ? Number(forms.order.tax) : 0

    const payload = {
      ...forms.order,
      shipping_fee: shipping,
      tax: tax,
      items: [
        {
          sku: forms.orderItem.sku || undefined,
          name: forms.orderItem.name,
          quantity,
          unit_price: unitPrice,
          line_total: lineTotal
        }
      ],
      subtotal: lineTotal,
      discount: 0,
      total: lineTotal + shipping + tax
    }

    if (!payload.placed_at) {
      payload.placed_at = new Date().toISOString().slice(0, 16)
    }

    await axios.post(`${API_BASE}/commerce/orders`, payload, { headers: authHeaders() })

    Object.assign(forms.order, {
      order_number: '',
      customer_id: null,
      status: 'pending',
      placed_at: '',
      shipping_fee: '',
      tax: ''
    })
    Object.assign(forms.orderItem, {
      sku: '',
      name: '',
      quantity: '1',
      unit_price: ''
    })

    await fetchOrders()
  } catch (error) {
    errors.orders = parseError(error)
  } finally {
    loading.orders = false
  }
}

onMounted(async () => {
  await Promise.all([fetchCustomers(), fetchOrders()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>


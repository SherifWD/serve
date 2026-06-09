<template>
  <OwnerLayout>
    <div class="inventory-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <div class="inventory-main">
        <v-container class="py-6 inventory-container" fluid>
          <v-card elevation="3" class="mb-8" color="card" style="border-radius: 2rem;">
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Inventory</span>
              <v-spacer />
              <v-btn color="secondary" variant="tonal" class="rounded-pill mr-3" size="large"
                     style="font-weight:bold;" @click="openOperationsDrawer">
                <v-icon start>mdi-swap-horizontal-bold</v-icon>Stock Operation
              </v-btn>
              <v-btn color="primary" variant="elevated" class="rounded-pill" size="large"
                     style="color:#181818;font-weight:bold;" @click="openAddDrawer">
                <v-icon start>mdi-plus</v-icon>Add Inventory Item
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field v-model="search" label="Search inventory..." class="mb-4" rounded variant="solo"
                            style="max-width:400px;" color="primary" />
              <v-row dense class="mb-4">
                <v-col cols="12" sm="6" lg="3">
                  <v-select
                    v-model="filters.branch_id"
                    :items="branches"
                    item-title="name"
                    item-value="id"
                    label="Branch"
                    clearable
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                  <v-select
                    v-model="filters.type"
                    :items="typeFilterItems"
                    label="Type"
                    clearable
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" sm="6" lg="3">
                  <v-select
                    v-model="filters.stock"
                    :items="stockFilterItems"
                    label="Stock"
                    clearable
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" sm="6" lg="3" class="d-flex align-center">
                  <v-btn variant="tonal" color="primary" class="rounded-pill" @click="resetFilters">
                    <v-icon start>mdi-filter-remove-outline</v-icon>Reset filters
                  </v-btn>
                </v-col>
              </v-row>
              <v-data-table
                :headers="headers"
                :items="filteredInventory"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
                :items-per-page="10"
              >
                <template #item.name="{ item }">
                  <span>
                    {{ item.ingredient ? item.ingredient.name : (item.product ? item.product.name : '-') }}
                  </span>
                </template>
                <template #item.type="{ item }">
                  <v-chip :color="item.ingredient ? 'blue' : (item.product ? 'green' : 'grey')" size="small" class="mr-1">
                    {{ item.ingredient ? 'Ingredient' : (item.product ? 'Product' : '-') }}
                  </v-chip>
                </template>
                <template #item.quantity="{ item }">
                  <span>
                    <v-chip v-if="item.quantity < item.min_stock" color="red" size="small" text-color="white" class="mr-1">Low</v-chip>
                    <span>{{ item.quantity }}</span>
                  </span>
                </template>
                <template #item.min_stock="{ item }">
                    {{ item.ingredient ? item.ingredient.min_stock : (item.product ? item.product.min_stock : '-') }}
                </template>
                <template #item.branch="{ item }">
                  <span>{{ branchName(item.branch_id) }}</span>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon class="rounded-xl" size="small" color="accent" @click="openViewDrawer(item)">
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteInventoryItem(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-container>
      </div>

      <!-- Add/Edit Drawer -->
      <v-navigation-drawer
        v-model="rightDrawer"
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
            {{ drawerTitle }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
          <v-tab value="general">General Info</v-tab>
          <v-tab value="stock">Stock Adjustment</v-tab>
        </v-tabs>
        <v-window v-model="tab">
          <!-- General Info Tab -->
          <v-window-item value="general">
            <v-form class="pa-6" @submit.prevent="saveInventoryItem">
              <v-select
                v-model="inventoryType"
                :items="[{ title: 'Ingredient', value: 'ingredient' }, { title: 'Product', value: 'product' }]"
                label="Inventory Type"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-if="inventoryType === 'ingredient'"
                label="Ingredient"
                :items="ingredients"
                item-title="name"
                item-value="id"
                v-model="drawerForm.ingredient_id"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-if="inventoryType === 'product'"
                label="Product"
                :items="products"
                item-title="name"
                item-value="id"
                v-model="drawerForm.product_id"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-text-field label="Unit" v-model="drawerForm.unit" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-text-field label="Quantity" v-model="drawerForm.quantity" type="number" min="0" required variant="outlined" color="primary"
                            class="mb-4 rounded-xl" hint="Current total available" persistent-hint />
              <v-select label="Branch" :items="branches" item-title="name" item-value="id" v-model="drawerForm.branch_id" required
                        variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-text-field label="Min Stock" v-model="drawerForm.min_stock" type="number" min="0" required variant="outlined" color="primary"
                            class="mb-4 rounded-xl" hint="Show warning when stock goes below this value" persistent-hint />
              <v-btn color="primary" class="rounded-pill mt-2" block size="large" style="color:#181818;font-weight:bold;" type="submit">
                <v-icon start>mdi-check</v-icon>{{ isEditing ? 'Update' : 'Add' }}
              </v-btn>
            </v-form>
          </v-window-item>
          <v-window-item value="stock">
            <v-form class="pa-6" @submit.prevent="submitInventoryOperation">
              <v-alert v-if="operationMessage" :type="operationMessage.type" border="start" variant="tonal" class="mb-4">
                {{ operationMessage.text }}
              </v-alert>
              <v-select
                v-model="operationForm.type"
                :items="operationTypes"
                item-title="title"
                item-value="value"
                label="Operation"
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-model="operationForm.branch_id"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Source branch"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-if="operationForm.type === 'transfer'"
                v-model="operationForm.to_branch_id"
                :items="targetBranches"
                item-title="name"
                item-value="id"
                label="Target branch"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-if="operationForm.type === 'receive'"
                v-model="operationForm.supplier_id"
                :items="suppliers"
                item-title="name"
                item-value="id"
                label="Supplier"
                clearable
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-select
                v-model="operationForm.inventory_item_id"
                :items="operationInventoryItems"
                item-title="label"
                item-value="id"
                label="Inventory item"
                required
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-row dense>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-if="operationForm.type === 'count'"
                    v-model="operationForm.counted_quantity"
                    label="Counted quantity"
                    type="number"
                    min="0"
                    required
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                  <v-text-field
                    v-else-if="operationForm.type === 'adjust' && operationForm.adjustment_operation === 'adjustment'"
                    v-model="operationForm.target_quantity"
                    label="Target quantity"
                    type="number"
                    min="0"
                    required
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                  <v-text-field
                    v-else
                    v-model="operationForm.quantity"
                    :label="operationForm.type === 'adjust' ? 'Adjustment quantity' : 'Quantity'"
                    type="number"
                    min="0.001"
                    required
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field
                    v-if="operationForm.type === 'receive'"
                    v-model="operationForm.unit_cost"
                    label="Unit cost"
                    type="number"
                    min="0"
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                  <v-select
                    v-else-if="operationForm.type === 'adjust'"
                    v-model="operationForm.adjustment_operation"
                    :items="adjustmentOperations"
                    item-title="title"
                    item-value="value"
                    label="Adjustment type"
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                  <v-text-field
                    v-else
                    v-model="operationForm.reference_code"
                    label="Reference"
                    variant="outlined"
                    color="primary"
                    class="mb-4 rounded-xl"
                  />
                </v-col>
              </v-row>
              <v-text-field
                v-if="operationForm.type === 'receive' || operationForm.type === 'adjust'"
                v-model="operationForm.reference_code"
                label="Reference"
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-textarea
                v-model="operationForm.reason"
                :label="operationForm.type === 'receive' || operationForm.type === 'transfer' ? 'Notes' : 'Reason'"
                rows="2"
                auto-grow
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
              />
              <v-btn
                color="primary"
                class="rounded-pill"
                block
                size="large"
                style="color:#181818;font-weight:bold;"
                type="submit"
                :loading="operationLoading"
              >
                <v-icon start>mdi-check</v-icon>Post Operation
              </v-btn>
            </v-form>
          </v-window-item>
        </v-window>
      </v-navigation-drawer>

      <!-- View Drawer (Read Only) -->
      <v-navigation-drawer
        v-model="viewDrawer"
        location="right"
        temporary
        width="410"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            Inventory Item Details
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="viewDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6">
          <div class="mb-5">
            <div class="text-h6 font-weight-bold mb-2" style="color:#2a9d8f;">
              {{ viewInventoryItem.ingredient ? viewInventoryItem.ingredient.name : (viewInventoryItem.product ? viewInventoryItem.product.name : '-') }}
            </div>
            <div class="my-2" style="color:#fff;">
              <v-icon size="small" color="primary" class="mr-1">mdi-home</v-icon>
              <span>Branch: </span><b style="color:#fff;">{{ branchName(viewInventoryItem.branch_id) }}</b>
            </div>
            <div class="my-2" style="color:#fff;">
              <v-icon size="small" color="primary" class="mr-1">mdi-cash</v-icon>
              <span>Quantity: </span>
              <b style="color:#fff;">
                {{ viewInventoryItem.quantity }}
                <v-chip v-if="viewInventoryItem.quantity < viewInventoryItem.min_stock" color="red" size="x-small" text-color="white" class="ml-2">Low</v-chip>
              </b>
            </div>
            <div class="my-2" style="color:#2a9d8f;">
              <v-icon size="small" color="primary" class="mr-1">mdi-check-circle</v-icon>
              <span>Min Stock: </span>
              <b style="color:#fff;">{{ viewInventoryItem.min_stock }}</b>
            </div>
            <v-divider class="my-4" />
            <div class="text-caption" style="color:#bbb;">
              ID: <span class="ml-1">{{ viewInventoryItem.id }}</span>
            </div>
          </div>
        </div>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import { confirmDelete } from '../lib/confirmDelete'
import { missingField, showValidationAlert } from '../lib/validationAlert'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const inventoryItems = ref([])
const rightDrawer = ref(false)
const viewDrawer = ref(false)
const drawerTitle = ref('Add Inventory Item')
const isEditing = ref(false)
const tab = ref('general')
const inventoryType = ref('ingredient')
const drawerForm = ref({
  id: null, ingredient_id: null, product_id: null, unit: '', quantity: '', min_stock: '', branch_id: ''
})
const branches = ref([])
const ingredients = ref([])
const products = ref([])
const suppliers = ref([])
const viewInventoryItem = ref({})
const search = ref('')
const operationLoading = ref(false)
const operationMessage = ref(null)
const filters = ref({
  branch_id: null,
  type: null,
  stock: null,
})

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Type', value: 'type' },
  { title: 'Branch', value: 'branch' },
  { title: 'Quantity', value: 'quantity' },
  { title: 'Unit', value: 'unit' },
  { title: 'Min Stock', value: 'min_stock' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const typeFilterItems = [
  { title: 'Ingredient', value: 'ingredient' },
  { title: 'Product', value: 'product' },
]

const stockFilterItems = [
  { title: 'Low stock', value: 'low' },
  { title: 'Healthy stock', value: 'healthy' },
]

const operationTypes = [
  { title: 'Receive purchase', value: 'receive' },
  { title: 'Transfer between branches', value: 'transfer' },
  { title: 'Stock count', value: 'count' },
  { title: 'Manual adjustment', value: 'adjust' },
  { title: 'Wastage', value: 'waste' },
]

const adjustmentOperations = [
  { title: 'Set exact quantity', value: 'adjustment' },
  { title: 'Restock', value: 'restock' },
  { title: 'Use', value: 'use' },
  { title: 'Return to stock', value: 'return' },
  { title: 'Comp', value: 'comp' },
]

const operationForm = ref(defaultOperationForm())

const filteredInventory = computed(() => {
  const q = search.value.toLowerCase()
  return inventoryItems.value.filter(item =>
    matchesInventorySearch(item, q) &&
    (!filters.value.branch_id || Number(item.branch_id) === Number(filters.value.branch_id)) &&
    matchesInventoryType(item) &&
    matchesStockFilter(item)
  )
})

const targetBranches = computed(() =>
  branches.value.filter(branch => {
    const source = branches.value.find(row => Number(row.id) === Number(operationForm.value.branch_id))
    return Number(branch.id) !== Number(operationForm.value.branch_id) &&
      (!source?.restaurant_id || Number(branch.restaurant_id) === Number(source.restaurant_id))
  })
)

const operationInventoryItems = computed(() =>
  inventoryItems.value
    .filter(item => !operationForm.value.branch_id || Number(item.branch_id) === Number(operationForm.value.branch_id))
    .map(item => ({
      id: item.id,
      label: `${inventoryName(item)} · ${branchName(item.branch_id)} · ${item.quantity} ${item.unit || ''}`.trim(),
    }))
)

function defaultOperationForm() {
  return {
    type: 'receive',
    branch_id: null,
    to_branch_id: null,
    supplier_id: null,
    inventory_item_id: null,
    quantity: '',
    counted_quantity: '',
    target_quantity: '',
    unit_cost: '',
    adjustment_operation: 'adjustment',
    reference_code: '',
    reason: '',
  }
}

function inventoryName(item) {
  return item.ingredient?.name || item.product?.name || item.name || ''
}

function matchesInventorySearch(item, query) {
  if (!query) return true
  return inventoryName(item).toLowerCase().includes(query) ||
    branchName(item.branch_id).toLowerCase().includes(query) ||
    (item.unit || '').toLowerCase().includes(query)
}

function matchesInventoryType(item) {
  if (!filters.value.type) return true
  return filters.value.type === 'ingredient' ? !!item.ingredient : !!item.product
}

function matchesStockFilter(item) {
  if (!filters.value.stock) return true
  const isLow = Number(item.quantity ?? 0) <= Number(item.min_stock ?? 0)
  return filters.value.stock === 'low' ? isLow : !isLow
}

function resetFilters() {
  search.value = ''
  filters.value = {
    branch_id: null,
    type: null,
    stock: null,
  }
}

function branchName(id) {
  return branches.value.find(b => Number(b.id) === Number(id))?.name || '—'
}

async function loadBranches() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/branches`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    branches.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load branches', err)
  }
}
async function loadIngredients() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/ingredients`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    ingredients.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load ingredients', err)
  }
}
async function loadProducts() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/products`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    products.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load products', err)
  }
}
async function loadSuppliers() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/suppliers`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    suppliers.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load suppliers', err)
  }
}
async function loadInventoryItems() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/inventory-items`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    inventoryItems.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load inventory items', err) }
}

function openOperationsDrawer() {
  isEditing.value = false
  tab.value = 'stock'
  drawerTitle.value = 'Stock Operation'
  operationForm.value = defaultOperationForm()
  operationForm.value.branch_id = filters.value.branch_id || branches.value[0]?.id || null
  operationMessage.value = null
  rightDrawer.value = true
}

function openAddDrawer() {
  isEditing.value = false
  tab.value = 'general'
  drawerTitle.value = 'Add Inventory Item'
  inventoryType.value = 'ingredient'
  drawerForm.value = { id: null, ingredient_id: null, product_id: null, unit: '', quantity: '', min_stock: '', branch_id: '' }
  rightDrawer.value = true
}

function openEditDrawer(item) {
  isEditing.value = true
  tab.value = 'general'
  drawerTitle.value = 'Edit Inventory Item'
  if (item.ingredient) {
    inventoryType.value = 'ingredient'
    drawerForm.value = {
      id: item.id,
      ingredient_id: item.ingredient.id,
      product_id: null,
      unit: item.unit,
      quantity: item.quantity,
      min_stock: item.min_stock,
      branch_id: item.branch_id
    }
  } else if (item.product) {
    inventoryType.value = 'product'
    drawerForm.value = {
      id: item.id,
      ingredient_id: null,
      product_id: item.product.id,
      unit: item.unit,
      quantity: item.quantity,
      min_stock: item.min_stock,
      branch_id: item.branch_id
    }
  }
  rightDrawer.value = true
}

function openViewDrawer(item) {
  viewInventoryItem.value = { ...item }
  viewDrawer.value = true
}

async function saveInventoryItem() {
  const validationFields = inventoryItemValidationFields()
  if (validationFields.length) {
    await showValidationAlert(validationFields, { title: 'Complete inventory item details' })
    return
  }

  const token = localStorage.getItem('token')
  const data = {
    ...drawerForm.value,
    ingredient_id: inventoryType.value === 'ingredient' ? drawerForm.value.ingredient_id : null,
    product_id: inventoryType.value === 'product' ? drawerForm.value.product_id : null,
  }
  const url = isEditing.value
    ? `${API_BASE_URL}/inventory-items/${drawerForm.value.id}`
    : `${API_BASE_URL}/inventory-items`
  const method = isEditing.value ? 'put' : 'post'
  await axios[method](url, data, { headers: { Authorization: `Bearer ${token}` } })
  rightDrawer.value = false
  loadInventoryItems()
}

function inventoryItemValidationFields() {
  return [
    missingField('Inventory Type', ['ingredient', 'product'].includes(inventoryType.value)),
    missingField('Ingredient', inventoryType.value !== 'ingredient' || Boolean(drawerForm.value.ingredient_id)),
    missingField('Product', inventoryType.value !== 'product' || Boolean(drawerForm.value.product_id)),
    missingField('Unit', Boolean(drawerForm.value.unit)),
    missingField('Quantity', hasNumberAtLeast(drawerForm.value.quantity, 0)),
    missingField('Branch', Boolean(drawerForm.value.branch_id)),
    missingField('Min Stock', hasNumberAtLeast(drawerForm.value.min_stock, 0)),
  ].filter(Boolean)
}

async function deleteInventoryItem(item) {
  if (!await confirmDelete(item.ingredient ? item.ingredient.name : item.product?.name || 'item')) return
  const token = localStorage.getItem('token')
  await axios.delete(`${API_BASE_URL}/inventory-items/${item.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadInventoryItems()
}

onMounted(() => {
  loadBranches()
  loadIngredients()
  loadProducts()
  loadSuppliers()
  loadInventoryItems()
})

watch(inventoryType, (val) => {
  // Reset the other field to prevent both being filled
  if (val === 'ingredient') {
    drawerForm.value.product_id = null
  } else {
    drawerForm.value.ingredient_id = null
  }
})

watch(() => operationForm.value.branch_id, () => {
  operationForm.value.inventory_item_id = null
  operationForm.value.to_branch_id = null
})

watch(() => operationForm.value.type, () => {
  operationMessage.value = null
  operationForm.value.quantity = ''
  operationForm.value.counted_quantity = ''
  operationForm.value.target_quantity = ''
  operationForm.value.unit_cost = ''
  operationForm.value.adjustment_operation = 'adjustment'
})

function numericValue(value, fallback = 0) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : fallback
}

function operationHeaders() {
  const token = localStorage.getItem('token')
  return { Authorization: `Bearer ${token}` }
}

function operationReference(prefix) {
  return operationForm.value.reference_code || `${prefix}-${new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14)}`
}

async function submitInventoryOperation() {
  const validationFields = inventoryOperationValidationFields()
  if (validationFields.length) {
    await showValidationAlert(validationFields, { title: 'Complete stock operation details' })
    return
  }

  operationLoading.value = true
  operationMessage.value = null

  try {
    const form = operationForm.value
    const item = inventoryItems.value.find(row => Number(row.id) === Number(form.inventory_item_id))
    if (!item) throw new Error('Select an inventory item.')

    let url = ''
    let payload = {}

    if (form.type === 'receive') {
      url = `${API_BASE_URL}/inventory-operations/purchase-receipts`
      payload = {
        branch_id: form.branch_id,
        supplier_id: form.supplier_id || null,
        reference_code: operationReference('PO'),
        notes: form.reason || null,
        items: [{
          inventory_item_id: form.inventory_item_id,
          quantity: numericValue(form.quantity),
          unit_cost: numericValue(form.unit_cost),
        }],
      }
    } else if (form.type === 'transfer') {
      url = `${API_BASE_URL}/inventory-operations/transfers`
      payload = {
        from_branch_id: form.branch_id,
        to_branch_id: form.to_branch_id,
        reference_code: operationReference('TR'),
        notes: form.reason || null,
        items: [{
          inventory_item_id: form.inventory_item_id,
          quantity: numericValue(form.quantity),
        }],
      }
    } else if (form.type === 'count') {
      url = `${API_BASE_URL}/inventory-operations/stock-counts`
      payload = {
        branch_id: form.branch_id,
        reference_code: operationReference('CNT'),
        notes: form.reason || null,
        items: [{
          inventory_item_id: form.inventory_item_id,
          counted_quantity: numericValue(form.counted_quantity),
          reason: form.reason || null,
        }],
      }
    } else if (form.type === 'adjust') {
      url = `${API_BASE_URL}/inventory-operations/adjustments`
      payload = {
        branch_id: form.branch_id,
        inventory_item_id: form.inventory_item_id,
        operation: form.adjustment_operation,
        reference_code: operationReference('ADJ'),
        reason: form.reason || null,
      }
      if (form.adjustment_operation === 'adjustment') {
        payload.target_quantity = numericValue(form.target_quantity)
      } else {
        payload.quantity = numericValue(form.quantity)
      }
    } else if (form.type === 'waste') {
      url = `${API_BASE_URL}/inventory-operations/wastage`
      payload = {
        branch_id: form.branch_id,
        inventory_item_id: form.inventory_item_id,
        quantity: numericValue(form.quantity),
        reference_code: operationReference('WST'),
        reason: form.reason || 'Wastage',
      }
    }

    await axios.post(url, payload, { headers: operationHeaders() })
    operationMessage.value = { type: 'success', text: `${operationTypes.find(type => type.value === form.type)?.title || 'Operation'} posted.` }
    await loadInventoryItems()
    operationForm.value = { ...defaultOperationForm(), type: form.type, branch_id: form.branch_id }
  } catch (err) {
    const text = err.response?.data?.message || err.response?.data?.error || Object.values(err.response?.data?.errors || {})?.flat()?.[0] || err.message || 'Operation failed.'
    operationMessage.value = { type: 'error', text }
  } finally {
    operationLoading.value = false
  }
}

function inventoryOperationValidationFields() {
  const form = operationForm.value

  return [
    missingField('Operation', Boolean(form.type)),
    missingField('Source branch', Boolean(form.branch_id)),
    missingField('Target branch', form.type !== 'transfer' || Boolean(form.to_branch_id)),
    missingField('Inventory item', Boolean(form.inventory_item_id)),
    missingField('Quantity', ['receive', 'transfer', 'waste'].includes(form.type) ? hasNumberAtLeast(form.quantity, 0.001) : true),
    missingField('Adjustment quantity', form.type !== 'adjust' || form.adjustment_operation === 'adjustment' || hasNumberAtLeast(form.quantity, 0.001)),
    missingField('Counted quantity', form.type !== 'count' || hasNumberAtLeast(form.counted_quantity, 0)),
    missingField('Target quantity', form.type !== 'adjust' || form.adjustment_operation !== 'adjustment' || hasNumberAtLeast(form.target_quantity, 0)),
    missingField('Unit cost', form.type !== 'receive' || form.unit_cost === '' || form.unit_cost === null || hasNumberAtLeast(form.unit_cost, 0)),
  ].filter(Boolean)
}

function hasNumberAtLeast(value, min) {
  if (value === '' || value === null || value === undefined) return false
  const numeric = Number(value)
  return Number.isFinite(numeric) && numeric >= min
}
</script>

<style scoped>
.inventory-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.inventory-main {
  flex: 1 1 auto;
  max-width: 100%;
  transition: max-width 0.35s cubic-bezier(.4,0,.2,1);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.inventory-layout.drawer-open .inventory-main {
  max-width: calc(100% - 430px);
}
.inventory-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
</style>

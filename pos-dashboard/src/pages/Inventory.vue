<template>
  <OwnerLayout>
    <div class="inventory-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <div class="inventory-main">
        <v-container class="py-6 inventory-container" fluid>
          <v-card elevation="3" class="mb-8" color="card" style="border-radius: 2rem;">
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Inventory</span>
              <v-spacer />
              <v-btn color="primary" variant="elevated" class="rounded-pill" size="large"
                     style="color:#181818;font-weight:bold;" @click="openAddDrawer">
                <v-icon start>mdi-plus</v-icon>Add Inventory Item
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field v-model="search" label="Search inventory..." class="mb-4" rounded variant="solo"
                            style="max-width:400px;" color="primary" />
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
          <!-- Stock Adjustment Tab (future feature) -->
          <v-window-item value="stock">
            <div class="pa-6 text-grey-darken-1">
              <v-alert type="info" border="start" color="primary" variant="tonal" class="mb-4">
                Coming soon: Move stock between branches, log movements, and adjustments here!
              </v-alert>
              <div class="text-caption">
                <v-icon color="primary" size="small" class="mr-1">mdi-information</v-icon>
                Track stock movements and branch transfers in this section for better audit and control.
              </div>
            </div>
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
const viewInventoryItem = ref({})
const search = ref('')

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Type', value: 'type' },
  { title: 'Branch', value: 'branch' },
  { title: 'Quantity', value: 'quantity' },
  { title: 'Unit', value: 'unit' },
  { title: 'Min Stock', value: 'min_stock' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredInventory = computed(() => {
  if (!search.value) return inventoryItems.value
  const q = search.value.toLowerCase()
  return inventoryItems.value.filter(item =>
    (item.ingredient ? item.ingredient.name.toLowerCase().includes(q) : false) ||
    (item.product ? item.product.name.toLowerCase().includes(q) : false)
  )
})

function branchName(id) {
  return branches.value.find(b => b.id === id)?.name || '—'
}

async function loadBranches() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/branches', {
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
    const res = await axios.get('http://localhost:8000/api/ingredients', {
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
    const res = await axios.get('http://localhost:8000/api/products', {
      headers: { Authorization: `Bearer ${token}` },
    })
    products.value = res.data.data || res.data
  } catch (err) {
    console.error('Failed to load products', err)
  }
}
async function loadInventoryItems() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get('http://localhost:8000/api/inventory-items', {
      headers: { Authorization: `Bearer ${token}` }
    })
    inventoryItems.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load inventory items', err) }
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
  const token = localStorage.getItem('token')
  const data = {
    ...drawerForm.value,
    ingredient_id: inventoryType.value === 'ingredient' ? drawerForm.value.ingredient_id : null,
    product_id: inventoryType.value === 'product' ? drawerForm.value.product_id : null,
  }
  const url = isEditing.value
    ? `http://localhost:8000/api/inventory-items/${drawerForm.value.id}`
    : 'http://localhost:8000/api/inventory-items'
  const method = isEditing.value ? 'put' : 'post'
  await axios[method](url, data, { headers: { Authorization: `Bearer ${token}` } })
  rightDrawer.value = false
  loadInventoryItems()
}

async function deleteInventoryItem(item) {
  if (!confirm(`Delete ${item.ingredient ? item.ingredient.name : item.product?.name || 'item'}?`)) return
  const token = localStorage.getItem('token')
  await axios.delete(`http://localhost:8000/api/inventory-items/${item.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadInventoryItems()
}

onMounted(() => {
  loadBranches()
  loadIngredients()
  loadProducts()
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

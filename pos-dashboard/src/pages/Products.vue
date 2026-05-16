<template>
  <OwnerLayout>
    <div class="products-layout" :class="{ 'drawer-open': rightDrawer || viewDrawer }">
      <div class="products-main">
        <v-container class="py-6 products-container" fluid>
          <v-card elevation="3" class="mb-8" color="card" style="border-radius: 2rem;">
            <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
              <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Products</span>
              <v-spacer />
              <v-btn color="primary" variant="elevated" class="rounded-pill" size="large" style="color:#181818;font-weight:bold;" @click="openAddDrawer">
                <v-icon start>mdi-plus</v-icon>Add Product
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-text-field
                v-model="search"
                label="Search products..."
                class="mb-4"
                rounded
                variant="solo"
                style="max-width:400px;"
                color="primary"
              />
              <v-data-table
                :headers="headers"
                :items="filteredProducts"
                class="rounded-xl"
                style="border-radius:1.5rem;overflow:hidden;"
                density="comfortable"
                item-value="id"
                :items-per-page="10"
              >
                <template #item.category.name="{ item }">
                  <v-chip color="success" size="small" text-color="white" v-if="item.category">{{ item.category.name }}</v-chip>
                </template>
                <template #item.branch.name="{ item }">
                  <v-chip color="primary" size="small" text-color="white" v-if="item.branch">{{ item.branch.name }}</v-chip>
                </template>
                <template #item.is_available="{ item }">
                  <v-chip :color="item.is_available ? 'success' : 'red'" size="small" text-color="white">
                    {{ item.is_available ? 'Available' : 'Unavailable' }}
                  </v-chip>
                </template>
                <template #item.actions="{ item }">
                  <v-btn icon class="rounded-xl" size="small" color="accent" @click="openViewDrawer(item)">
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteProduct(item)">
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-container>
      </div>

      <!-- Add/Edit Drawer with Tabs -->
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
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">{{ drawerTitle }}</v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <v-form @submit.prevent="saveProduct">
          <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
            <v-tab value="general">General Info</v-tab>
            <v-tab value="recipe">Recipe</v-tab>
          </v-tabs>
          <v-window v-model="tab">
            <!-- General Info Tab -->
            <v-window-item value="general">
              <div class="pa-6">
              <v-text-field label="Name" v-model="drawerForm.name" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-select label="Category" :items="categoryItems" item-title="name" item-value="id" v-model="drawerForm.category_id" required variant="outlined" color="primary" class="mb-4 rounded-xl" :menu-props="{ contentClass: 'dashboard-select-menu' }" />
              <v-select label="Branch" :items="branches" item-title="name" item-value="id" v-model="drawerForm.branch_id" required variant="outlined" color="primary" class="mb-4 rounded-xl" :menu-props="{ contentClass: 'dashboard-select-menu' }" @update:model-value="onBranchChange" />
              <v-text-field label="Price" v-model="drawerForm.price" type="number" required variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-text-field label="Minimum stock" v-model="drawerForm.min_stock" type="number" min="0" variant="outlined" color="primary" class="mb-4 rounded-xl" />
              <v-switch label="Available" v-model="drawerForm.is_available" color="primary" inset class="mb-6" style="--v-switch-track-color: #2a9d8f;" />
              </div>
            </v-window-item>
            <!-- Recipe Tab -->
            <v-window-item value="recipe">
              <div class="pa-6">
              <v-select
                label="Recipe"
                :items="filteredRecipes"
                :item-title="recipeTitle"
                item-value="id"
                v-model="drawerForm.recipe_id"
                clearable
                variant="outlined"
                color="primary"
                class="mb-4 rounded-xl"
                :menu-props="{ contentClass: 'dashboard-select-menu' }"
                @update:model-value="onRecipeChange"
              />
              <!-- Recipe preview below -->
              <div v-if="selectedRecipe" class="recipe-preview pa-4 mb-4">
                <div class="mb-2">
                  <span class="font-weight-bold" style="color:#2a9d8f;">Description:</span>
                  <span style="color:#333;">{{ selectedRecipe.description }}</span>
                </div>
                <div v-if="selectedRecipe.ingredients && selectedRecipe.ingredients.length">
                  <span class="font-weight-bold" style="color:#2a9d8f;">Ingredients:</span>
                  <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;">
                    <v-chip v-for="(ing, idx) in selectedRecipe.ingredients" :key="idx" color="success" variant="elevated" class="ingredient-chip">
                      <b>{{ ing.name }}</b>
                      <span v-if="ing.pivot">&nbsp;— {{ ing.pivot.quantity }} {{ ing.unit || '' }}</span>
                    </v-chip>
                  </div>
                </div>
                <div v-else class="text-caption" style="color:#bbb;">No ingredients assigned to this recipe.</div>
              </div>
              </div>
            </v-window-item>
          </v-window>

          <div class="px-6 pb-6">
            <v-alert
              v-if="formError"
              type="error"
              variant="tonal"
              class="mb-4"
            >
              {{ formError }}
            </v-alert>
            <v-btn
              color="primary"
              class="rounded-pill mt-2"
              block
              size="large"
              style="color:#181818;font-weight:bold;"
              type="submit"
              :disabled="!canSaveProduct"
            >
              <v-icon start>mdi-check</v-icon>{{ isEditing ? 'Update' : 'Add' }}
            </v-btn>
          </div>
        </v-form>
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
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">Product Details</v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="viewDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />
        <div class="pa-6">
          <div class="mb-5">
            <div class="text-h6 font-weight-bold mb-2" style="color:#2a9d8f;">{{ viewProduct.name }}</div>
            <div class="my-2" v-if="viewProduct.category"><v-chip color="success" size="small" text-color="white">{{ viewProduct.category.name }}</v-chip></div>
            <div class="my-2" v-if="viewProduct.branch"><v-chip color="primary" size="small" text-color="white">{{ viewProduct.branch.name }}</v-chip></div>
            <div class="my-2" style="color:#2a9d8f;"><v-icon size="small" color="primary" class="mr-1">mdi-cash</v-icon><span>Price: </span><b style="color:#fff;">{{ viewProduct.price }}</b></div>
            <div class="my-2">
              <v-chip :color="viewProduct.is_available ? 'success' : 'red'" size="small" text-color="white">
                {{ viewProduct.is_available ? 'Available' : 'Unavailable' }}
              </v-chip>
            </div>
            <v-divider class="my-4" />
            <div class="recipe-container">
              <h3 style="margin-bottom:4px;">Recipe:</h3>
              <p>{{ viewProduct.recipe?.description || 'No description available.' }}</p>
              <div v-if="viewProduct.recipe?.ingredients && viewProduct.recipe.ingredients.length">
                <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:7px;">
                  <v-chip v-for="(ingredient, index) in viewProduct.recipe.ingredients" :key="index" color="success" size="small" text-color="white">
                    <b>{{ ingredient.name }}</b>
                    <span v-if="ingredient.pivot">&nbsp;— {{ ingredient.pivot.quantity }} {{ ingredient.unit }}</span>
                  </v-chip>
                </div>
              </div>
            </div>
            <v-divider class="my-4" />
            <div class="text-caption" style="color:#bbb;">ID: <span class="ml-1">{{ viewProduct.id }}</span></div>
          </div>
        </div>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const products = ref([])
const categories = ref([])
const branches = ref([])
const recipes = ref([])
const rightDrawer = ref(false)
const viewDrawer = ref(false)
const drawerTitle = ref('Add Product')
const isEditing = ref(false)
const tab = ref('general')
const drawerForm = ref({
  id: null,
  name: '',
  category_id: null,
  price: '',
  branch_id: null,
  is_available: true,
  min_stock: 0,
  recipe_id: null,
})
const viewProduct = ref({})
const search = ref('')
const formError = ref('')

const headers = [
  { text: 'Name', value: 'name' },
  { title: 'Category', value: 'category.name' },
  { title: 'Branch', value: 'branch.name' },
  { title: 'Price', value: 'price' },
  { title: 'Available', value: 'is_available' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredProducts = computed(() => {
  if (!search.value) return products.value
  const q = search.value.toLowerCase()
  return products.value.filter(prod =>
    prod.name.toLowerCase().includes(q) ||
    (prod.category?.name ?? '').toLowerCase().includes(q) ||
    (prod.branch?.name ?? '').toLowerCase().includes(q)
  )
})

const selectedRecipe = computed(() => {
  if (!drawerForm.value.recipe_id) return null
  return recipes.value.find(r => Number(r.id) === Number(drawerForm.value.recipe_id))
})

const categoryItems = computed(() => {
  const seen = new Set()
  return categories.value.filter((category) => {
    const key = category.name.trim().toLowerCase()
    if (seen.has(key)) return false
    seen.add(key)
    return true
  })
})

const filteredRecipes = computed(() => {
  if (!drawerForm.value.branch_id) return recipes.value
  return recipes.value.filter((recipe) =>
    !recipe.branch_id || Number(recipe.branch_id) === Number(drawerForm.value.branch_id)
  )
})

const canSaveProduct = computed(() =>
  Boolean(drawerForm.value.name.trim()) &&
  Boolean(drawerForm.value.category_id) &&
  Boolean(drawerForm.value.branch_id) &&
  Number(drawerForm.value.price) >= 0
)

async function loadProducts() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/products`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    products.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load products', err) }
}

async function loadCategories() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/categories`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    categories.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load categories', err) }
}

async function loadBranches() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/branches`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    branches.value = res.data.data || res.data
  } catch (err) { console.error('Failed to load branches', err) }
}

async function loadRecipes() {
  try {
    const token = localStorage.getItem('token')
    const res = await axios.get(`${API_BASE_URL}/recipes`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    recipes.value = (res.data.data || res.data)
  } catch (err) { console.error('Failed to load recipes', err) }
}

function openAddDrawer() {
  isEditing.value = false;
  drawerTitle.value = 'Add Product';
  tab.value = 'general';
  formError.value = '';
  drawerForm.value = { 
    id: null, 
    name: '', 
    category_id: null, 
    price: '', 
    branch_id: null,
    is_available: true, 
    min_stock: 0,
    recipe_id: null,
  };
  rightDrawer.value = true;
}

function openEditDrawer(product) {
  isEditing.value = true;
  drawerTitle.value = 'Edit Product';
  tab.value = 'general';
  formError.value = '';
  drawerForm.value = {
    id: product.id,
    name: product.name,
    category_id: product.category_id,
    price: product.price,
    branch_id: product.branch_id,
    is_available: !!product.is_available,
    min_stock: product.min_stock ?? 0,
    recipe_id: product.recipe?.id || null,
  };
  rightDrawer.value = true;
}

function openViewDrawer(product) {
  viewProduct.value = { ...product }
  viewDrawer.value = true;
}

function recipeTitle(recipe) {
  return recipe?.description || `Recipe #${recipe?.id ?? ''}`
}

function onBranchChange() {
  if (!drawerForm.value.recipe_id) return
  const stillAvailable = filteredRecipes.value.some((recipe) =>
    Number(recipe.id) === Number(drawerForm.value.recipe_id)
  )
  if (!stillAvailable) drawerForm.value.recipe_id = null
}

function onRecipeChange() {
  formError.value = ''
}

async function saveProduct() {
  if (!canSaveProduct.value) return

  const token = localStorage.getItem('token');
  const url = isEditing.value
    ? `${API_BASE_URL}/products/${drawerForm.value.id}`
    : `${API_BASE_URL}/products`;
  const method = isEditing.value ? 'put' : 'post';

  const payload = {
    name: drawerForm.value.name.trim(),
    category_id: drawerForm.value.category_id,
    branch_id: drawerForm.value.branch_id,
    price: Number(drawerForm.value.price),
    is_available: drawerForm.value.is_available,
    min_stock: drawerForm.value.min_stock === '' || drawerForm.value.min_stock === null
      ? 0
      : Number(drawerForm.value.min_stock),
    recipe_id: drawerForm.value.recipe_id,
  }

  try {
    formError.value = '';
    await axios[method](url, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });
    rightDrawer.value = false;
    loadProducts();
  } catch (error) {
    formError.value = errorMessage(error);
  }
}

function errorMessage(error) {
  const errors = error?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join(' ')
  return error?.response?.data?.message || error?.response?.data?.error || 'Could not save this product.'
}

async function deleteProduct(prod) {
  if (!confirm(`Delete ${prod.name}?`)) return
  const token = localStorage.getItem('token')
  await axios.delete(`${API_BASE_URL}/products/${prod.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadProducts()
}

onMounted(() => {
  loadProducts()
  loadCategories()
  loadBranches()
  loadRecipes()
})
</script>

<style scoped>
.products-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.products-main {
  flex: 1 1 auto;
  max-width: 100%;
  transition: max-width 0.35s cubic-bezier(.4,0,.2,1);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.products-layout.drawer-open .products-main {
  max-width: calc(100% - 410px);
}
.products-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.recipe-container {
  background-color: transparent;
  box-shadow: 3px 3px 3px yellowgreen;
  padding: 1rem;
  border-radius: 1rem;
  margin-top: 1rem;
}
.ingredient-chip {
  font-size: 0.96em;
  padding: 0 10px;
  margin-bottom: 4px;
}
.recipe-preview {
  background: #f6f8f7;
  border: 1px solid #e0eee7;
  border-radius: 1rem;
}

:deep(.dashboard-select-menu .v-list) {
  background: #f8fafc !important;
  color: #111827 !important;
}

:deep(.dashboard-select-menu .v-list-item-title),
:deep(.dashboard-select-menu .v-list-item-subtitle) {
  color: #111827 !important;
  opacity: 1 !important;
}

:deep(.dashboard-select-menu .v-list-item--active .v-list-item-title) {
  color: #0f766e !important;
  font-weight: 700;
}
</style>

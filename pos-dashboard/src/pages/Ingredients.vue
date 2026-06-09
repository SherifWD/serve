<template>
  <OwnerLayout>
    <v-container>
      <v-card class="mb-6">
        <v-card-title>
          <span class="text-h5 font-weight-bold" style="color:#79bfa1;">Ingredients</span>
          <v-spacer />
          <v-btn color="primary" @click="openAddDrawer" class="rounded-pill">
            <v-icon>mdi-plus</v-icon> Add Ingredient
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-text-field v-model="search" label="Search ingredients..." prepend-inner-icon="mdi-magnify" clearable class="mb-4" />
          <v-row dense class="mb-4">
            <v-col cols="12" sm="6" lg="3">
              <v-select
                v-model="filters.branch_id"
                :items="branches"
                item-title="name"
                item-value="id"
                label="Branch stock"
                clearable
                variant="outlined"
                density="comfortable"
                :menu-props="{ contentClass: 'dashboard-select-menu' }"
              />
            </v-col>
            <v-col cols="12" sm="6" lg="3">
              <v-select
                v-model="filters.unit"
                :items="unitItems"
                label="Unit"
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
            :items="filteredIngredients"
            item-value="id"
            :items-per-page="10"
            class="rounded-xl"
            density="comfortable"
            style="border-radius:1.5rem;overflow:hidden;"
          >
            <template #item.branches="{ item }">
              <div class="d-flex flex-wrap ga-1">
                <v-chip
                  v-for="stock in item.ingredient_branches ?? []"
                  :key="stock.branch_id"
                  color="primary"
                  size="small"
                  text-color="white"
                >
                  {{ branchName(stock.branch_id) }}: {{ stock.stock }} {{ item.unit }}
                </v-chip>
              </div>
            </template>
            <template #item.actions="{ item }">
              <v-btn icon color="accent" class="rounded-xl" @click="openEditDrawer(item)"><v-icon>mdi-pencil</v-icon></v-btn>
              <v-btn icon color="red" class="rounded-xl" @click="deleteIngredient(item)"><v-icon>mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <!-- Drawer for Add/Edit Ingredient -->
      <v-navigation-drawer
        v-model="drawer"
        location="right"
        temporary
        width="460"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.12);"
      >
        <v-toolbar flat color="surface">
          <v-toolbar-title class="font-weight-bold" style="color:#79bfa1;">
            {{ isEditing ? 'Edit Ingredient' : 'Add Ingredient' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon @click="drawer = false"><v-icon color="black">mdi-close</v-icon></v-btn>
        </v-toolbar>
        <v-divider />
        <v-tabs v-model="tab" fixed-tabs color="primary" class="mb-2 mt-4">
          <v-tab value="general">General Info</v-tab>
          <v-tab value="branches">Branches/Stock</v-tab>
          <v-tab value="recipes">Recipe Assignments</v-tab>
        </v-tabs>
        <v-window v-model="tab">
          <!-- General Info Tab -->
          <v-window-item value="general">
            <v-form class="pa-6">
              <v-text-field label="Name" v-model="form.name" required variant="outlined" color="primary" class="mb-4" />
              <v-select label="Minimum Unit" v-model="form.unit" :items="minimumUnitItems" required variant="outlined" color="primary" class="mb-4" @update:model-value="onBaseUnitChange" />
              <div class="stock-row">
                <v-text-field
                  label="Global Stock"
                  v-model="form.stock"
                  type="number"
                  min="0"
                  variant="outlined"
                  color="primary"
                  class="mb-4"
                  :error="!!globalStockError"
                  :error-messages="globalStockError"
                  @blur="onGlobalStockChange"
                  @input="onGlobalStockChange"
                />
                <v-select
                  label="Unit"
                  v-model="form.stock_unit"
                  :items="quantityUnitItems(form.unit)"
                  variant="outlined"
                  color="primary"
                  class="mb-4 stock-unit"
                  @update:model-value="onGlobalStockChange"
                />
              </div>
              <v-switch
                v-model="form.distribute_equally"
                label="Equal distribution across selected branches"
                color="primary"
                inset
                class="mb-4"
                @update:model-value="applyEqualDistribution"
              />
              <v-alert v-if="globalStockError" type="error" variant="outlined" class="mb-2">
                {{ globalStockError }}
              </v-alert>
              <v-btn
                color="primary"
                @click="saveIngredient"
                block
                class="rounded-pill mt-2"
                :loading="saving"
                :disabled="!!globalStockError"
              >
                <v-icon start>mdi-check</v-icon>{{ isEditing ? 'Update' : 'Add' }}
              </v-btn>
            </v-form>
          </v-window-item>
          <!-- Branches/Stock Tab -->
          <v-window-item value="branches">
            <v-form class="pa-6">
              <div v-if="form.stock">
                <span class="text-caption" style="color:#d32f2f;">
                  Total branch stock: <b>{{ branchStockSum }}</b> / <b>{{ globalStockBase }}</b> {{ form.unit }}
                  <span v-if="branchStockError" style="color:#d32f2f; margin-left: 1em;">{{ branchStockError }}</span>
                </span>
              </div>
              <v-select
                label="Branches"
                v-model="form.branch_ids"
                :items="branches"
                item-title="name"
                item-value="id"
                multiple
                chips
                closable-chips
                clearable
                variant="outlined"
                color="primary"
                class="mb-4"
                @update:model-value="onSelectedBranchesChange"
              />
              <v-select
                label="Add to Branch"
                v-model="branchIngredient.branch_id"
                :items="branches"
                item-title="name"
                item-value="id"
                clearable
                variant="outlined"
                color="primary"
                class="mb-2"
              />
              <v-text-field
                label="Stock for Branch"
                v-model="branchIngredient.stock"
                type="number"
                min="0"
                variant="outlined"
                color="primary"
                class="mb-2"
                :disabled="!branchIngredient.branch_id"
              />
              <v-select
                label="Unit"
                v-model="branchIngredient.unit"
                :items="quantityUnitItems(form.unit)"
                variant="outlined"
                color="primary"
                class="mb-2"
                :disabled="!branchIngredient.branch_id"
              />
              <v-alert v-if="branchStockError" type="error" variant="outlined" class="mb-2">
                {{ branchStockError }}
              </v-alert>
              <v-btn color="primary" type="button" class="mb-3" :disabled="!branchIngredient.branch_id" @click="addBranchStock">Add Branch Stock</v-btn>
              <v-list two-line>
                <v-list-item v-for="ib in form.ingredient_branches ?? []" :key="ib.branch_id">
                  <v-list-item-title>{{ branchName(ib.branch_id) }}</v-list-item-title>
                  <v-list-item-subtitle>Stock: {{ ib.stock }} {{ form.unit }}</v-list-item-subtitle>
                  <template #append>
                    <v-btn icon color="red" @click="removeBranchStock(ib.branch_id)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-list-item>
                <div v-if="!(form.ingredient_branches && form.ingredient_branches.length)" class="text-caption mt-3">No branches assigned yet.</div>
              </v-list>
            </v-form>
          </v-window-item>
          <!-- Recipes Tab -->
          <v-window-item value="recipes">
            <v-form class="pa-6">
              <div v-if="form.stock">
                <span class="text-caption" style="color:#d32f2f;">
                  Assigned to recipes: <b>{{ recipeQtySum }}</b> / <b>{{ globalStockBase }}</b> {{ form.unit }}
                  <span v-if="recipeQtyError" style="color:#d32f2f; margin-left: 1em;">{{ recipeQtyError }}</span>
                </span>
              </div>
              <v-select
                label="Assign to Recipe"
                v-model="recipeIngredient.recipe_id"
                :items="recipes"
                :item-title="recipeTitle"
                item-value="id"
                clearable
                variant="outlined"
                color="primary"
                class="mb-2"
                :menu-props="{ contentClass: 'dashboard-select-menu' }"
              />
              <div v-if="selectedRecipe" class="recipe-preview pa-4 mb-4">
                <div class="font-weight-bold mb-2" style="color:#2a9d8f;">
                  {{ recipeTitle(selectedRecipe) }}
                </div>
                <div v-if="selectedRecipe.ingredients && selectedRecipe.ingredients.length" class="ingredient-list">
                  <v-chip
                    v-for="ingredient in selectedRecipe.ingredients"
                    :key="ingredient.id"
                    color="success"
                    variant="elevated"
                    size="small"
                  >
                    <b>{{ ingredient.name }}</b>
                    <span v-if="ingredient.pivot">&nbsp;- {{ ingredient.pivot.quantity }} {{ ingredient.unit || '' }}</span>
                  </v-chip>
                </div>
                <div v-else class="text-caption" style="color:#64748b;">No ingredients assigned to this recipe.</div>
              </div>
              <v-text-field
                label="Quantity"
                v-model="recipeIngredient.quantity"
                type="number"
                min="0"
                variant="outlined"
                color="primary"
                class="mb-2"
                :disabled="!recipeIngredient.recipe_id"
              />
              <v-select
                label="Unit"
                v-model="recipeIngredient.unit"
                :items="quantityUnitItems(form.unit)"
                variant="outlined"
                color="primary"
                class="mb-2"
                :disabled="!recipeIngredient.recipe_id"
              />
              <v-alert v-if="recipeQtyError" type="error" variant="outlined" class="mb-2">
                {{ recipeQtyError }}
              </v-alert>
              <v-btn color="primary" type="button" class="mb-3" :disabled="!recipeIngredient.recipe_id" @click="addRecipeIngredient">Add/Update Recipe</v-btn>
              <v-list two-line>
                <v-list-item v-for="ri in form.recipe_ingredients ?? []" :key="ri.recipe_id">
                  <v-list-item-title>{{ recipeName(ri.recipe_id) }}</v-list-item-title>
                  <v-list-item-subtitle>Qty: {{ ri.quantity }} {{ form.unit }}</v-list-item-subtitle>
                  <template #append>
                    <v-btn icon color="red" @click="removeRecipeIngredient(ri.recipe_id)">
                      <v-icon>mdi-delete</v-icon>
                    </v-btn>
                  </template>
                </v-list-item>
                <div v-if="!(form.recipe_ingredients && form.recipe_ingredients.length)" class="text-caption mt-3">No recipe assignments yet.</div>
              </v-list>
            </v-form>
          </v-window-item>
        </v-window>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const ingredients = ref([])
const branches = ref([])
const recipes = ref([])
const search = ref('')
const drawer = ref(false)
const isEditing = ref(false)
const tab = ref('general')
const saving = ref(false)
const filters = ref({
  branch_id: null,
  unit: null,
  stock: null,
})

const globalStockError = ref('')
const branchStockError = ref('')
const recipeQtyError = ref('')

const form = ref({
  id: null,
  name: '',
  unit: '',
  stock_unit: '',
  stock: 0,
  branch_ids: [],
  distribute_equally: false,
  ingredient_branches: [],
  recipe_ingredients: [],
})

const branchIngredient = ref({ branch_id: null, stock: 0, unit: '' })
const recipeIngredient = ref({ recipe_id: null, quantity: '', unit: '' })

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Unit', value: 'unit' },
  { title: 'Global Stock', value: 'stock' },
  { title: 'Branches', value: 'branches', sortable: false },
  { title: 'Minimum Stock', value: 'min_stock' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const minimumUnitItems = ['g', 'ml', 'pc']

const unitItems = computed(() =>
  [...new Set(ingredients.value.map(ingredient => ingredient.unit).filter(Boolean))].sort()
)

const stockFilterItems = [
  { title: 'Low stock', value: 'low' },
  { title: 'Healthy stock', value: 'healthy' },
  { title: 'Assigned to selected branch', value: 'assigned' },
]

const filteredIngredients = computed(() => {
  const q = search.value.toLowerCase()

  return ingredients.value.filter(ingredient =>
    matchesIngredientSearch(ingredient, q) &&
    (!filters.value.unit || ingredient.unit === filters.value.unit) &&
    matchesIngredientBranchFilter(ingredient) &&
    matchesIngredientStockFilter(ingredient)
  )
})

function matchesIngredientSearch(ingredient, query) {
  if (!query) return true

  return ingredient.name.toLowerCase().includes(query) ||
    ingredient.unit.toLowerCase().includes(query) ||
    (ingredient.ingredient_branches ?? []).some(stock => branchName(stock.branch_id).toLowerCase().includes(query))
}

function matchesIngredientBranchFilter(ingredient) {
  if (!filters.value.branch_id) return true
  return (ingredient.ingredient_branches ?? []).some(stock => Number(stock.branch_id) === Number(filters.value.branch_id))
}

function matchesIngredientStockFilter(ingredient) {
  if (!filters.value.stock) return true

  if (filters.value.stock === 'assigned') {
    return filters.value.branch_id
      ? matchesIngredientBranchFilter(ingredient)
      : (ingredient.ingredient_branches ?? []).length > 0
  }

  const isLow = Number(ingredient.stock ?? 0) <= Number(ingredient.min_stock ?? 0)
  return filters.value.stock === 'low' ? isLow : !isLow
}

function resetFilters() {
  search.value = ''
  filters.value = {
    branch_id: null,
    unit: null,
    stock: null,
  }
}

function branchName(id) {
  return branches.value.find(b => Number(b.id) === Number(id))?.name || '-'
}
function recipeName(id) {
  const recipe = recipes.value.find(r => Number(r.id) === Number(id))
  return recipe ? recipeTitle(recipe) : '-'
}

const selectedRecipe = computed(() => {
  if (!recipeIngredient.value.recipe_id) return null
  return recipes.value.find(r => Number(r.id) === Number(recipeIngredient.value.recipe_id)) || null
})

function recipeTitle(recipe) {
  const description = (recipe?.description ?? '').trim()
  if (description) return description

  const ingredients = recipeIngredientSummary(recipe)
  if (ingredients) return ingredients

  return `Recipe #${recipe?.id ?? ''}`
}

function recipeIngredientSummary(recipe) {
  return (recipe?.ingredients ?? [])
    .map(ingredient => {
      const quantity = ingredient.pivot?.quantity ?? ingredient.quantity ?? ''
      const unit = ingredient.unit ? ` ${ingredient.unit}` : ''
      const amount = quantity !== '' && quantity !== null ? ` ${quantity}${unit}` : ''
      return `${ingredient.name}${amount}`
    })
    .filter(Boolean)
    .join(', ')
}

// --- Sums for validation ---
const globalStockBase = computed(() =>
  convertQuantity(Number(form.value.stock || 0), form.value.stock_unit || form.value.unit, form.value.unit)
)
const branchStockSum = computed(() =>
  (form.value.ingredient_branches ?? []).reduce((sum, ib) => sum + Number(ib.stock), 0)
)
const recipeQtySum = computed(() =>
  (form.value.recipe_ingredients ?? []).reduce((sum, ri) => sum + Number(ri.quantity), 0)
)

// Always validate global stock vs branch and recipe assignments
function validateGlobalStock() {
  globalStockError.value = ''
  const stock = Number(form.value.stock)
  if (isNaN(stock) || stock < 0) {
    globalStockError.value = 'Global stock must be a non-negative number'
    return false
  }
  if (branchStockSum.value > globalStockBase.value) {
    globalStockError.value = `Cannot set global stock (${globalStockBase.value}) less than sum of branch stocks (${branchStockSum.value})`
    return false
  }
  if (recipeQtySum.value > globalStockBase.value) {
    globalStockError.value = `Cannot set global stock (${globalStockBase.value}) less than sum of assigned to recipes (${recipeQtySum.value})`
    return false
  }
  return true
}

function quantityUnitItems(baseUnit) {
  if (baseUnit === 'g') return ['g', 'kg']
  if (baseUnit === 'ml') return ['ml', 'l']
  if (baseUnit === 'pc') return ['pc']
  return baseUnit ? [baseUnit] : []
}

function convertQuantity(quantity, fromUnit, toUnit) {
  if (!quantity || !fromUnit || !toUnit || fromUnit === toUnit) return Number(quantity || 0)

  const factors = {
    mg: { dimension: 'weight', factor: 0.001 },
    g: { dimension: 'weight', factor: 1 },
    kg: { dimension: 'weight', factor: 1000 },
    ml: { dimension: 'volume', factor: 1 },
    l: { dimension: 'volume', factor: 1000 },
    pc: { dimension: 'count', factor: 1 },
  }
  const from = factors[fromUnit]
  const to = factors[toUnit]
  if (!from || !to || from.dimension !== to.dimension) return Number(quantity || 0)
  return Math.round(((Number(quantity) * from.factor) / to.factor) * 1000) / 1000
}

function onGlobalStockChange() {
  if (form.value.distribute_equally) applyEqualDistribution()
  validateGlobalStock()
}

function onBaseUnitChange() {
  form.value.stock_unit = form.value.unit
  branchIngredient.value.unit = form.value.unit
  recipeIngredient.value.unit = form.value.unit
  validateGlobalStock()
}

function onSelectedBranchesChange() {
  form.value.ingredient_branches = (form.value.ingredient_branches ?? [])
    .filter(stock => form.value.branch_ids.map(Number).includes(Number(stock.branch_id)))

  form.value.branch_ids.forEach((branchId) => {
    const exists = form.value.ingredient_branches.some(stock => Number(stock.branch_id) === Number(branchId))
    if (!exists) {
      form.value.ingredient_branches.push({ branch_id: branchId, stock: 0 })
    }
  })

  if (form.value.distribute_equally) applyEqualDistribution()
  validateGlobalStock()
}

function applyEqualDistribution() {
  if (!form.value.distribute_equally || !form.value.branch_ids.length) return

  const total = globalStockBase.value
  const branchIds = form.value.branch_ids.map(Number)
  const perBranch = Math.floor((total / branchIds.length) * 100) / 100
  let assigned = 0

  form.value.ingredient_branches = branchIds.map((branchId, index) => {
    const amount = index === branchIds.length - 1
      ? Math.round((total - assigned) * 100) / 100
      : Math.round(perBranch * 100) / 100
    assigned += amount
    return { branch_id: branchId, stock: amount }
  })

  validateGlobalStock()
}

// --- Data fetch ---
async function fetchIngredients() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/ingredients`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  ingredients.value = data
}
async function fetchBranches() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/branches`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = data.data || data
}
async function fetchRecipes() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/recipes`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  recipes.value = data.data || data
}

// --- Drawer ops ---
function openAddDrawer() {
  isEditing.value = false
  tab.value = 'general'
  form.value = {
    id: null,
    name: '',
    unit: 'g',
    stock_unit: 'g',
    stock: 0,
    branch_ids: branches.value.length === 1 ? [branches.value[0].id] : [],
    distribute_equally: false,
    ingredient_branches: [],
    recipe_ingredients: [],
  }
  branchIngredient.value = { branch_id: null, stock: 0, unit: form.value.unit }
  recipeIngredient.value = { recipe_id: null, quantity: '', unit: form.value.unit }
  globalStockError.value = ''
  branchStockError.value = ''
  recipeQtyError.value = ''
  drawer.value = true
}
function openEditDrawer(item) {
  isEditing.value = true
  tab.value = 'general'
  form.value = {
    ...JSON.parse(JSON.stringify(item)),
    stock_unit: item.unit,
    branch_ids: (item.ingredient_branches ?? []).map(stock => stock.branch_id),
    distribute_equally: false,
    ingredient_branches: item.ingredient_branches ?? [],
    recipe_ingredients: item.recipe_ingredients ?? [],
  }
  branchIngredient.value = { branch_id: null, stock: 0, unit: form.value.unit }
  recipeIngredient.value = { recipe_id: null, quantity: '', unit: form.value.unit }
  globalStockError.value = ''
  branchStockError.value = ''
  recipeQtyError.value = ''
  validateGlobalStock()
  drawer.value = true
}
function deleteIngredient(item) {
  if (!confirm(`Delete ingredient "${item.name}"?`)) return
  axios.delete(`${API_BASE_URL}/ingredients/${item.id}`, {
    headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
  }).then(fetchIngredients)
}

// Prevent branch stocks sum from exceeding global stock
function addBranchStock() {
  branchStockError.value = ''
  validateGlobalStock()
  if (!branchIngredient.value.branch_id) return
  const newStock = Number(branchIngredient.value.stock)
  if (isNaN(newStock) || newStock < 0) return
  const convertedStock = convertQuantity(newStock, branchIngredient.value.unit || form.value.unit, form.value.unit)
  const otherBranches = form.value.ingredient_branches.filter(
    ib => Number(ib.branch_id) !== Number(branchIngredient.value.branch_id)
  )
  const existing = form.value.ingredient_branches.find(ib => Number(ib.branch_id) === Number(branchIngredient.value.branch_id))
  const nextStock = Number(existing?.stock ?? 0) + convertedStock
  const totalIfAdd = otherBranches.reduce((sum, ib) => sum + Number(ib.stock), 0) + nextStock
  if (form.value.stock && totalIfAdd > globalStockBase.value) {
    branchStockError.value = `Sum for all branches (${totalIfAdd}) can't exceed global stock (${globalStockBase.value})`
    return
  }
  if (existing) {
    existing.stock = Math.round(nextStock * 1000) / 1000
  } else {
    form.value.ingredient_branches.push({
      branch_id: branchIngredient.value.branch_id,
      stock: convertedStock
    })
  }
  if (!form.value.branch_ids.map(Number).includes(Number(branchIngredient.value.branch_id))) {
    form.value.branch_ids.push(branchIngredient.value.branch_id)
  }
  branchIngredient.value = { branch_id: null, stock: 0, unit: form.value.unit }
  validateGlobalStock()
}
function removeBranchStock(branch_id) {
  form.value.ingredient_branches = form.value.ingredient_branches.filter(ib => Number(ib.branch_id) !== Number(branch_id))
  form.value.branch_ids = form.value.branch_ids.filter(id => Number(id) !== Number(branch_id))
  validateGlobalStock()
}

// Prevent assigned to recipes sum from exceeding global stock
function addRecipeIngredient() {
  recipeQtyError.value = ''
  validateGlobalStock()
  if (!recipeIngredient.value.recipe_id) return
  const newQty = Number(recipeIngredient.value.quantity)
  if (isNaN(newQty) || newQty < 0) return
  const convertedQty = convertQuantity(newQty, recipeIngredient.value.unit || form.value.unit, form.value.unit)
  const otherRecipes = form.value.recipe_ingredients.filter(
    ri => Number(ri.recipe_id) !== Number(recipeIngredient.value.recipe_id)
  )
  const totalIfAdd = otherRecipes.reduce((sum, ri) => sum + Number(ri.quantity), 0) + convertedQty
  if (form.value.stock && totalIfAdd > globalStockBase.value) {
    recipeQtyError.value = `Sum for all recipe assignments (${totalIfAdd}) can't exceed global stock (${globalStockBase.value})`
    return
  }
  const existing = form.value.recipe_ingredients.find(ri => Number(ri.recipe_id) === Number(recipeIngredient.value.recipe_id))
  if (existing) {
    existing.quantity = convertedQty
  } else {
    form.value.recipe_ingredients.push({
      recipe_id: Number(recipeIngredient.value.recipe_id),
      quantity: convertedQty
    })
  }
  recipeIngredient.value = { recipe_id: null, quantity: '', unit: form.value.unit }
  validateGlobalStock()
}
function removeRecipeIngredient(recipe_id) {
  form.value.recipe_ingredients = form.value.recipe_ingredients.filter(ri => Number(ri.recipe_id) !== Number(recipe_id))
  validateGlobalStock()
}

async function saveIngredient() {
  if (!validateGlobalStock()) {
    globalStockError.value = 'Please fix errors before saving'
    return
  }
  saving.value = true
  const token = localStorage.getItem('token')
  const payload = {
    ...form.value,
    ingredient_branches: (form.value.ingredient_branches ?? []).map(stock => ({
      branch_id: stock.branch_id,
      stock: Number(stock.stock),
      unit: form.value.unit,
    })),
    recipe_ingredients: (form.value.recipe_ingredients ?? []).map(row => ({
      recipe_id: row.recipe_id,
      quantity: Number(row.quantity),
      unit: form.value.unit,
    })),
  }

  try {
    if (isEditing.value) {
      await axios.put(`${API_BASE_URL}/ingredients/${payload.id}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
    } else {
      await axios.post(`${API_BASE_URL}/ingredients`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
    }
    drawer.value = false
    fetchIngredients()
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchIngredients()
  fetchBranches()
  fetchRecipes()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.recipe-preview {
  background: #f6f8f7;
  border: 1px solid #e0eee7;
  border-radius: 1rem;
}
.ingredient-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.stock-row {
  display: grid;
  gap: 12px;
  grid-template-columns: minmax(0, 1fr) 120px;
}
.stock-unit {
  min-width: 0;
}
</style>

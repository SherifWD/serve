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
          <v-data-table
            :headers="headers"
            :items="filteredIngredients"
            item-value="id"
            :items-per-page="10"
            class="rounded-xl"
            density="comfortable"
            style="border-radius:1.5rem;overflow:hidden;"
          >
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
              <v-text-field label="Unit" v-model="form.unit" required variant="outlined" color="primary" class="mb-4" />
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
                @blur="validateGlobalStock"
                @input="validateGlobalStock"
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
                  Total branch stock: <b>{{ branchStockSum }}</b> / <b>{{ Number(form.stock) || 0 }}</b>
                  <span v-if="branchStockError" style="color:#d32f2f; margin-left: 1em;">{{ branchStockError }}</span>
                </span>
              </div>
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
              <v-alert v-if="branchStockError" type="error" variant="outlined" class="mb-2">
                {{ branchStockError }}
              </v-alert>
              <v-btn color="primary" class="mb-3" :disabled="!branchIngredient.branch_id" @click="addBranchStock">Add/Update Branch Stock</v-btn>
              <v-list two-line>
                <v-list-item v-for="ib in form.ingredient_branches ?? []" :key="ib.branch_id">
                  <v-list-item-title>{{ branchName(ib.branch_id) }}</v-list-item-title>
                  <v-list-item-subtitle>Stock: {{ ib.stock }}</v-list-item-subtitle>
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
                  Assigned to recipes: <b>{{ recipeQtySum }}</b> / <b>{{ Number(form.stock) || 0 }}</b>
                  <span v-if="recipeQtyError" style="color:#d32f2f; margin-left: 1em;">{{ recipeQtyError }}</span>
                </span>
              </div>
              <v-select
                label="Assign to Recipe"
                v-model="recipeIngredient.recipe_id"
                :items="recipes"
                item-title="description"
                item-value="id"
                clearable
                variant="outlined"
                color="primary"
                class="mb-2"
              />
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
              <v-alert v-if="recipeQtyError" type="error" variant="outlined" class="mb-2">
                {{ recipeQtyError }}
              </v-alert>
              <v-btn color="primary" class="mb-3" :disabled="!recipeIngredient.recipe_id" @click="addRecipeIngredient">Add/Update Recipe</v-btn>
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
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const ingredients = ref([])
const branches = ref([])
const recipes = ref([])
const search = ref('')
const drawer = ref(false)
const isEditing = ref(false)
const tab = ref('general')
const saving = ref(false)

const globalStockError = ref('')
const branchStockError = ref('')
const recipeQtyError = ref('')

const form = ref({
  id: null,
  name: '',
  unit: '',
  stock: 0,
  ingredient_branches: [],
  recipe_ingredients: [],
})

const branchIngredient = ref({ branch_id: null, stock: 0 })
const recipeIngredient = ref({ recipe_id: null, quantity: '' })

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Unit', value: 'unit' },
  { title: 'Global Stock', value: 'stock' },
  { title: 'Minimum Stock', value: 'min_stock' },
  { title: 'Actions', value: 'actions', sortable: false }
]

const filteredIngredients = computed(() =>
  !search.value
    ? ingredients.value
    : ingredients.value.filter(i =>
        i.name.toLowerCase().includes(search.value.toLowerCase()) ||
        i.unit.toLowerCase().includes(search.value.toLowerCase())
      )
)

function branchName(id) {
  return branches.value.find(b => b.id === id)?.name || '—'
}
function recipeName(id) {
  return recipes.value.find(r => r.id === id)?.description || '—'
}

// --- Sums for validation ---
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
  if (branchStockSum.value > stock) {
    globalStockError.value = `Cannot set global stock (${stock}) less than sum of branch stocks (${branchStockSum.value})`
    return false
  }
  if (recipeQtySum.value > stock) {
    globalStockError.value = `Cannot set global stock (${stock}) less than sum of assigned to recipes (${recipeQtySum.value})`
    return false
  }
  return true
}

// --- Data fetch ---
async function fetchIngredients() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('http://localhost:8000/api/ingredients', {
    headers: { Authorization: `Bearer ${token}` }
  })
  ingredients.value = data
}
async function fetchBranches() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = data.data || data
}
async function fetchRecipes() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('http://localhost:8000/api/recipes', {
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
    unit: '',
    stock: 0,
    ingredient_branches: [],
    recipe_ingredients: [],
  }
  branchIngredient.value = { branch_id: null, stock: 0 }
  recipeIngredient.value = { recipe_id: null, quantity: '' }
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
    ingredient_branches: item.ingredient_branches ?? [],
    recipe_ingredients: item.recipe_ingredients ?? [],
  }
  branchIngredient.value = { branch_id: null, stock: 0 }
  recipeIngredient.value = { recipe_id: null, quantity: '' }
  globalStockError.value = ''
  branchStockError.value = ''
  recipeQtyError.value = ''
  validateGlobalStock()
  drawer.value = true
}
function deleteIngredient(item) {
  if (!confirm(`Delete ingredient "${item.name}"?`)) return
  axios.delete(`http://localhost:8000/api/ingredients/${item.id}`, {
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
  const otherBranches = form.value.ingredient_branches.filter(
    ib => ib.branch_id !== branchIngredient.value.branch_id
  )
  const totalIfAdd = otherBranches.reduce((sum, ib) => sum + Number(ib.stock), 0) + newStock
  if (form.value.stock && totalIfAdd > Number(form.value.stock)) {
    branchStockError.value = `Sum for all branches (${totalIfAdd}) can't exceed global stock (${form.value.stock})`
    return
  }
  const existing = form.value.ingredient_branches.find(ib => ib.branch_id === branchIngredient.value.branch_id)
  if (existing) {
    existing.stock = newStock
  } else {
    form.value.ingredient_branches.push({
      branch_id: branchIngredient.value.branch_id,
      stock: newStock
    })
  }
  branchIngredient.value = { branch_id: null, stock: 0 }
  validateGlobalStock()
}
function removeBranchStock(branch_id) {
  form.value.ingredient_branches = form.value.ingredient_branches.filter(ib => ib.branch_id !== branch_id)
  validateGlobalStock()
}

// Prevent assigned to recipes sum from exceeding global stock
function addRecipeIngredient() {
  recipeQtyError.value = ''
  validateGlobalStock()
  if (!recipeIngredient.value.recipe_id) return
  const newQty = Number(recipeIngredient.value.quantity)
  if (isNaN(newQty) || newQty < 0) return
  const otherRecipes = form.value.recipe_ingredients.filter(
    ri => ri.recipe_id !== recipeIngredient.value.recipe_id
  )
  const totalIfAdd = otherRecipes.reduce((sum, ri) => sum + Number(ri.quantity), 0) + newQty
  if (form.value.stock && totalIfAdd > Number(form.value.stock)) {
    recipeQtyError.value = `Sum for all recipe assignments (${totalIfAdd}) can't exceed global stock (${form.value.stock})`
    return
  }
  const existing = form.value.recipe_ingredients.find(ri => ri.recipe_id === recipeIngredient.value.recipe_id)
  if (existing) {
    existing.quantity = newQty
  } else {
    form.value.recipe_ingredients.push({
      recipe_id: recipeIngredient.value.recipe_id,
      quantity: newQty
    })
  }
  recipeIngredient.value = { recipe_id: null, quantity: '' }
  validateGlobalStock()
}
function removeRecipeIngredient(recipe_id) {
  form.value.recipe_ingredients = form.value.recipe_ingredients.filter(ri => ri.recipe_id !== recipe_id)
  validateGlobalStock()
}

async function saveIngredient() {
  if (!validateGlobalStock()) {
    globalStockError.value = 'Please fix errors before saving'
    return
  }
  saving.value = true
  const token = localStorage.getItem('token')
  const payload = { ...form.value }
  let response
  if (isEditing.value) {
    response = await axios.put(`http://localhost:8000/api/ingredients/${payload.id}`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    })
  } else {
    response = await axios.post('http://localhost:8000/api/ingredients', payload, {
      headers: { Authorization: `Bearer ${token}` }
    })
  }
  saving.value = false
  drawer.value = false
  fetchIngredients()
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
</style>

<template>
  <OwnerLayout>
    <v-container>
      <v-card class="mb-6">
        <v-card-title>
          <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Recipes</span>
          <v-spacer />
          <v-btn color="primary" @click="openAddDrawer" class="rounded-pill"><v-icon>mdi-plus</v-icon> Add Recipe</v-btn>
        </v-card-title>
        <v-card-text>
          <v-text-field v-model="search" label="Search recipes..." prepend-inner-icon="mdi-magnify" clearable class="mb-4" />
          <v-data-table
            :headers="headers"
            :items="filteredRecipes"
            item-value="id"
            :items-per-page="10"
            class="rounded-xl"
            density="comfortable"
            style="border-radius:1.5rem;overflow:hidden;"
          >
            <!-- Custom column for Ingredients display -->
            <template #item.ingredients="{ item }">
              <div class="d-flex flex-column" style="min-width:200px;">
                <template v-if="item.ingredients && item.ingredients.length">
                  <div
                    v-for="(ing, idx) in item.ingredients"
                    :key="idx"
                    class="d-flex align-center mb-1"
                  >
                    <v-chip size="small" class="mr-2" color="success" style="font-weight:600;">
                      {{ ing.name }}
                    </v-chip>
                    <span class="text-caption" style="color:#444;">
                      {{ ing.quantity }} {{ ingredientUnit(ing.ingredient_id) }}
                    </span>
                  </div>
                </template>
                <span v-else class="text-caption" style="color:#aaa;">—</span>
              </div>
            </template>
            <template #item.actions="{ item }">
              <v-btn icon color="accent" class="rounded-xl" @click="openEditDrawer(item)"><v-icon>mdi-pencil</v-icon></v-btn>
              <v-btn icon color="red" class="rounded-xl" @click="deleteRecipe(item)"><v-icon>mdi-delete</v-icon></v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <!-- Drawer for Add/Edit Recipe -->
      <v-navigation-drawer
        v-model="drawer"
        location="right"
        temporary
        width="480"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.12);"
      >
        <v-toolbar flat color="surface">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ isEditing ? 'Edit Recipe' : 'Add Recipe' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon @click="drawer = false"><v-icon color="black">mdi-close</v-icon></v-btn>
        </v-toolbar>
        <v-divider />
        <v-form class="pa-6" @submit.prevent="saveRecipe">
          <v-alert
            v-if="formError"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ formError }}
          </v-alert>
          <v-select
            label="Branch"
            :items="branches"
            :item-title="branchTitle"
            item-value="id"
            v-model="form.branch_id"
            required
            variant="outlined"
            color="primary"
            class="mb-4"
            :menu-props="{ contentClass: 'dashboard-select-menu' }"
          />
          <v-text-field label="Description" v-model="form.description" required variant="outlined" color="primary" class="mb-4" />
          <v-divider class="mb-3" />
          <h3 class="mb-2" style="color:#2a9d8f;">Ingredients</h3>
          <div v-for="(ing, idx) in form.ingredients" :key="idx" class="d-flex align-center mb-2">
            <v-select
              :items="allIngredients"
              v-model="ing.ingredient_id"
              item-title="name"
              item-value="id"
              label="Ingredient"
              style="max-width:180px"
              dense outlined color="primary" class="mr-2"
              :menu-props="{ contentClass: 'dashboard-select-menu' }"
              required
            />
            <v-text-field
              v-model="ing.quantity"
              type="number"
              min="0"
              label="Qty"
              style="max-width:95px"
              dense outlined color="primary" class="mr-2"
              required
            />
            <span v-if="ingredientUnit(ing.ingredient_id)" class="text-caption" style="min-width:50px;color:#888;">
              {{ ingredientUnit(ing.ingredient_id) }}
            </span>
            <v-btn icon type="button" size="small" color="red" @click="removeIngredient(idx)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </div>
          <v-btn color="primary" type="button" @click="addIngredient" class="mb-4">Add Ingredient</v-btn>
          <v-divider class="my-4" />
          <v-btn color="primary" block class="rounded-pill mt-2" :loading="saving" type="submit" :disabled="!canSaveRecipe">
            <v-icon start>mdi-check</v-icon>{{ isEditing ? 'Update' : 'Add' }}
          </v-btn>
        </v-form>
      </v-navigation-drawer>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const recipes = ref([])
const allIngredients = ref([])
const branches = ref([])
const search = ref('')
const drawer = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const formError = ref('')

const form = ref({
  id: null,
  branch_id: null,
  description: '',
  ingredients: []
})

const headers = [
  { title: 'Description', value: 'description', width: '30%' },
  { title: 'Branch', value: 'branch.name', width: '20%' },
  { title: 'Ingredients', value: 'ingredients', width: '50%' },
  { title: 'Actions', value: 'actions', sortable: false, width: '20%' }
]

const filteredRecipes = computed(() =>
  !search.value
    ? recipes.value
    : recipes.value.filter(r =>
        r.description.toLowerCase().includes(search.value.toLowerCase())
      )
)

const canSaveRecipe = computed(() => {
  const hasDescription = Boolean(form.value.description.trim())
  const hasBranch = Boolean(form.value.branch_id)
  const ingredientsAreValid = form.value.ingredients.every((ingredient) =>
    Boolean(ingredient.ingredient_id) && Number(ingredient.quantity) > 0
  )

  return hasDescription && hasBranch && ingredientsAreValid
})

function ingredientUnit(ingredient_id) {
  return allIngredients.value.find(i => Number(i.id) === Number(ingredient_id))?.unit ?? ''
}

function branchTitle(branch) {
  if (!branch) return ''
  return branch.restaurant?.name
    ? `${branch.name} - ${branch.restaurant.name}`
    : branch.name
}

// Format ingredients for table display
function formatRecipeIngredients(ingredients) {
  if (!ingredients || !ingredients.length) return '—'
  return ingredients.map(i =>
    `${i.name} (${i.quantity}${ingredientUnit(i.ingredient_id)})`
  ).join(', ')
}

async function fetchRecipes() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/recipes`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  // Map to flat array for table display
  recipes.value = (data.data || data).map(r => ({
    ...r,
    ingredients: r.ingredients?.map(ri => ({
      ingredient_id: ri.id,
      name: ri.name,
      quantity: ri.pivot?.quantity
    })) ?? []
  }))
}
async function fetchBranches() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/branches`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = data.data || data
}
async function fetchAllIngredients() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get(`${API_BASE_URL}/ingredients`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  allIngredients.value = data.data || data
}

function defaultBranchId() {
  return branches.value.length === 1 ? branches.value[0].id : null
}

function openAddDrawer() {
  isEditing.value = false
  formError.value = ''
  form.value = { id: null, branch_id: defaultBranchId(), description: '', ingredients: [] }
  drawer.value = true
}
function openEditDrawer(item) {
  isEditing.value = true
  formError.value = ''
  // Clone deep for safe editing
  form.value = {
    id: item.id,
    branch_id: item.branch_id,
    description: item.description,
    ingredients: (item.ingredients ?? []).map(ri => ({
      ingredient_id: ri.ingredient_id,
      quantity: ri.quantity
    }))
  }
  drawer.value = true
}
function removeIngredient(idx) {
  form.value.ingredients.splice(idx, 1)
}
function addIngredient() {
  form.value.ingredients.push({ ingredient_id: null, quantity: '' })
}
async function saveRecipe() {
  if (!canSaveRecipe.value) return

  saving.value = true
  const token = localStorage.getItem('token')
  const url = isEditing.value
    ? `${API_BASE_URL}/recipes/${form.value.id}`
    : `${API_BASE_URL}/recipes`
  const method = isEditing.value ? 'put' : 'post'

  const payload = {
    description: form.value.description.trim(),
    branch_id: form.value.branch_id,
    ingredients: form.value.ingredients.map(i => ({
      ingredient_id: i.ingredient_id,
      quantity: Number(i.quantity)
    }))
  }

  try {
    formError.value = ''
    await axios[method](url, payload, { headers: { Authorization: `Bearer ${token}` } })
    drawer.value = false
    await fetchRecipes()
  } catch (error) {
    formError.value = errorMessage(error)
  } finally {
    saving.value = false
  }
}

function errorMessage(error) {
  const errors = error?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join(' ')
  return error?.response?.data?.message || error?.response?.data?.error || 'Could not save this recipe.'
}
function deleteRecipe(item) {
  if (!confirm(`Delete recipe "${item.description}"?`)) return
  axios.delete(`${API_BASE_URL}/recipes/${item.id}`, {
    headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
  }).then(fetchRecipes)
}

onMounted(() => {
  fetchBranches()
  fetchRecipes()
  fetchAllIngredients()
})
</script>

<style scoped>
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.v-chip {
  font-size: .92em;
  padding: 0 .8em;
}
</style>

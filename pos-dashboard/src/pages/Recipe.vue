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
            <v-btn icon size="small" color="red" @click="removeIngredient(idx)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </div>
          <v-btn color="primary" @click="addIngredient" class="mb-4">Add Ingredient</v-btn>
          <v-divider class="my-4" />
          <v-btn color="primary" block class="rounded-pill mt-2" :loading="saving" type="submit">
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
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const recipes = ref([])
const allIngredients = ref([])
const search = ref('')
const drawer = ref(false)
const isEditing = ref(false)
const saving = ref(false)

const form = ref({
  id: null,
  description: '',
  ingredients: []
})

const headers = [
  { title: 'Description', value: 'description', width: '30%' },
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

function ingredientUnit(ingredient_id) {
  return allIngredients.value.find(i => i.id === ingredient_id)?.unit ?? ''
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
  const { data } = await axios.get('http://localhost:8000/api/recipes', {
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
async function fetchAllIngredients() {
  const token = localStorage.getItem('token')
  const { data } = await axios.get('http://localhost:8000/api/ingredients', {
    headers: { Authorization: `Bearer ${token}` }
  })
  allIngredients.value = data.data || data
}

function openAddDrawer() {
  isEditing.value = false
  form.value = { id: null, description: '', ingredients: [] }
  drawer.value = true
}
function openEditDrawer(item) {
  isEditing.value = true
  // Clone deep for safe editing
  form.value = {
    id: item.id,
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
  saving.value = true
  const token = localStorage.getItem('token')
  const url = isEditing.value
    ? `http://localhost:8000/api/recipes/${form.value.id}`
    : 'http://localhost:8000/api/recipes'
  const method = isEditing.value ? 'put' : 'post'

  const payload = {
    description: form.value.description,
    ingredients: form.value.ingredients.map(i => ({
      ingredient_id: i.ingredient_id,
      quantity: i.quantity
    }))
  }

  await axios[method](url, payload, { headers: { Authorization: `Bearer ${token}` } })
  drawer.value = false
  saving.value = false
  fetchRecipes()
}
function deleteRecipe(item) {
  if (!confirm(`Delete recipe "${item.description}"?`)) return
  axios.delete(`http://localhost:8000/api/recipes/${item.id}`, {
    headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
  }).then(fetchRecipes)
}

onMounted(() => {
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

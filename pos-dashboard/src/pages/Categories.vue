<template>
  <OwnerLayout>
    <div class="categories-layout" :class="{ 'drawer-open': rightDrawer }">
      <v-container class="py-6 categories-container" fluid>
        <v-card elevation="3" class="mb-8" color="card" style="border-radius: 2rem;">
          <v-card-title class="d-flex align-center" style="border-radius: 2rem 2rem 0 0;">
            <span class="text-h5 font-weight-bold" style="color:#2a9d8f;">Categories</span>
            <v-spacer />
            <v-btn
              color="primary"
              variant="elevated"
              class="rounded-pill"
              size="large"
              style="color:#181818;font-weight:bold;"
              @click="openAddDrawer"
            >
              <v-icon start>mdi-plus</v-icon>Add Category
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="search"
              label="Search categories..."
              class="mb-4"
              rounded
              variant="solo"
              style="max-width:400px;"
              color="primary"
            />
            <v-row dense class="mb-4">
              <v-col cols="12" md="4">
                <v-select
                  v-model="filters.branch_id"
                  :items="branches"
                  item-title="name"
                  item-value="id"
                  label="Branch"
                  clearable
                  variant="outlined"
                  density="comfortable"
                  :menu-props="{ contentClass: 'dashboard-select-menu' }"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.questions"
                  :items="presenceFilterItems"
                  label="Questions"
                  clearable
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.modifiers"
                  :items="presenceFilterItems"
                  label="Modifiers"
                  clearable
                  variant="outlined"
                  density="comfortable"
                />
              </v-col>
              <v-col cols="12" md="2" class="d-flex align-center">
                <v-btn variant="tonal" color="primary" class="rounded-pill" @click="resetFilters">
                  <v-icon start>mdi-filter-remove-outline</v-icon>Reset
                </v-btn>
              </v-col>
            </v-row>
            <v-data-table
              :headers="headers"
              :items="filteredCategories"
              class="rounded-xl"
              style="border-radius:1.5rem;overflow:hidden;"
              density="comfortable"
              item-value="id"
              :items-per-page="10"
            >
              <template #item.questions="{ item }">
                <v-chip color="primary" size="small" variant="tonal">
                  {{ questionCount(item) }} questions
                </v-chip>
              </template>
              <template #item.modifiers="{ item }">
                <v-chip color="success" size="small" variant="tonal">
                  {{ modifierCount(item) }} modifiers
                </v-chip>
              </template>
              <template #item.branches="{ item }">
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="branch in categoryBranches(item)"
                    :key="branch.id"
                    color="primary"
                    size="small"
                    text-color="white"
                  >
                    {{ branch.name }}
                  </v-chip>
                </div>
              </template>
              <template #item.actions="{ item }">
                <v-btn icon class="rounded-xl" size="small" color="primary" @click="openEditDrawer(item)">
                  <v-icon>mdi-pencil</v-icon>
                </v-btn>
                <v-btn icon class="rounded-xl" size="small" color="red" @click="deleteCategory(item)">
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-container>

      <v-navigation-drawer
        v-model="rightDrawer"
        location="right"
        temporary
        width="560"
        color="surface"
        transition="slide-x-reverse-transition"
        class="drawer-fade"
        style="border-radius:2rem 0 0 2rem;box-shadow:-6px 0 40px rgba(0,0,0,0.10);"
      >
        <v-toolbar flat color="surface" class="rounded-t-xl">
          <v-toolbar-title class="font-weight-bold" style="color:#2a9d8f;">
            {{ editing ? 'Edit Category' : 'Add Category' }}
          </v-toolbar-title>
          <v-spacer />
          <v-btn icon class="rounded-xl" @click="rightDrawer = false">
            <v-icon color="black">mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-divider />

        <v-form @submit.prevent="saveCategory">
          <div class="pa-6">
            <v-text-field
              label="Name"
              v-model="form.name"
              required
              variant="outlined"
              color="primary"
              class="mb-4 rounded-xl"
              style="border-radius: 1.5rem;"
            />
            <v-select
              label="Branches"
              :items="branchSelectItems"
              item-title="name"
              item-value="id"
              v-model="form.branch_ids"
              required
              multiple
              chips
              closable-chips
              variant="outlined"
              color="primary"
              class="mb-4 rounded-xl"
              style="border-radius: 1.5rem;"
              :menu-props="{ contentClass: 'dashboard-select-menu' }"
              @update:model-value="onSelectedBranchesChange"
            />
            <v-divider class="my-4" />
            <div class="editor-heading">
              <div>
                <div class="text-subtitle-1 font-weight-bold">Waiter Questions</div>
                <div class="text-caption">These choices appear when a waiter adds a product from this category.</div>
              </div>
              <v-btn color="primary" variant="tonal" type="button" @click="addQuestion">
                <v-icon start>mdi-plus</v-icon>Question
              </v-btn>
            </div>

            <div v-if="!form.questions.length" class="empty-note">
              No questions yet.
            </div>

            <div
              v-for="(question, questionIndex) in form.questions"
              :key="question.local_key"
              class="editor-block"
            >
              <div class="editor-block-actions">
                <v-btn
                  icon
                  color="red"
                  variant="text"
                  type="button"
                  @click="removeQuestion(questionIndex)"
                >
                  <v-icon>mdi-delete</v-icon>
                </v-btn>
              </div>
              <v-text-field
                v-model="question.question"
                label="Question"
                variant="outlined"
                color="primary"
                class="mb-3"
              />
              <div class="choice-row" v-for="(choice, choiceIndex) in question.choices" :key="choice.local_key">
                <v-text-field
                  v-model="choice.choice"
                  label="Choice"
                  variant="outlined"
                  color="primary"
                  density="comfortable"
                  hide-details
                />
                <v-btn
                  icon
                  color="red"
                  variant="text"
                  type="button"
                  @click="removeChoice(questionIndex, choiceIndex)"
                >
                  <v-icon>mdi-close</v-icon>
                </v-btn>
              </div>
              <v-btn
                color="primary"
                variant="text"
                type="button"
                class="mt-3"
                @click="addChoice(questionIndex)"
              >
                <v-icon start>mdi-plus</v-icon>Add Choice
              </v-btn>
            </div>

            <v-divider class="my-4" />
            <div class="editor-heading">
              <div>
                <div class="text-subtitle-1 font-weight-bold">Category Modifiers</div>
                <div class="text-caption">These modifiers only show for products in this category.</div>
              </div>
              <v-btn color="primary" variant="tonal" type="button" @click="addModifier">
                <v-icon start>mdi-plus</v-icon>Modifier
              </v-btn>
            </div>

            <div v-if="!form.modifiers.length" class="empty-note">
              No category modifiers yet.
            </div>

            <div
              v-for="(modifier, index) in form.modifiers"
              :key="modifier.local_key"
              class="modifier-row"
            >
              <v-text-field
                v-model="modifier.name"
                label="Modifier"
                variant="outlined"
                color="primary"
                density="comfortable"
                hide-details
              />
              <v-text-field
                v-model="modifier.price"
                label="Price"
                type="number"
                min="0"
                variant="outlined"
                color="primary"
                density="comfortable"
                hide-details
                class="modifier-price"
              />
              <v-btn icon color="red" variant="text" type="button" @click="removeModifier(index)">
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </div>
          </div>

          <div class="px-6 pb-6">
            <v-alert v-if="formError" type="error" variant="tonal" class="mb-4">
              {{ formError }}
            </v-alert>
            <v-btn
              color="primary"
              class="rounded-pill"
              block
              size="large"
              style="color:#181818;font-weight:bold;"
              type="submit"
              :loading="saving"
              :disabled="!canSave"
            >
              <v-icon start>mdi-check</v-icon>{{ editing ? 'Update' : 'Add' }}
            </v-btn>
          </div>
        </v-form>
      </v-navigation-drawer>
    </div>
  </OwnerLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { API_BASE_URL } from '../lib/api'
import { confirmDelete } from '../lib/confirmDelete'
import OwnerLayout from '@/layouts/OwnerLayout.vue'

const categories = ref([])
const branches = ref([])
const rightDrawer = ref(false)
const editing = ref(false)
const saving = ref(false)
const tab = ref('general')
const form = ref(blankForm())
const search = ref('')
const formError = ref('')
let localKey = 0
const filters = ref({
  branch_id: null,
  questions: null,
  modifiers: null,
})

const headers = [
  { title: 'Name', value: 'name' },
  { title: 'Branches', value: 'branches' },
  { title: 'Questions', value: 'questions', sortable: false },
  { title: 'Modifiers', value: 'modifiers', sortable: false },
  { title: 'Actions', value: 'actions', sortable: false }
]

const presenceFilterItems = [
  { title: 'Configured', value: 'has' },
  { title: 'Not configured', value: 'none' },
]

const ALL_BRANCHES_VALUE = '__all_branches__'

const branchSelectItems = computed(() =>
  branches.value.length > 1
    ? [{ id: ALL_BRANCHES_VALUE, name: 'All branches' }, ...branches.value]
    : branches.value
)

const filteredCategories = computed(() => {
  const q = search.value.toLowerCase()
  return categories.value.filter(cat =>
    (!q ||
      cat.name.toLowerCase().includes(q) ||
      categoryBranches(cat).some(branch => branch.name.toLowerCase().includes(q)) ||
      (cat.questions ?? []).some(question => question.question.toLowerCase().includes(q)) ||
      (cat.modifiers ?? []).some(modifier => modifier.name.toLowerCase().includes(q))) &&
    (!filters.value.branch_id || categoryBranchIds(cat).includes(Number(filters.value.branch_id))) &&
    matchesPresenceFilter(questionCount(cat), filters.value.questions) &&
    matchesPresenceFilter(modifierCount(cat), filters.value.modifiers)
  )
})

const canSave = computed(() =>
  Boolean(form.value.name.trim()) &&
  form.value.branch_ids.length > 0 &&
  !saving.value
)

async function loadCategories() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/categories`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  categories.value = res.data.data || res.data
}

async function loadBranches() {
  const token = localStorage.getItem('token')
  const res = await axios.get(`${API_BASE_URL}/branches`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  branches.value = res.data.data || res.data
}

function openAddDrawer() {
  editing.value = false
  tab.value = 'general'
  form.value = blankForm()
  formError.value = ''
  rightDrawer.value = true
}

function openEditDrawer(cat) {
  editing.value = true
  tab.value = 'general'
  form.value = categoryToForm(cat)
  formError.value = ''
  rightDrawer.value = true
}

async function saveCategory() {
  if (!canSave.value) return

  const token = localStorage.getItem('token')
  const payload = categoryPayload()
  const url = editing.value
    ? `${API_BASE_URL}/categories/${form.value.id}`
    : `${API_BASE_URL}/categories`
  const method = editing.value ? 'put' : 'post'

  try {
    saving.value = true
    formError.value = ''
    await axios[method](url, payload, {
      headers: { Authorization: `Bearer ${token}` }
    })
    rightDrawer.value = false
    await loadCategories()
  } catch (error) {
    formError.value = errorMessage(error)
  } finally {
    saving.value = false
  }
}

async function deleteCategory(cat) {
  if (!await confirmDelete(cat.name)) return
  const token = localStorage.getItem('token')
  await axios.delete(`${API_BASE_URL}/categories/${cat.id}`, {
    headers: { Authorization: `Bearer ${token}` }
  })
  loadCategories()
}

function addQuestion() {
  form.value.questions.push(blankQuestion())
}

function removeQuestion(index) {
  form.value.questions.splice(index, 1)
}

function addChoice(questionIndex) {
  form.value.questions[questionIndex].choices.push(blankChoice())
}

function removeChoice(questionIndex, choiceIndex) {
  form.value.questions[questionIndex].choices.splice(choiceIndex, 1)
}

function addModifier() {
  form.value.modifiers.push(blankModifier())
}

function removeModifier(index) {
  form.value.modifiers.splice(index, 1)
}

function onSelectedBranchesChange() {
  form.value.branch_ids = normalizedSelectedBranchIds(form.value.branch_ids)
}

function normalizedSelectedBranchIds(selected) {
  const values = Array.isArray(selected) ? selected : []
  if (values.includes(ALL_BRANCHES_VALUE)) {
    return branches.value.map(branch => branch.id)
  }

  return values
    .map(value => Number(value))
    .filter(value => Number.isFinite(value) && value > 0)
}

function questionCount(category) {
  return category.questions?.length ?? 0
}

function modifierCount(category) {
  return category.modifiers?.length ?? 0
}

function matchesPresenceFilter(count, value) {
  if (!value) return true
  return value === 'has' ? count > 0 : count === 0
}

function resetFilters() {
  search.value = ''
  filters.value = {
    branch_id: null,
    questions: null,
    modifiers: null,
  }
}

function blankForm() {
  return {
    id: null,
    name: '',
    branch_ids: branches.value.length === 1 ? [branches.value[0].id] : [],
    questions: [],
    modifiers: [],
  }
}

function blankQuestion() {
  return {
    id: null,
    local_key: nextLocalKey(),
    question: '',
    image: null,
    choices: [blankChoice()],
  }
}

function blankChoice(choice = {}) {
  return {
    id: choice.id ?? null,
    local_key: nextLocalKey(),
    choice: choice.choice ?? '',
    image: choice.image ?? null,
  }
}

function blankModifier(modifier = {}) {
  return {
    id: modifier.id ?? null,
    local_key: nextLocalKey(),
    name: modifier.name ?? '',
    price: modifier.price ?? 0,
  }
}

function categoryToForm(category) {
  return {
    id: category.id,
    name: category.name ?? '',
    branch_ids: categoryBranchIds(category),
    questions: (category.questions ?? []).map(question => ({
      id: question.id ?? null,
      local_key: nextLocalKey(),
      question: question.question ?? '',
      image: question.image ?? null,
      choices: (question.choices ?? []).map(choice => blankChoice(choice)),
    })),
    modifiers: (category.modifiers ?? []).map(modifier => blankModifier(modifier)),
  }
}

function categoryPayload() {
  return {
    name: form.value.name.trim(),
    branch_id: form.value.branch_ids[0],
    branch_ids: form.value.branch_ids,
    questions: form.value.questions
      .map(question => ({
        id: question.id,
        question: question.question.trim(),
        image: question.image || null,
        choices: question.choices
          .map(choice => ({
            id: choice.id,
            choice: choice.choice.trim(),
            image: choice.image || null,
          }))
          .filter(choice => choice.choice),
      }))
      .filter(question => question.question),
    modifiers: form.value.modifiers
      .map(modifier => ({
        id: modifier.id,
        name: modifier.name.trim(),
        price: Number(modifier.price || 0),
      }))
      .filter(modifier => modifier.name),
  }
}

function categoryBranchIds(category) {
  if (Array.isArray(category?.branch_ids) && category.branch_ids.length) {
    return category.branch_ids.map(Number)
  }
  return category?.branch_id ? [Number(category.branch_id)] : []
}

function categoryBranches(category) {
  if (Array.isArray(category?.branches) && category.branches.length) {
    return category.branches
  }
  if (category?.branch) return [category.branch]
  return categoryBranchIds(category)
    .map(id => branches.value.find(branch => Number(branch.id) === Number(id)))
    .filter(Boolean)
}

function nextLocalKey() {
  localKey += 1
  return `local-${Date.now()}-${localKey}`
}

function errorMessage(error) {
  const errors = error?.response?.data?.errors
  if (errors) return Object.values(errors).flat().join(' ')
  return error?.response?.data?.message || error?.response?.data?.error || 'Could not save this category.'
}

onMounted(() => {
  loadCategories()
  loadBranches()
})
</script>

<style scoped>
.categories-layout {
  display: flex;
  min-height: calc(100vh - 64px - 48px);
  transition: all 0.35s cubic-bezier(.4,0,.2,1);
  align-items: stretch;
}
.categories-container {
  padding-left: 0 !important;
  padding-right: 0 !important;
  width: 100%;
}
.v-card, .v-data-table, .v-toolbar, .v-navigation-drawer, .v-form, .v-btn, .v-text-field, .v-select, .v-switch {
  border-radius: 2rem !important;
}
.editor-heading {
  align-items: center;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 16px;
}
.editor-block {
  border: 1px solid rgba(42, 157, 143, 0.18);
  border-radius: 18px;
  margin-bottom: 16px;
  padding: 16px;
  position: relative;
}
.editor-block-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 4px;
}
.choice-row,
.modifier-row {
  align-items: center;
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}
.modifier-price {
  max-width: 130px;
}
.empty-note {
  color: #64748b;
  font-size: 0.9rem;
  padding: 18px 0;
}
</style>

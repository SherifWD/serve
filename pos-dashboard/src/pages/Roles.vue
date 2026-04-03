<template>
  <OwnerLayout>
    <v-container class="rs-page py-6" fluid>
      <v-row class="mb-4" align="stretch">
        <v-col cols="12" lg="7">
          <div class="rs-panel hero-panel">
            <div>
              <div class="page-kicker">Access Governance</div>
              <h1 class="page-title">Roles and permission bundles for every workspace.</h1>
              <p class="page-copy">
                Keep system-wide admin powers separate from restaurant owner controls and
                branch-level employee access.
              </p>
            </div>

            <v-btn
              v-if="auth.isAdmin"
              color="primary"
              size="large"
              class="rs-pill"
              @click="openCreate"
            >
              <v-icon start icon="mdi-plus" />
              Add role
            </v-btn>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Roles</div>
            <div class="metric-value">{{ roles.length }}</div>
          </div>
        </v-col>

        <v-col cols="12" md="6" lg="2">
          <div class="rs-panel metric-card">
            <div class="metric-label">Permission Rules</div>
            <div class="metric-value">{{ permissions.length }}</div>
          </div>
        </v-col>
      </v-row>

      <div class="rs-panel table-panel">
        <div class="panel-header">
          <div>
            <div class="panel-title">Role Matrix</div>
            <div class="panel-subtitle">Edit access by permission group and sync it to employees.</div>
          </div>
        </div>

        <v-data-table
          :headers="headers"
          :items="roles"
          item-value="id"
          class="rs-table"
          hide-default-footer
        >
          <template #item.permissions="{ item }">
            <div class="chip-stack">
              <v-chip
                v-for="permission in item.permissions.slice(0, 4)"
                :key="permission"
                size="small"
                variant="tonal"
                color="primary"
                class="rs-pill"
              >
                {{ permission }}
              </v-chip>
              <v-chip
                v-if="item.permissions.length > 4"
                size="small"
                variant="text"
                class="rs-pill"
              >
                +{{ item.permissions.length - 4 }} more
              </v-chip>
            </div>
          </template>

          <template #item.actions="{ item }">
            <div class="table-actions">
              <v-btn
                v-if="auth.isAdmin"
                size="small"
                icon="mdi-pencil-outline"
                variant="text"
                @click="openEdit(item)"
              />
              <v-btn
                v-if="auth.isAdmin"
                size="small"
                icon="mdi-delete-outline"
                variant="text"
                color="error"
                @click="removeRole(item.id)"
              />
            </div>
          </template>
        </v-data-table>
      </div>

      <v-dialog v-model="dialog" max-width="860">
        <v-card class="dialog-card">
          <v-card-title class="dialog-title">
            {{ editing ? 'Edit role' : 'Create role' }}
          </v-card-title>
          <v-card-text>
            <v-text-field
              v-model="form.name"
              label="Role name"
              variant="outlined"
              class="mb-4"
            />

            <div class="permission-groups">
              <div v-for="group in permissionGroups" :key="group.name" class="permission-group">
                <div class="group-title">{{ group.name }}</div>
                <v-row>
                  <v-col
                    v-for="permission in group.items"
                    :key="permission.name"
                    cols="12"
                    md="6"
                  >
                    <v-checkbox
                      v-model="form.permissions"
                      :label="permission.name"
                      :value="permission.name"
                      color="primary"
                      hide-details
                    />
                  </v-col>
                </v-row>
              </div>
            </div>
          </v-card-text>
          <v-card-actions class="justify-end px-6 pb-6">
            <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
            <v-btn color="primary" class="rs-pill" @click="saveRole">Save</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()

const roles = ref([])
const permissions = ref([])
const dialog = ref(false)
const editing = ref(false)
const form = ref({
  id: null,
  name: '',
  permissions: [],
})

const headers = [
  { title: 'Role', key: 'name' },
  { title: 'Users', key: 'users_count', width: 90 },
  { title: 'Permissions', key: 'permissions', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, width: 120 },
]

const permissionGroups = computed(() => {
  const grouped = new Map()

  for (const permission of permissions.value) {
    const bucket = grouped.get(permission.group) || []
    bucket.push(permission)
    grouped.set(permission.group, bucket)
  }

  return [...grouped.entries()].map(([name, items]) => ({ name, items }))
})

function authHeaders() {
  return { Authorization: `Bearer ${auth.token}` }
}

async function loadRoles() {
  const { data } = await axios.get(`${API_BASE_URL}/roles`, {
    headers: authHeaders(),
  })

  roles.value = data.roles || []
  permissions.value = data.permissions || []
}

function openCreate() {
  editing.value = false
  form.value = {
    id: null,
    name: '',
    permissions: [],
  }
  dialog.value = true
}

function openEdit(role) {
  editing.value = true
  form.value = {
    id: role.id,
    name: role.name,
    permissions: [...role.permissions],
  }
  dialog.value = true
}

async function saveRole() {
  const payload = {
    name: form.value.name,
    permissions: form.value.permissions,
  }

  if (editing.value) {
    await axios.put(`${API_BASE_URL}/roles/${form.value.id}`, payload, {
      headers: authHeaders(),
    })
  } else {
    await axios.post(`${API_BASE_URL}/roles`, payload, {
      headers: authHeaders(),
    })
  }

  dialog.value = false
  await loadRoles()
}

async function removeRole(id) {
  await axios.delete(`${API_BASE_URL}/roles/${id}`, {
    headers: authHeaders(),
  })
  await loadRoles()
}

onMounted(loadRoles)
</script>

<style scoped>
.hero-panel,
.metric-card,
.table-panel,
.dialog-card {
  padding: 24px;
}

.hero-panel {
  min-height: 220px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 24px;
}

.page-kicker,
.metric-label,
.panel-subtitle,
.group-title {
  color: var(--rs-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.76rem;
}

.page-title {
  max-width: 680px;
  margin-top: 0.8rem;
  color: var(--rs-text);
  font-size: 2.35rem;
  line-height: 1.05;
  letter-spacing: -0.04em;
}

.page-copy {
  max-width: 620px;
  margin-top: 0.8rem;
  color: var(--rs-text-soft);
  line-height: 1.7;
}

.metric-card {
  min-height: 220px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.metric-value {
  color: var(--rs-text);
  font-size: 3rem;
  line-height: 1;
  letter-spacing: -0.05em;
  margin-top: 1rem;
}

.table-panel {
  margin-top: 8px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.panel-title,
.dialog-title {
  color: var(--rs-text);
  font-size: 1.2rem;
  font-weight: 700;
}

.chip-stack {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.table-actions {
  display: flex;
  justify-content: flex-end;
}

.dialog-card {
  border: 1px solid var(--rs-border);
  border-radius: 24px;
  background: #101926;
}

.permission-groups {
  display: grid;
  gap: 18px;
}

.permission-group {
  padding: 18px;
  border: 1px solid var(--rs-border);
  border-radius: 20px;
  background: rgba(15, 24, 39, 0.72);
}

.group-title {
  margin-bottom: 10px;
}

@media (max-width: 960px) {
  .hero-panel {
    min-height: auto;
    flex-direction: column;
    align-items: flex-start;
  }

  .page-title {
    font-size: 2rem;
  }
}
</style>

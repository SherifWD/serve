<template>
  <v-container>
    <h2>Users</h2>
    <v-btn color="primary" @click="dialog = true" v-if="auth.user?.role === 'owner'">Invite User</v-btn>
    <v-data-table :items="users" :headers="headers" class="mt-4">
      <template #item.actions="{ item }">
        <v-btn icon @click="editUser(item)" v-if="auth.user?.role === 'owner'"><v-icon>mdi-pencil</v-icon></v-btn>
        <v-btn icon @click="deleteUser(item.id)" v-if="auth.user?.role === 'owner'"><v-icon>mdi-delete</v-icon></v-btn>
      </template>
    </v-data-table>
    <v-dialog v-model="dialog" max-width="500">
      <v-card>
        <v-card-title>{{ editing ? 'Edit User' : 'Invite User' }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="userForm.name" label="Name" required />
          <v-text-field v-model="userForm.email" label="Email" required />
          <v-select v-model="userForm.role" :items="['supervisor', 'staff']" label="Role" required />
          <v-select v-model="userForm.branch_id" :items="branches" item-title="name" item-value="id" label="Branch" required />
        </v-card-text>
        <v-card-actions>
          <v-btn color="primary" @click="saveUser">Save</v-btn>
          <v-btn @click="dialog = false">Cancel</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../store/auth'
const auth = useAuthStore()
const users = ref([])
const branches = ref([])
const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Email', key: 'email' },
  { title: 'Role', key: 'role' },
  { title: 'Branch', key: 'branch.name' },
  { title: 'Actions', key: 'actions', sortable: false }
]
const dialog = ref(false)
const editing = ref(false)
const userForm = ref({ id: null, name: '', email: '', role: '', branch_id: null })

async function loadUsers() {
  const { data } = await axios.get('http://localhost:8000/api/users', {
    headers: { Authorization: `Bearer ${auth.token}` }
  })
  users.value = data
}
async function loadBranches() {
  const { data } = await axios.get('http://localhost:8000/api/branches', {
    headers: { Authorization: `Bearer ${auth.token}` }
  })
  branches.value = data
}
function editUser(user) {
  userForm.value = { ...user }
  editing.value = true
  dialog.value = true
}
async function saveUser() {
  if (editing.value) {
    await axios.put(`http://localhost:8000/api/users/${userForm.value.id}`, userForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  } else {
    await axios.post('http://localhost:8000/api/users', userForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` }
    })
  }
  dialog.value = false
  loadUsers()
}
async function deleteUser(id) {
  await axios.delete(`http://localhost:8000/api/users/${id}`, {
    headers: { Authorization: `Bearer ${auth.token}` }
  })
  loadUsers()
}
onMounted(() => { loadUsers(); loadBranches() })
</script>

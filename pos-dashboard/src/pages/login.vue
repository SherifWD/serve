<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card>
          <v-card-title class="text-h5 text-center">Login</v-card-title>
          <v-card-text>
            <v-form @submit.prevent="login">
              <v-text-field
                v-model="email"
                label="Email"
                type="email"
                required
                class="mb-4"
              />
              <v-text-field
                v-model="password"
                label="Password"
                type="password"
                required
                class="mb-4"
              />
              <v-btn type="submit" color="primary" block>Login</v-btn>
              <v-alert
                v-if="error"
                type="error"
                class="mt-4"
                density="compact"
                border="start"
                text
              >
                {{ error }}
              </v-alert>
            </v-form>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'
const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const error = ref('')

async function login() {
  try {
    const { data } = await axios.post('http://localhost:8000/api/login', {
      email: email.value, password: password.value
    })
    auth.setToken(data.token)
    auth.setUser(data.user)
    localStorage.setItem('token', data.token)
    localStorage.setItem('user', JSON.stringify(data.user))
    router.push('/dashboard')
  } catch (e) {
    error.value = 'Invalid credentials'
  }
}
</script>

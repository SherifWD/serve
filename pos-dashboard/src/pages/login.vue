<template>
  <div class="login-page">
    <div class="login-hero">
      <div class="hero-copy">
        <div class="hero-kicker">Restaurant Suite</div>
        <h1 class="hero-title">Run every branch from one dark command center.</h1>
        <p class="hero-subtitle">
          Platform admin can control every restaurant. Owners can monitor live operations,
          branch performance, inventory, and permissions from a single dashboard.
        </p>
      </div>

      <div class="hero-points">
        <div class="hero-point">
          <v-icon icon="mdi-domain" />
          <span>Multi-restaurant platform control</span>
        </div>
        <div class="hero-point">
          <v-icon icon="mdi-chart-line" />
          <span>Live branch KPIs and payment mix</span>
        </div>
        <div class="hero-point">
          <v-icon icon="mdi-shield-account" />
          <span>Granular roles and permissions</span>
        </div>
      </div>
    </div>

    <v-card class="login-card">
      <div class="card-header">
        <div>
          <div class="card-kicker">Secure Access</div>
          <div class="card-title">Dashboard Login</div>
        </div>
        <v-chip color="primary" variant="tonal" class="rs-pill">
          Dark Mode
        </v-chip>
      </div>

      <v-form @submit.prevent="login">
        <v-text-field
          v-model="email"
          label="Email"
          type="email"
          variant="outlined"
          density="comfortable"
          class="mb-4"
          hide-details="auto"
          prepend-inner-icon="mdi-email-outline"
        />

        <v-text-field
          v-model="password"
          label="Password"
          type="password"
          variant="outlined"
          density="comfortable"
          class="mb-4"
          hide-details="auto"
          prepend-inner-icon="mdi-lock-outline"
        />

        <v-alert
          v-if="error"
          type="error"
          variant="tonal"
          density="comfortable"
          class="mb-4"
        >
          {{ error }}
        </v-alert>

        <v-btn
          type="submit"
          color="primary"
          size="large"
          block
          class="rs-pill login-button"
          :loading="loading"
        >
          Sign In
        </v-btn>
      </v-form>

      <div class="seed-hint">
        <div class="seed-hint-title">Seeded admin</div>
        <div>admin@restaurant-suite.com / password</div>
        <div class="seed-hint-api">API: {{ API_BASE_URL }}</div>
      </div>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('admin@restaurant-suite.com')
const password = ref('password')
const error = ref('')
const loading = ref(false)

async function login() {
  loading.value = true
  error.value = ''

  try {
    const { data } = await axios.post(`${API_BASE_URL}/login`, {
      email: email.value,
      password: password.value,
    })

    auth.setSession({ user: data.user, token: data.token })
    router.push('/dashboard')
  } catch (requestError) {
    if (requestError?.response?.data?.message) {
      error.value = requestError.response.data.message
    } else if (requestError?.response?.status) {
      error.value = `Login failed with status ${requestError.response.status}. Check backend logs for the exact cause.`
    } else {
      error.value = `Dashboard could not reach the API at ${API_BASE_URL}.`
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(360px, 480px);
  gap: 32px;
  padding: 32px;
}

.login-hero,
.login-card {
  border: 1px solid var(--rs-border);
  border-radius: 32px;
  background: linear-gradient(180deg, rgba(13, 22, 35, 0.95), rgba(8, 15, 26, 0.95));
  box-shadow: var(--rs-shadow);
}

.login-hero {
  padding: 44px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 540px;
}

.hero-kicker,
.card-kicker,
.seed-hint-title {
  color: var(--rs-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 0.78rem;
}

.hero-title {
  max-width: 720px;
  margin-top: 1rem;
  color: var(--rs-text);
  font-size: clamp(2.3rem, 4vw, 4.4rem);
  line-height: 1.02;
  letter-spacing: -0.05em;
}

.hero-subtitle {
  max-width: 640px;
  margin-top: 1rem;
  color: var(--rs-text-soft);
  font-size: 1.05rem;
  line-height: 1.7;
}

.hero-points {
  display: grid;
  gap: 14px;
}

.hero-point {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 18px;
  border: 1px solid var(--rs-border);
  border-radius: 20px;
  color: var(--rs-text-soft);
  background: rgba(17, 28, 43, 0.74);
}

.login-card {
  padding: 30px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 28px;
}

.card-title {
  color: var(--rs-text);
  font-size: 1.9rem;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.login-button {
  height: 52px !important;
  font-weight: 700;
}

.seed-hint {
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid var(--rs-border);
  color: var(--rs-text-soft);
}

.seed-hint-api {
  margin-top: 6px;
  color: var(--rs-text-muted);
  font-size: 0.82rem;
  word-break: break-all;
}

@media (max-width: 1100px) {
  .login-page {
    grid-template-columns: 1fr;
  }

  .login-hero {
    min-height: auto;
  }
}

@media (max-width: 640px) {
  .login-page {
    padding: 16px;
  }

  .login-hero,
  .login-card {
    padding: 24px;
    border-radius: 24px;
  }
}
</style>

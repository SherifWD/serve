<template>
  <OwnerLayout>
    <v-container class="py-6" fluid>
      <v-row>
        <v-col cols="12" lg="8">
          <v-card color="card" elevation="3" class="mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h5 font-weight-bold settings-title">Platform Settings</span>
              <v-spacer />
              <v-chip color="primary" variant="tonal">
                Connected
              </v-chip>
            </v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.platformName"
                    label="Platform name"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.supportEmail"
                    label="Support email"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.defaultCurrency"
                    label="Default currency"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field
                    v-model="form.timezone"
                    label="Timezone"
                    variant="outlined"
                    density="comfortable"
                  />
                </v-col>
              </v-row>

              <div class="d-flex flex-wrap ga-4 mt-4">
                <v-switch
                  v-model="form.loyaltyEnabled"
                  color="primary"
                  label="Enable loyalty program"
                  inset
                />
                <v-switch
                  v-model="form.multiBranchMode"
                  color="primary"
                  label="Enable multi-branch mode"
                  inset
                />
              </div>

              <v-btn color="primary" class="mt-4" @click="showSaved = true">
                Save settings
              </v-btn>

              <v-alert
                v-if="showSaved"
                type="success"
                variant="tonal"
                class="mt-4"
                closable
                @click:close="showSaved = false"
              >
                Settings UI is ready. Persist these values when the backend settings endpoint is added.
              </v-alert>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" lg="4">
          <v-card color="card" elevation="3" class="mb-6 rounded-xl">
            <v-card-title class="settings-title">Environment</v-card-title>
            <v-card-text>
              <div class="mb-3">
                <div class="label">API Base URL</div>
                <div class="value">{{ apiBaseUrl }}</div>
              </div>
              <div class="mb-3">
                <div class="label">Logged in user</div>
                <div class="value">{{ auth.user?.name || 'Unknown user' }}</div>
              </div>
              <div class="mb-3">
                <div class="label">Restaurant ID</div>
                <div class="value">{{ auth.user?.restaurant_id ?? 'N/A' }}</div>
              </div>
              <div>
                <div class="label">Branch ID</div>
                <div class="value">{{ auth.user?.branch_id ?? 'N/A' }}</div>
              </div>
            </v-card-text>
          </v-card>

          <v-card color="card" elevation="3" class="rounded-xl">
            <v-card-title class="settings-title">Deployment Notes</v-card-title>
            <v-card-text>
              <v-list density="compact" bg-color="transparent">
                <v-list-item title="Vue routes use hash history for Hostinger shared hosting." />
                <v-list-item title="All dashboard pages now require auth except login." />
                <v-list-item title="API calls use VITE_API_BASE_URL, so the same build can target staging or production." />
              </v-list>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { ref } from 'vue'
import OwnerLayout from '@/layouts/OwnerLayout.vue'
import { API_BASE_URL } from '../lib/api'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const apiBaseUrl = API_BASE_URL
const showSaved = ref(false)

const form = ref({
  platformName: 'Restaurant Suite',
  supportEmail: 'support@example.com',
  defaultCurrency: 'EGP',
  timezone: 'Africa/Cairo',
  loyaltyEnabled: true,
  multiBranchMode: true,
})
</script>

<style scoped>
.settings-title {
  color: #2a9d8f;
}

.label {
  color: #9fb2a8;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.35rem;
}

.value {
  color: #eef3ee;
  word-break: break-word;
}
</style>

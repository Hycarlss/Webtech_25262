<template>
  <div class="profile-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <h1 class="page-title">My Profile</h1>
      <p class="page-subtitle">Manage your student credentials and hostel room details</p>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading profile details...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Profile</h3>
      <p>{{ error }}</p>
      <button @click="fetchUser" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Profile Display -->
    <div v-else>
      <ProfileCard
        :user="user"
        :saving="saving"
        :success-msg="successMessage"
        @save="handleSaveProfile"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

import ProfileCard from '@/components/ProfileCard.vue'

const user = ref({})
const loading = ref(true)
const error = ref(null)

const saving = ref(false)
const successMessage = ref('')

const fetchUser = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('http://localhost:3000/users/1')
    if (!res.ok) throw new Error('Could not fetch user profile details.')
    user.value = await res.json()
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An error occurred while loading profile details.'
  } finally {
    loading.value = false
  }
}

const handleSaveProfile = async (updatedFields) => {
  saving.value = true
  successMessage.value = ''
  try {
    const res = await fetch('http://localhost:3000/users/1', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(updatedFields)
    })

    if (!res.ok) throw new Error('Failed to update profile on the server.')

    const updatedUser = await res.json()
    user.value = updatedUser
    successMessage.value = 'Profile updated successfully!'

    // Automatically hide success alert after 4 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 4000)
  } catch (err) {
    alert(err.message || 'An error occurred while saving profile changes.')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchUser()
})
</script>

<style scoped>
.profile-view {
  width: 100%;
}

.page-header-block {
  background-color: var(--primary-pink);
  margin-bottom: 24px;
}

.page-title {
  font-size: 32px;
  margin-bottom: 4px;
}

.page-subtitle {
  font-size: 16px;
  font-weight: 500;
}

.loading-state, .error-state {
  text-align: center;
  padding: 60px 24px;
  background-color: #FFFFFF;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--primary-yellow);
  border-top-color: #000000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

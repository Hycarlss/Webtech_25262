<template>
  <div class="new-request-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <h1 class="page-title">Submit Maintenance Issue</h1>
      <p class="page-subtitle">Report physical or utility problems in your room or hostel block</p>
    </div>

    <!-- Form container -->
    <div v-if="loadingUser" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Initializing request form...</p>
    </div>

    <div v-else>
      <MaintenanceForm
        :default-location="userRoom"
        @submit="handleSubmitRequest"
        @cancel="handleCancel"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import MaintenanceForm from '@/components/MaintenanceForm.vue'

const router = useRouter()

const user = ref({})
const userRoom = ref('')
const loadingUser = ref(true)

const fetchUserProfile = () => {
  try {
    const storedUser = JSON.parse(localStorage.getItem('user'))

    if (!storedUser) {
      throw new Error('No logged in user found')
    }

    user.value = storedUser
    userRoom.value = storedUser.roomNumber || ''
  } catch (err) {
    console.error('Failed to load user:', err)
  } finally {
    loadingUser.value = false
  }
}

onMounted(() => {
  fetchUserProfile()
})

const handleSubmitRequest = async (payload) => {
  try {
    // Generate submission date and deadline (+7 days)
    const today = new Date()
    const deadlineDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
    
    const formatDate = (date) => {
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    }

    const reportData = {
      title: payload.title,
      description: payload.description,
      room: payload.location,
      studentName: user.value.name,
      dateSubmitted: formatDate(today),
      assignedStaff: 'Unassigned',
      deadline: formatDate(deadlineDate),
      status: 'Pending'
    }

    const res = await fetch('http://localhost:8000/reports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(reportData)
    })

    if (!res.ok) throw new Error('Failed to submit maintenance report.')

    // Redirect to main maintenance request view
    router.push('/maintenance')
  } catch (err) {
    alert(err.message || 'An error occurred while submitting your request.')
  }
}

const handleCancel = () => {
  router.push('/maintenance')
}
</script>

<style scoped>
.new-request-view {
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

.loading-state {
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

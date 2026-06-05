<template>
  <div class="facilities-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Facilities Booking</h1>
        <p class="page-subtitle">Reserve rooms, halls, and courts for your studying or recreational needs</p>
      </div>
      <div class="header-actions">
        <RouterLink to="/bookings" class="neo-btn neo-btn-pink">
          Booking History &rarr;
        </RouterLink>
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading available facilities...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Facilities</h3>
      <p>{{ error }}</p>
      <button @click="fetchFacilities" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Facilities Grid -->
    <div v-else>
      <div v-if="facilities.length === 0" class="empty-state neo-card">
        <h3>No Facilities Available</h3>
        <p>There are no facilities listed at the moment. Please check back later.</p>
      </div>
      
      <div v-else class="grid-2 facilities-grid">
        <FacilityCard
          v-for="facility in facilities"
          :key="facility.id"
          :facility="facility"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

import FacilityCard from '@/components/FacilityCard.vue'

const facilities = ref([])
const loading = ref(true)
const error = ref(null)

const fetchFacilities = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await fetch('http://localhost:3000/facilities')
    if (!res.ok) throw new Error('Could not retrieve hostel facilities.')
    facilities.value = await res.json()
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An error occurred while fetching facilities.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchFacilities()
})
</script>

<style scoped>
.facilities-view {
  width: 100%;
}

.page-header-block {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.facilities-grid {
  margin-top: 12px;
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

.empty-state {
  text-align: center;
  padding: 40px 20px;
  background-color: #FFFFFF;
}

@media (max-width: 768px) {
  .page-header-block {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .header-actions {
    width: 100%;
  }
  .header-actions > * {
    width: 100%;
  }
}
</style>

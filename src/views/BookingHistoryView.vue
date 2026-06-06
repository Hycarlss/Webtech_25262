<template>
  <div class="bookings-history-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Booking History</h1>
        <p class="page-subtitle">
          {{ user.role === 'staff/admin' ? 'Manage all active and past hostel facility bookings' : 'View and search details of your active and past facility reservations' }}
        </p>
      </div>
      <div class="header-actions">
        <RouterLink v-if="user.role === 'student'" to="/facilities" class="neo-btn neo-btn-white">
          &larr; Book Facilities
        </RouterLink>
        <RouterLink v-else to="/facilities" class="neo-btn neo-btn-white">
          &larr; Facilities List
        </RouterLink>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="controls-panel neo-card">
      <div class="search-row">
        <SearchBar v-model="searchQuery" placeholder="Search reservations by facility name..." />
      </div>
      <div class="filter-row">
        <FilterBar
          v-model="selectedStatus"
          :options="['All', 'Pending', 'Approved', 'Rejected']"
        />
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Retrieving booking records...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Booking History</h3>
      <p>{{ error }}</p>
      <button @click="fetchBookings" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Bookings Listing -->
    <div v-else>
      <BookingList :bookings="filteredBookings" @update-status="handleUpdateStatus" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

import SearchBar from '@/components/SearchBar.vue'
import FilterBar from '@/components/FilterBar.vue'
import BookingList from '@/components/BookingList.vue'

const user = ref({})
const bookings = ref([])
const loading = ref(true)
const error = ref(null)

const searchQuery = ref('')
const selectedStatus = ref('All')

const fetchBookings = async () => {
  loading.value = true
  error.value = null
  try {
    const storedUser = JSON.parse(localStorage.getItem('user'))
    if (!storedUser) {
      throw new Error('No logged-in user found')
    }
    user.value = storedUser
    user.value.role = user.value.role || 'student'

    const res = await fetch('http://localhost:3000/bookings')
    if (!res.ok) throw new Error('Could not fetch booking history records.')
    const allBookings = await res.json()

    // Filter by role
    if (user.value.role === 'staff/admin') {
      bookings.value = allBookings
    } else {
      // Show student's own bookings
      bookings.value = allBookings.filter(
        b => b.studentName === user.value.name || (!b.studentName && user.value.name === 'John Doe')
      )
    }
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An error occurred while loading booking history.'
  } finally {
    loading.value = false
  }
}

const handleUpdateStatus = async (id, newStatus) => {
  try {
    const res = await fetch(`http://localhost:3000/bookings/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })
    if (!res.ok) throw new Error('Could not update booking status.')
    
    // Update local state directly
    const index = bookings.value.findIndex(b => b.id === id)
    if (index !== -1) {
      bookings.value[index].status = newStatus
    }
  } catch (err) {
    alert(err.message)
  }
}

onMounted(() => {
  fetchBookings()
})

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    const matchesSearch = booking.facilityName
      ? booking.facilityName.toLowerCase().includes(searchQuery.value.toLowerCase())
      : false
    const matchesStatus = selectedStatus.value === 'All' || booking.status === selectedStatus.value
    return matchesSearch && matchesStatus
  })
})
</script>

<style scoped>
.bookings-history-view {
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

.controls-panel {
  background-color: #FFFFFF;
  margin-bottom: 24px;
  padding: 20px;
}

.search-row {
  margin-bottom: 8px;
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

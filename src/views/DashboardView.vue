<template>
  <div class="dashboard-view">
    <!-- Loading State -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading your dashboard details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state neo-card">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        class="error-icon"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
        />
      </svg>
      <h3>Error Loading Dashboard</h3>
      <p>{{ error }}</p>
      <button @click="fetchDashboardData" class="neo-btn neo-btn-yellow mt-4">
        Retry
      </button>
    </div>

    <!-- Dashboard Content -->
    <div v-else>
      <WelcomeSection :name="user.name" />

      <!-- Statistics Grid -->
      <div class="grid-2 stats-container">
        <StatCard
          title="Maintenance Requests"
          :value="pendingReportsCount + inProgressReportsCount"
          :description="`${pendingReportsCount} pending, ${inProgressReportsCount} in progress`"
          variant="yellow"
        />
        <StatCard
          title="Active Bookings"
          :value="activeBookingsCount"
          description="Confirmed facility reservations"
          variant="pink"
        />
      </div>

      <!-- Main Layout Grid -->
      <div class="dashboard-grid mt-6">
        <div class="left-col">
          <div class="section-header">
            <h2>Recent Maintenance Requests</h2>
            <RouterLink to="/maintenance" class="view-all-link">View All &rarr;</RouterLink>
          </div>
          <MaintenanceList :reports="recentReports" :show-actions="false" />
        </div>

        <div class="right-col">
          <!-- Room Card -->
          <RoomCard
            v-if="user.role !== 'staff/admin'"
            :hostel-block="user.hostelBlock"
            :room-number="user.roomNumber"
          />

          <!-- Upcoming Bookings -->
          <div class="section-header mt-6">
            <h2>Upcoming Bookings</h2>
            <RouterLink to="/bookings" class="view-all-link">History &rarr;</RouterLink>
          </div>
          <BookingList :bookings="recentBookings" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

import WelcomeSection from '@/components/WelcomeSection.vue'
import StatCard from '@/components/StatCard.vue'
import RoomCard from '@/components/RoomCard.vue'
import MaintenanceList from '@/components/MaintenanceList.vue'
import BookingList from '@/components/BookingList.vue'

const user = ref({})
const reports = ref([])
const bookings = ref([])
const loading = ref(true)
const error = ref(null)

const fetchDashboardData = async () => {
  loading.value = true
  error.value = null
  try {
    // Fetch user profile from localStorage
    const storedUser = JSON.parse(localStorage.getItem('user'))

    if (!storedUser) {
      throw new Error('No logged-in user found.')
    }

    const userRes = await fetch(
      `http://localhost:3000/users/${storedUser.id}`
    )

    if (!userRes.ok) {
      throw new Error('Failed to load user profile.')
    }

    user.value = await userRes.json()
    // Ensure role defaults to 'student'
    user.value.role = user.value.role || 'student'

    // Fetch reports
    const reportsRes = await fetch('http://localhost:3000/reports')
    if (!reportsRes.ok) throw new Error('Failed to load maintenance requests.')
    const allReports = await reportsRes.json()

    // Filter reports by role
    if (user.value.role === 'staff/admin') {
      reports.value = allReports
    } else {
      reports.value = allReports.filter(r => r.studentName === user.value.name)
    }

    // Fetch bookings
    const bookingsRes = await fetch('http://localhost:3000/bookings')
    if (!bookingsRes.ok) throw new Error('Failed to load bookings.')
    const allBookings = await bookingsRes.json()

    // Filter bookings by role
    if (user.value.role === 'staff/admin') {
      bookings.value = allBookings
    } else {
      // Show student's own bookings. Also map John Doe to existing seed bookings without studentName
      bookings.value = allBookings.filter(
        b => b.studentName === user.value.name || (!b.studentName && user.value.name === 'John Doe')
      )
    }
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An unexpected error occurred.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

// Computations
const pendingReportsCount = computed(() => {
  return reports.value.filter(r => r.status === 'Pending').length
})

const inProgressReportsCount = computed(() => {
  return reports.value.filter(r => r.status === 'In Progress').length
})

const activeBookingsCount = computed(() => {
  return bookings.value.filter(b => b.status === 'Approved').length
})

// Latest 5 reports (sorted descending by ID or submission date)
const recentReports = computed(() => {
  return [...reports.value]
    .sort((a, b) => b.id - a.id)
    .slice(0, 5)
})

// Latest 5 bookings (sorted descending by ID)
const recentBookings = computed(() => {
  return [...bookings.value]
    .sort((a, b) => b.id - a.id)
    .slice(0, 5)
})
</script>

<style scoped>
.dashboard-view {
  width: 100%;
}

.stats-container {
  margin-bottom: 24px;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 28px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  border-bottom: 3px solid #000000;
  padding-bottom: 8px;
}

.section-header h2 {
  font-size: 22px;
}

.view-all-link {
  color: var(--logo-purple);
  text-decoration: none;
  font-weight: 700;
  font-size: 15px;
}

.view-all-link:hover {
  text-decoration: underline;
}

.mt-4 { margin-top: 16px; }
.mt-6 { margin-top: 24px; }

/* Loading / Error States styling */
.loading-state, .error-state {
  text-align: center;
  padding: 80px 24px;
  background-color: #FFFFFF;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 5px solid var(--primary-yellow);
  border-top-color: #000000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  width: 60px;
  height: 60px;
  color: var(--status-rejected);
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
</style>
<template>
  <div class="maintenance-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Maintenance Requests</h1>
        <p class="page-subtitle">Submit and track facility issues in your hostel</p>
      </div>
      <div class="header-actions">
        <RouterLink
          v-if="user.role === 'student'"
          to="/maintenance/new"
          class="neo-btn neo-btn-yellow"
        >
          New Request +
        </RouterLink>
        <RouterLink to="/maintenance/tracking" class="neo-btn neo-btn-pink">
          Track Timeline &rarr;
        </RouterLink>
      </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="controls-panel neo-card">
      <div class="search-row">
        <SearchBar v-model="searchQuery" placeholder="Search requests by title..." />
      </div>
      <div class="filter-row">
        <FilterBar v-model="selectedStatus" :options="['All', 'Pending', 'In Progress', 'Completed']" />
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading maintenance requests...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Requests</h3>
      <p>{{ error }}</p>
      <button @click="fetchReports" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Main List Content -->
    <div v-else>
      <MaintenanceList
        :reports="filteredReports"
        :show-actions="user.role === 'staff/admin'"
        @update-status="handleUpdateStatus"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

import SearchBar from '@/components/SearchBar.vue'
import FilterBar from '@/components/FilterBar.vue'
import MaintenanceList from '@/components/MaintenanceList.vue'

const user = ref({})
const reports = ref([])
const loading = ref(true)
const error = ref(null)

const searchQuery = ref('')
const selectedStatus = ref('All')

const fetchReports = async () => {
  loading.value = true
  error.value = null
  try {
    const storedUser = JSON.parse(localStorage.getItem('user'))
    if (!storedUser) {
      throw new Error('No logged-in user found')
    }
    user.value = storedUser
    user.value.role = user.value.role || 'student'

    const res = await fetch('http://localhost:8000/reports')
    if (!res.ok) throw new Error('Could not fetch maintenance reports.')
    const allReports = await res.json()

    // Filter by role
    if (user.value.role === 'staff/admin') {
      reports.value = allReports
    } else {
      reports.value = allReports.filter(r => r.studentName === user.value.name)
    }
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An error occurred while loading requests.'
  } finally {
    loading.value = false
  }
}

const handleUpdateStatus = async (id, newStatus) => {
  try {
    const res = await fetch(`http://localhost:8000/reports/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })
    if (!res.ok) throw new Error('Could not update request status.')
    
    // Update local state directly to prevent complete page re-load flicker
    const index = reports.value.findIndex(r => r.id === id)
    if (index !== -1) {
      reports.value[index].status = newStatus
    }
  } catch (err) {
    alert(err.message)
  }
}

onMounted(() => {
  fetchReports()
})

const filteredReports = computed(() => {
  return reports.value.filter(report => {
    const matchesSearch = report.title
      ? report.title.toLowerCase().includes(searchQuery.value.toLowerCase())
      : false
    const matchesStatus = selectedStatus.value === 'All' || report.status === selectedStatus.value
    return matchesSearch && matchesStatus
  })
})
</script>

<style scoped>
.maintenance-view {
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

.header-actions {
  display: flex;
  gap: 16px;
}

.controls-panel {
  background-color: #FFFFFF;
  margin-bottom: 24px;
  padding: 20px;
}

.search-row {
  margin-bottom: 8px;
}

/* Loading state styles */
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

.mt-4 { margin-top: 16px; }

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
    flex: 1;
  }
}
</style>

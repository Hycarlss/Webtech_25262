<template>
  <div class="tracking-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Maintenance Tracking</h1>
        <p class="page-subtitle">Track the status timeline of all hostel maintenance tasks</p>
      </div>
      <div class="header-actions">
        <RouterLink to="/maintenance" class="neo-btn neo-btn-white">
          &larr; Back to Requests
        </RouterLink>
      </div>
    </div>

    <!-- Search and Filter Panel -->
    <div class="controls-panel neo-card">
      <div class="search-row">
        <SearchBar v-model="searchQuery" placeholder="Search tracking reports by title..." />
      </div>
      <div class="filter-row">
        <FilterBar v-model="selectedStatus" :options="['All', 'Pending', 'In Progress', 'Completed']" />
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading tracking timeline...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Timeline</h3>
      <p>{{ error }}</p>
      <button @click="fetchReports" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Timeline List Content -->
    <div v-else>
      <div v-if="filteredReports.length === 0" class="empty-state neo-card">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="2"
          stroke="currentColor"
          class="empty-icon"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
          />
        </svg>
        <h3>No Reports Tracked</h3>
        <p>No maintenance issues match your search parameters.</p>
      </div>

      <div v-else class="reports-timeline-list">
        <!-- Loop over reports -->
        <div
          v-for="report in filteredReports"
          :key="report.id"
          class="tracking-item neo-card"
        >
          <div class="report-header">
            <div>
              <h3 class="report-title">{{ report.title }}</h3>
              <p class="report-room">Room: <strong>{{ report.room }}</strong> | Submitted by: <strong>{{ report.studentName }}</strong></p>
            </div>
            <StatusBadge :status="report.status" />
          </div>

          <p class="report-desc">{{ report.description }}</p>

          <!-- Timeline representation -->
          <div class="timeline-container">
            <h4 class="timeline-title">Status Flow</h4>
            
            <div class="timeline-flow">
              <!-- Step 1: Pending -->
              <div
                class="timeline-step"
                :class="{
                  'active': report.status === 'Pending',
                  'completed': report.status === 'In Progress' || report.status === 'Completed'
                }"
              >
                <div class="step-marker">1</div>
                <div class="step-label">Pending</div>
              </div>

              <div
                class="timeline-connector"
                :class="{ 'completed': report.status === 'In Progress' || report.status === 'Completed' }"
              ></div>

              <!-- Step 2: In Progress -->
              <div
                class="timeline-step"
                :class="{
                  'active': report.status === 'In Progress',
                  'completed': report.status === 'Completed',
                  'dimmed': report.status === 'Pending'
                }"
              >
                <div class="step-marker">2</div>
                <div class="step-label">In Progress</div>
              </div>

              <div
                class="timeline-connector"
                :class="{ 'completed': report.status === 'Completed' }"
              ></div>

              <!-- Step 3: Completed -->
              <div
                class="timeline-step"
                :class="{
                  'active': report.status === 'Completed',
                  'dimmed': report.status === 'Pending' || report.status === 'In Progress'
                }"
              >
                <div class="step-marker">3</div>
                <div class="step-label">Completed</div>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="report-footer-meta">
            <div>Date Submitted: <strong>{{ formatDate(report.dateSubmitted) }}</strong></div>
            <div>Assigned Staff: <strong>{{ report.assignedStaff || 'Unassigned' }}</strong></div>
            <div>Deadline: <strong class="text-red">{{ formatDate(report.deadline) || 'Not set' }}</strong></div>
          </div>

          <!-- Quick Action to move along status (Admin simulation) -->
          <div v-if="user.role === 'staff/admin' && report.status !== 'Completed'" class="quick-status-actions">
            <button
              v-if="report.status === 'Pending'"
              @click="updateStatus(report.id, 'In Progress')"
              class="neo-btn neo-btn-pink btn-sm"
            >
              Start Progress &rarr;
            </button>
            <button
              v-if="report.status === 'In Progress'"
              @click="updateStatus(report.id, 'Completed')"
              class="neo-btn neo-btn-yellow btn-sm"
            >
              Mark Completed &rarr;
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

import SearchBar from '@/components/SearchBar.vue'
import FilterBar from '@/components/FilterBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'

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
    if (!res.ok) throw new Error('Could not fetch maintenance timeline.')
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

const updateStatus = async (id, newStatus) => {
  try {
    const res = await fetch(`http://localhost:8000/reports/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })
    if (!res.ok) throw new Error('Could not update request status.')
    
    // Update locally
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

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return dateStr
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch (e) {
    return dateStr
  }
}
</script>

<style scoped>
.tracking-view {
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

.reports-timeline-list {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.tracking-item {
  background-color: #FFFFFF;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  border-bottom: 2px solid #000000;
  padding-bottom: 12px;
}

.report-title {
  font-size: 22px;
  margin-bottom: 4px;
}

.report-room {
  font-size: 14px;
  color: #666666;
}

.report-desc {
  font-size: 15px;
  color: #333333;
  line-height: 1.4;
}

/* Timeline flow styles */
.timeline-container {
  background: var(--bg-color);
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 2px 2px 0px #000000;
  margin: 8px 0;
}

.timeline-title {
  font-size: 14px;
  text-transform: uppercase;
  margin-bottom: 16px;
  letter-spacing: 0.5px;
}

.timeline-flow {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  flex: 1;
  position: relative;
  z-index: 1;
}

.step-marker {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid #000000;
  background-color: #FFFFFF;
  color: #000000;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 2px 2px 0px #000000;
  transition: all 0.2s ease;
}

.step-label {
  font-weight: 700;
  font-size: 13px;
  text-align: center;
}

/* Timeline step states */
.timeline-step.active .step-marker {
  background-color: var(--primary-yellow);
  transform: scale(1.1);
  box-shadow: 3px 3px 0px #000000;
}

.timeline-step.completed .step-marker {
  background-color: var(--status-completed);
  color: #000000;
}

.timeline-step.dimmed {
  opacity: 0.5;
}

.timeline-connector {
  height: 4px;
  background-color: #000000;
  opacity: 0.2;
  flex-grow: 1;
  margin: 0 -18px; /* overlap with step circles */
  position: relative;
  top: -12px; /* align center with circles */
  z-index: 0;
}

.timeline-connector.completed {
  opacity: 1;
  background-color: var(--status-completed);
}

.report-footer-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px 24px;
  border-top: 1px dashed #000000;
  padding-top: 12px;
  font-size: 14px;
}

.text-red {
  color: #FF006E;
}

.quick-status-actions {
  display: flex;
  gap: 12px;
  margin-top: 4px;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  background-color: #FFFFFF;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.empty-icon {
  width: 48px;
  height: 48px;
  color: #888888;
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

@media (max-width: 500px) {
  .timeline-flow {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    padding-left: 16px;
  }
  .timeline-step {
    flex-direction: row;
    gap: 16px;
    flex: initial;
    width: 100%;
  }
  .timeline-connector {
    display: none;
  }
}
</style>

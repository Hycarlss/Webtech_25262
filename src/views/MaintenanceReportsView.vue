<template>
  <div class="maintenance-reports-page">
    <!-- Page Header -->
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Hostel Maintenance Center</h1>
        <p class="page-subtitle">Submit requests and track resolution progress for facility issues</p>
      </div>
      <div class="header-actions">
        <button
          v-if="user.role === 'student'"
          @click="showCreateModal = true"
          class="neo-btn neo-btn-yellow"
        >
          New Request +
        </button>
        <button @click="fetchData" class="neo-btn neo-btn-white">
          Refresh Data
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div v-if="!loading" class="grid-4 stats-container">
      <StatCard
        title="Total Reports"
        :value="stats.totalReports"
        description="All reported issues"
        variant="white"
      />
      <StatCard
        title="Pending Action"
        :value="stats.pendingReports"
        description="Awaiting staff assignment"
        variant="yellow"
      />
      <StatCard
        title="In Progress"
        :value="stats.inProgressReports"
        description="Being resolved by staff"
        variant="pink"
      />
      <StatCard
        title="Resolved"
        :value="stats.resolvedReports"
        description="Completed issues"
        variant="white"
      />
    </div>

    <!-- Search and Filters Panel -->
    <div class="controls-panel neo-card">
      <div class="search-row">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search by title, description, or code (e.g. MR-0001)..."
          class="form-input search-input"
        />
      </div>
      <div class="filter-row filter-grid">
        <!-- Status Filter -->
        <div class="form-group">
          <label class="form-label" for="status-filter">Status</label>
          <select id="status-filter" v-model="filters.status" class="form-select">
            <option value="All">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Assigned">Assigned</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <!-- Priority Filter -->
        <div class="form-group">
          <label class="form-label" for="priority-filter">Priority</label>
          <select id="priority-filter" v-model="filters.priority" class="form-select">
            <option value="All">All Priorities</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
        </div>

        <!-- Category Filter -->
        <div class="form-group">
          <label class="form-label" for="category-filter">Category</label>
          <select id="category-filter" v-model="filters.category" class="form-select">
            <option value="All">All Categories</option>
            <option value="Electrical">Electrical</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Furniture">Furniture</option>
            <option value="Internet">Internet</option>
            <option value="Air Conditioning">Air Conditioning</option>
            <option value="Cleaning">Cleaning</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <!-- Block Filter -->
        <div class="form-group">
          <label class="form-label" for="block-filter">Hostel Block</label>
          <select id="block-filter" v-model="filters.block" class="form-select">
            <option value="All">All Blocks</option>
            <option value="A">Block A</option>
            <option value="B">Block B</option>
            <option value="C">Block C</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading / Error States -->
    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading maintenance reports...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Data</h3>
      <p>{{ error }}</p>
      <button @click="fetchData" class="neo-btn neo-btn-yellow mt-4">Retry</button>
    </div>

    <!-- Reports Table -->
    <div v-else>
      <MaintenanceReportTable
        :reports="filteredReports"
        :is-admin="user.role === 'staff/admin'"
        @assign-staff="openAssignModal"
        @update-status="openStatusModal"
        @delete-report="handleDeleteReport"
        @view-timeline="openTimelineModal"
      />
    </div>

    <!-- MODAL 1: Create Report -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="details-modal">
        <MaintenanceReportForm
          :user-profile="user"
          @submit="handleCreateReport"
          @cancel="showCreateModal = false"
        />
      </div>
    </div>

    <!-- MODAL 2: Assign Staff -->
    <div v-if="showAssignModal" class="modal-backdrop" @click.self="showAssignModal = false">
      <div class="details-modal neo-card assign-modal-card">
        <div class="modal-heading">
          <h2 class="bold">Assign Maintenance Staff</h2>
          <p class="block-sub">Select a staff member to assign to report {{ selectedReport?.report_code }}</p>
        </div>
        
        <div class="form-group">
          <label for="staff-select" class="form-label">Available Staff</label>
          <select id="staff-select" v-model="assignStaffId" class="form-select">
            <option value="" disabled>Select staff member</option>
            <option v-for="staff in staffUsers" :key="staff.id" :value="staff.id">
              {{ staff.name }} ({{ staff.email }})
            </option>
          </select>
        </div>

        <div class="modal-actions mt-6">
          <button @click="handleAssignStaff" class="neo-btn neo-btn-yellow">Assign Staff</button>
          <button @click="showAssignModal = false" class="neo-btn neo-btn-white">Cancel</button>
        </div>
      </div>
    </div>

    <!-- MODAL 3: Update Status & Remarks -->
    <div v-if="showStatusModal" class="modal-backdrop" @click.self="showStatusModal = false">
      <div class="details-modal neo-card status-modal-card">
        <div class="modal-heading">
          <h2 class="bold">Update Report Status</h2>
          <p class="block-sub">Modify status and add remarks for report {{ selectedReport?.report_code }}</p>
        </div>

        <div class="form-group">
          <label for="status-select" class="form-label">New Status</label>
          <select id="status-select" v-model="updateStatusVal" class="form-select">
            <option value="Pending">Pending</option>
            <option value="Assigned">Assigned</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>

        <div class="form-group mt-4">
          <label for="remarks-input" class="form-label">Staff Remarks</label>
          <textarea
            id="remarks-input"
            v-model="updateRemarks"
            placeholder="Add update remarks (e.g. Parts ordered, issue fixed)..."
            rows="3"
            class="form-input form-textarea"
          ></textarea>
        </div>

        <div class="modal-actions mt-6">
          <button @click="handleUpdateStatus" class="neo-btn neo-btn-yellow">Save Changes</button>
          <button @click="showStatusModal = false" class="neo-btn neo-btn-white">Cancel</button>
        </div>
      </div>
    </div>

    <!-- MODAL 4: Visual Status Tracking Timeline -->
    <div v-if="showTimelineModal" class="modal-backdrop" @click.self="showTimelineModal = false">
      <div class="details-modal neo-card timeline-modal-card">
        <div class="modal-heading">
          <h2 class="bold">Tracking Timeline: {{ selectedReport?.report_code }}</h2>
          <p class="block-sub">{{ selectedReport?.title }}</p>
        </div>

        <div class="timeline-visual">
          <!-- Step 1: Pending -->
          <div class="timeline-node" :class="{ 'completed': selectedReport?.status !== 'Rejected', 'active': selectedReport?.status === 'Pending' }">
            <div class="node-circle">1</div>
            <div class="node-label">
              <strong>Pending Submission</strong>
              <span>Awaiting review</span>
            </div>
          </div>

          <div class="timeline-line" :class="{ 'completed': isTimelineStepCompleted('Assigned') }"></div>

          <!-- Step 2: Assigned -->
          <div class="timeline-node" :class="{ 'completed': isTimelineStepCompleted('Assigned'), 'active': selectedReport?.status === 'Assigned' }">
            <div class="node-circle">2</div>
            <div class="node-label">
              <strong>Staff Assigned</strong>
              <span>{{ selectedReport?.assignedStaff !== 'Unassigned' ? selectedReport?.assignedStaff : 'Unassigned' }}</span>
              <span class="node-time" v-if="selectedReport?.assigned_at">
                {{ formatFullDate(selectedReport?.assigned_at) }}
              </span>
            </div>
          </div>

          <div class="timeline-line" :class="{ 'completed': isTimelineStepCompleted('In Progress') }"></div>

          <!-- Step 3: In Progress -->
          <div class="timeline-node" :class="{ 'completed': isTimelineStepCompleted('In Progress'), 'active': selectedReport?.status === 'In Progress' }">
            <div class="node-circle">3</div>
            <div class="node-label">
              <strong>In Progress</strong>
              <span>Work is underway</span>
            </div>
          </div>

          <div class="timeline-line" :class="{ 'completed': isTimelineStepCompleted('Resolved') }"></div>

          <!-- Step 4: Resolved / Rejected -->
          <div class="timeline-node" :class="{
            'completed': isTimelineStepCompleted('Resolved'),
            'active': selectedReport?.status === 'Resolved' || selectedReport?.status === 'Rejected',
            'rejected': selectedReport?.status === 'Rejected'
          }">
            <div class="node-circle">
              <span v-if="selectedReport?.status === 'Rejected'">&times;</span>
              <span v-else>4</span>
            </div>
            <div class="node-label">
              <strong>{{ selectedReport?.status === 'Rejected' ? 'Rejected' : 'Resolved' }}</strong>
              <span v-if="selectedReport?.resolved_at">
                Completed: {{ formatFullDate(selectedReport?.resolved_at) }}
              </span>
              <span v-else>Issue settled</span>
            </div>
          </div>
        </div>

        <!-- Remarks Summary inside timeline -->
        <div class="timeline-remarks mt-6" v-if="selectedReport?.student_remarks || selectedReport?.staff_remarks">
          <div v-if="selectedReport?.student_remarks" class="remarks-box student">
            <strong>Student Remarks:</strong> {{ selectedReport?.student_remarks }}
          </div>
          <div v-if="selectedReport?.staff_remarks" class="remarks-box staff">
            <strong>Staff Updates:</strong> {{ selectedReport?.staff_remarks }}
          </div>
        </div>

        <div class="modal-actions mt-6">
          <button @click="showTimelineModal = false" class="neo-btn neo-btn-white">Close Tracker</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import StatCard from '@/components/StatCard.vue'
import MaintenanceReportTable from '@/components/MaintenanceReportTable.vue'
import MaintenanceReportForm from '@/components/MaintenanceReportForm.vue'

// API base URL
const API_BASE = 'http://localhost:8000'

const user = ref({})
const reports = ref([])
const staffUsers = ref([])
const loading = ref(true)
const error = ref(null)

// Stats
const stats = reactive({
  totalReports: 0,
  pendingReports: 0,
  assignedReports: 0,
  inProgressReports: 0,
  resolvedReports: 0
})

// Filters
const filters = reactive({
  search: '',
  status: 'All',
  priority: 'All',
  category: 'All',
  block: 'All'
})

// Modal states
const showCreateModal = ref(false)
const showAssignModal = ref(false)
const showStatusModal = ref(false)
const showTimelineModal = ref(false)

// Action states
const selectedReport = ref(null)
const assignStaffId = ref('')
const updateStatusVal = ref('Pending')
const updateRemarks = ref('')

const loadUser = () => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
    user.value.role = user.value.role || 'student'
  }
}

const fetchReports = async () => {
  let endpoint = `${API_BASE}/maintenance`
  if (user.value.role !== 'staff/admin') {
    endpoint = `${API_BASE}/maintenance/student/${user.value.id}`
  }
  const res = await fetch(endpoint)
  if (!res.ok) throw new Error('Failed to fetch maintenance reports.')
  reports.value = await res.json()
}

const fetchStats = async () => {
  const res = await fetch(`${API_BASE}/maintenance/stats`)
  if (!res.ok) throw new Error('Failed to fetch statistics.')
  const data = await res.json()
  Object.assign(stats, data)
}

const fetchStaff = async () => {
  if (user.value.role === 'staff/admin') {
    const res = await fetch(`${API_BASE}/users`)
    if (res.ok) {
      const allUsers = await res.json()
      staffUsers.value = allUsers.filter(u => u.role === 'staff/admin')
    }
  }
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    loadUser()
    await Promise.all([
      fetchReports(),
      fetchStats(),
      fetchStaff()
    ])
  } catch (err) {
    console.error(err)
    error.value = err.message || 'An error occurred while loading maintenance reports.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})

// Filtering Computation
const filteredReports = computed(() => {
  return reports.value.filter(report => {
    // Search filter
    const matchesSearch = !filters.search.trim() || 
      (report.title && report.title.toLowerCase().includes(filters.search.toLowerCase())) ||
      (report.description && report.description.toLowerCase().includes(filters.search.toLowerCase())) ||
      (report.report_code && report.report_code.toLowerCase().includes(filters.search.toLowerCase()))

    // Status filter
    const matchesStatus = filters.status === 'All' || report.status === filters.status

    // Priority filter
    const matchesPriority = filters.priority === 'All' || report.priority === filters.priority

    // Category filter
    const matchesCategory = filters.category === 'All' || report.category === filters.category

    // Block filter
    const matchesBlock = filters.block === 'All' || report.hostel_block === filters.block

    return matchesSearch && matchesStatus && matchesPriority && matchesCategory && matchesBlock
  })
})

// Create Report action
const handleCreateReport = async (formData) => {
  try {
    const payload = {
      user_id: user.value.id,
      title: formData.title,
      description: formData.description,
      category: formData.category,
      priority: formData.priority,
      hostel_block: formData.hostel_block,
      room_number: formData.room_number,
      student_remarks: formData.description // map description to initial student_remarks
    }

    const res = await fetch(`${API_BASE}/maintenance`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    if (!res.ok) throw new Error('Could not submit report.')
    
    showCreateModal.value = false
    await fetchData()
  } catch (err) {
    alert(err.message)
  }
}

// Assign Staff Actions
const openAssignModal = (report) => {
  selectedReport.value = report
  assignStaffId.value = report.assigned_staff_id || ''
  showAssignModal.value = true
}

const handleAssignStaff = async () => {
  if (!assignStaffId.value) {
    alert('Please select a staff member.')
    return
  }
  try {
    const res = await fetch(`${API_BASE}/maintenance/${selectedReport.value.id}/assign`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ assigned_staff_id: assignStaffId.value })
    })

    if (!res.ok) throw new Error('Could not assign staff.')
    
    showAssignModal.value = false
    await fetchData()
  } catch (err) {
    alert(err.message)
  }
}

// Status Updates Actions
const openStatusModal = (report) => {
  selectedReport.value = report
  updateStatusVal.value = report.status || 'Pending'
  updateRemarks.value = report.staff_remarks || ''
  showStatusModal.value = true
}

const handleUpdateStatus = async () => {
  try {
    const res = await fetch(`${API_BASE}/maintenance/${selectedReport.value.id}/status`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        status: updateStatusVal.value,
        staff_remarks: updateRemarks.value
      })
    })

    if (!res.ok) throw new Error('Could not update status.')
    
    showStatusModal.value = false
    await fetchData()
  } catch (err) {
    alert(err.message)
  }
}

// Delete Report
const handleDeleteReport = async (id) => {
  try {
    const res = await fetch(`${API_BASE}/maintenance/${id}`, {
      method: 'DELETE'
    })
    if (!res.ok) throw new Error('Failed to delete report.')
    await fetchData()
  } catch (err) {
    alert(err.message)
  }
}

// View Timeline
const openTimelineModal = (report) => {
  selectedReport.value = report
  showTimelineModal.value = true
}

// Timeline Helper Checks
const isTimelineStepCompleted = (stepName) => {
  if (!selectedReport.value) return false
  const status = selectedReport.value.status
  if (status === 'Rejected') return false

  const statusOrder = ['Pending', 'Assigned', 'In Progress', 'Resolved']
  const currentIdx = statusOrder.indexOf(status)
  const targetIdx = statusOrder.indexOf(stepName)

  return currentIdx >= targetIdx
}

const formatFullDate = (dateStr) => {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    return d.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (e) {
    return dateStr
  }
}
</script>

<style scoped>
.maintenance-reports-page {
  width: 100%;
}

.page-header-block {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: var(--primary-pink);
  margin-bottom: 24px;
  padding: 24px;
}

.page-title {
  font-size: 32px;
  margin-bottom: 4px;
  font-weight: 800;
}

.page-subtitle {
  font-size: 16px;
  font-weight: 500;
}

.header-actions {
  display: flex;
  gap: 16px;
}

.stats-container {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.controls-panel {
  background-color: #FFFFFF;
  margin-bottom: 24px;
  padding: 20px;
}

.search-row {
  margin-bottom: 16px;
}

.search-input {
  width: 100%;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

/* Modals styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 200;
}

.details-modal {
  width: min(640px, 100%);
  margin-bottom: 0;
}

.modal-heading {
  margin-bottom: 20px;
  border-bottom: 3px solid #000000;
  padding-bottom: 12px;
}

.modal-heading h2 {
  font-size: 24px;
  font-weight: 700;
}

.modal-actions {
  display: flex;
  gap: 16px;
}

/* Timeline specific styles */
.timeline-visual {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 8px 16px;
  background-color: #FAFAFA;
  border: 3px solid #000000;
  border-radius: 8px;
}

.timeline-node {
  display: flex;
  gap: 16px;
  align-items: center;
  opacity: 0.5;
}

.timeline-node.completed,
.timeline-node.active {
  opacity: 1;
}

.node-circle {
  width: 32px;
  height: 32px;
  border: 2.5px solid #000000;
  border-radius: 50%;
  background-color: #FFFFFF;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  box-shadow: 2px 2px 0px #000000;
  flex-shrink: 0;
}

.timeline-node.active .node-circle {
  background-color: var(--primary-yellow);
}

.timeline-node.completed .node-circle {
  background-color: var(--status-completed);
}

.timeline-node.rejected .node-circle {
  background-color: var(--status-rejected);
  color: #FFFFFF;
}

.node-label {
  display: flex;
  flex-direction: column;
}

.node-label strong {
  font-size: 15px;
}

.node-label span {
  font-size: 12px;
  color: #666666;
}

.node-time {
  font-size: 11px !important;
  font-weight: 700;
  color: #333333 !important;
  margin-top: 2px;
}

.timeline-line {
  width: 4px;
  height: 24px;
  background-color: #000000;
  opacity: 0.2;
  margin-left: 14px;
}

.timeline-line.completed {
  opacity: 1;
  background-color: var(--status-completed);
}

.timeline-remarks {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.remarks-box {
  padding: 10px 14px;
  font-size: 14px;
  border: 2px solid #000000;
  border-radius: 6px;
  line-height: 1.4;
}

.remarks-box.student {
  background-color: #FFFDF0;
}

.remarks-box.staff {
  background-color: #F0FDFA;
}

/* Spinner */
.loading-state,
.error-state {
  text-align: center;
  padding: 60px 24px;
  background-color: #FFFFFF;
  border: 4px solid #000000;
  box-shadow: 4px 4px 0px #000000;
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
.mt-6 { margin-top: 24px; }
.bold { font-weight: 700; }

@media (max-width: 900px) {
  .stats-container {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .stats-container {
    grid-template-columns: 1fr;
  }
}
</style>

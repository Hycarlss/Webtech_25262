<template>
  <div class="analytics-view">
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">Monitoring & Analytics</h1>
        <p class="page-subtitle">
          Track hostel operations, alerts, trends, and saved report snapshots.
          <span v-if="lastUpdated">Last refreshed {{ lastUpdated }}</span>
        </p>
      </div>
      <div class="header-actions">
        <button class="neo-btn neo-btn-white" :disabled="refreshing" @click="fetchAnalyticsData()">
          {{ refreshing ? 'Refreshing...' : 'Refresh' }}
        </button>
        <button class="neo-btn neo-btn-yellow" :disabled="savingReport" @click="saveCurrentReport">
          {{ savingReport ? 'Saving...' : 'Save View' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Building analytics dashboard...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Analytics</h3>
      <p>{{ error }}</p>
      <button class="neo-btn neo-btn-yellow mt-4" @click="fetchAnalyticsData">Retry</button>
    </div>

    <div v-else>
      <div class="controls-panel neo-card">
        <div class="control-grid">
          <div class="form-group">
            <label class="form-label" for="date-range">Date Range</label>
            <select id="date-range" v-model="selectedRange" class="form-select">
              <option value="all">All dates</option>
              <option value="7">Last 7 days</option>
              <option value="30">Last 30 days</option>
              <option value="90">Last 90 days</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="status-filter">Request Status</label>
            <select id="status-filter" v-model="selectedStatus" class="form-select">
              <option value="All">All statuses</option>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="block-filter">Residential Block</label>
            <select id="block-filter" v-model="selectedBlock" class="form-select">
              <option value="All">All blocks</option>
              <option v-for="block in blockOptions" :key="block" :value="block">
                Block {{ block }}
              </option>
            </select>
          </div>
        </div>
        <p v-if="saveError" class="form-error mt-4">{{ saveError }}</p>
      </div>

      <div class="grid-4 stats-container">
        <StatCard
          title="Occupancy Rate"
          :value="`${occupancyRate}%`"
          :description="`${totalOccupied} of ${totalCapacity} beds occupied`"
          variant="yellow"
        />
        <StatCard
          title="Available Beds"
          :value="availableBeds"
          :description="`${maintenanceRoomsCount} rooms unavailable`"
          variant="pink"
        />
        <StatCard
          title="Overdue Work"
          :value="overdueReports.length"
          :description="`${unassignedReports.length} unassigned requests`"
          variant="white"
        />
        <StatCard
          title="Avg Resolution"
          :value="`${averageResolutionDays} days`"
          description="Completed maintenance turnaround"
          variant="yellow"
        />
        <StatCard
          title="Booking Approval"
          :value="`${bookingApprovalRate}%`"
          :description="`${pendingBookings.length} bookings pending review`"
          variant="white"
        />
        <StatCard
          title="Peak Facility Hours"
          :value="topFacility.hours"
          :description="`${topFacility.name} leads approved usage`"
          variant="white"
        />
      </div>

      <section class="insights-panel neo-card">
        <div class="panel-heading">
          <h2>Actionable Insights</h2>
          <span>{{ studentUsersCount }} students, {{ adminUsersCount }} staff/admin</span>
        </div>
        <div class="insight-grid">
          <div v-for="insight in actionableInsights" :key="insight.title" class="insight-card" :class="insight.level">
            <strong>{{ insight.title }}</strong>
            <p>{{ insight.message }}</p>
          </div>
        </div>
      </section>

      <section class="neo-card occupancy-details-panel">
        <div class="panel-heading">
          <h2>Occupancy Details</h2>
          <span>{{ occupancyDetails.length }} rooms in view</span>
        </div>
        <div class="occupancy-summary">
          <div v-for="item in roomStatusSummary" :key="item.status" class="occupancy-summary-item">
            <span>{{ item.status }}</span>
            <strong>{{ item.count }}</strong>
          </div>
        </div>
        <div class="occupancy-table-wrap">
          <table class="occupancy-table">
            <thead>
              <tr>
                <th>Room</th>
                <th>Block</th>
                <th>Capacity</th>
                <th>Occupied</th>
                <th>Available</th>
                <th>Utilization</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="room in occupancyDetails" :key="room.id">
                <td>{{ room.number }}</td>
                <td>Block {{ room.block }}</td>
                <td>{{ room.capacity }}</td>
                <td>{{ room.occupied }}</td>
                <td>{{ room.available }}</td>
                <td>
                  <div class="mini-meter">
                    <div
                      class="mini-meter-fill"
                      :class="room.statusClass"
                      :style="{ width: `${room.utilization}%` }"
                    ></div>
                  </div>
                  <strong>{{ room.utilization }}%</strong>
                </td>
                <td>
                  <span class="room-status" :class="room.statusClass">{{ room.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="analytics-grid">
        <section class="neo-card chart-panel">
          <div class="panel-heading">
            <h2>Maintenance Status</h2>
            <span>{{ filteredReports.length }} records</span>
          </div>
          <div class="donut-layout">
            <div
              class="donut-chart"
              :style="{ background: maintenanceDonutBackground }"
              aria-label="Maintenance status distribution"
            >
              <div class="donut-center">
                <strong>{{ filteredReports.length }}</strong>
                <span>Total</span>
              </div>
            </div>
            <div class="legend-list">
              <div v-for="item in maintenanceStatusData" :key="item.name" class="legend-row">
                <span class="legend-chip" :style="{ backgroundColor: item.color }"></span>
                <span>{{ item.name }}</span>
                <strong>{{ item.value }}</strong>
              </div>
            </div>
          </div>
        </section>

        <section class="neo-card chart-panel">
          <div class="panel-heading">
            <h2>Facility Usage</h2>
            <span>Approved hours and bookings</span>
          </div>
          <div class="bar-list">
            <div v-for="item in facilityUsageData" :key="item.name" class="bar-row">
              <div class="bar-label">
                <span>{{ item.name }}</span>
                <strong>{{ item.hours }}h / {{ formatCount(item.bookings, 'booking') }}</strong>
              </div>
              <div class="bar-track">
                <div
                  class="bar-fill yellow"
                  :style="{ width: `${barWidth(item.hours, maxFacilityHours)}%` }"
                ></div>
              </div>
            </div>
          </div>
        </section>

        <section class="neo-card chart-panel">
          <div class="panel-heading">
            <h2>Occupancy by Block</h2>
            <span>{{ selectedBlock === 'All' ? 'All blocks' : `Block ${selectedBlock}` }}</span>
          </div>
          <div class="block-bars">
            <div v-for="block in roomsByBlock" :key="block.name" class="block-row">
              <div class="block-meta">
                <h3>{{ block.name }}</h3>
                <p>{{ block.occupied }} occupied, {{ block.available }} available</p>
              </div>
              <div class="stacked-track">
                <div
                  class="stacked-fill occupied"
                  :style="{ width: `${percentage(block.occupied, block.capacity)}%` }"
                ></div>
                <div
                  class="stacked-fill available"
                  :style="{ width: `${percentage(block.available, block.capacity)}%` }"
                ></div>
              </div>
              <strong>{{ percentage(block.occupied, block.capacity) }}%</strong>
            </div>
          </div>
        </section>

        <section class="neo-card chart-panel">
          <div class="panel-heading">
            <h2>Booking Trend</h2>
            <span>Monthly activity</span>
          </div>
          <div class="trend-chart">
            <div v-for="point in bookingTrendData" :key="point.month" class="trend-column">
              <div class="trend-track">
                <div
                  class="trend-fill"
                  :style="{ height: `${barWidth(point.bookings, maxTrendBookings)}%` }"
                ></div>
              </div>
              <strong>{{ point.bookings }}</strong>
              <span>{{ point.month }}</span>
            </div>
          </div>
        </section>

        <section class="neo-card chart-panel">
          <div class="panel-heading">
            <h2>Maintenance Workload</h2>
            <span>Open work by assignee</span>
          </div>
          <div class="workload-list">
            <div v-for="item in maintenanceWorkload" :key="item.name" class="workload-row">
              <div>
                <h3>{{ item.name }}</h3>
                <p>{{ item.pending }} pending, {{ item.inProgress }} in progress</p>
              </div>
              <strong>{{ item.total }}</strong>
            </div>
          </div>
        </section>
      </div>

      <div class="operations-grid">
        <section class="neo-card">
          <div class="panel-heading">
            <h2>Live Notifications</h2>
            <span>{{ notifications.length }} updates</span>
          </div>
          <div class="notification-list">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="notification-item"
              :class="notification.type"
            >
              <div class="notification-icon">{{ notification.icon }}</div>
              <div>
                <h3>{{ notification.title }}</h3>
                <p>{{ notification.message }}</p>
                <span>{{ notification.time }}</span>
              </div>
            </div>
          </div>
        </section>

        <section class="neo-card">
          <div class="panel-heading">
            <h2>Saved Reports</h2>
            <span>{{ savedReports.length }} snapshots</span>
          </div>
          <div class="saved-report-list">
            <div v-for="report in savedReports" :key="report.id" class="saved-report">
              <div>
                <h3>{{ report.name }}</h3>
                <p>{{ report.summary }}</p>
                <span>{{ report.filterText }}</span>
              </div>
              <div class="report-actions">
                <span>{{ report.date }}</span>
                <button class="small-action-btn" @click="downloadReport(report)">Download</button>
              </div>
            </div>
            <p v-if="savedReports.length === 0" class="empty-copy">
              No saved reports yet. Save the current view to keep a permanent snapshot.
            </p>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'

import StatCard from '@/components/StatCard.vue'

const router = useRouter()

const user = ref({})
const reports = ref([])
const bookings = ref([])
const facilities = ref([])
const rooms = ref([])
const users = ref([])
const loading = ref(true)
const refreshing = ref(false)
const error = ref(null)
const saveError = ref(null)
const savingReport = ref(false)
const lastUpdated = ref('')

const selectedRange = ref('all')
const selectedStatus = ref('All')
const selectedBlock = ref('All')
const savedReports = ref([])
let refreshTimer = null

const reportColors = {
  Pending: '#FFA500',
  'In Progress': '#3A86C8',
  Completed: '#2EC4B6'
}

const fetchJson = async (url) => {
  const response = await fetch(url)
  if (!response.ok) {
    throw new Error(`Could not load ${url.split('/').pop()}.`)
  }
  return response.json()
}

const fetchAnalyticsData = async ({ showLoader = true } = {}) => {
  if (showLoader) {
    loading.value = true
  } else {
    refreshing.value = true
  }
  error.value = null

  try {
    const storedUser = JSON.parse(localStorage.getItem('user'))
    if (!storedUser) throw new Error('No logged-in user found.')

    user.value = storedUser
    if (user.value.role !== 'staff/admin') {
      router.replace('/dashboard')
      return
    }

    const [allReports, allBookings, allFacilities, allRooms, allUsers, allSavedReports] = await Promise.all([
      fetchJson('http://localhost:3000/reports'),
      fetchJson('http://localhost:3000/bookings'),
      fetchJson('http://localhost:3000/facilities'),
      fetchJson('http://localhost:3000/rooms'),
      fetchJson('http://localhost:3000/users'),
      fetchJson('http://localhost:3000/analyticsReports')
    ])

    reports.value = allReports
    bookings.value = allBookings
    facilities.value = allFacilities
    rooms.value = allRooms
    users.value = allUsers
    savedReports.value = allSavedReports.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    lastUpdated.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  } catch (err) {
    console.error(err)
    if (showLoader) {
      error.value = err.message || 'An unexpected error occurred while loading analytics.'
    } else {
      saveError.value = err.message || 'Live refresh failed. Try refreshing manually.'
    }
  } finally {
    if (showLoader) {
      loading.value = false
    }
    refreshing.value = false
  }
}

onMounted(() => {
  fetchAnalyticsData()
  refreshTimer = window.setInterval(() => {
    fetchAnalyticsData({ showLoader: false })
  }, 15000)
})

onUnmounted(() => {
  if (refreshTimer) window.clearInterval(refreshTimer)
})

const saveCurrentReport = async () => {
  savingReport.value = true
  saveError.value = null

  const now = new Date()
  const report = {
    name: `Analytics Snapshot ${savedReports.value.length + 1}`,
    summary: `${occupancyRate.value}% occupancy, ${pendingReportsCount.value} pending requests, ${approvedBookingHours.value} booking hours`,
    filterText: reportFilterText.value,
    date: now.toISOString().split('T')[0],
    createdAt: now.toISOString(),
    createdBy: user.value.name,
    filters: {
      dateRange: selectedRange.value,
      status: selectedStatus.value,
      block: selectedBlock.value
    },
    snapshot: currentReportSnapshot.value
  }

  try {
    const response = await fetch('http://localhost:3000/analyticsReports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(report)
    })

    if (!response.ok) throw new Error('Could not save the analytics report.')

    const savedReport = await response.json()
    savedReports.value = [savedReport, ...savedReports.value]
  } catch (err) {
    console.error(err)
    saveError.value = err.message || 'Could not save the analytics report.'
  } finally {
    savingReport.value = false
  }
}

const dateThreshold = computed(() => {
  if (selectedRange.value === 'all') return null
  const date = new Date()
  date.setDate(date.getDate() - Number(selectedRange.value))
  return date
})

const blockOptions = computed(() => {
  return [...new Set(rooms.value.map(room => room.block))].sort()
})

const filteredReports = computed(() => {
  return reports.value.filter(report => {
    const reportDate = report.dateSubmitted ? new Date(report.dateSubmitted) : null
    const matchesDate = !dateThreshold.value || (reportDate && reportDate >= dateThreshold.value)
    const matchesStatus = selectedStatus.value === 'All' || report.status === selectedStatus.value
    const matchesBlock = selectedBlock.value === 'All' || getBlockFromLocation(report.room) === selectedBlock.value

    return matchesDate && matchesStatus && matchesBlock
  })
})

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    const bookingDate = booking.date ? new Date(booking.date) : null
    return !dateThreshold.value || (bookingDate && bookingDate >= dateThreshold.value)
  })
})

const filteredRooms = computed(() => {
  if (selectedBlock.value === 'All') return rooms.value
  return rooms.value.filter(room => room.block === selectedBlock.value)
})

const pendingReportsCount = computed(() => {
  return filteredReports.value.filter(report => report.status === 'Pending').length
})

const pendingBookings = computed(() => {
  return filteredBookings.value.filter(booking => booking.status === 'Pending')
})

const totalCapacity = computed(() => {
  return filteredRooms.value.reduce((total, room) => total + Number(room.capacity || 0), 0)
})

const totalOccupied = computed(() => {
  return filteredRooms.value.reduce((total, room) => total + Number(room.occupied || 0), 0)
})

const occupancyRate = computed(() => {
  return percentage(totalOccupied.value, totalCapacity.value)
})

const availableBeds = computed(() => {
  return filteredRooms.value.reduce((total, room) => total + Math.max(Number(room.capacity || 0) - Number(room.occupied || 0), 0), 0)
})

const maintenanceRoomsCount = computed(() => {
  return filteredRooms.value.filter(room => room.status === 'Maintenance').length
})

const approvedBookingHours = computed(() => {
  return filteredBookings.value
    .filter(booking => booking.status === 'Approved')
    .reduce((total, booking) => total + getBookingHours(booking), 0)
})

const bookingApprovalRate = computed(() => {
  const decidedBookings = filteredBookings.value.filter(booking => booking.status === 'Approved' || booking.status === 'Rejected')
  const approvedBookings = decidedBookings.filter(booking => booking.status === 'Approved')

  return percentage(approvedBookings.length, decidedBookings.length)
})

const overdueReports = computed(() => {
  const today = getToday()

  return filteredReports.value.filter(report => {
    if (report.status === 'Completed' || !report.deadline) return false
    return new Date(report.deadline) < today
  })
})

const unassignedReports = computed(() => {
  return filteredReports.value.filter(report => {
    return report.status !== 'Completed' && (!report.assignedStaff || report.assignedStaff === 'Unassigned')
  })
})

const averageResolutionDays = computed(() => {
  const completedReports = filteredReports.value.filter(report => report.status === 'Completed' && report.dateSubmitted && report.deadline)
  if (completedReports.length === 0) return 0

  const totalDays = completedReports.reduce((total, report) => {
    return total + getDaysBetween(report.dateSubmitted, report.deadline)
  }, 0)

  return Math.round((totalDays / completedReports.length) * 10) / 10
})

const studentUsersCount = computed(() => {
  return users.value.filter(account => account.role === 'student').length
})

const adminUsersCount = computed(() => {
  return users.value.filter(account => account.role === 'staff/admin').length
})

const criticalAlerts = computed(() => {
  return [
    ...overdueReports.value,
    ...unassignedReports.value,
    ...pendingBookings.value
  ]
})

const actionableInsights = computed(() => {
  const highestOccupancyBlock = [...roomsByBlock.value].sort((a, b) => {
    return percentage(b.occupied, b.capacity) - percentage(a.occupied, a.capacity)
  })[0]

  return [
    {
      level: overdueReports.value.length > 0 ? 'danger' : 'good',
      title: overdueReports.value.length > 0 ? 'Escalate overdue maintenance' : 'Maintenance deadlines stable',
      message: overdueReports.value.length > 0
        ? `${formatCount(overdueReports.value.length, 'open request')} need follow-up before more complaints accumulate.`
        : 'No filtered maintenance request is past its deadline.'
    },
    {
      level: availableBeds.value <= 4 ? 'warning' : 'good',
      title: availableBeds.value <= 4 ? 'Low bed availability' : 'Bed availability healthy',
      message: `${availableBeds.value} beds are available in the selected residential view.`
    },
    {
      level: bookingApprovalRate.value < 70 ? 'warning' : 'good',
      title: bookingApprovalRate.value < 70 ? 'Review booking decisions' : 'Booking approvals on track',
      message: `${bookingApprovalRate.value}% of decided bookings are approved, with ${pendingBookings.value.length} still pending.`
    },
    {
      level: 'info',
      title: highestOccupancyBlock ? `${highestOccupancyBlock.name} is most occupied` : 'No occupancy data',
      message: highestOccupancyBlock
        ? `${percentage(highestOccupancyBlock.occupied, highestOccupancyBlock.capacity)}% occupied, useful for allocation and maintenance planning.`
        : 'Add room data to compare residential blocks.'
    }
  ]
})

const reportFilterText = computed(() => {
  const dateLabel = selectedRange.value === 'all' ? 'All dates' : `Last ${selectedRange.value} days`
  const statusLabel = selectedStatus.value === 'All' ? 'All statuses' : selectedStatus.value
  const blockLabel = selectedBlock.value === 'All' ? 'All blocks' : `Block ${selectedBlock.value}`

  return `${dateLabel} | ${statusLabel} | ${blockLabel}`
})

const currentReportSnapshot = computed(() => {
  return {
    occupancyRate: occupancyRate.value,
    totalCapacity: totalCapacity.value,
    totalOccupied: totalOccupied.value,
    pendingReports: pendingReportsCount.value,
    approvedBookingHours: approvedBookingHours.value,
    bookingApprovalRate: bookingApprovalRate.value,
    overdueReports: overdueReports.value.length,
    unassignedReports: unassignedReports.value.length,
    availableBeds: availableBeds.value,
    openAlerts: criticalAlerts.value.length,
    registeredStudents: studentUsersCount.value,
    staffAdmins: adminUsersCount.value,
    maintenanceStatus: maintenanceStatusData.value.map(item => ({
      name: item.name,
      value: item.value
    })),
    facilityUsage: facilityUsageData.value,
    occupancyByBlock: roomsByBlock.value,
    bookingTrend: bookingTrendData.value,
    maintenanceWorkload: maintenanceWorkload.value
  }
})

const maintenanceStatusData = computed(() => {
  return ['Pending', 'In Progress', 'Completed'].map(status => ({
    name: status,
    value: filteredReports.value.filter(report => report.status === status).length,
    color: reportColors[status]
  }))
})

const maintenanceDonutBackground = computed(() => {
  const total = filteredReports.value.length
  if (total === 0) return '#FFFFFF'

  let cursor = 0
  const segments = maintenanceStatusData.value.map(item => {
    const start = cursor
    cursor += (item.value / total) * 100
    return `${item.color} ${start}% ${cursor}%`
  })

  return `conic-gradient(${segments.join(', ')})`
})

const facilityUsageData = computed(() => {
  const facilityNames = facilities.value.map(facility => facility.name)
  const bookingNames = bookings.value.map(booking => booking.facilityName)
  const names = [...new Set([...facilityNames, ...bookingNames])].filter(Boolean)

  return names.map(name => ({
    name,
    bookings: filteredBookings.value.filter(
      booking => booking.facilityName === name && booking.status === 'Approved'
    ).length,
    hours: filteredBookings.value
      .filter(booking => booking.facilityName === name && booking.status === 'Approved')
      .reduce((total, booking) => total + getBookingHours(booking), 0)
  }))
})

const maxFacilityHours = computed(() => {
  return Math.max(...facilityUsageData.value.map(item => item.hours), 1)
})

const topFacility = computed(() => {
  return [...facilityUsageData.value].sort((a, b) => b.hours - a.hours)[0] || {
    name: 'None',
    hours: 0
  }
})

const roomsByBlock = computed(() => {
  return blockOptions.value
    .filter(block => selectedBlock.value === 'All' || block === selectedBlock.value)
    .map(block => {
      const blockRooms = rooms.value.filter(room => room.block === block)
      const capacity = blockRooms.reduce((total, room) => total + Number(room.capacity || 0), 0)
      const occupied = blockRooms.reduce((total, room) => total + Number(room.occupied || 0), 0)

      return {
        name: `Block ${block}`,
        capacity,
        occupied,
        available: capacity - occupied
      }
    })
})

const occupancyDetails = computed(() => {
  return filteredRooms.value
    .map(room => {
      const capacity = Number(room.capacity || 0)
      const occupied = Number(room.occupied || 0)
      const available = Math.max(capacity - occupied, 0)
      const utilization = percentage(occupied, capacity)

      return {
        ...room,
        capacity,
        occupied,
        available,
        utilization,
        statusClass: getRoomStatusClass(room.status, utilization)
      }
    })
    .sort((a, b) => {
      if (a.block !== b.block) return a.block.localeCompare(b.block)
      return a.number.localeCompare(b.number)
    })
})

const roomStatusSummary = computed(() => {
  return ['Available', 'Full', 'Maintenance'].map(status => ({
    status,
    count: filteredRooms.value.filter(room => room.status === status).length
  }))
})

const bookingTrendData = computed(() => {
  const buckets = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'].map(month => ({
    month,
    bookings: 0
  }))

  filteredBookings.value
    .filter(booking => booking.status === 'Approved')
    .forEach(booking => {
      const monthIndex = new Date(booking.date).getMonth()
      if (buckets[monthIndex]) buckets[monthIndex].bookings += 1
    })

  return buckets
})

const maxTrendBookings = computed(() => {
  return Math.max(...bookingTrendData.value.map(point => point.bookings), 1)
})

const maintenanceWorkload = computed(() => {
  const openReports = filteredReports.value.filter(report => report.status !== 'Completed')
  const names = [...new Set(openReports.map(report => report.assignedStaff || 'Unassigned'))]

  return names.map(name => {
    const assignedReports = openReports.filter(report => (report.assignedStaff || 'Unassigned') === name)

    return {
      name,
      total: assignedReports.length,
      pending: assignedReports.filter(report => report.status === 'Pending').length,
      inProgress: assignedReports.filter(report => report.status === 'In Progress').length
    }
  }).sort((a, b) => b.total - a.total)
})

const notifications = computed(() => {
  const overdueReport = overdueReports.value[0]
  const unassignedReport = unassignedReports.value[0]
  const pendingBooking = pendingBookings.value[0]

  return [
    overdueReport && {
      id: `overdue-${overdueReport.id}`,
      type: 'critical',
      icon: '!',
      title: 'Overdue maintenance',
      message: `${overdueReport.title} passed its deadline for ${overdueReport.room}.`,
      time: overdueReport.deadline ? `Due ${overdueReport.deadline}` : 'Deadline passed'
    },
    unassignedReport && {
      id: `unassigned-${unassignedReport.id}`,
      type: 'info',
      icon: 'i',
      title: 'Assign maintenance staff',
      message: `${unassignedReport.title} is still unassigned for ${unassignedReport.room}.`,
      time: unassignedReport.dateSubmitted || 'New request'
    },
    pendingBooking && {
      id: `booking-${pendingBooking.id}`,
      type: 'success',
      icon: '+',
      title: 'Booking approval queue',
      message: `${pendingBooking.facilityName} booking is waiting for administrator review.`,
      time: pendingBooking.date || 'Upcoming'
    }
  ].filter(Boolean)
})

const getBlockFromLocation = (location = '') => {
  const match = location.match(/(?:Block|Room)?\s*([A-Z])[-\s]/i)
  return match ? match[1].toUpperCase() : 'All'
}

const getBookingHours = (booking) => {
  if (!booking.startTime || !booking.endTime) return 0

  const start = Number(booking.startTime.split(':')[0]) + Number(booking.startTime.split(':')[1] || 0) / 60
  const end = Number(booking.endTime.split(':')[0]) + Number(booking.endTime.split(':')[1] || 0) / 60

  return Math.max(end - start, 0)
}

const getToday = () => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return today
}

const getDaysBetween = (startDate, endDate) => {
  const start = new Date(startDate)
  const end = new Date(endDate)
  const millisecondsPerDay = 1000 * 60 * 60 * 24

  return Math.max(Math.round((end - start) / millisecondsPerDay), 0)
}

const percentage = (value, total) => {
  if (!total) return 0
  return Math.round((value / total) * 100)
}

const getRoomStatusClass = (status, utilization) => {
  if (status === 'Maintenance') return 'maintenance'
  if (status === 'Full' || utilization >= 100) return 'full'
  if (utilization >= 80) return 'high'
  return 'available'
}

const barWidth = (value, max) => {
  if (!value) return 4
  return Math.max(8, percentage(value, max))
}

const formatCount = (value, label) => {
  return `${value} ${label}${value === 1 ? '' : 's'}`
}

const downloadReport = (report) => {
  const payload = {
    name: report.name,
    summary: report.summary,
    filters: report.filters,
    generatedBy: report.createdBy,
    generatedAt: report.createdAt,
    snapshot: report.snapshot
  }
  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${report.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.json`
  link.click()
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.analytics-view {
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

.page-subtitle span {
  display: block;
  margin-top: 4px;
  font-size: 14px;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 14px;
}

.header-actions button:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.controls-panel {
  background-color: #FFFFFF;
  padding: 20px;
}

.control-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

.form-group {
  margin-bottom: 0;
}

.grid-4 {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 16px;
}

.stats-container {
  margin: 20px 0;
}

.stats-container :deep(.neo-card) {
  padding: 18px;
  box-shadow: 5px 5px 0 #000000;
}

.stats-container :deep(.stat-title) {
  font-size: 15px;
  line-height: 1.2;
  margin-bottom: 10px;
  padding-bottom: 8px;
}

.stats-container :deep(.stat-value) {
  font-size: 36px;
  margin-bottom: 8px;
}

.stats-container :deep(.stat-desc) {
  font-size: 13px;
  line-height: 1.25;
}

.insights-panel {
  background: #FFFFFF;
}

.insight-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.insight-card {
  border: var(--border-thin);
  box-shadow: 3px 3px 0 #000000;
  padding: 14px;
}

.insight-card strong {
  display: block;
  font-size: 16px;
  margin-bottom: 6px;
}

.insight-card p {
  font-size: 14px;
  font-weight: 500;
}

.insight-card.good {
  background: #DDF7EE;
}

.insight-card.warning {
  background: #FFEAA7;
}

.insight-card.danger {
  background: #FFD1D1;
}

.insight-card.info {
  background: #E3E0FF;
}

.occupancy-details-panel {
  background: #FFFFFF;
}

.occupancy-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}

.occupancy-summary-item {
  border: var(--border-thin);
  box-shadow: 3px 3px 0 #000000;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 700;
}

.occupancy-summary-item strong {
  font-size: 24px;
}

.occupancy-table-wrap {
  overflow-x: auto;
}

.occupancy-table {
  width: 100%;
  min-width: 780px;
  border-collapse: collapse;
  font-weight: 700;
}

.occupancy-table th,
.occupancy-table td {
  border: 3px solid #000000;
  padding: 12px;
  text-align: left;
  vertical-align: middle;
}

.occupancy-table th {
  background: var(--primary-yellow);
  text-transform: uppercase;
  font-size: 14px;
}

.mini-meter {
  width: 110px;
  height: 14px;
  border: 2px solid #000000;
  background: #FFFFFF;
  display: inline-flex;
  margin-right: 8px;
  vertical-align: middle;
}

.mini-meter-fill {
  height: 100%;
  min-width: 4px;
}

.mini-meter-fill.available,
.room-status.available {
  background: var(--status-completed);
}

.mini-meter-fill.high,
.room-status.high {
  background: var(--primary-yellow);
}

.mini-meter-fill.full,
.room-status.full {
  background: var(--primary-pink);
}

.mini-meter-fill.maintenance,
.room-status.maintenance {
  background: var(--status-rejected);
}

.room-status {
  border: 2px solid #000000;
  border-radius: 20px;
  display: inline-block;
  padding: 6px 10px;
  text-transform: uppercase;
  font-size: 12px;
}

.analytics-grid,
.operations-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.chart-panel {
  min-height: 380px;
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border-bottom: var(--border-thin);
  padding-bottom: 10px;
  margin-bottom: 20px;
}

.panel-heading h2 {
  font-size: 21px;
  text-transform: uppercase;
}

.panel-heading span {
  font-weight: 700;
  color: var(--logo-purple);
}

.donut-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  align-items: center;
  gap: 26px;
}

.donut-chart {
  width: 220px;
  height: 220px;
  border: var(--border-thick);
  border-radius: 50%;
  box-shadow: var(--shadow-small);
  display: grid;
  place-items: center;
}

.donut-center {
  width: 112px;
  height: 112px;
  border: var(--border-thick);
  border-radius: 50%;
  background: #FFFFFF;
  display: grid;
  place-items: center;
  text-align: center;
}

.donut-center strong {
  font-size: 34px;
  line-height: 1;
}

.donut-center span {
  display: block;
  font-weight: 700;
  font-size: 13px;
}

.legend-list,
.bar-list,
.workload-list,
.notification-list,
.saved-report-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.legend-row {
  display: grid;
  grid-template-columns: 18px 1fr auto;
  align-items: center;
  gap: 12px;
  font-weight: 700;
}

.legend-chip {
  width: 18px;
  height: 18px;
  border: 2px solid #000000;
  border-radius: 50%;
}

.bar-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bar-label {
  display: flex;
  justify-content: space-between;
  font-weight: 700;
}

.bar-track,
.stacked-track {
  height: 24px;
  border: var(--border-thin);
  background: #FFFFFF;
  overflow: hidden;
}

.bar-fill,
.stacked-fill,
.trend-fill {
  height: 100%;
  border-right: 3px solid #000000;
}

.bar-fill.yellow {
  background: var(--primary-yellow);
}

.block-bars {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.block-row {
  display: grid;
  grid-template-columns: 140px 1fr 54px;
  align-items: center;
  gap: 16px;
}

.block-meta h3 {
  font-size: 18px;
}

.block-meta p {
  font-weight: 500;
  font-size: 14px;
}

.stacked-track {
  display: flex;
}

.stacked-fill.occupied {
  background: var(--primary-pink);
}

.stacked-fill.available {
  background: var(--status-completed);
}

.trend-chart {
  min-height: 270px;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  align-items: end;
  gap: 14px;
}

.trend-column {
  display: grid;
  grid-template-rows: 1fr auto auto;
  min-height: 250px;
  text-align: center;
  gap: 8px;
  font-weight: 700;
}

.trend-track {
  height: 200px;
  border: var(--border-thin);
  background: #FFFFFF;
  display: flex;
  align-items: flex-end;
  box-shadow: 3px 3px 0 #000000;
}

.trend-fill {
  width: 100%;
  min-height: 8px;
  background: var(--logo-purple);
  border-right: 0;
  border-top: 3px solid #000000;
}

.workload-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: var(--border-thin);
  box-shadow: 3px 3px 0 #000000;
  padding: 14px;
  background: #FFFFFF;
}

.workload-row h3 {
  font-size: 17px;
  margin-bottom: 4px;
}

.workload-row p {
  font-weight: 500;
  font-size: 14px;
}

.workload-row strong {
  min-width: 44px;
  min-height: 44px;
  border: var(--border-thin);
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: var(--primary-pink);
}

.operations-grid {
  margin-top: 24px;
}

.notification-item,
.saved-report {
  border: var(--border-thin);
  background: #FFFFFF;
  box-shadow: 3px 3px 0 #000000;
  padding: 14px;
}

.notification-item {
  display: grid;
  grid-template-columns: 42px 1fr;
  gap: 14px;
}

.notification-icon {
  width: 42px;
  height: 42px;
  border: var(--border-thin);
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-weight: 700;
  background: #FFFFFF;
}

.notification-item.critical .notification-icon {
  background: var(--status-rejected);
}

.notification-item.info .notification-icon {
  background: var(--status-progress);
  color: #FFFFFF;
}

.notification-item.success .notification-icon {
  background: var(--status-completed);
}

.notification-item h3,
.saved-report h3 {
  font-size: 17px;
  margin-bottom: 4px;
}

.notification-item p,
.saved-report p {
  font-weight: 500;
  margin-bottom: 6px;
}

.notification-item span,
.saved-report span,
.empty-copy {
  font-size: 14px;
  font-weight: 700;
  color: #595959;
}

.saved-report {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.report-actions {
  display: flex;
  align-items: flex-end;
  flex-direction: column;
  gap: 10px;
}

.small-action-btn {
  background: var(--primary-yellow);
  border: 2px solid #000000;
  border-radius: var(--radius-small);
  box-shadow: 2px 2px 0 #000000;
  cursor: pointer;
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  padding: 8px 12px;
}

.loading-state,
.error-state {
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

.mt-4 {
  margin-top: 16px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1000px) {
  .analytics-grid,
  .operations-grid,
  .control-grid,
  .donut-layout,
  .insight-grid,
  .occupancy-summary {
    grid-template-columns: 1fr;
  }

  .donut-chart {
    margin: 0 auto;
  }
}

@media (max-width: 1200px) {
  .grid-4 {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header-block {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .block-row {
    grid-template-columns: 1fr;
  }

  .trend-chart {
    gap: 8px;
  }

  .grid-4 {
    grid-template-columns: 1fr;
  }
}
</style>

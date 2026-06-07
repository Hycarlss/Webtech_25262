<template>
  <div class="facilities-view">
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">{{ pageTitle }}</h1>
        <p class="page-subtitle">{{ pageSubtitle }}</p>
      </div>
      <div class="header-summary">
        <span class="neo-badge">{{ filteredFacilities.length }} Facilities</span>
        <span v-if="isAdmin" class="neo-badge admin-badge">Admin</span>
      </div>
    </div>

    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading facility booking module...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load Facilities</h3>
      <p>{{ error }}</p>
      <button @click="loadModule" class="neo-btn neo-btn-yellow">Retry</button>
    </div>

    <template v-else>
      <section v-if="isAdmin && activeAdminSection === 'menu'" class="admin-menu-grid">
        <button
          v-for="item in adminMenuItems"
          :key="item.key"
          class="admin-menu-card neo-card"
          type="button"
          @click="openAdminSection(item.key)"
        >
          <span class="menu-card-kicker">{{ item.kicker }}</span>
          <strong>{{ item.label }}</strong>
          <span>{{ item.description }}</span>
        </button>
      </section>

      <button
        v-if="isAdmin && activeAdminSection !== 'menu'"
        class="neo-btn neo-btn-white admin-back-btn"
        type="button"
        @click="activeAdminSection = 'menu'"
      >
        Back to Facilities Menu
      </button>

      <div v-if="showCatalogSection" class="controls-panel neo-card">
        <div class="form-group search-field">
          <label class="form-label" for="facility-search">Search Facilities</label>
          <input
            id="facility-search"
            v-model="filters.search"
            class="form-input"
            type="search"
            placeholder="Study Room with Air Conditioning"
          />
        </div>

        <div class="filter-grid">
          <div class="form-group">
            <label class="form-label" for="facility-type">Facility Type</label>
            <select id="facility-type" v-model="filters.category" class="form-select">
              <option value="All">All</option>
              <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="capacity-filter">Minimum Capacity</label>
            <input id="capacity-filter" v-model.number="filters.capacity" class="form-input" type="number" min="0" />
          </div>
          <div class="form-group">
            <label class="form-label" for="amenity-filter">Amenity</label>
            <select id="amenity-filter" v-model="filters.amenity" class="form-select">
              <option value="All">All</option>
              <option v-for="amenity in amenities" :key="amenity" :value="amenity">{{ amenity }}</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="availability-filter">Availability</label>
            <select id="availability-filter" v-model="filters.availability" class="form-select">
              <option value="All">All</option>
              <option value="Available">Available</option>
              <option value="Unavailable">Unavailable</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="showCatalogSection" class="module-grid">
        <section class="catalog-panel">
          <div v-if="filteredFacilities.length === 0" class="empty-state neo-card">
            <h3>No Facilities Found</h3>
            <p>Adjust the search or filter settings to see more facilities.</p>
          </div>

          <div v-else class="facility-grid">
            <article
              v-for="facility in filteredFacilities"
              :key="facility.id"
              class="facility-card neo-card"
              :class="{ selected: selectedFacilityId === facility.id }"
            >
              <div class="card-title-row">
                <div>
                  <h2>{{ facility.name }}</h2>
                  <p>{{ facility.category }}</p>
                </div>
                <span class="neo-badge" :class="facility.availability ? 'badge-approved' : 'badge-rejected'">
                  {{ facilityStatus(facility) }}
                </span>
              </div>

              <p class="facility-description">{{ facility.description }}</p>

              <div class="facility-meta">
                <span>Capacity: <strong>{{ facility.capacity }}</strong></span>
                <span v-if="facility.restricted">Restricted</span>
              </div>

              <div class="amenities-list">
                <span v-for="amenity in facility.amenities" :key="amenity">{{ amenity }}</span>
              </div>

              <div class="card-actions">
                <button class="neo-btn neo-btn-yellow" @click="selectFacility(facility)">View Details</button>
                <button
                  v-if="!isAdmin"
                  class="neo-btn neo-btn-pink"
                  :disabled="!facility.availability"
                  @click="selectFacility(facility)"
                >
                  Book
                </button>
              </div>
            </article>
          </div>
        </section>

        <aside class="details-panel neo-card">
          <template v-if="selectedFacility">
            <div class="panel-heading">
              <h2>{{ selectedFacility.name }}</h2>
              <span class="neo-badge" :class="selectedFacility.availability ? 'badge-approved' : 'badge-rejected'">
                {{ facilityStatus(selectedFacility) }}
              </span>
            </div>
            <p class="facility-description">{{ selectedFacility.description }}</p>
            <div class="details-list">
              <div><strong>Category</strong><span>{{ selectedFacility.category }}</span></div>
              <div><strong>Capacity</strong><span>{{ selectedFacility.capacity }} pax</span></div>
              <div><strong>Amenities</strong><span>{{ selectedFacility.amenities.join(', ') }}</span></div>
            </div>

            <div class="calendar-block">
              <h3>Availability Calendar</h3>
              <input v-model="calendarDate" class="form-input" type="date" />
              <div class="slot-list">
                <div v-for="slot in calendarSlots" :key="slot.id" class="slot-row" :class="slot.status.toLowerCase()">
                  <span>{{ slot.startTime }} - {{ slot.endTime }}</span>
                  <strong>{{ slot.status }}</strong>
                </div>
              </div>
            </div>

            <form v-if="!isAdmin" class="booking-form" @submit.prevent="submitBooking">
              <h3>Create Booking Request</h3>
              <div class="form-group">
                <label class="form-label" for="booking-date">Date</label>
                <input id="booking-date" v-model="bookingForm.date" class="form-input" type="date" :min="today" />
              </div>
              <div class="time-grid">
                <div class="form-group">
                  <label class="form-label" for="start-time">Start Time</label>
                  <input id="start-time" v-model="bookingForm.startTime" class="form-input" type="time" />
                </div>
                <div class="form-group">
                  <label class="form-label" for="end-time">End Time</label>
                  <input id="end-time" v-model="bookingForm.endTime" class="form-input" type="time" />
                </div>
              </div>
              <p v-if="formError" class="form-error">{{ formError }}</p>
              <button class="neo-btn neo-btn-pink full-button" type="submit">Submit Request</button>
            </form>
          </template>
          <div v-else class="empty-details">
            <h2>Facility Details</h2>
            <p>Select a facility to view details, schedule, and booking options.</p>
          </div>
        </aside>
      </div>

      <section v-if="showAdminContentSection" class="admin-stack">
        <div v-if="activeAdminSection === 'approvals'" class="admin-section neo-card">
          <div class="section-heading">
            <h2>Booking Approvals</h2>
            <span class="neo-badge badge-pending">{{ pendingBookings.length }} Pending</span>
          </div>
          <div class="table-wrap">
            <table class="neo-table">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>User Name</th>
                  <th>Facility</th>
                  <th>Date</th>
                  <th>Start</th>
                  <th>End</th>
                  <th>Request Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="booking in pendingBookings" :key="booking.id">
                  <td>{{ booking.id }}</td>
                  <td>{{ booking.userName }}</td>
                  <td>{{ booking.facilityName }}</td>
                  <td>{{ booking.date }}</td>
                  <td>{{ booking.startTime }}</td>
                  <td>{{ booking.endTime }}</td>
                  <td>{{ shortDate(booking.createdAt) }}</td>
                  <td>{{ booking.status }}</td>
                  <td class="table-actions">
                    <button class="mini-btn yellow" @click="approveBooking(booking)">Approve</button>
                    <button class="mini-btn pink" @click="rejectBooking(booking)">Reject</button>
                  </td>
                </tr>
                <tr v-if="pendingBookings.length === 0">
                  <td colspan="9">No pending booking requests.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="activeAdminSection === 'all-bookings'" class="admin-section neo-card">
          <div class="section-heading">
            <h2>All Bookings</h2>
            <div class="section-controls">
              <input v-model="adminBookingSearch" class="form-input compact-input" type="search" placeholder="Search bookings" />
              <select v-model="adminBookingStatus" class="form-select compact-input">
                <option value="All">All Statuses</option>
                <option>Pending</option>
                <option>Approved</option>
                <option>Rejected</option>
                <option>Cancelled</option>
                <option>Completed</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table class="neo-table">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>User Name</th>
                  <th>Facility</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="booking in filteredAdminBookings" :key="booking.id">
                  <td>{{ booking.id }}</td>
                  <td>{{ booking.userName }}</td>
                  <td>{{ booking.facilityName }}</td>
                  <td>{{ booking.date }}</td>
                  <td>{{ booking.startTime }} - {{ booking.endTime }}</td>
                  <td>{{ booking.status }}</td>
                  <td>{{ shortDate(booking.createdAt) }}</td>
                  <td class="table-actions">
                    <button class="mini-btn white" @click="cancelBooking(booking)">Cancel</button>
                    <button class="mini-btn yellow" @click="overrideBooking(booking)">Override</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="activeAdminSection === 'add-facility' || activeAdminSection === 'management' || activeAdminSection === 'slots'" class="admin-grid">
          <form
            v-if="activeAdminSection === 'add-facility' || activeAdminSection === 'management'"
            class="admin-section neo-card"
            @submit.prevent="saveFacility"
          >
            <h2>{{ activeAdminSection === 'add-facility' ? 'Add Facility' : 'Facility Management' }}</h2>
            <div class="form-group">
              <label class="form-label" for="facility-name">Facility Name</label>
              <input id="facility-name" v-model="facilityForm.name" class="form-input" required />
            </div>
            <div class="time-grid">
              <div class="form-group">
                <label class="form-label" for="facility-category">Category</label>
                <input id="facility-category" v-model="facilityForm.category" class="form-input" required />
              </div>
              <div class="form-group">
                <label class="form-label" for="facility-capacity">Capacity</label>
                <input id="facility-capacity" v-model.number="facilityForm.capacity" class="form-input" type="number" min="1" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="facility-description">Description</label>
              <textarea id="facility-description" v-model="facilityForm.description" class="form-input" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label" for="facility-amenities">Amenities</label>
              <input id="facility-amenities" v-model="facilityForm.amenitiesText" class="form-input" placeholder="Air Conditioning, Whiteboard" />
            </div>
            <div class="time-grid">
              <button class="neo-btn neo-btn-yellow" type="submit">{{ facilityForm.id ? 'Update Facility' : 'Add Facility' }}</button>
              <button class="neo-btn neo-btn-white" type="button" @click="resetFacilityForm">Clear</button>
            </div>
            <div v-if="activeAdminSection === 'management'" class="facility-admin-list">
              <div v-for="facility in facilities" :key="facility.id" class="facility-admin-row">
                <span>{{ facility.name }}</span>
                <div>
                  <button class="mini-btn yellow" type="button" @click="editFacility(facility)">Edit</button>
                  <button class="mini-btn pink" type="button" @click="removeFacility(facility)">Delete</button>
                </div>
              </div>
            </div>
          </form>

          <form v-if="activeAdminSection === 'slots'" class="admin-section neo-card" @submit.prevent="blockSlot">
            <h2>Block/Unblock Slots</h2>
            <div class="form-group">
              <label class="form-label" for="block-facility">Facility</label>
              <select id="block-facility" v-model="slotForm.facilityId" class="form-select" required>
                <option value="" disabled>Select facility</option>
                <option v-for="facility in facilities" :key="facility.id" :value="facility.id">{{ facility.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="block-date">Date</label>
              <input id="block-date" v-model="slotForm.date" class="form-input" type="date" required />
            </div>
            <div class="time-grid">
              <div class="form-group">
                <label class="form-label" for="block-start">Start</label>
                <input id="block-start" v-model="slotForm.startTime" class="form-input" type="time" required />
              </div>
              <div class="form-group">
                <label class="form-label" for="block-end">End</label>
                <input id="block-end" v-model="slotForm.endTime" class="form-input" type="time" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="block-reason">Reason</label>
              <select id="block-reason" v-model="slotForm.reason" class="form-select">
                <option>Maintenance</option>
                <option>Cleaning</option>
                <option>University Event</option>
                <option>Emergency Closure</option>
              </select>
            </div>
            <button class="neo-btn neo-btn-pink full-button" type="submit">Block Slot</button>
            <div class="facility-admin-list">
              <div v-for="slot in activeBlockedSlots" :key="slot.id" class="facility-admin-row">
                <span>{{ slot.facilityName }} {{ slot.date }} {{ slot.startTime }}-{{ slot.endTime }}</span>
                <button class="mini-btn white" type="button" @click="unblockSlot(slot)">Unblock</button>
              </div>
            </div>
          </form>
        </div>

        <div v-if="activeAdminSection === 'logs' || activeAdminSection === 'analytics'" class="admin-grid">
          <div v-if="activeAdminSection === 'logs'" class="admin-section neo-card">
            <h2>Occupancy Logs</h2>
            <div class="log-list">
              <div v-for="log in recentLogs" :key="log.id" class="log-row">
                <strong>{{ log.action }}</strong>
                <span>{{ log.facilityName }} by {{ log.userName }}</span>
                <small>{{ shortDate(log.timestamp) }}</small>
              </div>
            </div>
          </div>

          <div v-if="activeAdminSection === 'analytics'" class="admin-section neo-card">
            <h2>Analytics</h2>
            <div class="analytics-grid">
              <div v-for="item in analyticsCards" :key="item.label" class="analytics-card">
                <strong>{{ item.value }}</strong>
                <span>{{ item.label }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  createFacility,
  deleteFacility,
  getFacilities,
  updateFacility
} from '@/services/facilityService'
import {
  createBlockedSlot,
  createBooking,
  createBookingLog,
  createNotification,
  getBlockedSlots,
  getBookingLogs,
  getBookings,
  updateBlockedSlot,
  updateBooking,
  validateBookingRequest
} from '@/services/bookingService'

const user = ref({})
const facilities = ref([])
const bookings = ref([])
const blockedSlots = ref([])
const bookingLogs = ref([])
const loading = ref(true)
const error = ref(null)
const selectedFacilityId = ref('')
const calendarDate = ref('')
const formError = ref('')
const adminBookingSearch = ref('')
const adminBookingStatus = ref('All')
const activeAdminSection = ref('menu')

const adminMenuItems = [
  {
    key: 'view-facilities',
    kicker: 'Catalog',
    label: 'View Facilities',
    description: 'Browse facilities, search options, and view the availability calendar.'
  },
  {
    key: 'approvals',
    kicker: 'Requests',
    label: 'Booking Approvals',
    description: 'Review pending requests and approve or reject booking slots.'
  },
  {
    key: 'all-bookings',
    kicker: 'Records',
    label: 'All Bookings',
    description: 'Search, filter, cancel, and override facility booking records.'
  },
  {
    key: 'add-facility',
    kicker: 'Create',
    label: 'Add Facility',
    description: 'Register a new hostel facility with capacity and amenities.'
  },
  {
    key: 'management',
    kicker: 'Manage',
    label: 'Facility Management',
    description: 'Edit, delete, and update facility details and availability.'
  },
  {
    key: 'slots',
    kicker: 'Schedule',
    label: 'Block/Unblock Slots',
    description: 'Reserve unavailable periods for maintenance, cleaning, or events.'
  },
  {
    key: 'logs',
    kicker: 'Audit',
    label: 'Occupancy Logs',
    description: 'Review booking, approval, cancellation, and slot activity.'
  },
  {
    key: 'analytics',
    kicker: 'Reports',
    label: 'Analytics',
    description: 'View booking statistics, utilization, popular facilities, and peak hours.'
  }
]

const filters = reactive({
  search: '',
  category: 'All',
  capacity: 0,
  amenity: 'All',
  availability: 'All'
})

const bookingForm = reactive({
  date: '',
  startTime: '',
  endTime: ''
})

const facilityForm = reactive({
  id: '',
  name: '',
  category: '',
  capacity: 1,
  description: '',
  amenitiesText: ''
})

const slotForm = reactive({
  facilityId: '',
  date: '',
  startTime: '',
  endTime: '',
  reason: 'Maintenance'
})

const today = computed(() => new Date().toISOString().slice(0, 10))
const isAdmin = computed(() => user.value.role === 'staff/admin' || user.value.role === 'Admin')
const showCatalogSection = computed(() => !isAdmin.value || activeAdminSection.value === 'view-facilities')
const showAdminContentSection = computed(() => isAdmin.value && activeAdminSection.value !== 'menu' && activeAdminSection.value !== 'view-facilities')
const pageTitle = computed(() => {
  if (!isAdmin.value) return 'Facilities Booking'
  const selected = adminMenuItems.find((item) => item.key === activeAdminSection.value)
  return activeAdminSection.value === 'menu' ? 'Facilities Management' : selected?.label || 'Facilities Management'
})
const pageSubtitle = computed(() => {
  if (!isAdmin.value) return 'Search facilities, check schedules, and request hostel facility slots'
  const selected = adminMenuItems.find((item) => item.key === activeAdminSection.value)
  return activeAdminSection.value === 'menu'
    ? 'Choose a facility management area to continue'
    : selected?.description || 'Manage hostel facilities and bookings'
})

const selectedFacility = computed(() => facilities.value.find((facility) => facility.id === selectedFacilityId.value))

const categories = computed(() => [...new Set(facilities.value.map((facility) => facility.category).filter(Boolean))])
const amenities = computed(() => [...new Set(facilities.value.flatMap((facility) => facility.amenities || []))])

const filteredFacilities = computed(() => {
  const query = filters.search.toLowerCase().trim()
  return facilities.value.filter((facility) => {
    const amenityText = (facility.amenities || []).join(' ').toLowerCase()
    const matchesSearch = !query ||
      facility.name.toLowerCase().includes(query) ||
      (facility.category || '').toLowerCase().includes(query) ||
      (facility.description || '').toLowerCase().includes(query) ||
      amenityText.includes(query)
    const matchesCategory = filters.category === 'All' || facility.category === filters.category
    const matchesCapacity = !filters.capacity || Number(facility.capacity) >= Number(filters.capacity)
    const matchesAmenity = filters.amenity === 'All' || (facility.amenities || []).includes(filters.amenity)
    const matchesAvailability = filters.availability === 'All' ||
      (filters.availability === 'Available' ? facility.availability : !facility.availability)
    return matchesSearch && matchesCategory && matchesCapacity && matchesAmenity && matchesAvailability
  })
})

const pendingBookings = computed(() => bookings.value.filter((booking) => booking.status === 'Pending'))
const activeBlockedSlots = computed(() => blockedSlots.value.filter((slot) => slot.status === 'Blocked'))

const calendarSlots = computed(() => {
  if (!selectedFacility.value || !calendarDate.value) return []

  const bookingSlots = bookings.value
    .filter((booking) => booking.facilityId === selectedFacility.value.id && booking.date === calendarDate.value)
    .map((booking) => ({
      id: `booking-${booking.id}`,
      startTime: booking.startTime,
      endTime: booking.endTime,
      status: booking.status
    }))

  const blocked = blockedSlots.value
    .filter((slot) => slot.facilityId === selectedFacility.value.id && slot.date === calendarDate.value && slot.status === 'Blocked')
    .map((slot) => ({
      id: `blocked-${slot.id}`,
      startTime: slot.startTime,
      endTime: slot.endTime,
      status: 'Blocked'
    }))

  const combined = [...bookingSlots, ...blocked].sort((a, b) => a.startTime.localeCompare(b.startTime))
  return combined.length ? combined : [{ id: 'available-day', startTime: '08:00', endTime: '22:00', status: 'Available' }]
})

const filteredAdminBookings = computed(() => {
  const query = adminBookingSearch.value.toLowerCase().trim()
  return bookings.value.filter((booking) => {
    const matchesQuery = !query || [booking.id, booking.userName, booking.facilityName, booking.status]
      .filter(Boolean)
      .some((value) => value.toLowerCase().includes(query))
    const matchesStatus = adminBookingStatus.value === 'All' || booking.status === adminBookingStatus.value
    return matchesQuery && matchesStatus
  })
})

const recentLogs = computed(() => [...bookingLogs.value]
  .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
  .slice(0, 8))

const analyticsCards = computed(() => {
  const total = bookings.value.length
  const count = (status) => bookings.value.filter((booking) => booking.status === status).length
  const approved = count('Approved')
  const utilization = facilities.value.length ? Math.round((approved / facilities.value.length) * 100) : 0
  const popular = facilities.value
    .map((facility) => ({
      name: facility.name,
      count: bookings.value.filter((booking) => booking.facilityId === facility.id).length
    }))
    .sort((a, b) => b.count - a.count)[0]
  const peak = bookings.value.reduce((acc, booking) => {
    const hour = booking.startTime ? booking.startTime.slice(0, 2) + ':00' : 'N/A'
    acc[hour] = (acc[hour] || 0) + 1
    return acc
  }, {})
  const peakHour = Object.entries(peak).sort((a, b) => b[1] - a[1])[0]

  return [
    { label: 'Total Bookings', value: total },
    { label: 'Pending Requests', value: count('Pending') },
    { label: 'Approved Requests', value: approved },
    { label: 'Rejected Requests', value: count('Rejected') },
    { label: 'Cancelled Requests', value: count('Cancelled') },
    { label: 'Facility Utilization Rate', value: `${utilization}%` },
    { label: 'Most Popular Facility', value: popular ? popular.name : 'N/A' },
    { label: 'Peak Usage Hour', value: peakHour ? peakHour[0] : 'N/A' }
  ]
})

watch(selectedFacilityId, () => {
  if (selectedFacility.value) {
    bookingForm.date = bookingForm.date || today.value
    calendarDate.value = bookingForm.date
  }
})

watch(() => bookingForm.date, (value) => {
  calendarDate.value = value || today.value
})

const loadModule = async () => {
  loading.value = true
  error.value = null
  try {
    user.value = JSON.parse(localStorage.getItem('user') || '{}')
    const [facilityRows, bookingRows, slotRows, logRows] = await Promise.all([
      getFacilities(),
      getBookings(),
      getBlockedSlots(),
      getBookingLogs()
    ])
    facilities.value = facilityRows
    bookings.value = bookingRows
    blockedSlots.value = slotRows
    bookingLogs.value = logRows
    selectedFacilityId.value = selectedFacilityId.value || facilityRows[0]?.id || ''
    calendarDate.value = calendarDate.value || today.value
    bookingForm.date = bookingForm.date || today.value
  } catch (err) {
    error.value = err.message || 'An error occurred while loading facilities.'
  } finally {
    loading.value = false
  }
}

const facilityStatus = (facility) => facility.availability ? 'Available' : 'Unavailable'
const openAdminSection = (section) => {
  activeAdminSection.value = section
  if (section === 'add-facility') resetFacilityForm()
}

const selectFacility = (facility) => {
  selectedFacilityId.value = facility.id
  formError.value = ''
}

const shortDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const logAction = async (action, payload) => {
  const log = await createBookingLog({
    action,
    timestamp: new Date().toISOString(),
    userId: payload.userId || user.value.id,
    userName: payload.userName || user.value.name,
    facilityId: payload.facilityId,
    facilityName: payload.facilityName
  })
  bookingLogs.value.push(log)
}

const notifyUser = (targetUserId, message) => {
  return createNotification({
    userId: targetUserId,
    message,
    type: 'Facility Booking',
    read: false,
    createdAt: new Date().toISOString()
  })
}

const submitBooking = async () => {
  if (!selectedFacility.value) return
  formError.value = ''

  const payload = {
    userId: user.value.id,
    userName: user.value.name,
    facilityId: selectedFacility.value.id,
    facilityName: selectedFacility.value.name,
    date: bookingForm.date,
    startTime: bookingForm.startTime,
    endTime: bookingForm.endTime,
    status: 'Pending',
    createdAt: new Date().toISOString()
  }

  const validationErrors = validateBookingRequest({
    booking: payload,
    facility: selectedFacility.value,
    bookings: bookings.value,
    blockedSlots: blockedSlots.value,
    user: user.value
  })

  if (validationErrors.length) {
    formError.value = validationErrors[0]
    return
  }

  try {
    const created = await createBooking(payload)
    bookings.value.push(created)
    await logAction('Booking Created', created)
    await notifyUser(user.value.id, 'Your booking request has been submitted and is awaiting approval.')
    bookingForm.startTime = ''
    bookingForm.endTime = ''
  } catch (err) {
    formError.value = err.message
  }
}

const approveBooking = async (booking) => {
  const facility = facilities.value.find((item) => item.id === booking.facilityId)
  const validationErrors = validateBookingRequest({
    booking,
    facility,
    bookings: bookings.value,
    blockedSlots: blockedSlots.value,
    user: { id: booking.userId, role: 'student' },
    ignoreBookingId: booking.id
  })

  if (validationErrors.length && !confirm(`${validationErrors[0]} Override and approve anyway?`)) return

  const updated = await updateBooking(booking.id, { status: 'Approved', approvedAt: new Date().toISOString() })
  Object.assign(booking, updated)
  await logAction('Booking Approved', booking)
  await notifyUser(booking.userId, 'Your booking request has been approved.')
}

const rejectBooking = async (booking) => {
  const reason = prompt('Rejection reason: Maintenance, Time Conflict, Policy Violation, or Facility Unavailable', 'Time Conflict')
  const updated = await updateBooking(booking.id, {
    status: 'Rejected',
    rejectionReason: reason || 'Facility Unavailable',
    rejectedAt: new Date().toISOString()
  })
  Object.assign(booking, updated)
  await logAction('Booking Rejected', booking)
  await notifyUser(booking.userId, 'Your booking request has been rejected.')
}

const cancelBooking = async (booking) => {
  const updated = await updateBooking(booking.id, { status: 'Cancelled', cancelledAt: new Date().toISOString() })
  Object.assign(booking, updated)
  await logAction('Booking Cancelled', booking)
  await notifyUser(booking.userId, 'Your booking has been cancelled.')
}

const overrideBooking = async (booking) => {
  const updated = await updateBooking(booking.id, { status: 'Approved', override: true, approvedAt: new Date().toISOString() })
  Object.assign(booking, updated)
  await logAction('Booking Override', booking)
  await notifyUser(booking.userId, 'Your booking request has been approved.')
}

const resetFacilityForm = () => {
  Object.assign(facilityForm, {
    id: '',
    name: '',
    category: '',
    capacity: 1,
    description: '',
    amenitiesText: ''
  })
}

const editFacility = (facility) => {
  Object.assign(facilityForm, {
    id: facility.id,
    name: facility.name,
    category: facility.category,
    capacity: facility.capacity,
    description: facility.description,
    amenitiesText: (facility.amenities || []).join(', ')
  })
}

const saveFacility = async () => {
  const payload = {
    name: facilityForm.name,
    category: facilityForm.category,
    capacity: Number(facilityForm.capacity),
    description: facilityForm.description,
    amenities: facilityForm.amenitiesText.split(',').map((item) => item.trim()).filter(Boolean),
    availability: true,
    status: 'Available',
    restricted: false,
    authorizedRoles: ['student', 'staff/admin']
  }

  if (facilityForm.id) {
    const updated = await updateFacility(facilityForm.id, payload)
    const index = facilities.value.findIndex((facility) => facility.id === updated.id)
    if (index !== -1) facilities.value[index] = updated
  } else {
    facilities.value.push(await createFacility(payload))
  }
  resetFacilityForm()
}

const removeFacility = async (facility) => {
  if (!confirm(`Delete ${facility.name}?`)) return
  await deleteFacility(facility.id)
  facilities.value = facilities.value.filter((item) => item.id !== facility.id)
}

const blockSlot = async () => {
  const facility = facilities.value.find((item) => item.id === slotForm.facilityId)
  if (!facility) return

  const payload = {
    facilityId: facility.id,
    facilityName: facility.name,
    date: slotForm.date,
    startTime: slotForm.startTime,
    endTime: slotForm.endTime,
    reason: slotForm.reason,
    status: 'Blocked',
    createdAt: new Date().toISOString(),
    createdBy: user.value.id
  }

  const created = await createBlockedSlot(payload)
  blockedSlots.value.push(created)
  await logAction('Slot Blocked', created)
  Object.assign(slotForm, { facilityId: '', date: '', startTime: '', endTime: '', reason: 'Maintenance' })
}

const unblockSlot = async (slot) => {
  const updated = await updateBlockedSlot(slot.id, { status: 'Unblocked', unblockedAt: new Date().toISOString() })
  Object.assign(slot, updated)
  await logAction('Slot Unblocked', slot)
}

onMounted(loadModule)
</script>

<style scoped>
.facilities-view {
  width: 100%;
}

.page-header-block,
.section-heading,
.card-title-row,
.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.page-header-block {
  background-color: var(--primary-pink);
}

.page-title {
  font-size: 32px;
  margin-bottom: 4px;
}

.page-subtitle,
.facility-description {
  font-size: 16px;
  font-weight: 500;
  line-height: 1.5;
}

.header-summary,
.card-actions,
.table-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.admin-badge,
.badge-pending {
  background-color: var(--primary-yellow);
}

.badge-approved {
  background-color: var(--status-completed);
}

.badge-rejected {
  background-color: var(--status-rejected);
}

.controls-panel,
.admin-section,
.details-panel,
.facility-card,
.admin-menu-card {
  background-color: #FFFFFF;
}

.admin-menu-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.admin-menu-card {
  width: 100%;
  margin-bottom: 0;
  text-align: left;
  font-family: 'Space Grotesk', sans-serif;
  color: #000000;
  cursor: pointer;
  display: grid;
  gap: 10px;
}

.admin-menu-card:hover {
  transform: translate(2px, 2px);
  box-shadow: 4px 4px 0 #000000;
}

.admin-menu-card strong {
  font-size: 24px;
}

.admin-menu-card span:last-child {
  font-size: 15px;
  font-weight: 500;
  line-height: 1.4;
}

.menu-card-kicker {
  width: fit-content;
  border: 2px solid #000000;
  border-radius: 16px;
  background-color: var(--primary-yellow);
  padding: 4px 10px;
  font-size: 13px;
  font-weight: 700;
}

.admin-back-btn {
  margin-bottom: 24px;
}

.section-controls {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.filter-grid,
.admin-grid,
.time-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.search-field {
  margin-bottom: 16px;
}

.module-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
  gap: 24px;
  align-items: start;
}

.facility-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.facility-card {
  margin-bottom: 0;
}

.facility-card.selected {
  background-color: #FFFDF5;
}

.facility-card h2,
.details-panel h2,
.admin-section h2 {
  font-size: 22px;
}

.facility-meta,
.details-list div,
.slot-row,
.facility-admin-row,
.log-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.facility-meta {
  border-top: 2px solid #000000;
  border-bottom: 2px solid #000000;
  padding: 10px 0;
  margin: 14px 0;
}

.amenities-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.amenities-list span {
  border: 2px solid #000000;
  border-radius: 16px;
  padding: 4px 10px;
  font-weight: 700;
  background: #FFFFFF;
}

.details-panel {
  position: sticky;
  top: 96px;
}

.details-list {
  display: grid;
  gap: 10px;
  margin: 16px 0;
}

.details-list div,
.slot-row,
.facility-admin-row,
.log-row,
.analytics-card {
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 10px;
  background-color: #FFFDF5;
}

.calendar-block,
.booking-form,
.facility-admin-list,
.log-list {
  margin-top: 20px;
}

.calendar-block h3,
.booking-form h3 {
  margin-bottom: 12px;
}

.slot-list,
.facility-admin-list,
.log-list {
  display: grid;
  gap: 10px;
}

.slot-row.pending {
  background-color: #FFF1CC;
}

.slot-row.approved,
.slot-row.available {
  background-color: #DDF8F5;
}

.slot-row.blocked,
.slot-row.rejected,
.slot-row.cancelled {
  background-color: #FFE0E0;
}

.full-button {
  width: 100%;
}

.admin-stack {
  display: grid;
  gap: 24px;
  margin-top: 24px;
}

.table-wrap {
  width: 100%;
  overflow-x: auto;
}

.neo-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 820px;
}

.neo-table th,
.neo-table td {
  border: 3px solid #000000;
  padding: 10px;
  text-align: left;
  background-color: #FFFFFF;
}

.neo-table th {
  background-color: var(--primary-yellow);
}

.mini-btn {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
  box-shadow: 2px 2px 0 #000000;
}

.mini-btn.yellow {
  background-color: var(--primary-yellow);
}

.mini-btn.pink {
  background-color: var(--primary-pink);
}

.mini-btn.white {
  background-color: #FFFFFF;
}

.compact-input {
  max-width: 260px;
}

.analytics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-top: 16px;
}

.analytics-card {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.analytics-card strong {
  font-size: 24px;
}

.loading-state,
.error-state,
.empty-state,
.empty-details {
  text-align: center;
  padding: 40px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--primary-yellow);
  border-top-color: #000000;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

button:disabled {
  background-color: #DDDDDD;
  color: #777777;
  box-shadow: none;
  cursor: not-allowed;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 960px) {
  .module-grid,
  .facility-grid,
  .admin-menu-grid,
  .admin-grid,
  .filter-grid,
  .time-grid {
    grid-template-columns: 1fr;
  }

  .details-panel {
    position: static;
  }
}

@media (max-width: 768px) {
  .page-header-block,
  .section-heading,
  .card-title-row,
  .panel-heading {
    flex-direction: column;
  }
}
</style>

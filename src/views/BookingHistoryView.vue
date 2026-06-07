<template>
  <div class="bookings-history-view">
    <div class="page-header-block neo-card">
      <div>
        <h1 class="page-title">{{ isAdmin ? 'All Bookings' : 'My Bookings' }}</h1>
        <p class="page-subtitle">
          {{ isAdmin ? 'Review facility booking records from the Facilities tab' : 'Track your facility requests and booking history' }}
        </p>
      </div>
      <RouterLink to="/facilities" class="neo-btn neo-btn-white">
        Facilities
      </RouterLink>
    </div>

    <div v-if="loading" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Retrieving booking records...</p>
    </div>

    <div v-else-if="error" class="error-state neo-card">
      <h3>Failed to Load My Bookings</h3>
      <p>{{ error }}</p>
      <button @click="loadBookings" class="neo-btn neo-btn-yellow">Retry</button>
    </div>

    <template v-else>
      <div class="controls-panel neo-card">
        <div class="form-group">
          <label class="form-label" for="booking-search">Search Bookings</label>
          <input
            id="booking-search"
            v-model="searchQuery"
            class="form-input"
            type="search"
            placeholder="Search by facility, date, or status"
          />
        </div>
        <div class="booking-summary">
          <span class="neo-badge badge-approved">{{ upcomingBookings.length }} Upcoming</span>
          <span class="neo-badge badge-pending">{{ pendingRequests.length }} Pending</span>
          <span class="neo-badge">{{ bookingHistory.length }} History</span>
        </div>
      </div>

      <section class="booking-section">
        <div class="section-heading">
          <h2>Upcoming Bookings</h2>
        </div>
        <div v-if="upcomingBookings.length === 0" class="empty-state neo-card">
          <h3>No Upcoming Bookings</h3>
          <p>Approved future bookings will appear here.</p>
        </div>
        <div v-else class="booking-grid">
          <article v-for="booking in upcomingBookings" :key="booking.id" class="booking-card neo-card">
            <BookingDetails :booking="booking" />
            <div class="card-actions">
              <button class="neo-btn neo-btn-white" @click="openDetails(booking)">View Details</button>
              <button class="neo-btn neo-btn-pink" @click="cancelOwnBooking(booking)">Cancel</button>
            </div>
          </article>
        </div>
      </section>

      <section class="booking-section">
        <div class="section-heading">
          <h2>Pending Requests</h2>
        </div>
        <div v-if="pendingRequests.length === 0" class="empty-state neo-card">
          <h3>No Pending Requests</h3>
          <p>Submitted requests awaiting admin approval will appear here.</p>
        </div>
        <div v-else class="booking-grid">
          <article v-for="booking in pendingRequests" :key="booking.id" class="booking-card neo-card">
            <BookingDetails :booking="booking" />
            <div class="card-actions">
              <button class="neo-btn neo-btn-white" @click="openDetails(booking)">View Details</button>
              <button class="neo-btn neo-btn-pink" @click="cancelOwnBooking(booking)">Cancel</button>
            </div>
          </article>
        </div>
      </section>

      <section class="booking-section">
        <div class="section-heading">
          <h2>Booking History</h2>
        </div>
        <div v-if="bookingHistory.length === 0" class="empty-state neo-card">
          <h3>No Booking History</h3>
          <p>Approved, rejected, cancelled, and completed bookings will appear here.</p>
        </div>
        <div v-else class="booking-grid">
          <article v-for="booking in bookingHistory" :key="booking.id" class="booking-card neo-card">
            <BookingDetails :booking="booking" />
            <div class="card-actions">
              <button class="neo-btn neo-btn-white" @click="openDetails(booking)">View Details</button>
            </div>
          </article>
        </div>
      </section>
    </template>

    <div v-if="selectedBooking" class="modal-backdrop" @click.self="selectedBooking = null">
      <div class="details-modal neo-card">
        <div class="modal-heading">
          <h2>Booking Details</h2>
          <button class="mini-btn white" @click="selectedBooking = null">Close</button>
        </div>
        <BookingDetails :booking="selectedBooking" detailed />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref } from 'vue'
import StatusBadge from '@/components/StatusBadge.vue'
import {
  createBookingLog,
  createNotification,
  getBookings,
  updateBooking
} from '@/services/bookingService'

const BookingDetails = defineComponent({
  props: {
    booking: {
      type: Object,
      required: true
    },
    detailed: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const rows = computed(() => [
      ['Booking ID', props.booking.id],
      ['Facility Name', props.booking.facilityName],
      ['Date', props.booking.date],
      ['Start Time', props.booking.startTime],
      ['End Time', props.booking.endTime],
      ['Status', props.booking.status],
      ['Created Date', formatDate(props.booking.createdAt)]
    ])

    return () => h('div', { class: 'details-box' }, [
      h('div', { class: 'details-title' }, [
        h('h3', props.booking.facilityName),
        h(StatusBadge, { status: props.booking.status })
      ]),
      h('div', { class: 'details-grid' }, rows.value.map(([label, value]) =>
        h('div', { class: 'detail-row', key: label }, [
          h('span', label),
          h('strong', value || 'N/A')
        ])
      )),
      props.detailed && props.booking.rejectionReason
        ? h('p', { class: 'detail-note' }, `Reason: ${props.booking.rejectionReason}`)
        : null
    ])
  }
})

const user = ref({})
const bookings = ref([])
const loading = ref(true)
const error = ref(null)
const searchQuery = ref('')
const selectedBooking = ref(null)

const isAdmin = computed(() => user.value.role === 'staff/admin' || user.value.role === 'Admin')

const userBookings = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  const source = isAdmin.value ? bookings.value : bookings.value.filter((booking) => {
    return booking.userId === user.value.id || booking.userName === user.value.name || booking.studentName === user.value.name
  })

  if (!query) return source

  return source.filter((booking) => {
    return [booking.facilityName, booking.date, booking.status]
      .filter(Boolean)
      .some((value) => value.toLowerCase().includes(query))
  })
})

const upcomingBookings = computed(() => {
  return userBookings.value
    .filter((booking) => booking.status === 'Approved' && new Date(`${booking.date}T${booking.endTime}`) >= new Date())
    .sort(sortByDate)
})

const pendingRequests = computed(() => {
  return userBookings.value
    .filter((booking) => booking.status === 'Pending')
    .sort(sortByDate)
})

const bookingHistory = computed(() => {
  return userBookings.value
    .filter((booking) => ['Approved', 'Rejected', 'Cancelled', 'Completed'].includes(booking.status))
    .filter((booking) => booking.status !== 'Approved' || new Date(`${booking.date}T${booking.endTime}`) < new Date())
    .sort((a, b) => sortByDate(b, a))
})

function sortByDate(a, b) {
  return new Date(`${a.date}T${a.startTime}`) - new Date(`${b.date}T${b.startTime}`)
}

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString()
}

const loadBookings = async () => {
  loading.value = true
  error.value = null
  try {
    user.value = JSON.parse(localStorage.getItem('user') || '{}')
    bookings.value = await getBookings()
  } catch (err) {
    error.value = err.message || 'An error occurred while loading bookings.'
  } finally {
    loading.value = false
  }
}

const openDetails = (booking) => {
  selectedBooking.value = booking
}

const cancelOwnBooking = async (booking) => {
  const bookingStart = new Date(`${booking.date}T${booking.startTime}`)
  const minutesUntilStart = (bookingStart - new Date()) / 60000

  if (minutesUntilStart < 30) {
    alert('Bookings can only be cancelled at least 30 minutes before the start time.')
    return
  }

  if (!confirm('Cancel this booking?')) return

  const updated = await updateBooking(booking.id, {
    status: 'Cancelled',
    cancelledAt: new Date().toISOString()
  })
  Object.assign(booking, updated)

  await createBookingLog({
    action: 'Booking Cancelled',
    timestamp: new Date().toISOString(),
    userId: user.value.id,
    userName: user.value.name,
    facilityId: booking.facilityId,
    facilityName: booking.facilityName
  })

  await createNotification({
    userId: user.value.id,
    message: 'Your booking has been cancelled.',
    type: 'Facility Booking',
    read: false,
    createdAt: new Date().toISOString()
  })
}

onMounted(loadBookings)
</script>

<style scoped>
.bookings-history-view {
  width: 100%;
}

.page-header-block,
.section-heading,
.details-title,
.modal-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.page-header-block {
  background-color: var(--primary-pink);
}

.page-title {
  font-size: 32px;
  margin-bottom: 4px;
}

.page-subtitle {
  font-size: 16px;
  font-weight: 500;
}

.controls-panel,
.booking-card,
.loading-state,
.error-state,
.empty-state,
.details-modal {
  background-color: #FFFFFF;
}

.booking-summary,
.card-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.booking-summary {
  margin-top: 12px;
}

.booking-section {
  margin-bottom: 24px;
}

.section-heading {
  margin-bottom: 14px;
}

.section-heading h2 {
  font-size: 24px;
}

.booking-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.booking-card {
  margin-bottom: 0;
}

.details-box {
  display: grid;
  gap: 16px;
}

.details-title {
  border-bottom: 2px solid #000000;
  padding-bottom: 12px;
}

.details-title h3 {
  font-size: 20px;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.detail-row {
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 10px;
  background-color: #FFFDF5;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.detail-row span {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  color: #555555;
}

.detail-note {
  font-weight: 700;
}

.badge-approved {
  background-color: var(--status-completed);
}

.badge-pending {
  background-color: var(--status-pending);
}

.loading-state,
.error-state,
.empty-state {
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

.mini-btn {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  border: 2px solid #000000;
  border-radius: 8px;
  padding: 6px 10px;
  cursor: pointer;
  box-shadow: 2px 2px 0 #000000;
}

.mini-btn.white {
  background-color: #FFFFFF;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .page-header-block,
  .section-heading,
  .details-title,
  .modal-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .booking-grid,
  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>

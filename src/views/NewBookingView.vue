<template>
  <div class="new-booking-view">
    <!-- Header Block -->
    <div class="page-header-block neo-card">
      <h1 class="page-title">Facility Booking Form</h1>
      <p class="page-subtitle">Schedule a reservation slot for public hostel facilities</p>
    </div>

    <!-- Booking Form Container -->
    <div v-if="loadingFacilities" class="loading-state neo-card">
      <div class="spinner"></div>
      <p>Loading available options...</p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="booking-form neo-card">
      <h2 class="form-title">Reserve Facility</h2>

      <!-- Facility Select -->
      <div class="form-group">
        <label for="facility" class="form-label">Facility</label>
        <select
          id="facility"
          v-model="form.facilityName"
          class="form-select"
          :class="{ 'input-error': errors.facilityName }"
        >
          <option value="" disabled>Select a facility...</option>
          <option
            v-for="fac in facilities"
            :key="fac.id"
            :value="fac.name"
            :disabled="!fac.availability"
          >
            {{ fac.name }} {{ fac.availability ? '' : '(Unavailable)' }}
          </option>
        </select>
        <span v-if="errors.facilityName" class="form-error">{{ errors.facilityName }}</span>
      </div>

      <!-- Date -->
      <div class="form-group">
        <label for="date" class="form-label">Date</label>
        <input
          type="date"
          id="date"
          v-model="form.date"
          class="form-input"
          :class="{ 'input-error': errors.date }"
          :min="todayStr"
        />
        <span v-if="errors.date" class="form-error">{{ errors.date }}</span>
      </div>

      <!-- Time slot row -->
      <div class="grid-2">
        <div class="form-group">
          <label for="start-time" class="form-label">Start Time</label>
          <input
            type="time"
            id="start-time"
            v-model="form.startTime"
            class="form-input"
            :class="{ 'input-error': errors.startTime }"
          />
          <span v-if="errors.startTime" class="form-error">{{ errors.startTime }}</span>
        </div>

        <div class="form-group">
          <label for="end-time" class="form-label">End Time</label>
          <input
            type="time"
            id="end-time"
            v-model="form.endTime"
            class="form-input"
            :class="{ 'input-error': errors.endTime }"
          />
          <span v-if="errors.endTime" class="form-error">{{ errors.endTime }}</span>
        </div>
      </div>

      <!-- Purpose -->
      <div class="form-group">
        <label for="purpose" class="form-label">Purpose / Description</label>
        <textarea
          id="purpose"
          v-model="form.purpose"
          placeholder="What will you be using this facility for?"
          rows="3"
          class="form-input form-textarea"
          :class="{ 'input-error': errors.purpose }"
        ></textarea>
        <span v-if="errors.purpose" class="form-error">{{ errors.purpose }}</span>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" class="neo-btn neo-btn-yellow">
          Confirm Reservation
        </button>
        <button type="button" @click="handleCancel" class="neo-btn neo-btn-white">
          Cancel
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const facilities = ref([])
const loadingFacilities = ref(true)
const todayStr = ref('')

const form = reactive({
  facilityName: '',
  date: '',
  startTime: '',
  endTime: '',
  purpose: ''
})

const errors = reactive({
  facilityName: '',
  date: '',
  startTime: '',
  endTime: '',
  purpose: ''
})

const fetchFacilities = async () => {
  try {
    const res = await fetch('http://localhost:3000/facilities')
    if (res.ok) {
      facilities.value = await res.json()
    }
  } catch (err) {
    console.error('Error fetching facilities:', err)
  } finally {
    loadingFacilities.value = false
  }
}

onMounted(async () => {
  // Set minimum date to today
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  todayStr.value = `${year}-${month}-${day}`

  await fetchFacilities()

  // Pre-fill facility selection from route query parameters if applicable
  if (route.query.facility) {
    const matched = facilities.value.find(
      (f) => f.name.toLowerCase() === route.query.facility.toLowerCase()
    )
    if (matched && matched.availability) {
      form.facilityName = matched.name
    }
  }
})

const toMinutes = (timeStr) => {
  if (!timeStr) return 0
  const [h, m] = timeStr.split(':').map(Number)
  return h * 60 + m
}

const validateForm = () => {
  let isValid = true

  // Facility Name
  if (!form.facilityName) {
    errors.facilityName = 'Please select a facility.'
    isValid = false
  } else {
    errors.facilityName = ''
  }

  // Date
  if (!form.date) {
    errors.date = 'Date is required.'
    isValid = false
  } else {
    errors.date = ''
  }

  // Start Time
  if (!form.startTime) {
    errors.startTime = 'Start time is required.'
    isValid = false
  } else {
    errors.startTime = ''
  }

  // End Time
  if (!form.endTime) {
    errors.endTime = 'End time is required.'
    isValid = false
  } else {
    errors.endTime = ''
  }

  // End Time > Start Time
  if (form.startTime && form.endTime) {
    const startMins = toMinutes(form.startTime)
    const endMins = toMinutes(form.endTime)
    if (endMins <= startMins) {
      errors.endTime = 'End time must be after the start time.'
      isValid = false
    } else {
      errors.endTime = ''
    }
  }

  // Purpose
  if (!form.purpose.trim()) {
    errors.purpose = 'Purpose of reservation is required.'
    isValid = false
  } else if (form.purpose.trim().length < 5) {
    errors.purpose = 'Purpose description must be at least 5 characters.'
    isValid = false
  } else {
    errors.purpose = ''
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) return

  try {
    const bookingPayload = {
      facilityName: form.facilityName,
      date: form.date,
      startTime: form.startTime,
      endTime: form.endTime,
      purpose: form.purpose,
      status: 'Pending'
    }

    const res = await fetch('http://localhost:3000/bookings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(bookingPayload)
    })

    if (!res.ok) throw new Error('Failed to create booking.')

    router.push('/bookings')
  } catch (err) {
    alert(err.message || 'An error occurred while scheduling your booking.')
  }
}

const handleCancel = () => {
  router.push('/facilities')
}
</script>

<style scoped>
.new-booking-view {
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

.booking-form {
  background-color: #FFFFFF;
  max-width: 600px;
  margin: 0 auto;
}

.form-title {
  font-size: 24px;
  margin-bottom: 20px;
  border-bottom: 3px solid #000000;
  padding-bottom: 12px;
}

.form-textarea {
  resize: vertical;
}

.input-error {
  border-color: var(--status-rejected);
  background-color: #FFF5F5;
}

.form-actions {
  display: flex;
  gap: 16px;
  margin-top: 24px;
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

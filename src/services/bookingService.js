const API_BASE = 'http://localhost:8000'
const MAX_DURATION_MINUTES = 120
const WEEKLY_BOOKING_LIMIT = 3
const ACTIVE_STATUSES = ['Pending', 'Approved']

const request = async (path, options = {}) => {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    },
    ...options
  })

  if (!res.ok) {
    throw new Error(options.errorMessage || 'Booking request failed.')
  }

  return res.status === 204 ? null : res.json()
}

export const getBookings = () => request('/bookings', {
  errorMessage: 'Could not retrieve bookings.'
})

export const getBookingLogs = () => request('/bookingLogs', {
  errorMessage: 'Could not retrieve booking logs.'
})

export const getNotifications = () => request('/notifications', {
  errorMessage: 'Could not retrieve notifications.'
})

export const getBlockedSlots = () => request('/blockedSlots', {
  errorMessage: 'Could not retrieve blocked slots.'
})

export const toMinutes = (time) => {
  if (!time) return 0
  const [hours, minutes] = time.split(':').map(Number)
  return hours * 60 + minutes
}

export const overlaps = (first, second) => {
  if (first.date !== second.date) return false
  if (first.facilityId !== second.facilityId && first.facilityName !== second.facilityName) return false
  return toMinutes(first.startTime) < toMinutes(second.endTime) &&
    toMinutes(second.startTime) < toMinutes(first.endTime)
}

export const getWeekKey = (dateString) => {
  const date = new Date(`${dateString}T00:00:00`)
  const firstDay = new Date(date.getFullYear(), 0, 1)
  const days = Math.floor((date - firstDay) / 86400000)
  return `${date.getFullYear()}-W${Math.ceil((days + firstDay.getDay() + 1) / 7)}`
}

export const validateBookingRequest = ({
  booking,
  facility,
  bookings = [],
  blockedSlots = [],
  user = {},
  ignoreBookingId = null
}) => {
  const errors = []
  const start = toMinutes(booking.startTime)
  const end = toMinutes(booking.endTime)

  if (!booking.facilityId && !booking.facilityName) errors.push('Please select a facility.')
  if (!booking.date) errors.push('Please select a booking date.')
  if (!booking.startTime) errors.push('Please select a start time.')
  if (!booking.endTime) errors.push('Please select an end time.')
  if (booking.startTime && booking.endTime && end <= start) {
    errors.push('End time must be after the start time.')
  }
  if (booking.startTime && booking.endTime && end - start > MAX_DURATION_MINUTES) {
    errors.push('Maximum booking duration is 2 hours per session.')
  }
  if (facility && facility.availability === false) {
    errors.push('This facility is currently unavailable.')
  }
  if (facility && facility.restricted && user.role !== 'staff/admin' && !facility.authorizedRoles?.includes(user.role)) {
    errors.push('You are not authorized to book this restricted facility.')
  }

  const comparable = {
    ...booking,
    facilityName: booking.facilityName || facility?.name
  }

  const hasBookingConflict = bookings.some((existing) => {
    if (existing.id === ignoreBookingId) return false
    if (!ACTIVE_STATUSES.includes(existing.status)) return false
    return overlaps(existing, comparable)
  })

  if (hasBookingConflict) {
    errors.push('This facility is already booked during the selected time period.')
  }

  const hasBlockedConflict = blockedSlots.some((slot) => {
    if (slot.status !== 'Blocked') return false
    return overlaps(slot, comparable)
  })

  if (hasBlockedConflict) {
    errors.push('This facility is blocked during the selected time period.')
  }

  const userWeek = getWeekKey(booking.date)
  const weeklyBookings = bookings.filter((existing) => {
    if (existing.id === ignoreBookingId) return false
    if (existing.userId !== user.id) return false
    if (!ACTIVE_STATUSES.includes(existing.status)) return false
    return getWeekKey(existing.date) === userWeek
  })

  if (weeklyBookings.length >= WEEKLY_BOOKING_LIMIT) {
    errors.push('Weekly booking limit reached. You can make up to 3 bookings per week.')
  }

  return errors
}

export const createBooking = (booking) => request('/bookings', {
  method: 'POST',
  body: JSON.stringify(booking),
  errorMessage: 'Could not submit booking request.'
})

export const updateBooking = (id, updates) => request(`/bookings/${id}`, {
  method: 'PATCH',
  body: JSON.stringify(updates),
  errorMessage: 'Could not update booking.'
})

export const createBlockedSlot = (slot) => request('/blockedSlots', {
  method: 'POST',
  body: JSON.stringify(slot),
  errorMessage: 'Could not block slot.'
})

export const updateBlockedSlot = (id, updates) => request(`/blockedSlots/${id}`, {
  method: 'PATCH',
  body: JSON.stringify(updates),
  errorMessage: 'Could not update blocked slot.'
})

export const createBookingLog = (log) => request('/bookingLogs', {
  method: 'POST',
  body: JSON.stringify(log),
  errorMessage: 'Could not create booking log.'
})

export const createNotification = (notification) => request('/notifications', {
  method: 'POST',
  body: JSON.stringify(notification),
  errorMessage: 'Could not create notification.'
})

<template>
  <div class="booking-list">
    <div v-if="bookings.length === 0" class="empty-state neo-card">
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
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
      <h3>No Bookings Found</h3>
      <p>There are no bookings matching your criteria.</p>
    </div>
    
    <div v-else class="cards-grid">
      <BookingCard
        v-for="booking in bookings"
        :key="booking.id"
        :booking="booking"
      />
    </div>
  </div>
</template>

<script setup>
import BookingCard from './BookingCard.vue'

defineProps({
  bookings: {
    type: Array,
    required: true
  }
})
</script>

<style scoped>
.booking-list {
  width: 100%;
}

.cards-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
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

.empty-state h3 {
  font-size: 20px;
}

.empty-state p {
  color: #666666;
  font-size: 15px;
}
</style>
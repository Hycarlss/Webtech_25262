<template>
  <div class="room-card neo-card">
    <div class="header-section">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2.5"
        stroke="currentColor"
        class="icon"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"
        />
      </svg>
      <h3 class="room-title">YOUR ROOM</h3>
    </div>
    <div class="room-details">
      <div class="detail-item">
        <span class="label">Hostel Block</span>
        <span class="value">{{ hostelBlock || 'Not Assigned' }}</span>
      </div>
      <div class="detail-item">
        <span class="label">Room Number</span>
        <span class="value highlight-yellow">{{ roomNumber || 'Not Assigned' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  hostelBlock: {
    type: String,
    default: ''
  },
  roomNumber: {
    type: String,
    default: ''
  }
})
</script>

<style scoped>
.room-card {
  background-color: #FFFFFF;
}

.header-section {
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 3px solid #000000;
  padding-bottom: 12px;
  margin-bottom: 16px;
}

.icon {
  width: 28px;
  height: 28px;
  color: var(--logo-purple);
}

.room-title {
  font-size: 20px;
  letter-spacing: -0.2px;
}

.room-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.label {
  font-size: 16px;
  font-weight: 500;
  color: #555555;
}

.value {
  font-size: 20px;
  font-weight: 700;
}

.highlight-yellow {
  background-color: var(--primary-yellow);
  padding: 4px 8px;
  border: 2px solid #000000;
  border-radius: 6px;
  box-shadow: 2px 2px 0px #000000;
}
</style>
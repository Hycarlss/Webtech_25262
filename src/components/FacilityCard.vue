<template>
  <div class="facility-card neo-card" :class="{ 'unavailable': !facility.availability }">
    <div class="card-header">
      <h3 class="facility-name">{{ facility.name }}</h3>
      <span
        class="neo-badge"
        :style="{ backgroundColor: facility.availability ? 'var(--status-completed)' : 'var(--status-rejected)' }"
      >
        {{ facility.availability ? 'Available' : 'Unavailable' }}
      </span>
    </div>

    <div class="card-body">
      <div class="capacity-info">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="2.5"
          stroke="currentColor"
          class="user-icon"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.97 5.97 0 00-.75-2.906m-.173 0A9.09 9.09 0 0012 15.75c-2.25 0-4.307.823-5.877 2.193m11.892-3.193a5.97 5.97 0 01-.75-2.906V12a3.75 3.75 0 117.5 0v1.031c0 .714-.107 1.4-.3 2.066M3.75 18.72a9.094 9.094 0 01-3.741-.479 3 3 0 014.682-2.72m-.94 3.198l-.001.031c0 .225.012.447.037.666A11.944 11.944 0 0012 21c2.17 0 4.207-.576 5.963-1.584A6.062 6.062 0 0018 18.722m-12 0a5.97 5.97 0 01.75-2.906m.173 0A9.09 9.09 0 0112 15.75c2.25 0 4.307.823 5.877 2.193m-11.892-3.193a5.97 5.97 0 00.75-2.906V12a3.75 3.75 0 10-7.5 0v1.031c0 .714.107 1.4.3 2.066M9.75 9.75c0 .414-.168.789-.439 1.061A3.735 3.735 0 016 12a3.75 3.75 0 01-3.75-3.75c0-1.036.42-1.974 1.098-2.656A3.734 3.734 0 016 4.5a3.75 3.75 0 013.75 3.75V9.75z"
          />
        </svg>
        <span class="capacity-text">Capacity: <strong>{{ facility.capacity }} pax</strong></span>
      </div>
    </div>

    <div class="card-footer">
      <button
        v-if="user.role !== 'staff/admin'"
        @click="bookFacility"
        :disabled="!facility.availability"
        class="neo-btn neo-btn-pink w-full"
      >
        Book Now
      </button>
      <button
        v-else
        @click="toggleAvailability"
        class="neo-btn neo-btn-yellow w-full"
      >
        {{ facility.availability ? 'Set Unavailable' : 'Set Available' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  facility: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['refresh'])
const router = useRouter()
const user = ref({})

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (storedUser) {
    user.value = JSON.parse(storedUser)
  }
})

const bookFacility = () => {
  if (props.facility.availability) {
    router.push({
      path: '/facilities/book',
      query: { facility: props.facility.name }
    })
  }
}

const toggleAvailability = async () => {
  try {
    const res = await fetch(`http://localhost:8000/facilities/${props.facility.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ availability: !props.facility.availability })
    })
    if (!res.ok) throw new Error('Could not update facility availability.')
    emit('refresh')
  } catch (err) {
    alert(err.message)
  }
}
</script>

<style scoped>
.facility-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 16px;
  background-color: #FFFFFF;
}

.facility-card.unavailable {
  opacity: 0.85;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #000000;
  padding-bottom: 12px;
}

.facility-name {
  font-size: 20px;
}

.card-body {
  padding: 4px 0;
}

.capacity-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-icon {
  width: 20px;
  height: 20px;
  color: #555555;
}

.capacity-text {
  font-size: 16px;
}

.w-full {
  width: 100%;
}

button:disabled {
  background-color: #DDDDDD;
  color: #888888;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
  border-color: #888888;
}
</style>

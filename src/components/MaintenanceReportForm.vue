<template>
  <form @submit.prevent="handleSubmit" class="maintenance-form neo-card">
    <h2 class="form-title">Submit Maintenance Issue</h2>
    
    <!-- Title Field -->
    <div class="form-group">
      <label for="title" class="form-label">Issue Title</label>
      <input
        type="text"
        id="title"
        v-model="form.title"
        placeholder="e.g. Leaking pipe under bathroom sink"
        class="form-input"
        :class="{ 'input-error': errors.title }"
      />
      <span v-if="errors.title" class="form-error">{{ errors.title }}</span>
    </div>

    <!-- Category Field -->
    <div class="form-group">
      <label for="category" class="form-label">Issue Category</label>
      <select
        id="category"
        v-model="form.category"
        class="form-select"
        :class="{ 'input-error': errors.category }"
      >
        <option value="" disabled>Select a category</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
      <span v-if="errors.category" class="form-error">{{ errors.category }}</span>
    </div>

    <!-- Priority Field -->
    <div class="form-group">
      <label for="priority" class="form-label">Priority Level</label>
      <select
        id="priority"
        v-model="form.priority"
        class="form-select"
      >
        <option value="Low">Low</option>
        <option value="Medium">Medium</option>
        <option value="High">High</option>
        <option value="Critical">Critical</option>
      </select>
    </div>

    <!-- Location Fields (Block and Room) -->
    <div class="grid-2">
      <div class="form-group">
        <label for="hostel_block" class="form-label">Hostel Block</label>
        <select
          id="hostel_block"
          v-model="form.hostel_block"
          class="form-select"
          :class="{ 'input-error': errors.hostel_block }"
        >
          <option value="" disabled>Select block</option>
          <option value="A">Block A</option>
          <option value="B">Block B</option>
          <option value="C">Block C</option>
        </select>
        <span v-if="errors.hostel_block" class="form-error">{{ errors.hostel_block }}</span>
      </div>

      <div class="form-group">
        <label for="room_number" class="form-label">Room Number</label>
        <input
          type="text"
          id="room_number"
          v-model="form.room_number"
          placeholder="e.g. 205 or A-205"
          class="form-input"
          :class="{ 'input-error': errors.room_number }"
        />
        <span v-if="errors.room_number" class="form-error">{{ errors.room_number }}</span>
      </div>
    </div>

    <!-- Description Field -->
    <div class="form-group">
      <label for="description" class="form-label">Detail Description</label>
      <textarea
        id="description"
        v-model="form.description"
        placeholder="Describe the issue in detail (minimum 10 characters)..."
        rows="4"
        class="form-input form-textarea"
        :class="{ 'input-error': errors.description }"
      ></textarea>
      <span v-if="errors.description" class="form-error">{{ errors.description }}</span>
    </div>

    <!-- Buttons -->
    <div class="form-actions">
      <button type="submit" class="neo-btn neo-btn-yellow">
        Submit Report
      </button>
      <button type="button" @click="$emit('cancel')" class="neo-btn neo-btn-white">
        Cancel
      </button>
    </div>
  </form>
</template>

<script setup>
import { reactive, onMounted } from 'vue'

const emit = defineEmits(['submit', 'cancel'])

const props = defineProps({
  userProfile: {
    type: Object,
    default: () => ({})
  }
})

const categories = [
  'Electrical',
  'Plumbing',
  'Furniture',
  'Internet',
  'Air Conditioning',
  'Cleaning',
  'Other'
]

const form = reactive({
  title: '',
  category: '',
  priority: 'Medium',
  hostel_block: '',
  room_number: '',
  description: ''
})

const errors = reactive({
  title: '',
  category: '',
  hostel_block: '',
  room_number: '',
  description: ''
})

onMounted(() => {
  if (props.userProfile) {
    const block = props.userProfile.hostelBlock || ''
    // Extract block letter (A, B, C) if in format "Block A"
    const matchedBlock = block.match(/[A-C]/i)
    form.hostel_block = matchedBlock ? matchedBlock[0].toUpperCase() : ''
    form.room_number = props.userProfile.roomNumber || ''
  }
})

const validateForm = () => {
  let isValid = true
  
  // Title
  if (!form.title.trim()) {
    errors.title = 'Title is required.'
    isValid = false
  } else if (form.title.trim().length < 5) {
    errors.title = 'Title must be at least 5 characters.'
    isValid = false
  } else {
    errors.title = ''
  }

  // Category
  if (!form.category) {
    errors.category = 'Please select a category.'
    isValid = false
  } else {
    errors.category = ''
  }

  // Hostel Block
  if (!form.hostel_block) {
    errors.hostel_block = 'Block selection is required.'
    isValid = false
  } else {
    errors.hostel_block = ''
  }

  // Room Number
  if (!form.room_number.trim()) {
    errors.room_number = 'Room number is required.'
    isValid = false
  } else {
    errors.room_number = ''
  }

  // Description
  if (!form.description.trim()) {
    errors.description = 'Description is required.'
    isValid = false
  } else if (form.description.trim().length < 10) {
    errors.description = 'Description must be at least 10 characters.'
    isValid = false
  } else {
    errors.description = ''
  }

  return isValid
}

const handleSubmit = () => {
  if (validateForm()) {
    emit('submit', { ...form })
  }
}
</script>

<style scoped>
.maintenance-form {
  background-color: #FFFFFF;
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  padding: 24px;
}

.form-title {
  font-size: 24px;
  margin-bottom: 20px;
  border-bottom: 3px solid #000000;
  padding-bottom: 12px;
  font-weight: 700;
}

.form-textarea {
  resize: vertical;
}

.input-error {
  border-color: var(--status-rejected) !important;
  background-color: #FFF5F5;
}

.form-actions {
  display: flex;
  gap: 16px;
  margin-top: 24px;
}
</style>

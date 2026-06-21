<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const name = ref('')
const matrixNumber = ref('')
const email = ref('')
const password = ref('')
const phone = ref('')
const hostelBlock = ref('')
const roomNumber = ref('')
const role = ref('student') // Default role

const loading = ref(false)
const error = ref('')

const register = async () => {
  loading.value = true
  error.value = ''

  try {
    if (!name.value || !email.value || !password.value || !phone.value || !role.value) {
      throw new Error('Please fill in all required fields')
    }

    if (role.value === 'student' && (!matrixNumber.value || !hostelBlock.value || !roomNumber.value)) {
      throw new Error('Please fill in all student details (Matric Number, Hostel Block, Room Number)')
    }

    // create user object (MATCH your required format)
    const newUser = {
      name: name.value,
      email: email.value,
      password: password.value,
      phone: phone.value,
      role: role.value,
      ...(role.value === 'student' ? {
        matrixNumber: matrixNumber.value,
        hostelBlock: hostelBlock.value,
        roomNumber: roomNumber.value
      } : {
        matrixNumber: '',
        hostelBlock: '',
        roomNumber: ''
      })
    }

    await fetch('http://localhost:8000/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(newUser)
    })

    router.push('/login')
  } catch (err) {
    error.value = err.message || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="card">

      <!-- ICON -->
      <div class="icon-box">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
          <path
            d="M21.3333 28V25.3333C21.3333 23.9188 20.7714 22.5623 19.7712 21.5621C18.771 20.5619 17.4145 20 16 20H8C6.5855 20 5.22895 20.5619 4.22875 21.5621C3.22856 22.5623 2.66666 23.9188 2.66666 25.3333V28"
            stroke="black"
            stroke-width="2.6"
            stroke-linecap="round"
          />
          <path
            d="M12 14.6667C14.9455 14.6667 17.3333 12.2789 17.3333 9.33333C17.3333 6.38781 14.9455 4 12 4C9.05447 4 6.66666 6.38781 6.66666 9.33333C6.66666 12.2789 9.05447 14.6667 12 14.6667Z"
            stroke="black"
            stroke-width="2.6"
          />
        </svg>
      </div>

      <h1>Create Account</h1>
      <p class="subtitle">Register for hostel management</p>

      <form @submit.prevent="register" class="form">

        <label>Account Role</label>
        <select v-model="role" class="role-select">
          <option value="student">Student</option>
          <option value="staff/admin">Staff / Admin</option>
        </select>

        <label>Full Name</label>
        <input v-model="name" placeholder="Full Name" />

        <div v-if="role === 'student'" class="student-fields">
          <label>Matric Number</label>
          <input v-model="matrixNumber" placeholder="Matric Number" />
        </div>

        <label>Email Address</label>
        <input v-model="email" type="email" placeholder="Email Address" />

        <label>Password</label>
        <input v-model="password" type="password" placeholder="Password" />

        <div v-if="role === 'student'" class="student-fields">
          <label>Hostel Block</label>
          <select v-model="hostelBlock">
            <option disabled value="">Select Hostel Block</option>
            <option>Block A</option>
            <option>Block B</option>
            <option>Block C</option>
          </select>

          <label style="margin-top: 12px;">Room Number</label>
          <input v-model="roomNumber" placeholder="Room Number (e.g. A-215)" />
        </div>

        <label>Phone Number</label>
        <input v-model="phone" placeholder="Phone Number" />

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Registering...' : 'Register' }}
        </button>

      </form>

      <p class="login">
        Already have an account?
        <RouterLink to="/login">Sign in here</RouterLink>
      </p>

    </div>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #FFC900;
  display: flex;
  justify-content: center;
  align-items: center;
}

.card {
  width: 548px;
  background: white;
  padding: 36px;
  border: 4px solid black;
  box-shadow: 6px 6px 0 black;
  margin-top: 50px;
  margin-bottom: 50px;
}

.icon-box {
  width: 64px;
  height: 64px;
  background: #4ECDC4;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0 auto 16px;
  border: 2px solid black;
}

label {
  display: block;
  font-weight: bold;
}

h1 {
  text-align: center;
  text-transform: uppercase;
}

.subtitle {
  text-align: center;
  color: #4A5565;
  margin-bottom: 20px;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

input, select {
  height: 50px;
  border: 3px solid black;
  padding: 0 12px;
  font-weight: 500;
}

button {
  height: 55px;
  background: #FF90E8;
  border: 3px solid black;
  font-weight: bold;
  cursor: pointer;
}

.error {
  color: red;
}

.login {
  margin-top: 16px;
  text-align: center;
}
</style>
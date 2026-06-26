<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import LoginHeader from '@/components/auth/LoginHeader.vue'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const login = async () => {
  loading.value = true
  error.value = ''

  try {
    const res = await fetch('http://localhost:8000/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: email.value,
        password: password.value
      })
    })

    const data = await res.json()

    if (!data.success) {
      throw new Error(data.message)
    }

    if (!data.token) {
      throw new Error('Login succeeded, but no JWT token was returned.')
    }

    localStorage.setItem('user', JSON.stringify(data.user))
    localStorage.setItem('token', data.token)

    router.push('/dashboard')

  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">

      <!-- HEADER COMPONENT -->
      <LoginHeader />

      <form @submit.prevent="login">
        <div class="form-group">
          <label>Email Address</label>
          <input
            v-model="email"
            type="email"
            placeholder="Enter email"
          />
        </div>

        <div class="form-group password-group">
          <div class="label-row">
            <span class="label-text">Password</span>

            <RouterLink class="forgot-password" to="/forgot-password">
              Forgot Password?
            </RouterLink>
          </div>

          <input
            v-model="password"
            type="password"
            placeholder="Enter password"
          />
        </div>

        <p v-if="error" class="error">
          {{ error }}
        </p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Signing In...' : 'Sign In' }}
        </button>
      </form>

      <div class="auth-footer">
        <span>Don't have an account?</span>
        <RouterLink to="/register">Register here</RouterLink>
      </div>

    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #ffc900;
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-card {
  width: 450px;
  background: white;
  padding: 36px;
  border: 4px solid black;
  box-shadow: 6px 6px 0 black;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  font-weight: bold;
  margin-bottom: 8px;
}

input {
  width: 100%;
  height: 50px;
  padding: 0 12px;
  border: 3px solid black;
  box-sizing: border-box;
}

.password-group {
  display: flex;
  flex-direction: column;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 8px;
}

.label-text {
  font-weight: bold;
  color: black;
}

.forgot-password {
  font-size: 14px;
  font-weight: 700;
  color: black;
  text-decoration: none;
}

.forgot-password:hover {
  text-decoration: underline;
}

button {
  width: 100%;
  height: 55px;
  border: 3px solid black;
  background: #ff90e8;
  font-weight: bold;
  cursor: pointer;
  box-shadow: 4px 4px 0 black;
}

.error {
  color: red;
  margin-bottom: 15px;
}

.auth-footer {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
  color: #4a5565;
}

.auth-footer a {
  font-weight: 700;
  color: black;
  text-decoration: none;
  margin-left: 5px;
}

.auth-footer a:hover {
  text-decoration: underline;
}
</style>

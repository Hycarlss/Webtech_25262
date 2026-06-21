<template>
  <AppHeader v-if="!$route.meta.hideHeader" />

  <div :class="{ 'page-container': !$route.meta.hideHeader }">
    <router-view />
  </div>
</template>

<script>
import AppHeader from '@/components/AppHeader.vue'

export default {
  name: 'App',

  components: {
    AppHeader
  },

  data () {
    return {
      email: '',
      password: '',
      message: ''
    }
  },

  methods: {
    login () {
      fetch('http://localhost:8000/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: this.email,
          password: this.password
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            this.message = 'Login successful'
            console.log(data.user)
          } else {
            this.message = data.message || 'Login failed'
          }
        })
        .catch(err => {
          console.error(err)
          this.message = 'Server error'
        })
    }
  }
}
</script>

<style>
/* Global CSS transitions or customizations if needed */
</style>
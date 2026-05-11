<template>
  <AppHeader />

  <div class="layout">
    <AppSidebar />

    <div class="main-content">
      <router-view
        :persons="persons"
        :last-person="lastPerson"
        @add-person="addPerson"
      />
    </div>
  </div>

  <AppFooter />
</template>

<script>
import AppHeader from './components/AppHeader.vue'
import AppSidebar from './components/AppSidebar.vue'
import AppFooter from './components/AppFooter.vue'

export default {
  name: 'App',

  components: {
    AppHeader,
    AppSidebar,
    AppFooter
  },

  data () {
    return {
      persons: []
    }
  },

  computed: {
    lastPerson () {
      if (this.persons.length === 0) {
        return null
      }

      return this.persons[this.persons.length - 1]
    }
  },

  methods: {
    addPerson (person) {
      this.persons.push(person)
    }
  }
}
</script>
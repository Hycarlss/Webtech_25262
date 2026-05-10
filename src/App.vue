

<template>
  <DummyVue />
  <AppHeader />
<!-- <AppSidebar /> -->
  <div class="layout">
    <AppSidebar />

    <div class="main-content">
      <BmiForm @add-person="addPerson" />
      <LastPerson :person="lastPerson" />
      <PersonList :persons="persons" />
    </div>
  </div>

  <AppFooter />
</template>

<script>
import DummyVue from './components/DummyVue.vue'
import AppHeader from './components/AppHeader.vue'
import AppSidebar from './components/AppSidebar.vue'
import BmiForm from './components/BmiForm.vue'
import LastPerson from './components/LastPerson.vue'
import PersonList from './components/PersonList.vue'
import AppFooter from './components/AppFooter.vue'

export default {
  name: 'App',

  components: {
    DummyVue,
    AppHeader,
    AppSidebar,
    BmiForm,
    LastPerson,
    PersonList,
    AppFooter
  },

  data() {
    return {
      persons: []
    }
  },

  computed: {
    lastPerson() {
      if (this.persons.length === 0) {
        return null
      }

      return this.persons[this.persons.length - 1]
    }
  },

  methods: {
    addPerson(person) {
      this.persons.push(person)
    }
  }
}
</script>
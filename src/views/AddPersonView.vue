<template>
  <div>
    <h2>BMI Calculator</h2>

    <PersonForm @add-person="$emit('add-person', $event)" />
  </div>
</template>

<script>
import PersonForm from '../components/PersonForm.vue'

export default {
  name: 'AddPersonView',

  components: {
    PersonForm
  },

  emits: ['add-person']
}
</script>
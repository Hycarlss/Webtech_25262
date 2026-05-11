<template>
  <div>
    <h2>Last Added Person</h2>

    <PersonCard
      v-if="lastPerson"
      :person="lastPerson"
    />

    <EmptyState
      v-else
      message="No person added yet."
    />
  </div>
</template>

<script>
import PersonCard from '../components/PersonCard.vue'
import EmptyState from '../components/EmptyState.vue'

export default {
  name: 'LastPersonView',

  components: {
    PersonCard,
    EmptyState
  },

  props: {
    lastPerson: {
      type: Object,
      default: null
    }
  }
}
</script>
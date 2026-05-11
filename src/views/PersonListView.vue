<template>
  <div>
    <h2>Person List</h2>

    <ul v-if="persons.length > 0">
      <li v-for="(p, index) in persons" :key="index">
        <PersonCard :person="p" />
      </li>
    </ul>

    <EmptyState
      v-else
      message="No person in the list yet."
    />
  </div>
</template>

<script>
import PersonCard from '../components/PersonCard.vue'
import EmptyState from '../components/EmptyState.vue'

export default {
  name: 'PersonListView',

  components: {
    PersonCard,
    EmptyState
  },

  props: {
    persons: {
      type: Array,
      required: true
    }
  }
}
</script>
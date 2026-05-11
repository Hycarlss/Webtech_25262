<template>
  <li v-if="listMode">
    Name: {{ person.name }},
    Age: {{ person.age }},
    Weight: {{ person.weight }} kg,
    Height: {{ person.height }} cm,
    BMI: {{ person.bmi }}
  </li>

  <div v-else class="single">
    Name: {{ person.name }},
    Age: {{ person.age }},
    Weight: {{ person.weight }} kg,
    Height: {{ person.height }} cm,
    BMI: {{ person.bmi }}
  </div>
</template>

<script>
export default {
  name: 'PersonCard',

  props: {
    person: {
      type: Object,
      required: true
    },

    listMode: {
      type: Boolean,
      default: false
    }
  }
}
</script>
<template>
  <div>
    <h2>Person List</h2>

    <ul v-if="persons.length > 0">
      <li v-for="(p, index) in persons" :key="index">
        Name: {{ p.name }},
        Age: {{ p.age }},
        Weight: {{ p.weight }} kg,
        Height: {{ p.height }} cm,
        BMI: {{ p.bmi }}
      </li>
    </ul>

    <div class="single" v-else>
      No person in the list yet.
    </div>
  </div>
</template>

<script>
export default {
  name: 'PersonList',

  props: {
    persons: {
      type: Array,
      required: true
    }
  }
}
</script>
<template>
  <div>
    <h2>Last Added Person</h2>

    <div class="single" v-if="person">
      Name: {{ person.name }},
      Age: {{ person.age }},
      Weight: {{ person.weight }} kg,
      Height: {{ person.height }} cm,
      BMI: {{ person.bmi }}
    </div>

    <div class="single" v-else>
      No person added yet.
    </div>
  </div>
</template>

<script>
export default {
  name: 'LastPerson',

  props: {
    person: Object
  }
}
</script>
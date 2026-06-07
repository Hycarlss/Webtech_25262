<template>
  <div class="single">
    {{ message }}
  </div>
</template>

<script>
export default {
  name: 'EmptyState',

  props: {
    message: {
      type: String,
      required: true
    }
  }
}
</script>
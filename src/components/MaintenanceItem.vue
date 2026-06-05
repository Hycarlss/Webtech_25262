<template>
  <span :class="badgeClass">
    {{ status }}
  </span>
</template>

<script>
export default {
  props: {
    status: String
  },

  computed: {
    badgeClass() {
      return {
        pending: this.status === "Pending",
        progress: this.status === "In Progress",
        resolved: this.status === "Resolved"
      };
    }
  }
}
</script>
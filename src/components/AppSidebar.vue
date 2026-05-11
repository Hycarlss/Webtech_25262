<template>
  <div class="sidebar">
    <h3>Menu</h3>

    <ul class="side-menu">
      <li>
        <router-link to="/" class="menu-link">
          Add Person
        </router-link>
      </li>

      <li>
        <router-link to="/last" class="menu-link">
          Last Added
        </router-link>
      </li>

      <li>
        <router-link to="/list" class="menu-link">
          Person List
        </router-link>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'AppSidebar'
}
</script>
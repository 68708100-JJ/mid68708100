<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <router-link class="navbar-brand fw-bold" to="/">Student888</router-link>
      <button
        class="navbar-toggler"
        type="button"
        :aria-expanded="isMenuOpen"
        aria-controls="navbarNav"
        aria-label="เปิดหรือปิดเมนูนำทาง"
        @click="toggleMenu"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="navbarNav" class="collapse navbar-collapse" :class="{ show: isMenuOpen }">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <router-link class="nav-link" to="/" @click="closeMenu">รายชื่อนักศึกษา</router-link>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: "Navbar",
  data() {
    return {
      isMenuOpen: false
    };
  },
  methods: {
    toggleMenu() {
      this.isMenuOpen = !this.isMenuOpen;
    },
    closeMenu() {
      this.isMenuOpen = false;
    }
  }
}
</script>

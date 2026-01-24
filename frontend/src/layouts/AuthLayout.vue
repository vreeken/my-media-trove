<script setup lang="ts">
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8 bg-gradient-to-br from-dark-900 via-dark-800 to-dark-900">
    <!-- Logo -->
    <div class="mb-8 text-center">
      <div class="inline-flex items-center justify-center w-64 h-64 mb-3">
        <img src="/assets/my_media_trove_logo_with_text.png" alt="My Media Trove Logo" class="w-64 h-64" />
      </div>
      <p class="text-dark-400 mt-1">Your personal media collection</p>
    </div>

    <!-- Content -->
    <div class="w-full max-w-md">
      <slot />
    </div>
  </div>
</template>

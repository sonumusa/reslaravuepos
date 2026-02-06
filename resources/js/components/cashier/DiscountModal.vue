<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 text-center">
      <h2 class="text-xl font-bold mb-4">Discount Modal</h2>
      <p class="text-gray-500 mb-6">This feature is coming soon.</p>
      <button @click="$emit('close')" class="btn-primary w-full">Close</button>
    </div>
  </div>
</template>

<script setup>
defineEmits(['close', 'apply'])
</script>
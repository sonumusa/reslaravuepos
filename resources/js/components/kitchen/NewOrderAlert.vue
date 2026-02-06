<template>
  <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-50">
    <div class="bg-amber-500 rounded-2xl p-8 text-center max-w-md animate-bounce-slow">
      <i class="fas fa-bell w-24 h-24 mx-auto mb-4 text-white text-6xl animate-ring block"></i>
      <h2 class="text-4xl font-bold text-white mb-2">NEW ORDER!</h2>
      <p class="text-2xl text-amber-100 mb-4">#{{ order?.order_number }}</p>
      <p class="text-xl text-white mb-6">{{ order?.items?.length }} items</p>
      <button 
        @click="$emit('dismiss')" 
        class="px-8 py-4 bg-white text-amber-600 rounded-xl font-bold text-xl hover:bg-gray-100 transition"
      >
        ACKNOWLEDGE
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  order: { type: Object, default: null }
})

defineEmits(['dismiss'])
</script>

<style scoped>
.animate-bounce-slow {
  animation: bounce 1s ease-in-out infinite;
}

.animate-ring {
  animation: ring 0.5s ease-in-out infinite;
}

@keyframes ring {
  0%, 100% { transform: rotate(-10deg); }
  50% { transform: rotate(10deg); }
}
</style>
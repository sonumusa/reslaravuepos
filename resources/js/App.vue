<template>
  <router-view></router-view>
  <Toast />
  <PwaInstallPrompt />
  <OfflineIndicator />
  <UpdatePrompt />
</template>

<script setup>
import PwaInstallPrompt from '@/components/pwa/PwaInstallPrompt.vue'
import OfflineIndicator from '@/components/pwa/OfflineIndicator.vue'
import UpdatePrompt from '@/components/pwa/UpdatePrompt.vue'
import Toast from '@/components/common/Toast.vue'
</script>

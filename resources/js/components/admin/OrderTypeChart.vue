<template>
  <div class="h-64 flex items-center justify-center">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: {
    type: Object,
    default: () => ({
      labels: ['Dine In', 'Takeaway', 'Delivery'],
      values: [0, 0, 0]
    })
  }
})

const chartCanvas = ref(null)
let chartInstance = null

function renderChart() {
  if (chartInstance) {
    chartInstance.destroy()
  }

  if (!chartCanvas.value) return

  chartInstance = new Chart(chartCanvas.value, {
    type: 'doughnut',
    data: {
      labels: props.data.labels || [],
      datasets: [{
        data: props.data.values || [],
        backgroundColor: [
          '#3B82F6', // blue - dine in
          '#F59E0B', // amber - takeaway
          '#8B5CF6'  // purple - delivery
        ],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  })
}

watch(() => props.data, renderChart, { deep: true })

onMounted(() => {
  renderChart()
})
</script>

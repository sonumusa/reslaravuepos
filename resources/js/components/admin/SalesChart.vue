<template>
  <div class="h-64">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: { type: Object, default: () => ({ labels: [], values: [] }) }
})

const chartCanvas = ref(null)
let chartInstance = null

function renderChart() {
  if (chartInstance) {
    chartInstance.destroy()
  }

  if (!chartCanvas.value) return

  chartInstance = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels: props.data.labels || [],
      datasets: [{
        label: 'Sales',
        data: props.data.values || [],
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => 'Rs ' + value.toLocaleString()
          }
        }
      }
    }
  })
}

onMounted(() => {
  renderChart()
})

watch(() => props.data, () => {
  renderChart()
}, { deep: true })
</script>

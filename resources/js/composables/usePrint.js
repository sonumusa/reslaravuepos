import { ref } from 'vue'
import printService from '@/services/printService'

export function usePrint() {
  const isPrinting = ref(false)
  const lastPrintError = ref(null)

  async function printReceipt(invoice, options = {}) {
    isPrinting.value = true
    lastPrintError.value = null

    try {
      await printService.print(invoice, options)
      return { success: true }
    } catch (error) {
      lastPrintError.value = error.message
      return { success: false, error: error.message }
    } finally {
      isPrinting.value = false
    }
  }

  async function printKitchen(order) {
    isPrinting.value = true
    lastPrintError.value = null

    try {
      await printService.printKitchen(order)
      return { success: true }
    } catch (error) {
      lastPrintError.value = error.message
      return { success: false, error: error.message }
    } finally {
      isPrinting.value = false
    }
  }

  function setPrinterType(type) {
    printService.setPrinterType(type)
  }

  return {
    isPrinting,
    lastPrintError,
    printReceipt,
    printKitchen,
    setPrinterType
  }
}

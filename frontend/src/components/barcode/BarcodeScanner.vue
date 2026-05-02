<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Quagga, { type QuaggaJSResultObject } from '@ericblade/quagga2'

const emit = defineEmits<{
  detected: [barcode: string, format: string]
  error: [error: Error]
  close: []
}>()

const scannerRef = ref<HTMLDivElement | null>(null)
const isInitialized = ref(false)
const error = ref<string | null>(null)
const lastDetectedCode = ref<string | null>(null)

// Debounce detection to avoid multiple rapid detections
let detectionTimeout: ReturnType<typeof setTimeout> | null = null

function initScanner() {
  if (!scannerRef.value) return

  Quagga.init(
    {
      inputStream: {
        type: 'LiveStream',
        target: scannerRef.value,
        constraints: {
          facingMode: 'environment', // Use back camera
          width: { min: 640, ideal: 1280, max: 1920 },
          height: { min: 480, ideal: 720, max: 1080 },
        },
      },
      locator: {
        patchSize: 'medium',
        halfSample: true,
      },
      numOfWorkers: navigator.hardwareConcurrency || 4,
      frequency: 10,
      decoder: {
        readers: [
          'ean_reader', // EAN-13
          'ean_8_reader', // EAN-8
          'upc_reader', // UPC-A
          'upc_e_reader', // UPC-E
        ],
      },
      locate: true,
    },
    (err) => {
      if (err) {
        console.error('Quagga init error:', err)
        error.value = 'Failed to access camera. Please ensure camera permissions are granted.'
        emit('error', new Error(error.value))
        return
      }

      isInitialized.value = true
      Quagga.start()
    }
  )

  Quagga.onDetected(handleDetection)
}

function handleDetection(result: QuaggaJSResultObject) {
  const code = result.codeResult.code
  const format = result.codeResult.format

  if (!code) return

  // Avoid duplicate detections
  if (code === lastDetectedCode.value) return

  // Debounce to ensure stable detection
  if (detectionTimeout) {
    clearTimeout(detectionTimeout)
  }

  detectionTimeout = setTimeout(() => {
    lastDetectedCode.value = code

    // Play a subtle haptic feedback if available
    if (navigator.vibrate) {
      navigator.vibrate(100)
    }

    // Stop scanning and emit the result
    stopScanner()
    emit('detected', code, format)
  }, 200)
}

function stopScanner() {
  if (isInitialized.value) {
    Quagga.stop()
    isInitialized.value = false
  }
}

function handleClose() {
  stopScanner()
  emit('close')
}

onMounted(() => {
  initScanner()
})

onUnmounted(() => {
  stopScanner()
  if (detectionTimeout) {
    clearTimeout(detectionTimeout)
  }
})
</script>

<template>
  <div class="fixed inset-0 z-50 bg-black">
    <!-- Header -->
    <div class="absolute top-0 left-0 right-0 z-10 flex items-center justify-between p-4 bg-gradient-to-b from-black/80 to-transparent">
      <h2 class="text-lg font-semibold text-white">Scan Barcode</h2>
      <button
        @click="handleClose"
        class="p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Scanner viewport -->
    <div ref="scannerRef" class="w-full h-full">
      <!-- Quagga will inject the video element here -->
    </div>

    <!-- Scanning overlay -->
    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
      <!-- Scan guide box -->
      <div class="relative w-72 h-40">
        <!-- Corner markers -->
        <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-primary-500 rounded-tl-lg"></div>
        <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-primary-500 rounded-tr-lg"></div>
        <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-primary-500 rounded-bl-lg"></div>
        <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-primary-500 rounded-br-lg"></div>

        <!-- Animated scan line -->
        <div class="absolute left-2 right-2 h-0.5 bg-primary-500 animate-scan-line"></div>
      </div>
    </div>

    <!-- Instructions -->
    <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent text-center">
      <p v-if="error" class="text-red-400 mb-2">{{ error }}</p>
      <p v-else class="text-white/80 text-sm">
        Point your camera at the barcode on the movie case
      </p>
    </div>
  </div>
</template>

<style scoped>
/* Make Quagga's video element fill the container */
:deep(video) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

:deep(canvas) {
  display: none;
}

/* Scanning line animation */
@keyframes scanLine {
  0%, 100% {
    top: 10%;
    opacity: 1;
  }
  50% {
    top: 90%;
    opacity: 0.5;
  }
}

.animate-scan-line {
  animation: scanLine 2s ease-in-out infinite;
}
</style>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

interface TabItem {
  id: string | number | null
  label: string
}

const props = defineProps<{
  items: TabItem[]
  modelValue?: string | number | null
  skeleton?: boolean
  skeletonCount?: number
}>()

const emit = defineEmits<{
  'update:modelValue': [id: string | number | null]
}>()

const trackRef = ref<HTMLElement | null>(null)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const updateScrollState = () => {
  if (!trackRef.value) return
  const { scrollLeft, scrollWidth, clientWidth } = trackRef.value
  canScrollLeft.value = scrollLeft > 0
  // Allow a 1px threshold for rounding errors
  canScrollRight.value = Math.ceil(scrollLeft + clientWidth) < scrollWidth
}

const scrollByAmount = (amount: number) => {
  if (!trackRef.value) return
  trackRef.value.scrollBy({
    left: amount,
    behavior: 'smooth'
  })
}

const handleScroll = () => {
  updateScrollState()
}

let resizeObserver: ResizeObserver | null = null

onMounted(async () => {
  if (trackRef.value) {
    trackRef.value.addEventListener('scroll', handleScroll, { passive: true })
    resizeObserver = new ResizeObserver(() => {
      updateScrollState()
    })
    resizeObserver.observe(trackRef.value)
    
    // Give DOM a tick to render items
    await nextTick()
    updateScrollState()
  }
})

onUnmounted(() => {
  if (trackRef.value) {
    trackRef.value.removeEventListener('scroll', handleScroll)
  }
  if (resizeObserver) {
    resizeObserver.disconnect()
  }
})

const onTabClick = (id: string | number | null, event: MouseEvent) => {
  emit('update:modelValue', id)
  
  // Center the clicked tab
  const target = event.currentTarget as HTMLElement
  if (trackRef.value && target) {
    const track = trackRef.value
    const tabRect = target.getBoundingClientRect()
    const trackRect = track.getBoundingClientRect()
    
    const tabCenter = tabRect.left + tabRect.width / 2
    const trackCenter = trackRect.left + trackRect.width / 2
    const scrollAmount = tabCenter - trackCenter
    
    track.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    })
  }
}
</script>

<template>
  <div class="scroll-tabs" :class="{ 'scroll-tabs--skeleton': skeleton }">
    <button 
      v-show="canScrollLeft && !skeleton" 
      class="scroll-tabs__arrow scroll-tabs__arrow--left"
      type="button"
      aria-label="Прокрутить влево"
      @click="scrollByAmount(-300)"
    >
      <ChevronLeft :size="20" />
    </button>

    <div ref="trackRef" class="scroll-tabs__track">
      <div v-if="skeleton" class="scroll-tabs__list" aria-hidden="true">
        <div 
          v-for="i in (skeletonCount || 6)" 
          :key="i"
          class="scroll-tabs__tab scroll-tabs__tab--skeleton skeleton"
        />
      </div>
      <div v-else class="scroll-tabs__list">
        <button
          v-for="item in items"
          :key="item.id ?? 'null'"
          type="button"
          class="scroll-tabs__tab"
          :class="{ 'scroll-tabs__tab--active': modelValue === item.id }"
          :aria-pressed="modelValue === item.id"
          @click="onTabClick(item.id, $event)"
        >
          {{ item.label }}
        </button>
      </div>
    </div>

    <button 
      v-show="canScrollRight && !skeleton" 
      class="scroll-tabs__arrow scroll-tabs__arrow--right"
      type="button"
      aria-label="Прокрутить вправо"
      @click="scrollByAmount(300)"
    >
      <ChevronRight :size="20" />
    </button>
  </div>
</template>

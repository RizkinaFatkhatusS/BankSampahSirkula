<script setup>
defineProps({ label: String, value: [String, Number], hint: String, icon: String, tone: { type: String, default: 'green' } })
const tones = { green: 'bg-green-100', amber: 'bg-amber-100', blue: 'bg-sky-100', rose: 'bg-rose-100' }
</script>
<template>
  <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-green-100">
    <div class="flex items-start justify-between">
      <p class="text-sm font-medium text-gray-500">{{ label }}</p>
      <span v-if="icon" class="flex h-9 w-9 items-center justify-center rounded-xl text-lg" :class="tones[tone]">{{ icon }}</span>
    </div>
    <p class="mt-2 text-2xl font-bold text-green-900">{{ value }}</p>
    <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
  </div>
</template>

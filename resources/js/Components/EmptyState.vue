<script setup>
defineProps({ icon: { type: String, default: '🌱' }, title: String, text: String })
</script>
<template>
  <div class="flex flex-col items-center rounded-2xl border border-dashed border-green-200 bg-white/60 px-6 py-10 text-center">
    <span class="text-4xl">{{ icon }}</span>
    <p class="mt-2 font-semibold text-green-900">{{ title }}</p>
    <p v-if="text" class="mt-1 max-w-sm text-sm text-gray-500">{{ text }}</p>
    <div class="mt-4"><slot /></div>
  </div>
</template>

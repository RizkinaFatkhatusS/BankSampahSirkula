<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ lebar: { type: Boolean, default: false } })
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-green-100 to-green-50 px-4 py-8">
    <Link href="/" class="mb-6 flex items-center gap-3">
      <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-700 text-2xl text-white">♻</span>
      <span><b class="block text-xl leading-tight text-green-900">SIRKULA</b><small class="text-gray-500">Bank Sampah Sirkular</small></span>
    </Link>
    <div class="w-full overflow-hidden rounded-2xl bg-white px-6 py-6 shadow-lg ring-1 ring-green-100" :class="lebar ? 'sm:max-w-3xl' : 'sm:max-w-md'">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ links: Array })
</script>
<template>
  <nav v-if="links && links.length > 3" class="flex flex-wrap justify-center gap-1 pt-3">
    <template v-for="(l, i) in links" :key="i">
      <span v-if="!l.url" class="rounded-lg px-3 py-1.5 text-sm text-gray-300" v-html="l.label" />
      <Link v-else :href="l.url" preserve-scroll class="rounded-lg px-3 py-1.5 text-sm" :class="l.active ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200 hover:bg-green-50'" v-html="l.label" />
    </template>
  </nav>
</template>

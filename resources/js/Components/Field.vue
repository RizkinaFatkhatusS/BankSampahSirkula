<script setup>
defineProps({ label: String, error: String, hint: String, required: Boolean })
</script>
<template>
  <div>
    <label v-if="label" class="mb-1 block text-sm font-medium text-gray-700">
      {{ label }}<span v-if="required" class="text-red-500"> *</span>
    </label>
    <slot />
    <p v-if="hint && !error" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>

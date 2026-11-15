<script setup>
import Modal from '@/Components/Modal.vue'
defineProps({ show: Boolean, title: String, text: String, confirmText: { type: String, default: 'Ya, lanjutkan' }, danger: Boolean, processing: Boolean })
defineEmits(['close', 'confirm'])
</script>
<template>
  <Modal :show="show" max-width="md" @close="$emit('close')">
    <div class="p-6">
      <h3 class="text-lg font-bold text-green-900">{{ title }}</h3>
      <p v-if="text" class="mt-2 text-sm text-gray-600">{{ text }}</p>
      <div class="mt-3"><slot /></div>
      <div class="mt-6 flex justify-end gap-2">
        <button type="button" class="btn btn-outline" @click="$emit('close')">Batal</button>
        <button type="button" class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="processing" @click="$emit('confirm')">{{ confirmText }}</button>
      </div>
    </div>
  </Modal>
</template>

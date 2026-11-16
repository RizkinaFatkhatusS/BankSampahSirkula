<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'

const props = defineProps({ item: Array })
const form = useForm(Object.fromEntries(props.item.map((i) => [i.kunci, i.nilai])))
</script>

<template>
  <AppLayout title="Pengaturan Sistem">
    <form class="mx-auto max-w-2xl space-y-4" @submit.prevent="form.patch(route('superadmin.pengaturan.update'), { preserveScroll: true })">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Parameter operasional. Perubahan berlaku untuk proses berikutnya dan tercatat pada log audit.</p>
      <div class="card space-y-4">
        <Field v-for="i in item" :key="i.kunci" :label="i.label" :error="form.errors[i.kunci]" :hint="`Bawaan: ${i.bawaan} ${i.tipe === 'int' ? i.satuan : ''}${i.min ? ' · minimal ' + i.min : ''}`">
          <div class="flex items-center gap-2"><input v-model="form[i.kunci]" :type="i.tipe === 'int' ? 'number' : 'text'" :min="i.min" class="input" required /><span v-if="i.tipe === 'int'" class="shrink-0 text-sm text-gray-500">{{ i.satuan }}</span></div>
        </Field>
      </div>
      <button class="btn btn-primary w-full py-3" :disabled="form.processing">Simpan pengaturan</button>
    </form>
  </AppLayout>
</template>

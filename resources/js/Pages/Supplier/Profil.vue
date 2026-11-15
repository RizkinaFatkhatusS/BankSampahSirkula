<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import MapView from '@/Components/MapView.vue'

const props = defineProps({ bank: Object })
const form = useForm({ _method: 'patch', nama: props.bank.nama, alamat: props.bank.alamat, latitude: props.bank.latitude, longitude: props.bank.longitude, nomor_kontak: props.bank.nomor_kontak, jam_operasional: props.bank.jam_operasional, foto: null })
const preview = ref(props.bank.foto)
function onFile(e) {
  const f = e.target.files[0]
  if (!f) return
  form.foto = f
  preview.value = URL.createObjectURL(f)
}
const simpan = () => form.post(route('supplier.profil.update'), { forceFormData: true, preserveScroll: true })
</script>

<template>
  <AppLayout title="Profil Bank Sampah">
    <form class="mx-auto grid max-w-4xl gap-5 lg:grid-cols-2" @submit.prevent="simpan">
      <div class="card space-y-4">
        <Field label="Nama bank sampah" :error="form.errors.nama" required><input v-model="form.nama" class="input" required /></Field>
        <Field label="Alamat" :error="form.errors.alamat" required><textarea v-model="form.alamat" rows="2" class="input" required /></Field>
        <div class="grid grid-cols-2 gap-3">
          <Field label="Latitude" :error="form.errors.latitude" required><input v-model="form.latitude" type="number" step="any" class="input" required /></Field>
          <Field label="Longitude" :error="form.errors.longitude" required><input v-model="form.longitude" type="number" step="any" class="input" required /></Field>
        </div>
        <Field label="Nomor kontak" :error="form.errors.nomor_kontak" required><input v-model="form.nomor_kontak" class="input" required /></Field>
        <Field label="Jam operasional" :error="form.errors.jam_operasional" required><input v-model="form.jam_operasional" class="input" placeholder="08.00 – 16.00 WIB" required /></Field>
      </div>
      <div class="space-y-5">
        <div class="card space-y-3">
          <h3 class="font-bold text-green-900">Foto bank sampah</h3>
          <img v-if="preview" :src="preview" alt="" class="h-44 w-full rounded-xl object-cover" />
          <input type="file" accept="image/jpeg,image/png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-green-100 file:px-3 file:py-2 file:font-semibold file:text-green-800" @change="onFile" />
          <p v-if="form.errors.foto" class="text-sm text-red-600">{{ form.errors.foto }}</p>
          <p class="text-xs text-gray-400">JPG/PNG, maksimal 2 MB.</p>
        </div>
        <div class="card space-y-2">
          <h3 class="font-bold text-green-900">Letak di peta</h3>
          <MapView :markers="[{ id: 1, nama: form.nama, latitude: form.latitude, longitude: form.longitude }]" height="200px" />
          <p class="text-xs text-gray-400">Jarak ke Customer dihitung dari koordinat ini.</p>
        </div>
        <button class="btn btn-primary w-full" :disabled="form.processing">Simpan profil</button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'

const props = defineProps({ role: Object, katalog: Object })
const ubah = !!props.role
const form = useForm({ nama: props.role?.nama ?? '', deskripsi: props.role?.deskripsi ?? '', aktif: props.role?.aktif ?? true, izin: props.role ? [...props.role.izin] : [] })
const semua = computed(() => Object.values(props.katalog).flat())
const sensitifDipilih = computed(() => semua.value.filter((p) => p.jenis === 'sensitif' && form.izin.includes(p.kode)).length)

function toggle(kode) { const i = form.izin.indexOf(kode); i >= 0 ? form.izin.splice(i, 1) : form.izin.push(kode) }
const penuh = (g) => props.katalog[g].every((p) => form.izin.includes(p.kode))
function toggleGrup(g) {
  const kode = props.katalog[g].map((p) => p.kode)
  form.izin = penuh(g) ? form.izin.filter((k) => !kode.includes(k)) : [...new Set([...form.izin, ...kode])]
}
const simpan = () => (ubah ? form.patch(route('superadmin.role.update', props.role.id)) : form.post(route('superadmin.role.store')))
</script>

<template>
  <AppLayout :title="ubah ? 'Ubah Role' : 'Buat Role'">
    <form class="mx-auto max-w-3xl space-y-5" @submit.prevent="simpan">
      <Link :href="route('superadmin.role.index')" class="text-sm font-semibold text-green-700 hover:underline">← Daftar role</Link>
      <div class="card space-y-4">
        <Field label="Nama role" :error="form.errors.nama" required><input v-model="form.nama" class="input" required /></Field>
        <Field label="Deskripsi" :error="form.errors.deskripsi"><textarea v-model="form.deskripsi" rows="2" class="input" /></Field>
        <label class="flex items-center gap-2 text-sm"><input v-model="form.aktif" type="checkbox" class="rounded border-green-300 text-green-700 focus:ring-green-600" /> Aktif (role nonaktif tidak memberi hak akses)</label>
        <p v-if="ubah && role.admin.length" class="rounded-lg bg-blue-50 p-2 text-xs text-blue-900">Dipegang: {{ role.admin.join(', ') }}. Perubahan berlaku segera dan mereka diberi tahu.</p>
      </div>

      <div class="card space-y-5">
        <div><h3 class="font-bold text-green-900">Hak akses</h3><p class="text-xs text-gray-500">Centang tugas yang boleh dilakukan role ini. Hak akses khusus Super Admin tidak dapat didelegasikan.</p></div>
        <p v-if="sensitifDipilih" class="rounded-lg bg-amber-50 p-2 text-xs text-amber-900">⚠ {{ sensitifDipilih }} hak akses sensitif dipilih. Admin pemegangnya wajib memakai autentikasi dua langkah.</p>
        <section v-for="(daftar, grup) in katalog" :key="grup" class="space-y-2">
          <div class="flex items-center justify-between"><h4 class="text-sm font-bold uppercase tracking-wide text-gray-500">{{ grup }}</h4><button type="button" class="text-xs font-semibold text-green-700 hover:underline" @click="toggleGrup(grup)">{{ penuh(grup) ? 'Kosongkan' : 'Pilih semua' }}</button></div>
          <label v-for="p in daftar" :key="p.kode" class="flex items-start gap-3 rounded-xl border p-3 text-sm" :class="form.izin.includes(p.kode) ? 'border-green-500 bg-green-50' : 'border-green-100'">
            <input type="checkbox" class="mt-1 rounded border-green-300 text-green-700 focus:ring-green-600" :checked="form.izin.includes(p.kode)" @change="toggle(p.kode)" />
            <span><span class="font-medium">{{ p.deskripsi }}</span> <span v-if="p.jenis === 'sensitif'" class="rounded bg-amber-100 px-1.5 text-xs font-semibold text-amber-800">sensitif</span><br><code class="text-xs text-gray-400">{{ p.kode }}</code></span>
          </label>
        </section>
      </div>
      <button class="btn btn-primary w-full py-3" :disabled="form.processing">{{ ubah ? 'Simpan role' : 'Buat role' }}</button>
    </form>
  </AppLayout>
</template>

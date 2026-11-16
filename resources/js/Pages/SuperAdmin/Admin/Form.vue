<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'

const props = defineProps({ admin: Object, roles: Array })
const ubah = !!props.admin
const form = useForm({
  nama: props.admin?.nama ?? '', email: props.admin?.email ?? '', nomor_hp: props.admin?.nomor_hp ?? '',
  jenis: props.admin?.jenis ?? 'admin', role_ids: props.admin?.role_ids ? [...props.admin.role_ids] : [], konfirmasi_sandi: '',
})
const perluSandi = computed(() => form.jenis === 'super_admin' || props.admin?.jenis === 'super_admin')
const simpan = () => (ubah ? form.patch(route('superadmin.admin.update', props.admin.id)) : form.post(route('superadmin.admin.store')))
function toggle(id) { const i = form.role_ids.indexOf(id); i >= 0 ? form.role_ids.splice(i, 1) : form.role_ids.push(id) }
</script>

<template>
  <AppLayout :title="ubah ? 'Ubah Akun Admin' : 'Tambah Akun Admin'">
    <form class="mx-auto max-w-2xl space-y-5" @submit.prevent="simpan">
      <Link :href="route('superadmin.admin.index')" class="text-sm font-semibold text-green-700 hover:underline">← Daftar Admin</Link>
      <div class="card space-y-4">
        <Field label="Nama" :error="form.errors.nama" required><input v-model="form.nama" class="input" required /></Field>
        <Field label="Email" :error="form.errors.email" :hint="ubah ? 'Email tidak dapat diubah.' : 'Undangan untuk menetapkan kata sandi dikirim ke email ini.'" required><input v-model="form.email" type="email" class="input" :disabled="ubah" required /></Field>
        <Field label="Nomor HP" :error="form.errors.nomor_hp" required><input v-model="form.nomor_hp" class="input" inputmode="tel" required /></Field>
        <Field label="Jenis akun" :error="form.errors.jenis">
          <select v-model="form.jenis" class="input"><option value="admin">Admin (hak akses mengikuti role)</option><option value="super_admin">Super Admin (seluruh hak akses)</option></select>
        </Field>
      </div>

      <div v-if="form.jenis === 'admin'" class="card space-y-3">
        <div><h3 class="font-bold text-green-900">Role tugas</h3><p class="text-xs text-gray-500">Satu Admin dapat memegang beberapa role; hak akses digabungkan. Perubahan berlaku segera.</p></div>
        <p v-if="!roles.length" class="text-sm text-gray-400">Belum ada role. Buat dulu di Manajemen Role.</p>
        <label v-for="r in roles" :key="r.id" class="flex items-start gap-3 rounded-xl border p-3" :class="form.role_ids.includes(r.id) ? 'border-green-500 bg-green-50' : 'border-green-100'">
          <input type="checkbox" class="mt-1 rounded border-green-300 text-green-700 focus:ring-green-600" :checked="form.role_ids.includes(r.id)" @change="toggle(r.id)" />
          <span><b>{{ r.nama }}</b> <span v-if="!r.aktif" class="rounded bg-gray-200 px-1.5 text-xs">nonaktif</span><br><span class="text-xs text-gray-500">{{ r.deskripsi }}</span></span>
        </label>
        <p v-if="form.errors.role_ids" class="text-sm text-red-600">{{ form.errors.role_ids }}</p>
      </div>

      <div v-if="perluSandi" class="card space-y-2">
        <Field label="Kata sandi Anda (konfirmasi ulang)" :error="form.errors.konfirmasi_sandi" hint="Diperlukan untuk membuat atau mengubah akun Super Admin." required><input v-model="form.konfirmasi_sandi" type="password" class="input" autocomplete="current-password" /></Field>
      </div>
      <p v-if="form.errors.status" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ form.errors.status }}</p>
      <button class="btn btn-primary w-full py-3" :disabled="form.processing">{{ ubah ? 'Simpan perubahan' : 'Buat akun dan kirim undangan' }}</button>
    </form>
  </AppLayout>
</template>

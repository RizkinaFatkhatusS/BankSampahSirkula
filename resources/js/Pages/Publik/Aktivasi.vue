<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Field from '@/Components/Field.vue'

const props = defineProps({ token: String, berlaku: Boolean, jenis: String, nama: String, alasan: String, bisaMinta: Boolean })
const form = useForm({ password: '', password_confirmation: '' })
const judul = { aktivasi_supplier: 'Aktivasi akun Supplier', undangan_admin: 'Aktivasi akun Admin', reset_password: 'Atur ulang kata sandi' }
</script>

<template>
  <GuestLayout>
    <Head :title="judul[jenis] ?? 'Tautan akun'" />
    <template v-if="berlaku">
      <h2 class="text-xl font-bold text-green-900">{{ judul[jenis] }}</h2>
      <p class="mb-5 text-sm text-gray-500">Halo {{ nama }}. Tetapkan kata sandi Anda sendiri. Tautan ini hanya dapat dipakai sekali. Kata sandi tidak diketahui siapa pun, termasuk Admin.</p>
      <form class="space-y-4" @submit.prevent="form.post(route('aktivasi.store', token))">
        <Field label="Kata sandi baru" :error="form.errors.password" hint="Minimal 8 karakter (Admin: 10 karakter dengan huruf besar, kecil, dan angka)" required><input v-model="form.password" type="password" class="input" autocomplete="new-password" required /></Field>
        <Field label="Ulangi kata sandi" required><input v-model="form.password_confirmation" type="password" class="input" autocomplete="new-password" required /></Field>
        <button class="btn btn-primary w-full" :disabled="form.processing">Simpan kata sandi</button>
      </form>
    </template>
    <template v-else>
      <h2 class="text-xl font-bold text-green-900">Tautan tidak dapat dipakai</h2>
      <p class="mt-2 text-sm text-gray-600">{{ alasan }}</p>
      <p class="mt-3 text-sm text-gray-500">Calon Supplier dapat meminta tautan baru lewat halaman <Link :href="route('pengajuan.cek')" class="font-semibold text-green-700 hover:underline">Cek Status Pengajuan</Link>. Pengguna lain dapat meminta tautan reset melalui <Link :href="route('password.request')" class="font-semibold text-green-700 hover:underline">Lupa kata sandi</Link> atau menghubungi Admin.</p>
      <Link :href="route('login')" class="btn btn-primary mt-5 w-full">Ke halaman masuk</Link>
    </template>
  </GuestLayout>
</template>

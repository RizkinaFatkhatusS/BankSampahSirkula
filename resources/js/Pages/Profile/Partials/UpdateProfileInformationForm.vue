<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import Field from '@/Components/Field.vue'

defineProps({ mustVerifyEmail: Boolean, status: String })

const user = usePage().props.auth.user
const form = useForm({ nama: user.nama, email: user.email, nomor_hp: user.nomor_hp })
// Email Supplier/Admin dan nama Supplier dikunci: perubahannya melalui verifikasi ulang Admin
const kunciEmail = user.role !== 'customer'
const kunciNama = user.role === 'supplier'
</script>

<template>
  <section>
    <h2 class="text-lg font-bold text-green-900">Informasi profil</h2>
    <p class="mb-4 mt-1 text-sm text-gray-500">Perbarui nama, email, dan nomor HP akun Anda.</p>
    <form class="space-y-4" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
      <Field label="Nama" :error="form.errors.nama" :hint="kunciNama ? 'Pergantian penanggung jawab hanya setelah verifikasi ulang oleh Admin.' : ''"><input v-model="form.nama" class="input" :class="{ 'bg-gray-50': kunciNama }" :readonly="kunciNama" required autocomplete="name" /></Field>
      <Field label="Email" :error="form.errors.email" :hint="kunciEmail ? 'Email akun hanya dapat diubah setelah verifikasi ulang oleh Admin.' : ''"><input v-model="form.email" type="email" class="input" :class="{ 'bg-gray-50': kunciEmail }" :readonly="kunciEmail" required autocomplete="username" /></Field>
      <Field label="Nomor HP" :error="form.errors.nomor_hp"><input v-model="form.nomor_hp" class="input" required inputmode="tel" /></Field>
      <button class="btn btn-primary" :disabled="form.processing">Simpan</button>
    </form>
  </section>
</template>

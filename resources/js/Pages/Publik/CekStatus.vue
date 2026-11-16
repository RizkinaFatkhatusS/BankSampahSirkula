<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Field from '@/Components/Field.vue'

const form = useForm({ kode: '', email: '' })
</script>

<template>
  <GuestLayout>
    <Head title="Cek Status Pengajuan" />
    <h2 class="text-xl font-bold text-green-900">Cek Status Pengajuan</h2>
    <p class="mb-5 text-sm text-gray-500">Masukkan kode pengajuan dan email yang Anda pakai saat mendaftar. Anda juga bisa memakai tautan pribadi dari email konfirmasi.</p>
    <form class="space-y-4" @submit.prevent="form.post(route('pengajuan.cari'))">
      <Field label="Kode pengajuan" :error="form.errors.kode" required><input v-model="form.kode" class="input uppercase" placeholder="SRK-XXXXXXXX" required autofocus /></Field>
      <Field label="Email" :error="form.errors.email" required><input v-model="form.email" type="email" class="input" required /></Field>
      <button class="btn btn-primary w-full" :disabled="form.processing">Cek status</button>
    </form>
    <p class="mt-5 text-center text-sm text-gray-500"><Link :href="route('pengajuan.daftar')" class="font-semibold text-green-700 hover:underline">Daftar sebagai Supplier</Link> · <Link :href="route('login')" class="font-semibold text-green-700 hover:underline">Masuk</Link></p>
  </GuestLayout>
</template>

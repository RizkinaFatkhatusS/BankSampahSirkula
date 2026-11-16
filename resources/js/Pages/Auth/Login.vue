<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Field from '@/Components/Field.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({ canResetPassword: Boolean, status: String })

const form = useForm({ email: '', password: '', remember: false })
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') })
</script>

<template>
  <GuestLayout>
    <Head title="Masuk" />
    <h2 class="text-xl font-bold text-green-900">Masuk ke SIRKULA</h2>
    <p class="mb-5 text-sm text-gray-500">Customer, Supplier, dan Admin masuk lewat halaman yang sama.</p>

    <div v-if="status" class="mb-4 rounded-xl bg-green-50 p-3 text-sm font-medium text-green-700">{{ status }}</div>

    <form class="space-y-4" @submit.prevent="submit">
      <Field label="Email" :error="form.errors.email">
        <input v-model="form.email" type="email" class="input" required autofocus autocomplete="username" placeholder="nama@email.com" />
      </Field>
      <Field label="Kata sandi" :error="form.errors.password">
        <input v-model="form.password" type="password" class="input" required autocomplete="current-password" />
      </Field>
      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-gray-600">
          <input v-model="form.remember" type="checkbox" class="rounded border-green-300 text-green-700 focus:ring-green-600" /> Ingat saya
        </label>
        <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-medium text-green-700 hover:underline">Lupa kata sandi?</Link>
      </div>
      <button class="btn btn-primary w-full" :disabled="form.processing">Masuk</button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">Belum punya akun? <Link :href="route('register')" class="font-semibold text-green-700 hover:underline">Daftar sebagai Customer</Link></p>
    <p class="mt-2 text-center text-sm text-gray-500">Pengelola bank sampah? <Link :href="route('pengajuan.daftar')" class="font-semibold text-green-700 hover:underline">Daftar sebagai Supplier</Link> · <Link :href="route('pengajuan.cek')" class="font-semibold text-green-700 hover:underline">Cek status pengajuan</Link></p>
  </GuestLayout>
</template>

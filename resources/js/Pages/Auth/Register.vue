<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Field from '@/Components/Field.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const form = useForm({ nama: '', email: '', nomor_hp: '', password: '', password_confirmation: '' })
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') })
</script>

<template>
  <GuestLayout>
    <Head title="Daftar" />
    <h2 class="text-xl font-bold text-green-900">Daftar sebagai Customer</h2>
    <p class="mb-5 text-sm text-gray-500">Akun Supplier dibuat oleh pengelola bank sampah.</p>

    <form class="space-y-4" @submit.prevent="submit">
      <Field label="Nama lengkap" :error="form.errors.nama" required>
        <input v-model="form.nama" type="text" class="input" required autofocus autocomplete="name" />
      </Field>
      <Field label="Email" :error="form.errors.email" required>
        <input v-model="form.email" type="email" class="input" required autocomplete="username" />
      </Field>
      <Field label="Nomor HP" :error="form.errors.nomor_hp" hint="Contoh: 081234567890" required>
        <input v-model="form.nomor_hp" type="tel" inputmode="tel" class="input" required autocomplete="tel" />
      </Field>
      <Field label="Kata sandi" :error="form.errors.password" hint="Minimal 8 karakter" required>
        <input v-model="form.password" type="password" class="input" required autocomplete="new-password" />
      </Field>
      <Field label="Ulangi kata sandi" :error="form.errors.password_confirmation" required>
        <input v-model="form.password_confirmation" type="password" class="input" required autocomplete="new-password" />
      </Field>
      <button class="btn btn-primary w-full" :disabled="form.processing">Daftar</button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">Sudah punya akun? <Link :href="route('login')" class="font-semibold text-green-700 hover:underline">Masuk</Link></p>
  </GuestLayout>
</template>

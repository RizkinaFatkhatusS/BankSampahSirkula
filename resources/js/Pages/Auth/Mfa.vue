<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Field from '@/Components/Field.vue'
import QrKode from '@/Components/QrKode.vue'

defineProps({ setup: Boolean, nama: String, rahasia: String, uri: String })
const form = useForm({ kode: '' })
</script>

<template>
  <GuestLayout>
    <Head title="Autentikasi Dua Langkah" />
    <template v-if="setup">
      <h2 class="text-xl font-bold text-green-900">Aktifkan autentikasi dua langkah</h2>
      <p class="mb-4 text-sm text-gray-500">Halo {{ nama }}. Akun Anda wajib memakai kode dari aplikasi autentikator.</p>
      <ol class="mb-4 list-decimal space-y-1 pl-5 text-sm text-gray-600">
        <li>Pasang aplikasi autentikator (Google Authenticator, Authy, Microsoft Authenticator).</li>
        <li>Pindai kode QR di bawah, atau masukkan kunci secara manual.</li>
        <li>Masukkan kode 6 digit yang muncul.</li>
      </ol>
      <div class="flex flex-col items-center gap-2 rounded-xl bg-green-50 p-4">
        <QrKode :teks="uri" />
        <p class="text-xs text-gray-500">Kunci manual:</p>
        <code class="select-all break-all rounded bg-white px-2 py-1 text-sm font-semibold tracking-wider text-green-900">{{ rahasia }}</code>
      </div>
    </template>
    <template v-else>
      <h2 class="text-xl font-bold text-green-900">Verifikasi dua langkah</h2>
      <p class="mb-4 text-sm text-gray-500">Masukkan kode 6 digit dari aplikasi autentikator Anda.</p>
    </template>

    <form class="mt-4 space-y-4" @submit.prevent="form.post(route('mfa.verifikasi'))">
      <Field label="Kode verifikasi" :error="form.errors.kode" required>
        <input v-model="form.kode" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="input text-center text-2xl tracking-[0.5em]" placeholder="••••••" autofocus required />
      </Field>
      <button class="btn btn-primary w-full" :disabled="form.processing">{{ setup ? 'Aktifkan dan masuk' : 'Verifikasi' }}</button>
    </form>
    <p class="mt-4 text-center text-xs text-gray-400">Kehilangan perangkat? Hubungi Super Admin atau pengembang untuk pemulihan.</p>
  </GuestLayout>
</template>

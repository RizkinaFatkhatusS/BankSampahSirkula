<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

defineProps({ mustVerifyEmail: Boolean, status: String })
const me = computed(() => usePage().props.auth.user)
const supplier = computed(() => me.value.role === 'supplier')
const staf = computed(() => ['admin', 'super_admin'].includes(me.value.role))
</script>

<template>
  <AppLayout title="Profil Saya">
    <div class="mx-auto max-w-2xl space-y-5">
      <div class="card"><UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" /></div>
      <div class="card"><UpdatePasswordForm /></div>
      <div v-if="staf" class="card">
        <h2 class="text-lg font-bold text-green-900">Autentikasi dua langkah</h2>
        <p class="mt-1 text-sm text-gray-500">Kode 6 digit dari aplikasi autentikator (Google Authenticator, Authy, dll.) diminta setiap kali Anda masuk.</p>
        <p v-if="me.mfa_aktif" class="mt-3 rounded-xl bg-green-50 p-3 text-sm font-semibold text-green-800">✓ Aktif. Bila perangkat hilang, hubungi Super Admin atau pengembang.</p>
        <template v-else>
          <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Belum aktif.</p>
          <Link :href="route('mfa.show')" class="btn btn-primary mt-3">Aktifkan sekarang</Link>
        </template>
      </div>
      <!-- Hanya Customer yang dapat menghapus akunnya sendiri; akun lain dikelola sistem agar jejak audit utuh -->
      <div v-if="!supplier && !staf" class="card"><DeleteUserForm /></div>
    </div>
  </AppLayout>
</template>

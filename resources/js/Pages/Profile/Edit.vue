<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'

defineProps({ mustVerifyEmail: Boolean, status: String })
const supplier = computed(() => usePage().props.auth.user.role === 'supplier')
</script>

<template>
  <AppLayout title="Profil Saya">
    <div class="mx-auto max-w-2xl space-y-5">
      <div class="card"><UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" /></div>
      <div class="card"><UpdatePasswordForm /></div>
      <!-- Akun Supplier dikelola pengelola bank sampah, jadi tidak dapat dihapus sendiri -->
      <div v-if="!supplier" class="card"><DeleteUserForm /></div>
    </div>
  </AppLayout>
</template>

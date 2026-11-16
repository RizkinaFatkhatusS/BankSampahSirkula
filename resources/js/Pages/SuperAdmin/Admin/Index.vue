<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { roleLabel } from '@/utils'

const props = defineProps({ admin: Object, filter: Object, superAktif: Number })
const q = ref(props.filter.q ?? '')
const cari = () => router.get(route('superadmin.admin.index'), { q: q.value || undefined }, { preserveState: true, replace: true })

const target = ref(null)
const form = useForm({ status: '', alasan: '', konfirmasi_sandi: '' })
const buka = (a, status) => { target.value = a; form.reset(); form.clearErrors(); form.status = status }
const kirim = () => form.post(route('superadmin.admin.status', target.value.id), { preserveScroll: true, onSuccess: () => (target.value = null) })
const post = (nama, a) => router.post(route(`superadmin.admin.${nama}`, a.id), {}, { preserveScroll: true })
</script>

<template>
  <AppLayout title="Akun Admin">
    <div class="space-y-4">
      <div class="flex flex-wrap gap-2">
        <form class="flex flex-1 gap-2" @submit.prevent="cari"><input v-model="q" type="search" class="input" placeholder="Cari nama atau email…" /><button class="btn btn-primary">Cari</button></form>
        <Link :href="route('superadmin.admin.create')" class="btn btn-primary">+ Tambah Admin</Link>
      </div>
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Akun tidak dihapus, hanya dinonaktifkan. Harus ada minimal satu Super Admin aktif ({{ superAktif }} saat ini). Kata sandi ditetapkan sendiri oleh pemilik akun lewat tautan undangan.</p>
      <ul class="space-y-2">
        <li v-for="a in admin.data" :key="a.id" class="card flex flex-wrap items-center justify-between gap-3 !p-4">
          <div class="min-w-0">
            <p class="font-semibold">{{ a.nama }} <span class="rounded bg-green-100 px-1.5 text-xs text-green-800">{{ roleLabel[a.role] }}</span> <span v-if="a.saya" class="text-xs text-gray-400">(Anda)</span></p>
            <p class="text-sm text-gray-500">{{ a.email }} · {{ a.hp }}</p>
            <p class="text-xs text-gray-400"><template v-if="a.role === 'admin'">Role: {{ a.roles.length ? a.roles.join(', ') : 'belum ada role' }} · </template>2FA {{ a.mfa ? 'aktif' : 'belum' }} · login {{ a.terakhir_login ?? 'belum pernah' }}</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <StatusBadge :status="a.status" />
            <Link :href="route('superadmin.admin.edit', a.id)" class="btn btn-outline !py-1.5">Ubah</Link>
            <button v-if="a.status === 'menunggu_aktivasi'" class="btn btn-outline !py-1.5" @click="post('undang-ulang', a)">✉️ Undang ulang</button>
            <button v-else-if="a.status === 'aktif'" class="btn btn-outline !py-1.5" @click="post('reset-password', a)">🔑 Reset sandi</button>
            <button v-if="a.status !== 'nonaktif'" class="btn btn-outline !border-red-200 !py-1.5 !text-red-600" @click="buka(a, 'nonaktif')">Nonaktifkan</button>
            <button v-else class="btn btn-outline !py-1.5" @click="buka(a, 'aktif')">Aktifkan</button>
          </div>
        </li>
      </ul>
      <Pagination :links="admin.links" />
    </div>
    <ConfirmModal :show="!!target" :title="form.status === 'nonaktif' ? `Nonaktifkan ${target?.nama}?` : `Aktifkan ${target?.nama}?`" :text="form.status === 'nonaktif' ? 'Akses langsung ditutup, sesi yang berjalan berhenti. Jejak audit tetap utuh.' : ''" :danger="form.status === 'nonaktif'" :processing="form.processing" @close="target = null" @confirm="kirim">
      <div class="space-y-3 text-sm">
        <div><label class="mb-1 block font-medium">Alasan <span class="text-red-500">*</span></label><input v-model="form.alasan" class="input" /><p v-if="form.errors.alasan" class="text-red-600">{{ form.errors.alasan }}</p></div>
        <div v-if="target?.role === 'super_admin'"><label class="mb-1 block font-medium">Kata sandi Anda (konfirmasi) <span class="text-red-500">*</span></label><input v-model="form.konfirmasi_sandi" type="password" class="input" /><p v-if="form.errors.konfirmasi_sandi" class="text-red-600">{{ form.errors.konfirmasi_sandi }}</p></div>
        <p v-if="form.errors.status" class="text-red-600">{{ form.errors.status }}</p>
      </div>
    </ConfirmModal>
  </AppLayout>
</template>

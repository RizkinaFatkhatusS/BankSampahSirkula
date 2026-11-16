<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

defineProps({ roles: Array })
const hapus = ref(null)
const duplikat = (r) => router.post(route('superadmin.role.duplikat', r.id))
const konfirmasiHapus = () => router.delete(route('superadmin.role.destroy', hapus.value.id), { preserveScroll: true, onFinish: () => (hapus.value = null) })
</script>

<template>
  <AppLayout title="Manajemen Role">
    <div class="space-y-4">
      <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-gray-500">Role tugas adalah kumpulan hak akses. Satu Admin dapat memegang beberapa role.</p>
        <Link :href="route('superadmin.role.create')" class="btn btn-primary shrink-0">+ Buat role</Link>
      </div>
      <p v-if="Object.values($page.props.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ Object.values($page.props.errors)[0] }}</p>
      <ul class="space-y-2">
        <li v-for="r in roles" :key="r.id" class="card space-y-2 !p-4" :class="{ 'opacity-60': !r.aktif }">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div><p class="font-bold text-green-900">{{ r.nama }} <span v-if="r.sistem" class="rounded bg-green-700 px-1.5 text-xs font-medium text-white">role sistem</span> <span v-if="!r.aktif" class="rounded bg-gray-200 px-1.5 text-xs">nonaktif</span></p><p class="text-sm text-gray-500">{{ r.deskripsi }}</p></div>
            <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-800">{{ r.jumlah_izin }} hak akses</span>
          </div>
          <p class="text-xs text-gray-500">Dipegang: {{ r.sistem ? 'akun Super Admin (otomatis seluruh hak akses)' : r.admin.length ? r.admin.join(', ') : 'belum ada Admin' }}</p>
          <div class="flex flex-wrap gap-2">
            <template v-if="!r.sistem"><Link :href="route('superadmin.role.edit', r.id)" class="btn btn-outline !py-1.5">Ubah</Link><button class="btn btn-outline !border-red-200 !py-1.5 !text-red-600" @click="hapus = r">Hapus</button></template>
            <button class="btn btn-outline !py-1.5" @click="duplikat(r)">⧉ Duplikat</button>
          </div>
          <p v-if="r.sistem" class="text-xs text-gray-400">Role sistem selalu memiliki seluruh hak akses dan tidak dapat diubah atau dihapus.</p>
        </li>
      </ul>
    </div>
    <ConfirmModal :show="!!hapus" title="Hapus role?" :text="`Role “${hapus?.nama}” akan dihapus. Role yang masih dipegang Admin tidak dapat dihapus.`" confirm-text="Hapus role" danger @close="hapus = null" @confirm="konfirmasiHapus" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ log: Object, filter: Object, jenisPilihan: Array })
const q = ref(props.filter.q ?? '')
const cari = (jenis = props.filter.jenis) => router.get(route('superadmin.log-akses'), { jenis: jenis || undefined, q: q.value || undefined }, { preserveState: true, replace: true })
const warna = { login: 'bg-green-100 text-green-700', login_gagal: 'bg-red-100 text-red-700', penolakan_403: 'bg-red-100 text-red-700', perubahan_role: 'bg-amber-100 text-amber-800', buka_dokumen: 'bg-blue-100 text-blue-700', mfa: 'bg-indigo-100 text-indigo-700', logout: 'bg-gray-100 text-gray-600' }
</script>

<template>
  <AppLayout title="Log Akses">
    <div class="space-y-4">
      <form class="flex gap-2" @submit.prevent="cari()"><input v-model="q" type="search" class="input" placeholder="Cari pengguna, IP, atau detail…" /><button class="btn btn-primary">Cari</button></form>
      <div class="flex flex-wrap gap-2"><button v-for="j in ['', ...jenisPilihan]" :key="j" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(filter.jenis ?? '') === j ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="cari(j)">{{ j || 'Semua' }}</button></div>
      <EmptyState v-if="!log.data.length" icon="🧾" title="Belum ada catatan" />
      <ul v-else class="space-y-2">
        <li v-for="l in log.data" :key="l.id" class="card flex flex-wrap items-start justify-between gap-2 !p-4 text-sm">
          <div class="min-w-0"><p><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="warna[l.jenis] ?? 'bg-gray-100 text-gray-600'">{{ l.jenis }}</span> <b>{{ l.pengguna }}</b> <span v-if="l.role" class="text-xs text-gray-400">({{ l.role }})</span></p><p class="mt-1 break-all text-xs text-gray-500">{{ l.rute }}<span v-if="l.detail"> · {{ JSON.stringify(l.detail) }}</span></p></div>
          <p class="text-xs text-gray-400">{{ l.waktu }}<br>{{ l.ip }}</p>
        </li>
      </ul>
      <Pagination :links="log.links" />
    </div>
  </AppLayout>
</template>

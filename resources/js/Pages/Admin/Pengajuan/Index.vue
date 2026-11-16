<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ pengajuan: Object, filter: Object, hitung: Object })
const q = ref(props.filter.q ?? '')
const tab = [['', 'Semua'], ['menunggu_verifikasi', 'Menunggu'], ['sedang_diverifikasi', 'Diverifikasi'], ['perlu_revisi', 'Perlu revisi'], ['disetujui', 'Disetujui'], ['ditolak', 'Ditolak'], ['kedaluwarsa', 'Kedaluwarsa']]
const cari = (status = props.filter.status) => router.get(route('admin.pengajuan.index'), { status: status || undefined, q: q.value || undefined }, { preserveState: true, replace: true })
</script>

<template>
  <AppLayout title="Pengajuan Supplier">
    <div class="space-y-4">
      <div class="flex flex-wrap items-center gap-2">
        <form class="flex flex-1 gap-2" @submit.prevent="cari()"><input v-model="q" type="search" class="input" placeholder="Cari bank sampah, kode, atau penanggung jawab…" /><button class="btn btn-primary">Cari</button></form>
        <Link v-if="$page.props.auth.user.izin.includes('pengajuan.buat')" :href="route('admin.pengajuan.create')" class="btn btn-outline">+ Pengajuan manual</Link>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-for="t in tab" :key="t[0]" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(filter.status ?? '') === t[0] ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="cari(t[0])">{{ t[1] }}<span v-if="t[0] && hitung[t[0]]" class="ml-1 text-xs opacity-70">({{ hitung[t[0]] }})</span></button>
      </div>
      <EmptyState v-if="!pengajuan.data.length" icon="📭" title="Tidak ada pengajuan" text="Belum ada pengajuan dengan filter ini." />
      <ul v-else class="space-y-2">
        <li v-for="p in pengajuan.data" :key="p.id">
          <Link :href="route('admin.pengajuan.show', p.id)" class="card flex items-center justify-between gap-3 !p-4 hover:bg-green-50/60">
            <div class="min-w-0"><p class="font-semibold">{{ p.bank }} <span v-if="p.manual" class="rounded bg-blue-100 px-1.5 text-xs text-blue-700">manual</span></p><p class="text-sm text-gray-500">{{ p.pj }} · {{ p.kode }}</p><p class="text-xs text-gray-400">{{ p.tanggal }} ({{ p.waktu }})<span v-if="p.penangan"> · ditangani {{ p.penangan }}</span></p></div>
            <StatusBadge :status="p.status" />
          </Link>
        </li>
      </ul>
      <Pagination :links="pengajuan.links" />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { statusAkunLabel } from '@/utils'

const props = defineProps({ supplier: Object, filter: Object, hitung: Object })
const q = ref(props.filter.q ?? '')
const cari = (status = props.filter.status) => router.get(route('admin.supplier.index'), { status: status || undefined, q: q.value || undefined }, { preserveState: true, replace: true })
</script>

<template>
  <AppLayout title="Akun Supplier">
    <div class="space-y-4">
      <form class="flex gap-2" @submit.prevent="cari()"><input v-model="q" type="search" class="input" placeholder="Cari nama, email, atau bank sampah…" /><button class="btn btn-primary">Cari</button></form>
      <div class="flex flex-wrap gap-2">
        <button v-for="s in ['', 'aktif', 'menunggu_aktivasi', 'ditangguhkan', 'dalam_penyelesaian', 'nonaktif']" :key="s" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(filter.status ?? '') === s ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="cari(s)">{{ s ? statusAkunLabel[s] : 'Semua' }}<span v-if="s && hitung[s]" class="ml-1 text-xs opacity-70">({{ hitung[s] }})</span></button>
      </div>
      <EmptyState v-if="!supplier.data.length" icon="🏢" title="Tidak ada Supplier" text="Supplier muncul setelah pengajuannya disetujui." />
      <ul v-else class="space-y-2">
        <li v-for="s in supplier.data" :key="s.id">
          <Link :href="route('admin.supplier.show', s.id)" class="card flex items-center justify-between gap-3 !p-4 hover:bg-green-50/60">
            <div class="min-w-0"><p class="font-semibold">{{ s.bank ?? '-' }}</p><p class="text-sm text-gray-500">{{ s.nama }} · {{ s.email }}</p><p class="text-xs text-gray-400">Login terakhir: {{ s.terakhir_login ?? 'belum pernah' }}</p></div>
            <div class="flex shrink-0 flex-col items-end gap-1"><StatusBadge :status="s.status" /><StatusBadge :status="s.tayang" /></div>
          </Link>
        </li>
      </ul>
      <Pagination :links="supplier.links" />
    </div>
  </AppLayout>
</template>

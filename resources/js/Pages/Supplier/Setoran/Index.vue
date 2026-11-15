<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, metodeLabel } from '@/utils'

const props = defineProps({ setoran: Array, status: String })
const filter = [['', 'Semua'], ['diajukan', 'Diajukan'], ['menunggu_penjemputan', 'Dijemput'], ['diterima', 'Perlu ditimbang'], ['selesai', 'Selesai'], ['ditolak', 'Ditolak'], ['dibatalkan', 'Dibatalkan']]
const pilih = (s) => router.get(route('supplier.setoran.index'), s ? { status: s } : {}, { preserveState: true, replace: true })
</script>

<template>
  <AppLayout title="Setoran Masuk">
    <div class="space-y-4">
      <div class="flex flex-wrap gap-2">
        <button v-for="f in filter" :key="f[0]" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(status ?? '') === f[0] ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="pilih(f[0])">{{ f[1] }}</button>
      </div>
      <EmptyState v-if="!setoran.length" icon="📭" title="Tidak ada setoran" text="Belum ada setoran dengan status ini." />
      <ul v-else class="space-y-2">
        <li v-for="s in setoran" :key="s.id">
          <Link :href="route('supplier.setoran.show', s.id)" class="card flex items-center justify-between gap-3 !p-4 hover:bg-green-50/60">
            <div class="min-w-0">
              <p class="font-semibold">#{{ s.id }} · {{ s.customer }}</p>
              <p class="truncate text-sm text-gray-500">{{ s.jenis }}</p>
              <p class="text-xs text-gray-400">{{ s.tanggal }} · {{ metodeLabel[s.metode] }}</p>
            </div>
            <div class="shrink-0 text-right">
              <p class="font-bold text-green-700">{{ s.nilai !== null ? rp(s.nilai) : 'Est. ' + rp(s.estimasi) }}</p>
              <StatusBadge :status="s.status" />
            </div>
          </Link>
        </li>
      </ul>
    </div>
  </AppLayout>
</template>

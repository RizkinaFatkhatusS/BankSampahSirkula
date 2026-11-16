<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, kg } from '@/utils'

defineProps({ saldo: Number, setoran: Array, mutasi: Array })
const tab = ref('setoran')
</script>

<template>
  <AppLayout title="Riwayat & Saldo">
    <div class="space-y-5">
      <div class="card">
        <p class="text-sm text-gray-500">Saldo saat ini</p>
        <p class="text-3xl font-extrabold text-green-800">{{ rp(saldo) }}</p>
        <p class="text-xs text-gray-500">Pencairan saldo dilakukan melalui bank sampah (tunai atau transfer e-wallet) dan dicatat oleh petugas.</p>
      </div>

      <div class="flex gap-2">
        <button v-for="t in [['setoran', 'Riwayat Setoran'], ['mutasi', 'Mutasi Saldo']]" :key="t[0]" class="rounded-full px-4 py-1.5 text-sm font-medium"
          :class="tab === t[0] ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="tab = t[0]">{{ t[1] }}</button>
      </div>

      <template v-if="tab === 'setoran'">
        <EmptyState v-if="!setoran.length" icon="♻️" title="Belum ada setoran" text="Mulai setor sampah pertamamu.">
          <Link :href="route('customer.bank.index')" class="btn btn-primary">Setor Sampah</Link>
        </EmptyState>
        <ul v-else class="space-y-2">
          <li v-for="s in setoran" :key="s.id">
            <Link :href="route('customer.setoran.show', s.id)" class="card flex items-center justify-between gap-3 !p-4 hover:bg-green-50/60">
              <div class="min-w-0">
                <p class="truncate font-semibold">{{ s.jenis }}</p>
                <p class="text-xs text-gray-500">{{ s.tanggal }} · {{ s.bank }}</p>
                <p class="text-xs text-gray-400">{{ s.berat !== null ? kg(s.berat) + ' (aktual)' : 'Estimasi ' + rp(s.estimasi) }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="font-bold text-green-700">{{ s.nilai !== null ? '+' + rp(s.nilai) : '–' }}</p>
                <StatusBadge :status="s.status" />
              </div>
            </Link>
          </li>
        </ul>
      </template>

      <template v-else>
        <EmptyState v-if="!mutasi.length" icon="💰" title="Belum ada mutasi saldo" text="Saldo akan berubah setelah setoran ditimbang atau saldo dicairkan." />
        <ul v-else class="card divide-y divide-green-50 !p-0">
          <li v-for="m in mutasi" :key="m.id" class="flex items-center justify-between gap-3 px-5 py-3">
            <div>
              <p class="font-semibold">{{ m.tipe === 'setoran' ? 'Setoran' + (m.setoran_id ? ' #' + m.setoran_id : '') : 'Pencairan' }}</p>
              <p class="text-xs text-gray-500">{{ m.tanggal }}<span v-if="m.bank"> · {{ m.bank }}</span><span v-if="m.cara"> · {{ m.cara }}</span></p>
            </div>
            <div class="text-right">
              <p class="font-bold" :class="m.tipe === 'setoran' ? 'text-green-700' : 'text-red-600'">{{ m.tipe === 'setoran' ? '+' : '−' }}{{ rp(m.nilai) }}</p>
              <p class="text-xs text-gray-400">Saldo bank ini {{ rp(m.saldo_setelah) }}</p>
            </div>
          </li>
        </ul>
      </template>
    </div>
  </AppLayout>
</template>

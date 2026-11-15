<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, kg } from '@/utils'

defineProps({ saldo: Number, totalBerat: Number, totalSetoran: Number, setoranAktif: Number, riwayat: Array })
const nama = computed(() => usePage().props.auth.user.nama)
</script>

<template>
  <AppLayout title="Beranda">
    <div class="space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-green-900">Halo, {{ nama }}! 👋</h2>
        <p class="text-sm text-gray-500">Mari pilah sampah untuk bumi yang lebih lestari.</p>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
          <p class="text-sm font-medium text-gray-500">Saldo Tabungan</p>
          <p class="mt-1 text-3xl font-extrabold text-green-800">{{ rp(saldo) }}</p>
          <p class="mt-1 text-xs text-gray-500">Saldo bertambah dari nilai final setelah sampah ditimbang.</p>
        </div>
        <div class="card">
          <p class="text-sm font-medium text-gray-500">Total Berat Setoran</p>
          <p class="mt-1 text-3xl font-extrabold text-green-800">{{ kg(totalBerat) }}</p>
          <p class="mt-1 text-xs text-gray-500">{{ totalSetoran }} setoran · {{ setoranAktif }} sedang diproses</p>
        </div>
      </div>

      <div class="flex flex-col items-start justify-between gap-4 rounded-2xl bg-green-800 p-6 text-white md:flex-row md:items-center">
        <div>
          <h3 class="text-xl font-bold">Punya sampah terpilah?</h3>
          <p class="text-sm text-green-100">Setor sekarang. Dijemput kurir atau antar sendiri ke bank sampah.</p>
        </div>
        <Link :href="route('customer.bank.index')" class="btn bg-amber-400 text-green-950 hover:bg-amber-300">Setor Sampah</Link>
      </div>

      <div class="card">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="font-bold text-green-900">Riwayat Setoran Terakhir</h3>
          <Link :href="route('customer.riwayat')" class="text-sm font-semibold text-green-700 hover:underline">Lihat semua</Link>
        </div>
        <EmptyState v-if="!riwayat.length" icon="♻️" title="Belum ada setoran" text="Setoran pertamamu akan tampil di sini." />
        <ul v-else class="divide-y divide-green-50">
          <li v-for="r in riwayat" :key="r.id">
            <Link :href="route('customer.setoran.show', r.id)" class="flex items-center justify-between gap-3 py-3 hover:bg-green-50/60">
              <div class="min-w-0">
                <p class="truncate font-semibold">{{ r.jenis }}</p>
                <p class="text-xs text-gray-500">{{ r.tanggal }} · {{ r.berat !== null ? kg(r.berat) + ' (aktual)' : 'Belum ditimbang' }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="font-bold text-green-700">{{ r.nilai !== null ? '+' + rp(r.nilai) : '–' }}</p>
                <StatusBadge :status="r.status" />
              </div>
            </Link>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

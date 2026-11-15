<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { metodeLabel } from '@/utils'

defineProps({ bank: Object, ringkasan: Object, masuk: Array, notifikasi: Array })
</script>

<template>
  <AppLayout title="Beranda Supplier">
    <div class="space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-green-900">{{ bank.nama }}</h2>
        <p class="text-sm text-gray-500">Setoran masuk dan tugas yang perlu ditindaklanjuti.</p>
      </div>

      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Setoran baru" :value="ringkasan.baru" hint="menunggu persetujuan" icon="📥" tone="blue" />
        <StatCard label="Perlu dijemput" :value="ringkasan.perlu_dijemput" hint="kurir bank sampah" icon="🛵" tone="amber" />
        <StatCard label="Perlu ditimbang" :value="ringkasan.perlu_ditimbang" hint="sampah sudah diterima" icon="⚖️" tone="rose" />
        <StatCard label="Selesai bulan ini" :value="ringkasan.selesai_bulan_ini" hint="setoran tuntas" icon="✅" tone="green" />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div class="card lg:col-span-2">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="font-bold text-green-900">Setoran Masuk</h3>
            <Link :href="route('supplier.setoran.index')" class="text-sm font-semibold text-green-700 hover:underline">Semua setoran</Link>
          </div>
          <EmptyState v-if="!masuk.length" icon="📭" title="Belum ada setoran aktif" text="Setoran dari Customer akan muncul di sini." />
          <ul v-else class="divide-y divide-green-50">
            <li v-for="s in masuk" :key="s.id">
              <Link :href="route('supplier.setoran.show', s.id)" class="flex items-center gap-3 py-3 hover:bg-green-50/60">
                <img :src="s.foto" alt="" class="h-14 w-14 shrink-0 rounded-xl object-cover" />
                <div class="min-w-0 flex-1">
                  <p class="truncate font-semibold">{{ s.customer }} <span class="font-normal text-gray-400">· #{{ s.id }}</span></p>
                  <p class="truncate text-sm text-gray-500">{{ s.jenis }}</p>
                  <p class="text-xs text-gray-400">{{ metodeLabel[s.metode] }}<span v-if="s.jadwal"> · {{ s.jadwal }}</span> · {{ s.waktu }}</p>
                </div>
                <StatusBadge :status="s.status" />
              </Link>
            </li>
          </ul>
        </div>

        <div class="card">
          <div class="mb-3 flex items-center justify-between">
            <h3 class="font-bold text-green-900">Notifikasi</h3>
            <Link :href="route('supplier.notifikasi')" class="text-sm font-semibold text-green-700 hover:underline">Lihat</Link>
          </div>
          <p v-if="!notifikasi.length" class="text-sm text-gray-400">Belum ada notifikasi.</p>
          <ul v-else class="space-y-3">
            <li v-for="n in notifikasi" :key="n.id" class="text-sm">
              <p class="font-semibold text-green-900"><span v-if="!n.dibaca" class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500" />{{ n.judul }}</p>
              <p class="text-gray-600">{{ n.pesan }}</p>
              <p class="text-xs text-gray-400">{{ n.waktu }}</p>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

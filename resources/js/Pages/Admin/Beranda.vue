<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { rp, kg, statusAkunLabel } from '@/utils'

defineProps({ punyaIzin: Boolean, antrean: Object, akunSupplier: Object, jumlahCustomer: Number, ringkas: Object, saldoBelumDicairkan: Number, notifikasi: Array })
</script>

<template>
  <AppLayout title="Beranda Admin">
    <div class="space-y-6">
      <p v-if="!punyaIzin" class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-900">Akun Anda belum memiliki role tugas. Hubungi Super Admin agar hak akses diberikan.</p>

      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard v-if="antrean" label="Menunggu verifikasi" :value="antrean.menunggu" :hint="`${antrean.diverifikasi} sedang diverifikasi`" icon="📝" tone="blue" />
        <StatCard v-if="antrean" label="Pengajuan saya" :value="antrean.saya" hint="sedang Anda tangani" icon="🧑‍💼" tone="amber" />
        <StatCard v-if="akunSupplier" label="Supplier aktif" :value="akunSupplier.aktif ?? 0" :hint="`${(akunSupplier.ditangguhkan ?? 0) + (akunSupplier.dalam_penyelesaian ?? 0)} ditangguhkan/penyelesaian`" icon="🏢" />
        <StatCard v-if="jumlahCustomer !== undefined" label="Customer" :value="jumlahCustomer" icon="👥" tone="blue" />
        <StatCard v-if="ringkas" label="Sampah diterima" :value="kg(ringkas.berat)" :hint="`${ringkas.bank} bank sampah`" icon="🗑️" />
        <StatCard v-if="ringkas" label="Penjualan hasil olahan" :value="rp(ringkas.penjualan)" icon="🧾" tone="amber" />
        <StatCard v-if="saldoBelumDicairkan !== undefined" label="Saldo belum dicairkan" :value="rp(saldoBelumDicairkan)" hint="di bank sampah tidak aktif" icon="💰" tone="rose" />
      </div>

      <div class="grid gap-4 lg:grid-cols-3">
        <div v-if="antrean" class="card lg:col-span-2">
          <div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-green-900">Antrean pendaftaran Supplier</h3><Link :href="route('admin.pengajuan.index')" class="text-sm font-semibold text-green-700 hover:underline">Semua</Link></div>
          <p v-if="!antrean.daftar.length" class="text-sm text-gray-400">Tidak ada pengajuan yang menunggu.</p>
          <ul v-else class="divide-y divide-green-50">
            <li v-for="p in antrean.daftar" :key="p.id"><Link :href="route('admin.pengajuan.show', p.id)" class="flex items-center justify-between gap-3 py-3 hover:bg-green-50/60"><div><p class="font-semibold">{{ p.bank }}</p><p class="text-xs text-gray-400">{{ p.kode }} · {{ p.waktu }}</p></div><StatusBadge :status="p.status" /></Link></li>
          </ul>
        </div>
        <div class="card" :class="{ 'lg:col-span-3': !antrean }">
          <div class="mb-3 flex items-center justify-between"><h3 class="font-bold text-green-900">Notifikasi</h3><Link :href="route('admin.notifikasi')" class="text-sm font-semibold text-green-700 hover:underline">Lihat</Link></div>
          <p v-if="!notifikasi.length" class="text-sm text-gray-400">Belum ada notifikasi.</p>
          <ul v-else class="space-y-3">
            <li v-for="n in notifikasi" :key="n.id" class="text-sm"><p class="font-semibold text-green-900"><span v-if="!n.dibaca" class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500" />{{ n.judul }}</p><p class="text-gray-600">{{ n.pesan }}</p><a v-if="n.url" :href="n.url" class="text-xs font-semibold text-green-700 hover:underline">Buka</a><span class="ml-2 text-xs text-gray-400">{{ n.waktu }}</span></li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

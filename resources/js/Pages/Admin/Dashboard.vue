<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ChartCanvas from '@/Components/ChartCanvas.vue'
import { rp, kg, statusAkunLabel } from '@/utils'

const props = defineProps({ pengajuan: Object, akunSupplier: Object, jumlahCustomer: Number, jumlahBank: Number, totalBerat: Number, totalNilai: Number, penjualanNilai: Number, penjualanJumlah: Number, perBulan: Array, perBank: Array })
const labelPengajuan = { menunggu_verifikasi: 'Menunggu', sedang_diverifikasi: 'Diverifikasi', perlu_revisi: 'Perlu revisi', disetujui: 'Disetujui', ditolak: 'Ditolak', kedaluwarsa: 'Kedaluwarsa' }
const kunci = Object.keys(labelPengajuan)
</script>

<template>
  <AppLayout title="Dashboard Platform">
    <div class="space-y-6">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Ringkasan seluruh platform. Hanya data agregat; tidak memuat data pribadi atau saldo Customer.</p>
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Bank sampah" :value="jumlahBank" :hint="`${akunSupplier.aktif ?? 0} akun aktif`" icon="🏢" />
        <StatCard label="Customer" :value="jumlahCustomer" icon="👥" tone="blue" />
        <StatCard label="Sampah diterima" :value="kg(totalBerat)" :hint="rp(totalNilai)" icon="🗑️" tone="amber" />
        <StatCard label="Penjualan hasil olahan" :value="rp(penjualanNilai)" :hint="`${penjualanJumlah} transaksi`" icon="🧾" tone="rose" />
      </div>
      <div class="grid gap-4 lg:grid-cols-2">
        <div class="card"><h3 class="mb-3 font-bold text-green-900">Sampah diterima per bulan (kg)</h3><ChartCanvas type="bar" :labels="perBulan.map((b) => b.label)" :datasets="[{ label: 'Berat (kg)', data: perBulan.map((b) => b.berat) }]" /></div>
        <div class="card"><h3 class="mb-3 font-bold text-green-900">Bank sampah menurut status akun</h3><ChartCanvas type="doughnut" :labels="Object.keys(statusAkunLabel).map((k) => statusAkunLabel[k])" :datasets="[{ data: Object.keys(statusAkunLabel).map((k) => akunSupplier[k] ?? 0) }]" /></div>
        <div class="card"><h3 class="mb-3 font-bold text-green-900">Status pengajuan Supplier</h3><ChartCanvas type="doughnut" :labels="kunci.map((k) => labelPengajuan[k])" :datasets="[{ data: kunci.map((k) => pengajuan[k] ?? 0) }]" /></div>
      </div>
      <div class="card overflow-x-auto">
        <h3 class="mb-3 font-bold text-green-900">Ringkasan per bank sampah</h3>
        <table class="w-full text-sm"><thead class="text-left text-xs text-gray-400"><tr><th class="pb-2">Bank sampah</th><th>Akun</th><th>Tayang</th><th class="text-right">Setoran</th><th class="text-right">Berat</th><th class="text-right">Nilai</th><th class="text-right">Penjualan</th></tr></thead>
          <tbody><tr v-for="b in perBank" :key="b.id" class="border-t border-green-50"><td class="py-2 font-medium">{{ b.nama }}</td><td><StatusBadge :status="b.status_akun" /></td><td><StatusBadge :status="b.status_tayang" /></td><td class="text-right">{{ b.setoran }}</td><td class="text-right">{{ kg(b.berat) }}</td><td class="text-right">{{ rp(b.nilai) }}</td><td class="text-right">{{ rp(b.penjualan) }}</td></tr></tbody></table>
      </div>
    </div>
  </AppLayout>
</template>

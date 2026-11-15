<script setup>
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import ChartCanvas from '@/Components/ChartCanvas.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, kg, angka } from '@/utils'

const props = defineProps({ total: Object, perBulan: Array, komposisiSampah: Array, komposisiProduk: Array })
const labels = computed(() => props.perBulan.map((b) => b.label))
</script>

<template>
  <AppLayout title="Dashboard Statistik">
    <div class="space-y-6">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Statistik ini hanya tersedia untuk Supplier dan hanya memuat data bank sampah Anda.</p>

      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Total sampah diterima" :value="kg(total.sampah_kg)" :hint="`${kg(total.sampah_bulan_ini_kg)} bulan ini`" icon="🗑️" />
        <StatCard label="Setoran selesai" :value="total.setoran_selesai" :hint="`Nilai ${rp(total.nilai_setoran)}`" icon="✅" tone="blue" />
        <StatCard label="Penjualan ke mitra" :value="rp(total.penjualan_nilai)" :hint="`${total.penjualan_jumlah} transaksi · ${total.penjualan_diterima} diterima`" icon="🧾" tone="amber" />
        <StatCard label="Kas bank sampah" :value="rp(total.kas_masuk - total.kas_keluar)" :hint="`Masuk ${rp(total.kas_masuk)} · Keluar ${rp(total.kas_keluar)}`" icon="💼" tone="rose" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Sampah diterima per bulan (kg)</h3>
          <ChartCanvas type="bar" :labels="labels" :datasets="[{ label: 'Berat (kg)', data: perBulan.map((b) => b.berat) }]" />
        </div>
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Penjualan per bulan (Rp)</h3>
          <ChartCanvas type="line" :labels="labels" :datasets="[{ label: 'Penjualan', data: perBulan.map((b) => b.penjualan), borderColor: '#f59e0b', backgroundColor: '#fde68a', tension: 0.3, fill: true }]" />
        </div>
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Komposisi sampah per jenis</h3>
          <EmptyState v-if="!komposisiSampah.length" icon="📊" title="Belum ada data" text="Tampil setelah ada setoran yang ditimbang." />
          <ChartCanvas v-else type="doughnut" :labels="komposisiSampah.map((k) => k.nama)" :datasets="[{ data: komposisiSampah.map((k) => k.berat) }]" />
        </div>
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Komposisi hasil olahan (stok)</h3>
          <EmptyState v-if="!komposisiProduk.length" icon="📦" title="Belum ada produk olahan" text="Catat pengolahan untuk melihat komposisi produk." />
          <template v-else>
            <ChartCanvas type="doughnut" :labels="komposisiProduk.map((k) => k.nama)" :datasets="[{ data: komposisiProduk.map((k) => k.jumlah) }]" />
            <ul class="mt-3 text-sm text-gray-600">
              <li v-for="k in komposisiProduk" :key="k.nama">{{ k.nama }}: {{ angka(k.jumlah) }} unit dari {{ k.jenis }} produk</li>
            </ul>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

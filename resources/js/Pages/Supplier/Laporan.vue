<script setup>
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import { rp, kg } from '@/utils'

const props = defineProps({ periode: String, tanggal: String, rentang: Object, data: Object, tersimpan: Array })
const p = ref(props.periode)
const t = ref(props.tanggal)

const muat = () => router.get(route('supplier.laporan.index'), { periode: p.value, tanggal: t.value }, { preserveState: true, replace: true })
const simpan = () => router.post(route('supplier.laporan.store'), { periode: p.value, tanggal: t.value }, { preserveScroll: true })
// Unduh memakai navigasi biasa (bukan Inertia) karena responsnya berkas
const unduh = (format, per = p.value, tgl = t.value) => (window.location.href = route('supplier.laporan.unduh', { periode: per, format, tanggal: tgl }))
const r = props.data.ringkasan
</script>

<template>
  <AppLayout title="Laporan">
    <div class="space-y-5">
      <div class="card flex flex-wrap items-end gap-3">
        <div><label class="mb-1 block text-sm font-medium">Periode</label><select v-model="p" class="input" @change="muat"><option value="mingguan">Mingguan</option><option value="bulanan">Bulanan</option></select></div>
        <div><label class="mb-1 block text-sm font-medium">Tanggal acuan</label><input v-model="t" type="date" class="input" @change="muat" /></div>
        <p class="pb-2 text-sm text-gray-500">{{ rentang.mulai }} – {{ rentang.selesai }}</p>
        <div class="ml-auto flex flex-wrap gap-2">
          <button class="btn btn-outline" @click="simpan">💾 Simpan laporan</button>
          <button class="btn btn-primary" @click="unduh('excel')">⬇ Excel</button>
          <button class="btn btn-primary" @click="unduh('pdf')">⬇ PDF</button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Setoran selesai" :value="r.jumlah_setoran" icon="✅" />
        <StatCard label="Sampah diterima" :value="kg(r.total_berat_kg)" :hint="rp(r.total_nilai_setoran)" icon="🗑️" tone="blue" />
        <StatCard label="Pengolahan" :value="r.jumlah_pengolahan" icon="♻️" tone="amber" />
        <StatCard label="Penjualan" :value="rp(r.total_penjualan)" :hint="`${r.jumlah_penjualan} transaksi`" icon="🧾" tone="rose" />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <div class="card overflow-x-auto"><h3 class="mb-2 font-bold text-green-900">Setoran</h3>
          <p v-if="!data.setoran.length" class="text-sm text-gray-400">Tidak ada data pada periode ini.</p>
          <table v-else class="w-full text-sm"><thead class="text-left text-xs text-gray-400"><tr><th>Tgl</th><th>Customer</th><th class="text-right">Berat</th><th class="text-right">Nilai</th></tr></thead>
            <tbody><tr v-for="s in data.setoran" :key="s.nomor" class="border-t border-green-50"><td class="py-1.5">{{ s.tanggal }}</td><td>{{ s.customer }}</td><td class="text-right">{{ kg(s.berat) }}</td><td class="text-right">{{ rp(s.nilai) }}</td></tr></tbody></table>
        </div>
        <div class="card overflow-x-auto"><h3 class="mb-2 font-bold text-green-900">Penjualan</h3>
          <p v-if="!data.penjualan.length" class="text-sm text-gray-400">Tidak ada data pada periode ini.</p>
          <table v-else class="w-full text-sm"><thead class="text-left text-xs text-gray-400"><tr><th>Tgl</th><th>Mitra</th><th>Pengiriman</th><th class="text-right">Nilai</th></tr></thead>
            <tbody><tr v-for="(s, i) in data.penjualan" :key="i" class="border-t border-green-50"><td class="py-1.5">{{ s.tanggal }}</td><td>{{ s.mitra }}</td><td class="capitalize">{{ s.pengiriman }}</td><td class="text-right">{{ rp(s.nilai) }}</td></tr></tbody></table>
        </div>
      </div>

      <div class="card">
        <h3 class="mb-2 font-bold text-green-900">Laporan tersimpan</h3>
        <p v-if="!tersimpan.length" class="text-sm text-gray-400">Belum ada laporan disimpan.</p>
        <ul v-else class="divide-y divide-green-50 text-sm">
          <li v-for="l in tersimpan" :key="l.id" class="flex flex-wrap items-center justify-between gap-2 py-2">
            <span><b class="capitalize">{{ l.periode }}</b> · {{ l.label }} <span class="text-xs text-gray-400">({{ l.dibuat }})</span></span>
            <span class="flex gap-3"><button class="font-semibold text-green-700 hover:underline" @click="unduh('excel', l.periode, l.mulai)">Excel</button><button class="font-semibold text-green-700 hover:underline" @click="unduh('pdf', l.periode, l.mulai)">PDF</button></span>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

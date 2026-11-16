<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { rp, kg } from '@/utils'

const props = defineProps({ periode: String, tanggal: String, rentang: Object, ringkasan: Object, pengajuan: Array, bank: Array })
const p = ref(props.periode)
const t = ref(props.tanggal)
const muat = () => router.get(route('admin.laporan.index'), { periode: p.value, tanggal: t.value }, { preserveState: true, replace: true })
const unduh = (format) => (window.location.href = route('admin.laporan.unduh', { periode: p.value, format, tanggal: t.value }))
</script>

<template>
  <AppLayout title="Laporan Platform">
    <div class="space-y-5">
      <div class="card flex flex-wrap items-end gap-3">
        <div><label class="mb-1 block text-sm font-medium">Periode</label><select v-model="p" class="input" @change="muat"><option value="mingguan">Mingguan</option><option value="bulanan">Bulanan</option></select></div>
        <div><label class="mb-1 block text-sm font-medium">Tanggal acuan</label><input v-model="t" type="date" class="input" @change="muat" /></div>
        <p class="pb-2 text-sm text-gray-500">{{ rentang.mulai }} – {{ rentang.selesai }}</p>
        <div class="ml-auto flex gap-2"><button class="btn btn-primary" @click="unduh('excel')">⬇ Excel</button><button class="btn btn-primary" @click="unduh('pdf')">⬇ PDF</button></div>
      </div>
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Pengajuan Supplier" :value="ringkasan.pengajuan" icon="📝" tone="blue" />
        <StatCard label="Bank sampah" :value="ringkasan.bank" icon="🏢" />
        <StatCard label="Setoran selesai" :value="ringkasan.setoran" icon="✅" tone="amber" />
        <StatCard label="Sampah diterima" :value="kg(ringkasan.berat)" :hint="rp(ringkasan.nilai)" icon="🗑️" tone="rose" />
      </div>
      <div class="card overflow-x-auto"><h3 class="mb-2 font-bold text-green-900">Pengajuan dan hasil verifikasi</h3>
        <p v-if="!pengajuan.length" class="text-sm text-gray-400">Tidak ada pengajuan pada periode ini.</p>
        <table v-else class="w-full text-sm"><thead class="text-left text-xs text-gray-400"><tr><th>Kode</th><th>Bank sampah</th><th>Status</th><th>Diputuskan</th></tr></thead>
          <tbody><tr v-for="x in pengajuan" :key="x.kode" class="border-t border-green-50"><td class="py-1.5">{{ x.kode }}</td><td>{{ x.bank }}</td><td>{{ x.status }}</td><td>{{ x.diputuskan_oleh }} {{ x.diputuskan_pada !== '-' ? '· ' + x.diputuskan_pada : '' }}</td></tr></tbody></table></div>
      <div class="card overflow-x-auto"><h3 class="mb-2 font-bold text-green-900">Bank sampah pada periode</h3>
        <table class="w-full text-sm"><thead class="text-left text-xs text-gray-400"><tr><th>Bank sampah</th><th>Akun</th><th class="text-right">Setoran</th><th class="text-right">Berat</th><th class="text-right">Nilai</th></tr></thead>
          <tbody><tr v-for="b in bank" :key="b.id" class="border-t border-green-50"><td class="py-1.5">{{ b.nama }}</td><td><StatusBadge :status="b.status_akun" /></td><td class="text-right">{{ b.setoran }}</td><td class="text-right">{{ kg(b.berat) }}</td><td class="text-right">{{ rp(b.nilai) }}</td></tr></tbody></table></div>
    </div>
  </AppLayout>
</template>

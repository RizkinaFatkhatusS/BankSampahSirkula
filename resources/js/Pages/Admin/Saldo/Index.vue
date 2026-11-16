<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, statusAkunLabel } from '@/utils'

defineProps({ bank: Array, total: Number, status: String })
const buka = ref(null)
const filter = (s) => router.get(route('admin.saldo.index'), s ? { status: s } : {}, { preserveState: true, replace: true })
</script>

<template>
  <AppLayout title="Saldo Belum Dicairkan">
    <div class="space-y-4">
      <div class="card"><p class="text-sm text-gray-500">Total saldo belum dicairkan (sesuai filter)</p><p class="text-3xl font-extrabold text-green-800">{{ rp(total) }}</p><p class="text-xs text-gray-500">Dipakai Admin untuk menindaklanjuti penghentian bank sampah. Admin tidak mengubah saldo; pencairan dicatat Supplier.</p></div>
      <div class="flex flex-wrap gap-2"><button v-for="s in ['', 'ditangguhkan', 'dalam_penyelesaian', 'nonaktif', 'aktif']" :key="s" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(status ?? '') === s ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="filter(s)">{{ s ? statusAkunLabel[s] : 'Semua' }}</button></div>
      <EmptyState v-if="!bank.length" icon="💰" title="Tidak ada saldo atau setoran yang tertahan" text="Tidak ada bank sampah dengan saldo belum dicairkan atau setoran belum final pada filter ini." />
      <div v-for="b in bank" :key="b.id" class="card space-y-2">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div><p class="font-bold text-green-900">{{ b.nama }}</p><p class="text-xs text-gray-400">Kontak bank sampah: {{ b.kontak }}</p></div>
          <div class="text-right"><StatusBadge :status="b.status_akun" /><p class="mt-1 text-lg font-extrabold text-green-800">{{ rp(b.saldo) }}</p></div>
        </div>
        <p class="text-sm text-gray-600">{{ b.customer.length }} Customer bersaldo · {{ b.setoran_belum_final }} setoran belum final</p>
        <div class="flex gap-3 text-sm font-semibold">
          <button class="text-green-700 hover:underline" @click="buka = buka === b.id ? null : b.id">{{ buka === b.id ? 'Sembunyikan' : 'Lihat Customer' }}</button>
          <Link v-if="b.supplier_id && $page.props.auth.user.izin.includes('supplier.lihat')" :href="route('admin.supplier.show', b.supplier_id)" class="text-green-700 hover:underline">Kelola akun</Link>
        </div>
        <ul v-if="buka === b.id" class="divide-y divide-green-50 rounded-xl bg-green-50/50 text-sm">
          <li v-for="c in b.customer" :key="c.id" class="flex justify-between px-4 py-2"><span>{{ c.nama }} <span class="text-xs text-gray-400">{{ c.kontak }}</span></span><b>{{ rp(c.saldo) }}</b></li>
          <li v-if="!b.customer.length" class="px-4 py-2 text-gray-400">Tidak ada Customer bersaldo.</li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

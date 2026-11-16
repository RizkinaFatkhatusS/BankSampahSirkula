<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ log: Object, filter: Object, hitung: Object })
const q = ref(props.filter.q ?? '')
const cari = (status = props.filter.status) => router.get(route('admin.log-email'), { status: status || undefined, q: q.value || undefined }, { preserveState: true, replace: true })
const ulang = (l) => router.post(route('admin.log-email.kirim-ulang', l.id), {}, { preserveScroll: true })
</script>

<template>
  <AppLayout title="Log Email">
    <div class="space-y-4">
      <form class="flex gap-2" @submit.prevent="cari()"><input v-model="q" type="search" class="input" placeholder="Cari penerima atau jenis email…" /><button class="btn btn-primary">Cari</button></form>
      <div class="flex gap-2"><button v-for="s in ['', 'antre', 'terkirim', 'gagal']" :key="s" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(filter.status ?? '') === s ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="cari(s)">{{ s || 'Semua' }}<span v-if="s && hitung[s]" class="ml-1 text-xs opacity-70">({{ hitung[s] }})</span></button></div>
      <EmptyState v-if="!log.data.length" icon="✉️" title="Belum ada email" />
      <ul v-else class="space-y-2">
        <li v-for="l in log.data" :key="l.id" class="card flex flex-wrap items-center justify-between gap-3 !p-4">
          <div class="min-w-0"><p class="font-semibold">{{ l.subjek || l.jenis }}</p><p class="text-sm text-gray-500">{{ l.penerima }} · <span class="text-xs">{{ l.jenis }}</span></p><p class="text-xs text-gray-400">{{ l.waktu }} · {{ l.percobaan }} percobaan<span v-if="l.dikirim"> · terkirim {{ l.dikirim }}</span></p><p v-if="l.error" class="text-xs text-red-600">{{ l.error }}</p></div>
          <div class="flex items-center gap-2"><StatusBadge :status="l.status" /><button v-if="l.status === 'gagal'" class="btn btn-outline !py-1.5" @click="ulang(l)">↻ Kirim ulang</button></div>
        </li>
      </ul>
      <Pagination :links="log.links" />
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ log: Object, filter: Object, entitas: Array })
const f = reactive({ q: props.filter.q ?? '', entitas: props.filter.entitas ?? '', dari: props.filter.dari ?? '', sampai: props.filter.sampai ?? '' })
const cari = () => router.get(route('admin.log-audit'), Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, replace: true })
const terbuka = ref(null)
const ringkas = (v) => (v == null ? '–' : JSON.stringify(v))
</script>

<template>
  <AppLayout title="Log Audit">
    <div class="space-y-4">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Log audit bersifat baca saja: tidak dapat diubah atau dihapus dari aplikasi.</p>
      <form class="card grid gap-3 sm:grid-cols-5" @submit.prevent="cari">
        <input v-model="f.q" class="input sm:col-span-2" placeholder="Cari aksi, alasan, pelaku…" />
        <select v-model="f.entitas" class="input"><option value="">Semua entitas</option><option v-for="e in entitas" :key="e" :value="e">{{ e }}</option></select>
        <input v-model="f.dari" type="date" class="input" /><input v-model="f.sampai" type="date" class="input" />
        <button class="btn btn-primary sm:col-span-5">Terapkan filter</button>
      </form>
      <EmptyState v-if="!log.data.length" icon="🔍" title="Tidak ada catatan" text="Belum ada catatan audit untuk filter ini." />
      <ul v-else class="space-y-2">
        <li v-for="l in log.data" :key="l.id" class="card !p-4">
          <button class="flex w-full flex-wrap items-start justify-between gap-2 text-left" @click="terbuka = terbuka === l.id ? null : l.id">
            <div><p class="font-semibold text-green-900">{{ l.aksi }} <span class="text-xs font-normal text-gray-400">{{ l.entitas }} #{{ l.entitas_id }}</span></p><p class="text-xs text-gray-500">{{ l.pelaku }} · {{ l.waktu }}<span v-if="l.ip"> · {{ l.ip }}</span></p></div>
            <span v-if="l.alasan" class="max-w-xs truncate text-xs text-gray-600">“{{ l.alasan }}”</span>
          </button>
          <div v-if="terbuka === l.id" class="mt-3 grid gap-3 text-xs sm:grid-cols-2">
            <div class="rounded-lg bg-red-50 p-3"><p class="mb-1 font-semibold text-red-800">Sebelum</p><pre class="whitespace-pre-wrap break-all">{{ ringkas(l.sebelum) }}</pre></div>
            <div class="rounded-lg bg-green-50 p-3"><p class="mb-1 font-semibold text-green-800">Sesudah</p><pre class="whitespace-pre-wrap break-all">{{ ringkas(l.sesudah) }}</pre></div>
            <p v-if="l.alasan" class="sm:col-span-2"><b>Alasan:</b> {{ l.alasan }}</p>
          </div>
        </li>
      </ul>
      <Pagination :links="log.links" />
    </div>
  </AppLayout>
</template>

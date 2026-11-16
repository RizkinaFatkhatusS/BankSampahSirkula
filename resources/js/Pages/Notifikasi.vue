<script setup>
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ notifikasi: Array, role: String })
const prefix = ['admin', 'super_admin'].includes(props.role) ? 'admin' : props.role === 'supplier' ? 'supplier' : 'customer'
const ikon = { sukses: '✅', peringatan: '⚠️', info: '🔔' }

const baca = (n) => !n.dibaca && router.post(route(`${prefix}.notifikasi.baca`, n.id), {}, { preserveScroll: true })
const bacaSemua = () => router.post(route(`${prefix}.notifikasi.baca-semua`), {}, { preserveScroll: true })
const tautan = (n) => n.url ? n.url : n.setoran_id ? route(prefix === 'supplier' ? 'supplier.setoran.show' : 'customer.setoran.show', n.setoran_id) : null
</script>

<template>
  <AppLayout title="Notifikasi">
    <div class="mx-auto max-w-2xl space-y-4">
      <div class="flex justify-end" v-if="notifikasi.some((n) => !n.dibaca)">
        <button class="text-sm font-semibold text-green-700 hover:underline" @click="bacaSemua">Tandai semua sudah dibaca</button>
      </div>
      <EmptyState v-if="!notifikasi.length" icon="🔔" title="Belum ada notifikasi" text="Pemberitahuan setoran, penimbangan, dan saldo akan muncul di sini." />
      <ul v-else class="space-y-2">
        <li v-for="n in notifikasi" :key="n.id" class="card flex gap-3 !p-4" :class="n.dibaca ? 'opacity-70' : 'ring-green-300'">
          <span class="text-xl">{{ ikon[n.tipe] ?? '🔔' }}</span>
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-green-900">{{ n.judul }} <span v-if="!n.dibaca" class="ml-1 inline-block h-2 w-2 rounded-full bg-red-500" /></p>
            <p class="text-sm text-gray-600">{{ n.pesan }}</p>
            <div class="mt-1 flex items-center gap-3 text-xs text-gray-400">
              {{ n.waktu }}
              <Link v-if="tautan(n)" :href="tautan(n)" class="font-semibold text-green-700 hover:underline" @click="baca(n)">{{ n.url ? 'Buka' : 'Lihat setoran' }}</Link>
              <button v-if="!n.dibaca" class="font-semibold text-gray-500 hover:underline" @click="baca(n)">Tandai dibaca</button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </AppLayout>
</template>

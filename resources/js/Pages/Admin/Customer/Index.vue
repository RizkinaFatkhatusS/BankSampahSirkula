<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import EmptyState from '@/Components/EmptyState.vue'

const props = defineProps({ customer: Object, filter: Object, kontakLengkap: Boolean, bolehStatus: Boolean })
const q = ref(props.filter.q ?? '')
const cari = (status = props.filter.status) => router.get(route('admin.customer.index'), { status: status || undefined, q: q.value || undefined }, { preserveState: true, replace: true })

const target = ref(null)
const form = useForm({ status: '', alasan: '' })
const buka = (c, status) => { target.value = c; form.reset(); form.clearErrors(); form.status = status }
const kirim = () => form.post(route('admin.customer.status', target.value.id), { preserveScroll: true, onSuccess: () => (target.value = null) })
const reset = (c) => router.post(route('admin.customer.reset-password', c.id), {}, { preserveScroll: true })
</script>

<template>
  <AppLayout title="Akun Customer">
    <div class="space-y-4">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Admin mengelola status akun saja. Saldo, riwayat setoran, dan data pribadi tidak dapat diubah. <span v-if="!kontakLengkap">Email dan nomor HP disamarkan.</span></p>
      <form class="flex gap-2" @submit.prevent="cari()"><input v-model="q" type="search" class="input" placeholder="Cari nama atau email…" /><button class="btn btn-primary">Cari</button></form>
      <div class="flex gap-2"><button v-for="s in [['', 'Semua'], ['aktif', 'Aktif'], ['nonaktif', 'Nonaktif']]" :key="s[0]" class="rounded-full px-4 py-1.5 text-sm font-medium" :class="(filter.status ?? '') === s[0] ? 'bg-green-700 text-white' : 'bg-white text-green-800 ring-1 ring-green-200'" @click="cari(s[0])">{{ s[1] }}</button></div>
      <EmptyState v-if="!customer.data.length" icon="👥" title="Tidak ada Customer" />
      <ul v-else class="space-y-2">
        <li v-for="c in customer.data" :key="c.id" class="card flex flex-wrap items-center justify-between gap-3 !p-4">
          <div class="min-w-0"><p class="font-semibold">{{ c.nama }} <StatusBadge :status="c.status" /></p><p class="text-sm text-gray-500">{{ c.email }} · {{ c.hp }}</p><p class="text-xs text-gray-400">Terdaftar {{ c.terdaftar }}<span v-if="c.alasan && c.status === 'nonaktif'"> · alasan: {{ c.alasan }}</span></p></div>
          <div v-if="bolehStatus" class="flex gap-2">
            <button v-if="c.status === 'aktif'" class="btn btn-outline !border-red-200 !py-1.5 !text-red-600" @click="buka(c, 'nonaktif')">Nonaktifkan</button>
            <button v-else class="btn btn-outline !py-1.5" @click="buka(c, 'aktif')">Aktifkan</button>
            <button class="btn btn-outline !py-1.5" @click="reset(c)">🔑 Reset sandi</button>
          </div>
        </li>
      </ul>
      <Pagination :links="customer.links" />
    </div>
    <ConfirmModal :show="!!target" :title="form.status === 'nonaktif' ? 'Nonaktifkan akun Customer?' : 'Aktifkan akun Customer?'" :text="form.status === 'nonaktif' ? 'Customer tidak dapat login. Saldo dan riwayat tidak hilang. Customer diberi tahu lewat email.' : ''" :danger="form.status === 'nonaktif'" :processing="form.processing" @close="target = null" @confirm="kirim">
      <label class="mb-1 block text-sm font-medium">Alasan <span class="text-red-500">*</span></label><textarea v-model="form.alasan" rows="2" class="input" /><p v-if="form.errors.alasan" class="mt-1 text-sm text-red-600">{{ form.errors.alasan }}</p>
    </ConfirmModal>
  </AppLayout>
</template>

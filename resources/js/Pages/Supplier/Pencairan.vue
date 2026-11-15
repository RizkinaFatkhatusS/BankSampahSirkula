<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp } from '@/utils'

const props = defineProps({ customers: Array, riwayat: Array })
const form = useForm({ customer_id: '', nilai: '', cara_pencairan: 'Tunai di bank sampah' })
const konfirm = ref(false)
const terpilih = computed(() => props.customers.find((c) => c.id === Number(form.customer_id)))
const simpan = () => form.post(route('supplier.pencairan.store'), { preserveScroll: true, onSuccess: () => form.reset('nilai', 'customer_id'), onFinish: () => (konfirm.value = false) })
</script>

<template>
  <AppLayout title="Pencairan Saldo">
    <div class="grid gap-5 lg:grid-cols-5">
      <form class="card h-fit space-y-4 lg:col-span-2" @submit.prevent="konfirm = true">
        <div><h3 class="font-bold text-green-900">Catat pencairan</h3><p class="text-xs text-gray-500">Uang diserahkan di luar sistem (tunai atau transfer e-wallet). Di sini hanya dicatat agar saldo berkurang.</p></div>
        <Field label="Customer" :error="form.errors.customer_id" required>
          <select v-model="form.customer_id" class="input" required>
            <option value="" disabled>Pilih customer</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.nama }} · saldo {{ rp(c.saldo) }}</option>
          </select>
        </Field>
        <Field label="Nilai pencairan (Rp)" :error="form.errors.nilai" :hint="terpilih ? `Saldo tersedia ${rp(terpilih.saldo)}` : ''" required>
          <input v-model="form.nilai" type="number" min="1" :max="terpilih?.saldo" step="1000" class="input" required />
        </Field>
        <Field label="Cara pencairan" :error="form.errors.cara_pencairan">
          <select v-model="form.cara_pencairan" class="input"><option>Tunai di bank sampah</option><option>Transfer e-wallet</option></select>
        </Field>
        <button class="btn btn-primary w-full" :disabled="form.processing || !form.customer_id">Catat pencairan</button>
      </form>

      <div class="space-y-5 lg:col-span-3">
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Saldo Customer</h3>
          <EmptyState v-if="!customers.length" icon="👥" title="Belum ada customer bersaldo" text="Customer muncul setelah setorannya ditimbang." />
          <ul v-else class="divide-y divide-green-50 text-sm">
            <li v-for="c in customers" :key="c.id" class="flex justify-between py-2"><span>{{ c.nama }} <span class="text-xs text-gray-400">{{ c.hp }}</span></span><b class="text-green-800">{{ rp(c.saldo) }}</b></li>
          </ul>
        </div>
        <div class="card">
          <h3 class="mb-3 font-bold text-green-900">Riwayat pencairan</h3>
          <p v-if="!riwayat.length" class="text-sm text-gray-400">Belum ada pencairan.</p>
          <ul v-else class="divide-y divide-green-50 text-sm">
            <li v-for="r in riwayat" :key="r.id" class="flex justify-between py-2"><span>{{ r.customer }}<span class="block text-xs text-gray-400">{{ r.tanggal }} · {{ r.cara }}</span></span><span class="text-right"><b class="text-red-600">−{{ rp(r.nilai) }}</b><span class="block text-xs text-gray-400">sisa {{ rp(r.saldo_setelah) }}</span></span></li>
          </ul>
        </div>
      </div>
    </div>
    <ConfirmModal :show="konfirm" title="Catat pencairan?" :text="`${rp(form.nilai)} untuk ${terpilih?.nama} (${form.cara_pencairan}). Saldo akan langsung berkurang.`" :processing="form.processing" @close="konfirm = false" @confirm="simpan" />
  </AppLayout>
</template>

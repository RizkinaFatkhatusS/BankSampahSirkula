<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import EmptyState from '@/Components/EmptyState.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

defineProps({ mitra: Array })
const form = useForm({ nama: '', kontak: '' })
const editId = ref(null)
const hapus = ref(null)

function edit(m) { editId.value = m.id; form.defaults({ nama: m.nama, kontak: m.kontak }).reset(); form.clearErrors() }
function batal() { editId.value = null; form.defaults({ nama: '', kontak: '' }).reset() }
function simpan() {
  const o = { preserveScroll: true, onSuccess: batal }
  editId.value ? form.patch(route('supplier.mitra.update', editId.value), o) : form.post(route('supplier.mitra.store'), o)
}
const wa = (k) => 'https://wa.me/' + k.replace(/\D/g, '').replace(/^0/, '62')
const konfirmHapus = () => router.delete(route('supplier.mitra.destroy', hapus.value.id), { preserveScroll: true, onFinish: () => (hapus.value = null) })
</script>

<template>
  <AppLayout title="Data Mitra">
    <div class="grid gap-5 lg:grid-cols-3">
      <div class="space-y-3 lg:col-span-2">
        <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Mitra tidak punya akun. Komunikasi dilakukan lewat WhatsApp atau telepon di luar website; di sini hanya data rujukan.</p>
        <EmptyState v-if="!mitra.length" icon="🤝" title="Belum ada mitra" text="Tambahkan mitra pembeli hasil olahan." />
        <ul v-else class="space-y-2">
          <li v-for="m in mitra" :key="m.id" class="card flex flex-wrap items-center gap-3 !p-4">
            <div class="min-w-0 flex-1"><p class="font-semibold">{{ m.nama }}</p><p class="text-sm text-gray-500">{{ m.kontak }} · {{ m.penjualan }} transaksi</p></div>
            <a :href="wa(m.kontak)" target="_blank" rel="noopener" class="btn btn-outline !py-1.5">💬 WhatsApp</a>
            <a :href="`tel:${m.kontak}`" class="btn btn-outline !py-1.5">📞</a>
            <button class="btn btn-outline !py-1.5" @click="edit(m)">Ubah</button>
            <button class="btn btn-outline !border-red-200 !py-1.5 !text-red-600" @click="hapus = m">Hapus</button>
          </li>
        </ul>
      </div>
      <form class="card h-fit space-y-4" @submit.prevent="simpan">
        <h3 class="font-bold text-green-900">{{ editId ? 'Ubah mitra' : 'Tambah mitra' }}</h3>
        <Field label="Nama" :error="form.errors.nama" required><input v-model="form.nama" class="input" required /></Field>
        <Field label="Kontak (WhatsApp / telepon)" :error="form.errors.kontak" required><input v-model="form.kontak" class="input" inputmode="tel" required /></Field>
        <div class="flex gap-2"><button class="btn btn-primary flex-1" :disabled="form.processing">{{ editId ? 'Simpan' : 'Tambah' }}</button><button v-if="editId" type="button" class="btn btn-outline" @click="batal">Batal</button></div>
      </form>
    </div>
    <ConfirmModal :show="!!hapus" title="Hapus mitra?" :text="`${hapus?.nama} akan dihapus.`" confirm-text="Hapus" danger @close="hapus = null" @confirm="konfirmHapus" />
  </AppLayout>
</template>

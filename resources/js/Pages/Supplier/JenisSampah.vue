<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp } from '@/utils'

defineProps({ jenis: Array })

const kosong = () => ({ nama: '', ikon: '♻️', kategori: 'anorganik', harga_per_kg: '', aktif: true })
const form = useForm(kosong())
const editId = ref(null)
const hapus = ref(null)

function edit(j) {
  editId.value = j.id
  form.defaults({ nama: j.nama, ikon: j.ikon, kategori: j.kategori, harga_per_kg: j.harga, aktif: j.aktif }).reset()
  form.clearErrors()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
function batal() { editId.value = null; form.defaults(kosong()).reset(); form.clearErrors() }
function simpan() {
  const opsi = { preserveScroll: true, onSuccess: batal }
  editId.value ? form.patch(route('supplier.jenis.update', editId.value), opsi) : form.post(route('supplier.jenis.store'), opsi)
}
const konfirmasiHapus = () => router.delete(route('supplier.jenis.destroy', hapus.value.id), { preserveScroll: true, onFinish: () => (hapus.value = null) })
</script>

<template>
  <AppLayout title="Jenis Sampah & Harga">
    <div class="grid gap-5 lg:grid-cols-3">
      <form class="card h-fit space-y-4 lg:order-2" @submit.prevent="simpan">
        <h3 class="font-bold text-green-900">{{ editId ? 'Ubah jenis sampah' : 'Tambah jenis sampah' }}</h3>
        <Field label="Nama" :error="form.errors.nama" required><input v-model="form.nama" class="input" required /></Field>
        <div class="grid grid-cols-3 gap-3">
          <Field label="Ikon" :error="form.errors.ikon"><input v-model="form.ikon" class="input text-center" maxlength="4" /></Field>
          <div class="col-span-2"><Field label="Kategori" :error="form.errors.kategori"><select v-model="form.kategori" class="input"><option value="anorganik">Anorganik</option><option value="organik">Organik</option></select></Field></div>
        </div>
        <Field label="Harga beli per kg (Rp)" :error="form.errors.harga_per_kg" hint="Berlaku untuk penimbangan berikutnya. Setoran lama tidak berubah." required><input v-model="form.harga_per_kg" type="number" min="0" step="100" class="input" required /></Field>
        <label class="flex items-center gap-2 text-sm"><input v-model="form.aktif" type="checkbox" class="rounded border-green-300 text-green-700 focus:ring-green-600" /> Aktif (tampil untuk Customer)</label>
        <div class="flex gap-2">
          <button class="btn btn-primary flex-1" :disabled="form.processing">{{ editId ? 'Simpan perubahan' : 'Tambah' }}</button>
          <button v-if="editId" type="button" class="btn btn-outline" @click="batal">Batal</button>
        </div>
      </form>

      <div class="lg:col-span-2 lg:order-1">
        <EmptyState v-if="!jenis.length" icon="🏷️" title="Belum ada jenis sampah" text="Tambahkan jenis sampah beserta harganya agar Customer dapat menyetor." />
        <ul v-else class="space-y-2">
          <li v-for="j in jenis" :key="j.id" class="card flex items-center gap-3 !p-4" :class="{ 'opacity-60': !j.aktif }">
            <span class="text-2xl">{{ j.ikon }}</span>
            <div class="min-w-0 flex-1">
              <p class="font-semibold">{{ j.nama }} <span class="text-xs font-normal capitalize text-gray-400">{{ j.kategori }}</span> <span v-if="!j.aktif" class="rounded bg-gray-200 px-1.5 text-xs">nonaktif</span></p>
              <p class="text-xs text-gray-400">Berlaku sejak {{ j.berlaku_sejak ?? '–' }}</p>
            </div>
            <p class="font-bold text-green-800">{{ rp(j.harga) }}<span class="text-xs font-normal text-gray-400">/kg</span></p>
            <button class="btn btn-outline !px-3 !py-1.5" @click="edit(j)">Ubah</button>
            <button class="btn btn-outline !border-red-200 !px-3 !py-1.5 !text-red-600" @click="hapus = j">Hapus</button>
          </li>
        </ul>
      </div>
    </div>
    <ConfirmModal :show="!!hapus" title="Hapus jenis sampah?" :text="`${hapus?.nama} akan dihapus. Bila sudah pernah dipakai di setoran, jenis ini hanya dinonaktifkan.`" confirm-text="Hapus" danger @close="hapus = null" @confirm="konfirmasiHapus" />
  </AppLayout>
</template>

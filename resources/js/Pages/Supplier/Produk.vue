<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import EmptyState from '@/Components/EmptyState.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { rp, angka, olahanLabel } from '@/utils'

defineProps({ produk: Array })
const edit = ref(null)
const hapus = ref(null)
const form = useForm({ _method: 'patch', nama_produk: '', jumlah: '', satuan: '', harga_jual: '', foto: null })

function buka(p) { edit.value = p; form.defaults({ _method: 'patch', nama_produk: p.nama, jumlah: p.jumlah, satuan: p.satuan, harga_jual: p.harga, foto: null }).reset(); form.clearErrors() }
const simpan = () => form.post(route('supplier.produk.update', edit.value.id), { forceFormData: true, preserveScroll: true, onSuccess: () => (edit.value = null) })
const konfirmHapus = () => router.delete(route('supplier.produk.destroy', hapus.value.id), { preserveScroll: true, onFinish: () => (hapus.value = null) })
</script>

<template>
  <AppLayout title="Produk Hasil Olahan">
    <EmptyState v-if="!produk.length" icon="📦" title="Belum ada produk" text="Produk muncul otomatis saat Anda mencatat barang jadi di menu Olah." />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="p in produk" :key="p.id" class="card space-y-2">
        <img v-if="p.foto" :src="p.foto" alt="" class="h-32 w-full rounded-xl object-cover" />
        <div v-else class="flex h-32 items-center justify-center rounded-xl bg-green-100 text-4xl">📦</div>
        <p class="font-bold text-green-900">{{ p.nama }}</p>
        <p class="text-xs text-gray-500">{{ olahanLabel[p.kategori] }}</p>
        <div class="flex justify-between text-sm"><span>Stok <b :class="p.jumlah <= 0 ? 'text-red-600' : ''">{{ angka(p.jumlah) }} {{ p.satuan }}</b></span><b class="text-green-800">{{ rp(p.harga) }}</b></div>
        <div class="flex gap-2"><button class="btn btn-outline flex-1 !py-1.5" @click="buka(p)">Ubah</button><button class="btn btn-outline !border-red-200 !py-1.5 !text-red-600" @click="hapus = p">Hapus</button></div>
      </div>
    </div>

    <ConfirmModal :show="!!edit" title="Ubah produk" confirm-text="Simpan" :processing="form.processing" @close="edit = null" @confirm="simpan">
      <div class="space-y-3">
        <Field label="Nama produk" :error="form.errors.nama_produk"><input v-model="form.nama_produk" class="input" /></Field>
        <div class="grid grid-cols-3 gap-2">
          <Field label="Stok" :error="form.errors.jumlah"><input v-model="form.jumlah" type="number" step="0.01" min="0" class="input" /></Field>
          <Field label="Satuan" :error="form.errors.satuan"><input v-model="form.satuan" class="input" /></Field>
          <Field label="Harga jual" :error="form.errors.harga_jual"><input v-model="form.harga_jual" type="number" min="0" class="input" /></Field>
        </div>
        <Field label="Foto (opsional)" :error="form.errors.foto"><input type="file" accept="image/jpeg,image/png" class="block w-full text-sm" @change="form.foto = $event.target.files[0]" /></Field>
      </div>
    </ConfirmModal>
    <ConfirmModal :show="!!hapus" title="Hapus produk?" :text="`${hapus?.nama} akan dihapus dari daftar.`" confirm-text="Hapus" danger @close="hapus = null" @confirm="konfirmHapus" />
  </AppLayout>
</template>

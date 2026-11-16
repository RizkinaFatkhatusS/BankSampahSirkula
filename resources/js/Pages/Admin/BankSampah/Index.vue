<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import MapPicker from '@/Components/MapPicker.vue'

const props = defineProps({ bank: Object, q: String, bolehKelola: Boolean })
const cari = ref(props.q ?? '')
const edit = ref(null)
const form = useForm({ status_tayang: 'tayang', alamat: '', latitude: '', longitude: '', alasan: '' })
const buka = (b) => { edit.value = b; form.clearErrors(); form.defaults({ status_tayang: b.status_tayang, alamat: b.alamat, latitude: b.latitude, longitude: b.longitude, alasan: '' }).reset() }
const simpan = () => form.patch(route('admin.bank.update', edit.value.id), { preserveScroll: true, onSuccess: () => (edit.value = null) })
const peta = ({ lat, lng }) => { form.latitude = lat; form.longitude = lng }
</script>

<template>
  <AppLayout title="Bank Sampah">
    <div class="space-y-4">
      <form class="flex gap-2" @submit.prevent="router.get(route('admin.bank.index'), { q: cari || undefined }, { preserveState: true })"><input v-model="cari" type="search" class="input" placeholder="Cari nama atau alamat…" /><button class="btn btn-primary">Cari</button></form>
      <p class="text-xs text-gray-500">Bank sampah hanya tampil pada pencarian Customer bila akun Supplier Aktif, status tayang Tayang, dan memiliki jenis sampah berharga.</p>
      <ul class="space-y-2">
        <li v-for="b in bank.data" :key="b.id" class="card flex flex-wrap items-center justify-between gap-3 !p-4">
          <div class="min-w-0"><p class="font-semibold">{{ b.nama }}</p><p class="text-sm text-gray-500">{{ b.alamat }}</p><p class="text-xs" :class="b.tampil ? 'text-green-700' : 'text-gray-400'">{{ b.tampil ? '✓ Tampil pada pencarian Customer' : 'Tidak tampil pada pencarian' }}</p></div>
          <div class="flex flex-wrap items-center gap-2"><StatusBadge :status="b.status_akun" /><StatusBadge :status="b.status_tayang" />
            <Link v-if="b.supplier_id && $page.props.auth.user.izin.includes('supplier.lihat')" :href="route('admin.supplier.show', b.supplier_id)" class="btn btn-outline !py-1.5">Akun</Link>
            <button v-if="bolehKelola" class="btn btn-outline !py-1.5" @click="buka(b)">Ubah</button></div>
        </li>
      </ul>
      <Pagination :links="bank.links" />
    </div>

    <ConfirmModal :show="!!edit" :title="`Ubah ${edit?.nama}`" confirm-text="Simpan" :processing="form.processing" @close="edit = null" @confirm="simpan">
      <div class="space-y-3 text-sm">
        <div><label class="mb-1 block font-medium">Status tayang</label><select v-model="form.status_tayang" class="input"><option value="tayang">Tayang</option><option value="tidak_tayang">Tidak tayang</option></select></div>
        <div><label class="mb-1 block font-medium">Alamat</label><textarea v-model="form.alamat" rows="2" class="input" /><p v-if="form.errors.alamat" class="text-red-600">{{ form.errors.alamat }}</p></div>
        <MapPicker v-if="edit" :lat="form.latitude" :lng="form.longitude" height="180px" @update="peta" />
        <div><label class="mb-1 block font-medium">Alasan <span v-if="edit && form.status_tayang !== edit.status_tayang" class="text-red-500">*</span></label><input v-model="form.alasan" class="input" /><p v-if="form.errors.alasan" class="text-red-600">{{ form.errors.alasan }}</p></div>
        <p class="text-xs text-gray-500">Data tidak dihapus. Perubahan tercatat pada log audit.</p>
      </div>
    </ConfirmModal>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import PengajuanForm from '@/Components/PengajuanForm.vue'

const props = defineProps({ jenisDokumen: Array, batas: Object })

const form = useForm({
  nama_bank_sampah: '', alamat: '', latitude: '', longitude: '', nomor_kontak: '', jam_operasional: '',
  nama_penanggung_jawab: '', email: '', nomor_hp: '', dokumen: {},
})
const kirim = () => form.post(route('pengajuan.store'), { forceFormData: true, preserveScroll: true })
</script>

<template>
  <GuestLayout lebar>
    <Head title="Daftar sebagai Supplier" />
    <h2 class="text-xl font-bold text-green-900">Daftar sebagai Supplier</h2>
    <p class="mb-5 text-sm text-gray-500">Ajukan bank sampah Anda. Admin akan memeriksa data dan dokumen. Setelah disetujui, tautan aktivasi dikirim ke email penanggung jawab.</p>

    <form class="space-y-5" @submit.prevent="kirim">
      <PengajuanForm :form="form" :jenis-dokumen="jenisDokumen" :batas="batas" />
      <p v-if="Object.keys(form.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">Periksa kembali isian yang bertanda merah.</p>
      <button class="btn btn-primary w-full py-3" :disabled="form.processing">{{ form.processing ? 'Mengirim…' : 'Kirim Pengajuan' }}</button>
    </form>

    <p class="mt-5 text-center text-sm text-gray-500">Sudah mengajukan? <Link :href="route('pengajuan.cek')" class="font-semibold text-green-700 hover:underline">Cek status pengajuan</Link> · <Link :href="route('login')" class="font-semibold text-green-700 hover:underline">Masuk</Link></p>
  </GuestLayout>
</template>

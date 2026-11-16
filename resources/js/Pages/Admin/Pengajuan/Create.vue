<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PengajuanForm from '@/Components/PengajuanForm.vue'

defineProps({ jenisDokumen: Array, batas: Object })
const form = useForm({ nama_bank_sampah: '', alamat: '', latitude: '', longitude: '', nomor_kontak: '', jam_operasional: '', nama_penanggung_jawab: '', email: '', nomor_hp: '', dokumen: {} })
</script>

<template>
  <AppLayout title="Pengajuan Manual">
    <div class="mx-auto max-w-3xl space-y-5">
      <Link :href="route('admin.pengajuan.index')" class="text-sm font-semibold text-green-700 hover:underline">← Antrean pengajuan</Link>
      <p class="rounded-xl bg-blue-50 p-4 text-sm text-blue-900">Pengajuan dibuat atas nama calon Supplier yang tidak dapat mengisi sendiri. <b>Pengajuan ini harus diverifikasi Admin lain</b> (prinsip dua pasang mata). Email konfirmasi dikirim ke calon Supplier.</p>
      <form class="space-y-5" @submit.prevent="form.post(route('admin.pengajuan.store'), { forceFormData: true })">
        <PengajuanForm :form="form" :jenis-dokumen="jenisDokumen" :batas="batas" />
        <button class="btn btn-primary w-full py-3" :disabled="form.processing">Buat pengajuan</button>
      </form>
    </div>
  </AppLayout>
</template>

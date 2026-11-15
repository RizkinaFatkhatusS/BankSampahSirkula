<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MapView from '@/Components/MapView.vue'
import { rp } from '@/utils'

const props = defineProps({ bank: Object, jenisSampah: Array, koordinat: Object })
const koordinat = Object.fromEntries(Object.entries(props.koordinat).filter(([, v]) => v !== null))
</script>

<template>
  <AppLayout title="Detail Bank Sampah">
    <div class="mx-auto max-w-3xl space-y-5">
      <Link :href="route('customer.bank.index', koordinat)" class="text-sm font-semibold text-green-700 hover:underline">← Kembali ke pencarian</Link>

      <div class="card space-y-4">
        <img v-if="bank.foto" :src="bank.foto" alt="" class="h-48 w-full rounded-xl object-cover" />
        <div v-else class="flex h-32 items-center justify-center rounded-xl bg-green-100 text-5xl">🏢</div>
        <div>
          <h2 class="text-xl font-bold text-green-900">{{ bank.nama }}</h2>
          <p v-if="bank.jarak !== null" class="text-sm font-semibold text-amber-700">{{ bank.jarak }} km dari lokasi Anda</p>
        </div>
        <dl class="grid gap-3 text-sm sm:grid-cols-3">
          <div><dt class="text-gray-400">Alamat</dt><dd class="font-medium">{{ bank.alamat }}</dd></div>
          <div><dt class="text-gray-400">Jam operasional</dt><dd class="font-medium">{{ bank.jam }}</dd></div>
          <div><dt class="text-gray-400">Kontak</dt><dd class="font-medium"><a :href="`tel:${bank.kontak}`" class="text-green-700 hover:underline">{{ bank.kontak }}</a></dd></div>
        </dl>
        <MapView :markers="[bank]" height="200px" />
      </div>

      <div class="card">
        <h3 class="font-bold text-green-900">Jenis sampah yang diterima</h3>
        <p class="mb-3 text-xs text-gray-500">Harga contoh; harga berlaku ditetapkan bank sampah saat penimbangan.</p>
        <ul class="grid gap-2 sm:grid-cols-2">
          <li v-for="j in jenisSampah" :key="j.id" class="flex items-center justify-between rounded-xl bg-green-50 px-4 py-3">
            <span>{{ j.ikon }} <b>{{ j.nama }}</b> <span class="text-xs capitalize text-gray-500">({{ j.kategori }})</span></span>
            <span class="font-semibold text-green-800">{{ rp(j.harga) }}/kg</span>
          </li>
        </ul>
      </div>

      <Link :href="route('customer.setoran.create', { bank: bank.id, ...koordinat })" class="btn btn-primary w-full py-3">Pilih Lokasi & Setor Sampah</Link>
    </div>
  </AppLayout>
</template>

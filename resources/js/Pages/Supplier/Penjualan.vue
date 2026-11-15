<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { Link } from '@inertiajs/vue3'
import { rp, angka } from '@/utils'

const props = defineProps({ penjualan: Array, mitra: Array, produk: Array })

const baris = () => ({ produk_olahan_id: '', jumlah: '', harga_jual: '' })
const form = useForm({ mitra_id: '', hasil_komunikasi: 'disetujui', tanggal: new Date().toISOString().slice(0, 10), status_pengiriman: 'diproses', catatan: '', bukti_pembayaran: null, items: [baris()] })

const produkById = (id) => props.produk.find((p) => p.id === Number(id))
function pilihProduk(row) { const p = produkById(row.produk_olahan_id); if (p) row.harga_jual = p.harga }
const total = computed(() => form.items.reduce((a, r) => a + (Number(r.jumlah) || 0) * (Number(r.harga_jual) || 0), 0))
const simpan = () => form.post(route('supplier.penjualan.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => { form.reset('catatan', 'bukti_pembayaran', 'mitra_id'); form.items = [baris()] } })

const ubahStatus = (p, status) => router.patch(route('supplier.penjualan.update', p.id), { status_pengiriman: status }, { preserveScroll: true })
function unggahBukti(p, e) {
  const f = e.target.files[0]
  if (f) router.post(route('supplier.penjualan.update', p.id), { _method: 'patch', status_pengiriman: p.status, bukti_pembayaran: f }, { forceFormData: true, preserveScroll: true })
}
const err = (k) => form.errors[k]
const adaMitra = computed(() => props.mitra.length > 0)
</script>

<template>
  <AppLayout title="Penjualan ke Mitra">
    <div class="grid gap-5 lg:grid-cols-5">
      <form class="card h-fit space-y-4 lg:col-span-2" @submit.prevent="simpan">
        <div><h3 class="font-bold text-green-900">Catat penjualan</h3><p class="text-xs text-gray-500">Pembayaran dilakukan di luar sistem dan tidak mengubah saldo Customer.</p></div>
        <p v-if="!adaMitra" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Belum ada mitra. <Link :href="route('supplier.mitra.index')" class="font-semibold underline">Tambah mitra dulu</Link>.</p>
        <Field label="Mitra" :error="err('mitra_id')" required><select v-model="form.mitra_id" class="input" required><option value="" disabled>Pilih mitra</option><option v-for="m in mitra" :key="m.id" :value="m.id">{{ m.nama }}</option></select></Field>
        <div class="grid grid-cols-2 gap-3">
          <Field label="Hasil komunikasi" :error="err('hasil_komunikasi')"><select v-model="form.hasil_komunikasi" class="input"><option value="disetujui">Setuju beli</option><option value="ditolak">Menolak</option></select></Field>
          <Field label="Tanggal" :error="err('tanggal')" required><input v-model="form.tanggal" type="date" class="input" required /></Field>
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-700">Produk <span class="text-red-500">*</span></p><button type="button" class="text-sm font-semibold text-green-700 hover:underline" @click="form.items.push(baris())">+ Tambah</button></div>
          <p v-if="!produk.length" class="text-sm text-amber-700">Belum ada produk bersetok. Catat pengolahan lebih dulu.</p>
          <div v-for="(r, i) in form.items" :key="i" class="space-y-2 rounded-xl bg-green-50 p-3">
            <select v-model="r.produk_olahan_id" class="input" required @change="pilihProduk(r)"><option value="" disabled>Pilih produk</option><option v-for="p in produk" :key="p.id" :value="p.id">{{ p.nama }} (stok {{ angka(p.stok) }} {{ p.satuan }})</option></select>
            <div class="grid grid-cols-2 gap-2"><input v-model="r.jumlah" type="number" step="0.01" min="0" class="input" placeholder="Jumlah" required /><input v-model="r.harga_jual" type="number" min="0" class="input" placeholder="Harga jual" required /></div>
            <button v-if="form.items.length > 1" type="button" class="text-xs text-red-600 hover:underline" @click="form.items.splice(i, 1)">Hapus baris</button>
            <p v-for="k in ['produk_olahan_id', 'jumlah', 'harga_jual']" :key="k" v-show="err(`items.${i}.${k}`)" class="text-sm text-red-600">{{ err(`items.${i}.${k}`) }}</p>
          </div>
          <p v-if="err('items')" class="text-sm text-red-600">{{ err('items') }}</p>
        </div>
        <div class="flex justify-between rounded-xl bg-amber-50 p-3"><span class="text-sm text-amber-900">Nilai transaksi</span><b class="text-green-800">{{ rp(form.hasil_komunikasi === 'disetujui' ? total : 0) }}</b></div>
        <Field label="Status pengiriman" :error="err('status_pengiriman')"><select v-model="form.status_pengiriman" class="input"><option value="diproses">Diproses</option><option value="dikirim">Dikirim</option><option value="diterima">Diterima mitra</option></select></Field>
        <Field label="Bukti pembayaran (opsional, bisa diunggah nanti)" :error="err('bukti_pembayaran')"><input type="file" accept="image/jpeg,image/png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-green-100 file:px-3 file:py-2 file:font-semibold file:text-green-800" @change="form.bukti_pembayaran = $event.target.files[0]" /></Field>
        <Field label="Catatan" :error="err('catatan')"><textarea v-model="form.catatan" rows="2" class="input" /></Field>
        <button class="btn btn-primary w-full" :disabled="form.processing || !adaMitra">Simpan penjualan</button>
      </form>

      <div class="space-y-3 lg:col-span-3">
        <h3 class="font-bold text-green-900">Riwayat penjualan</h3>
        <EmptyState v-if="!penjualan.length" icon="🧾" title="Belum ada penjualan" text="Penjualan ke mitra tercatat di sini." />
        <div v-for="p in penjualan" :key="p.id" class="card space-y-2 !p-4">
          <div class="flex items-start justify-between gap-2">
            <div><p class="font-semibold">{{ p.mitra }}</p><p class="text-xs text-gray-400">{{ p.tanggal }} · {{ p.hasil === 'disetujui' ? 'Disetujui' : 'Ditolak mitra' }}</p></div>
            <div class="text-right"><p class="font-bold text-green-800">{{ rp(p.nilai) }}</p><StatusBadge :status="p.status" /></div>
          </div>
          <ul class="text-sm text-gray-600"><li v-for="(i, k) in p.items" :key="k">{{ i.nama }} · {{ angka(i.jumlah) }} {{ i.satuan }} × {{ rp(i.harga) }}</li></ul>
          <p v-if="p.catatan" class="text-xs text-gray-500">{{ p.catatan }}</p>
          <div class="flex flex-wrap items-center gap-2 text-sm">
            <select :value="p.status" class="input !w-auto !py-1.5" @change="ubahStatus(p, $event.target.value)"><option value="diproses">Diproses</option><option value="dikirim">Dikirim</option><option value="diterima">Diterima</option></select>
            <a v-if="p.bukti" :href="p.bukti" target="_blank" class="font-semibold text-green-700 hover:underline">Lihat bukti bayar</a>
            <label v-else class="cursor-pointer font-semibold text-amber-700 hover:underline">Unggah bukti bayar<input type="file" accept="image/jpeg,image/png" class="hidden" @change="unggahBukti(p, $event)" /></label>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

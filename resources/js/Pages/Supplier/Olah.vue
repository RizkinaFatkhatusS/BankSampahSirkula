<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'
import EmptyState from '@/Components/EmptyState.vue'
import { rp, angka, olahanLabel } from '@/utils'

defineProps({ pengolahan: Array, setoranTersedia: Array })
const master = usePage().props.master ?? { kategori_sampah: { organik: 'Organik', anorganik: 'Anorganik' }, kategori_olahan: olahanLabel, satuan: {}, label_olahan: olahanLabel }
const labelOlahan = (k) => master.label_olahan?.[k] ?? olahanLabel[k] ?? k

const baris = () => ({ nama_produk: '', jumlah: '', satuan: 'botol', harga_jual: '' })
const form = useForm({
  kategori_sampah: 'organik', jenis_olahan: 'pupuk_cair', berat_diolah: '', tanggal: new Date().toISOString().slice(0, 10),
  catatan: '', foto_dokumentasi: null, setoran_ids: [], produk: [baris()],
})
const preview = ref(null)
function onFile(e) { const f = e.target.files[0]; if (f) { form.foto_dokumentasi = f; preview.value = URL.createObjectURL(f) } }
const simpan = () => form.post(route('supplier.pengolahan.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => { form.reset('catatan', 'foto_dokumentasi', 'setoran_ids', 'berat_diolah'); form.produk = [baris()]; preview.value = null } })
const err = (k) => form.errors[k]
</script>

<template>
  <AppLayout title="Pemilahan & Pengolahan">
    <div class="grid gap-5 lg:grid-cols-5">
      <form class="card h-fit space-y-4 lg:col-span-3" @submit.prevent="simpan">
        <h3 class="font-bold text-green-900">Catat pengolahan & barang jadi</h3>
        <div class="grid gap-3 sm:grid-cols-2">
          <Field label="Kategori sampah (pemilahan)" :error="err('kategori_sampah')"><select v-model="form.kategori_sampah" class="input"><option v-for="(nama, kode) in master.kategori_sampah" :key="kode" :value="kode">{{ nama }}</option></select></Field>
          <Field label="Jenis olahan" :error="err('jenis_olahan')"><select v-model="form.jenis_olahan" class="input"><option v-for="(l, k) in master.kategori_olahan" :key="k" :value="k">{{ l }}</option></select></Field>
          <Field label="Tanggal" :error="err('tanggal')" required><input v-model="form.tanggal" type="date" class="input" required /></Field>
          <Field label="Berat bahan diolah (kg)" :error="err('berat_diolah')"><input v-model="form.berat_diolah" type="number" step="0.01" min="0" class="input" /></Field>
        </div>

        <Field label="Asal bahan (setoran yang sudah ditimbang)" hint="Opsional, untuk menelusuri hasil olahan ke setoran asalnya." :error="err('setoran_ids')">
          <div class="max-h-36 space-y-1 overflow-y-auto rounded-xl border border-green-200 p-2">
            <p v-if="!setoranTersedia.length" class="p-1 text-sm text-gray-400">Belum ada setoran selesai.</p>
            <label v-for="s in setoranTersedia" :key="s.id" class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-green-50">
              <input v-model="form.setoran_ids" type="checkbox" :value="s.id" class="rounded border-green-300 text-green-700 focus:ring-green-600" /> {{ s.label }}
            </label>
          </div>
        </Field>

        <div class="space-y-2">
          <div class="flex items-center justify-between"><p class="text-sm font-medium text-gray-700">Barang jadi <span class="text-red-500">*</span></p><button type="button" class="text-sm font-semibold text-green-700 hover:underline" @click="form.produk.push(baris())">+ Tambah barang</button></div>
          <div v-for="(p, i) in form.produk" :key="i" class="grid grid-cols-12 gap-2 rounded-xl bg-green-50 p-3">
            <input v-model="p.nama_produk" class="input col-span-12 sm:col-span-5" placeholder="Nama produk" required />
            <input v-model="p.jumlah" type="number" step="0.01" min="0" class="input col-span-4 sm:col-span-2" placeholder="Jumlah" required />
            <input v-model="p.satuan" list="daftar-satuan" class="input col-span-4 sm:col-span-2" placeholder="Satuan" required />
            <input v-model="p.harga_jual" type="number" min="0" class="input col-span-4 sm:col-span-3" placeholder="Harga jual" required />
            <button v-if="form.produk.length > 1" type="button" class="col-span-12 text-left text-xs text-red-600 hover:underline" @click="form.produk.splice(i, 1)">Hapus baris</button>
            <p v-for="k in ['nama_produk', 'jumlah', 'satuan', 'harga_jual']" :key="k" v-show="err(`produk.${i}.${k}`)" class="col-span-12 text-sm text-red-600">{{ err(`produk.${i}.${k}`) }}</p>
          </div>
          <p v-if="err('produk')" class="text-sm text-red-600">{{ err('produk') }}</p>
        </div>

        <datalist id="daftar-satuan"><option v-for="(nama, kode) in master.satuan" :key="kode" :value="nama" /></datalist>
        <Field label="Foto dokumentasi" :error="err('foto_dokumentasi')" required>
          <img v-if="preview" :src="preview" class="mb-2 h-32 rounded-lg object-cover" alt="" />
          <input type="file" accept="image/jpeg,image/png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-green-100 file:px-3 file:py-2 file:font-semibold file:text-green-800" @change="onFile" />
        </Field>
        <Field label="Catatan" :error="err('catatan')"><textarea v-model="form.catatan" rows="2" class="input" /></Field>
        <button class="btn btn-primary w-full" :disabled="form.processing">Simpan pengolahan</button>
      </form>

      <div class="space-y-3 lg:col-span-2">
        <h3 class="font-bold text-green-900">Riwayat pengolahan</h3>
        <EmptyState v-if="!pengolahan.length" icon="♻️" title="Belum ada pengolahan" text="Catatan pengolahan tampil di sini." />
        <div v-for="p in pengolahan" :key="p.id" class="card space-y-2 !p-4">
          <div class="flex gap-3">
            <img v-if="p.foto" :src="p.foto" alt="" class="h-16 w-16 rounded-xl object-cover" />
            <div class="text-sm"><p class="font-semibold text-green-900">{{ labelOlahan(p.jenis_olahan) }} · <span class="capitalize">{{ p.kategori_sampah }}</span></p><p class="text-xs text-gray-400">{{ p.tanggal }}<span v-if="p.berat"> · {{ angka(p.berat) }} kg bahan</span></p><p v-if="p.setoran.length" class="text-xs text-gray-400">Asal: setoran {{ p.setoran.map((x) => '#' + x).join(', ') }}</p></div>
          </div>
          <ul class="text-sm"><li v-for="x in p.produk" :key="x.id" class="flex justify-between"><span>{{ x.nama }} ({{ angka(x.jumlah) }} {{ x.satuan }})</span><span class="text-gray-500">{{ rp(x.harga) }}</span></li></ul>
          <p v-if="p.catatan" class="text-xs text-gray-500">{{ p.catatan }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

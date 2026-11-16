<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Field from '@/Components/Field.vue'

const props = defineProps({ dokumen: Array, kategori: Object, judulGrup: Object, bolehKelola: Boolean })

const baruDok = useForm({ grup: 'dokumen', nama: '', keterangan: '', wajib: true, aktif: true, urutan: 10, min_berkas: 1 })
const tambahDok = () => baruDok.post(route('admin.master.store'), { preserveScroll: true, onSuccess: () => baruDok.reset('nama', 'keterangan') })
const ubahDok = (d, ubah = {}) => router.patch(route('admin.master.update', { grup: 'dokumen', id: d.id }), { nama: d.nama, keterangan: d.keterangan, wajib: d.wajib, aktif: d.aktif, urutan: d.urutan, min_berkas: d.min_berkas, ...ubah }, { preserveScroll: true })

const baru = ref({ kategori_sampah: '', kategori_olahan: '', satuan: '' })
const tambah = (grup) => { if (!baru.value[grup].trim()) return; router.post(route('admin.master.store'), { grup, nama: baru.value[grup] }, { preserveScroll: true, onSuccess: () => (baru.value[grup] = '') }) }
const ganti = (grup, m) => { const nama = window.prompt('Ubah nama', m.nama); if (nama && nama.trim() && nama !== m.nama) ubahKat(grup, m, { nama: nama.trim() }) }
const gantiDok = (d) => { const nama = window.prompt('Ubah nama dokumen', d.nama); if (nama && nama.trim() && nama !== d.nama) ubahDok(d, { nama: nama.trim() }) }
const ubahKat = (grup, m, ubah = {}) => router.patch(route('admin.master.update', { grup, id: m.id }), { nama: m.nama, aktif: m.aktif, ...ubah }, { preserveScroll: true })
</script>

<template>
  <AppLayout title="Data Master">
    <div class="space-y-6">
      <p class="rounded-xl bg-green-100 px-4 py-2 text-sm text-green-900">Data master tidak dihapus, hanya dinonaktifkan. Perubahan tidak mengubah pengajuan dan transaksi yang sudah tersimpan. Harga sampah ditetapkan masing-masing Supplier, bukan di sini.</p>

      <section class="card space-y-3">
        <h3 class="font-bold text-green-900">Persyaratan dokumen pendaftaran Supplier</h3>
        <ul class="divide-y divide-green-50 text-sm">
          <li v-for="d in dokumen" :key="d.id" class="flex flex-wrap items-center gap-3 py-3" :class="{ 'opacity-60': !d.aktif }">
            <div class="min-w-0 flex-1"><p class="font-semibold">{{ d.nama }}</p><p class="text-xs text-gray-500">{{ d.keterangan }}</p></div>
            <span class="text-xs text-gray-500">{{ d.wajib ? 'Wajib' : 'Opsional' }} · min {{ d.min_berkas }} berkas · urutan {{ d.urutan }}</span>
            <template v-if="bolehKelola">
              <button class="rounded-lg bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-700" @click="gantiDok(d)">Ubah nama</button>
              <button class="rounded-lg bg-green-50 px-3 py-1 text-xs font-semibold text-green-800" @click="ubahDok(d, { wajib: !d.wajib })">{{ d.wajib ? 'Jadikan opsional' : 'Jadikan wajib' }}</button>
              <button class="rounded-lg px-3 py-1 text-xs font-semibold" :class="d.aktif ? 'bg-gray-100 text-gray-700' : 'bg-green-700 text-white'" @click="ubahDok(d, { aktif: !d.aktif })">{{ d.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </template>
          </li>
        </ul>
        <form v-if="bolehKelola" class="grid gap-3 rounded-xl bg-green-50 p-4 sm:grid-cols-6" @submit.prevent="tambahDok">
          <div class="sm:col-span-2"><Field label="Nama dokumen" :error="baruDok.errors.nama"><input v-model="baruDok.nama" class="input" required /></Field></div>
          <div class="sm:col-span-2"><Field label="Keterangan" :error="baruDok.errors.keterangan"><input v-model="baruDok.keterangan" class="input" /></Field></div>
          <Field label="Min. berkas"><input v-model="baruDok.min_berkas" type="number" min="1" class="input" /></Field>
          <Field label="Urutan"><input v-model="baruDok.urutan" type="number" min="0" class="input" /></Field>
          <label class="flex items-center gap-2 text-sm sm:col-span-4"><input v-model="baruDok.wajib" type="checkbox" class="rounded border-green-300 text-green-700" /> Wajib</label>
          <button class="btn btn-primary sm:col-span-2" :disabled="baruDok.processing">Tambah dokumen</button>
        </form>
      </section>

      <section v-for="(daftar, grup) in kategori" :key="grup" class="card space-y-3">
        <h3 class="font-bold text-green-900">{{ judulGrup[grup] }}</h3>
        <ul class="flex flex-wrap gap-2">
          <li v-for="m in daftar" :key="m.id" class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm" :class="m.aktif ? 'bg-green-50 text-green-900' : 'bg-gray-100 text-gray-400 line-through'">
            {{ m.nama }}
            <button v-if="bolehKelola" class="text-xs font-semibold hover:underline" @click="ganti(grup, m)">ubah nama</button>
            <button v-if="bolehKelola" class="text-xs font-semibold hover:underline" @click="ubahKat(grup, m, { aktif: !m.aktif })">{{ m.aktif ? 'nonaktifkan' : 'aktifkan' }}</button>
          </li>
        </ul>
        <form v-if="bolehKelola" class="flex gap-2" @submit.prevent="tambah(grup)"><input v-model="baru[grup]" class="input" placeholder="Tambah baru…" /><button class="btn btn-outline">Tambah</button></form>
      </section>
    </div>
  </AppLayout>
</template>

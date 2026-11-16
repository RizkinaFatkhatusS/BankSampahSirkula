<script setup>
// Formulir data bank sampah dan dokumen verifikasi. Dipakai pendaftaran publik, pengajuan manual Admin, dan revisi.
import { computed } from 'vue'
import Field from '@/Components/Field.vue'
import MapPicker from '@/Components/MapPicker.vue'

const props = defineProps({
  form: Object,
  jenisDokumen: Array,
  batas: Object,
  // Pada revisi: hanya dokumen berikut yang boleh diganti (id jenis dokumen); kosong = semua dokumen
  hanyaJenis: { type: Array, default: null },
  emailTerkunci: Boolean,
})

const daftar = computed(() => (props.hanyaJenis ? props.jenisDokumen.filter((j) => props.hanyaJenis.includes(j.id)) : props.jenisDokumen))
const galat = (id) => Object.entries(props.form.errors).filter(([k]) => k === `dokumen.${id}` || k.startsWith(`dokumen.${id}.`)).map(([, v]) => v)
const pilih = (id, e) => { props.form.dokumen = { ...props.form.dokumen, [id]: Array.from(e.target.files) } }
const jumlah = (id) => (props.form.dokumen?.[id] ?? []).length
const peta = ({ lat, lng }) => { props.form.latitude = lat; props.form.longitude = lng }
const suratUrl = computed(() => route('pengajuan.surat', { nama: props.form.nama_penanggung_jawab || undefined, bank: props.form.nama_bank_sampah || undefined }))
</script>

<template>
  <div class="space-y-6">
    <section class="card space-y-4">
      <h3 class="font-bold text-green-900">Data bank sampah</h3>
      <Field label="Nama bank sampah" :error="form.errors.nama_bank_sampah" required><input v-model="form.nama_bank_sampah" class="input" required /></Field>
      <Field label="Alamat lengkap" :error="form.errors.alamat" required><textarea v-model="form.alamat" rows="2" class="input" required /></Field>
      <Field label="Titik lokasi di peta" :error="form.errors.latitude || form.errors.longitude" hint="Ketuk peta atau geser penanda; titik ini dipakai untuk pencarian dan jarak." required>
        <MapPicker :lat="form.latitude" :lng="form.longitude" @update="peta" />
      </Field>
      <div class="grid gap-4 sm:grid-cols-2">
        <Field label="Nomor kontak bank sampah" :error="form.errors.nomor_kontak" required><input v-model="form.nomor_kontak" class="input" inputmode="tel" required /></Field>
        <Field label="Jam operasional" :error="form.errors.jam_operasional" required><input v-model="form.jam_operasional" class="input" placeholder="08.00 – 16.00 WIB" required /></Field>
      </div>
    </section>

    <section class="card space-y-4">
      <h3 class="font-bold text-green-900">Penanggung jawab</h3>
      <Field label="Nama penanggung jawab" :error="form.errors.nama_penanggung_jawab" required><input v-model="form.nama_penanggung_jawab" class="input" required /></Field>
      <div class="grid gap-4 sm:grid-cols-2">
        <Field label="Email" :error="form.errors.email" hint="Akun Supplier dan tautan aktivasi dikirim ke email ini." required><input v-model="form.email" type="email" class="input" :readonly="emailTerkunci" :class="{ 'bg-gray-50': emailTerkunci }" required /></Field>
        <Field label="Nomor HP" :error="form.errors.nomor_hp" hint="Contoh: 081234567890" required><input v-model="form.nomor_hp" class="input" inputmode="tel" required /></Field>
      </div>
    </section>

    <section class="card space-y-4">
      <div>
        <h3 class="font-bold text-green-900">Dokumen verifikasi</h3>
        <p class="text-xs text-gray-500">Format {{ batas.format }}, maksimal {{ batas.kb }} KB per berkas. Dokumen bersifat rahasia dan hanya dilihat Admin yang berwenang.</p>
        <p v-if="hanyaJenis" class="mt-1 rounded-lg bg-amber-50 p-2 text-xs text-amber-800">Unggah ulang hanya dokumen yang diminta diperbaiki.</p>
      </div>
      <div v-for="j in daftar" :key="j.id" class="rounded-xl bg-green-50 p-4">
        <p class="font-semibold text-green-900">{{ j.nama }} <span v-if="j.wajib && !hanyaJenis" class="text-red-500">*</span><span v-else-if="!j.wajib" class="text-xs font-normal text-gray-400">(opsional)</span></p>
        <p v-if="j.keterangan" class="text-xs text-gray-500">{{ j.keterangan }}</p>
        <p v-if="j.min_berkas > 1" class="text-xs text-gray-500">Minimal {{ j.min_berkas }} berkas.</p>
        <a v-if="j.nama.toLowerCase().includes('surat pernyataan')" :href="suratUrl" target="_blank" class="mt-1 inline-block text-sm font-semibold text-green-700 hover:underline">⬇ Buka format surat pernyataan</a>
        <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-green-100 file:px-3 file:py-2 file:font-semibold file:text-green-800" @change="pilih(j.id, $event)" />
        <p v-if="jumlah(j.id)" class="mt-1 text-xs text-green-700">{{ jumlah(j.id) }} berkas dipilih</p>
        <p v-for="(g, i) in galat(j.id)" :key="i" class="mt-1 text-sm text-red-600">{{ g }}</p>
      </div>
      <p v-if="form.errors.dokumen" class="text-sm text-red-600">{{ form.errors.dokumen }}</p>
    </section>
  </div>
</template>

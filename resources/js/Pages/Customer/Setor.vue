<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MapView from '@/Components/MapView.vue'
import { rp } from '@/utils'

const props = defineProps({ bank: Object, bankTerdekat: Object, jenisSampah: Array })

const langkah = ['Jenis & Foto', 'Metode', 'Konfirmasi']
const step = ref(1)
const preview = ref(null)
const fileError = ref('')
const jadwalOpsi = ['09.00 – 11.00', '13.00 – 15.00', '15.30 – 17.00']

const form = useForm({
  bank_sampah_id: props.bank.id,
  jenis_sampah_ids: [],
  foto_bukti: null,
  metode_penyerahan: 'dijemput_kurir',
  alamat_jemput: '',
  jadwal_jemput: '',
})

const terpilih = computed(() => props.jenisSampah.filter((j) => form.jenis_sampah_ids.includes(j.id)))
// Estimasi = jumlah harga per kg tiap jenis terpilih. Customer tidak mengisi berat (PRD FR-08 / OI-22).
const estimasi = computed(() => terpilih.value.reduce((s, j) => s + j.harga, 0))

function toggle(id) {
  const i = form.jenis_sampah_ids.indexOf(id)
  i >= 0 ? form.jenis_sampah_ids.splice(i, 1) : form.jenis_sampah_ids.push(id)
}
function onFile(e) {
  const f = e.target.files[0]
  fileError.value = ''
  if (!f) return
  if (!['image/jpeg', 'image/png'].includes(f.type)) return (fileError.value = 'Format harus JPG atau PNG.')
  if (f.size > 2 * 1024 * 1024) return (fileError.value = 'Ukuran foto maksimal 2 MB.')
  form.foto_bukti = f
  preview.value = URL.createObjectURL(f)
}
const bisaLanjut = computed(() => {
  if (step.value === 1) return terpilih.value.length > 0 && !!form.foto_bukti // foto wajib
  if (step.value === 2) return form.metode_penyerahan === 'antar_sendiri' || (form.alamat_jemput.trim() && form.jadwal_jemput)
  return true
})
const markers = computed(() => [{ ...props.bank, warna: 'hijau' }, ...(props.bankTerdekat ? [{ ...props.bankTerdekat, warna: 'biru' }] : [])])

function kirim() {
  form.post(route('customer.setoran.store'), {
    forceFormData: true,
    onError: (e) => { step.value = e.foto_bukti || e.jenis_sampah_ids ? 1 : e.alamat_jemput || e.jadwal_jemput ? 2 : step.value },
  })
}
</script>

<template>
  <AppLayout title="Setor Sampah">
    <div class="mx-auto max-w-3xl space-y-5">
      <ol class="flex items-center gap-2">
        <li v-for="(l, i) in langkah" :key="l" class="flex flex-1 items-center gap-2">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
            :class="step > i + 1 ? 'bg-green-700 text-white' : step === i + 1 ? 'bg-amber-400 text-green-950' : 'bg-green-100 text-green-700'">{{ step > i + 1 ? '✓' : i + 1 }}</span>
          <span class="hidden text-sm font-medium sm:block" :class="step === i + 1 ? 'text-green-900' : 'text-gray-400'">{{ l }}</span>
          <span v-if="i < 2" class="h-0.5 flex-1 bg-green-100" />
        </li>
      </ol>

      <div class="rounded-xl bg-green-100 px-4 py-3 text-sm text-green-900">📍 Bank sampah: <b>{{ bank.nama }}</b> · {{ bank.alamat }}</div>

      <!-- Langkah 1: jenis + foto + estimasi -->
      <section v-if="step === 1" class="card space-y-5">
        <div>
          <h2 class="font-bold text-green-900">Pilih jenis sampah</h2>
          <p class="text-sm text-gray-500">Boleh lebih dari satu. Anda tidak perlu mengisi berat, petugas akan menimbangnya.</p>
        </div>
        <p v-if="!jenisSampah.length" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Bank sampah ini belum menetapkan jenis sampah. <Link :href="route('customer.bank.index')" class="font-semibold underline">Pilih bank lain</Link>.</p>
        <div class="grid grid-cols-2 gap-3">
          <button v-for="j in jenisSampah" :key="j.id" type="button" class="rounded-xl border-2 p-4 text-left transition" :class="form.jenis_sampah_ids.includes(j.id) ? 'border-green-600 bg-green-50' : 'border-green-100 hover:border-green-300'" @click="toggle(j.id)">
            <span class="text-2xl">{{ j.ikon }}</span>
            <p class="mt-1 font-semibold">{{ j.nama }}</p>
            <p class="text-sm text-green-700">{{ rp(j.harga) }}/kg</p>
          </button>
        </div>
        <p v-if="form.errors.jenis_sampah_ids" class="text-sm text-red-600">{{ form.errors.jenis_sampah_ids }}</p>

        <div>
          <h3 class="font-semibold text-green-900">Foto sampah <span class="text-red-500">*</span></h3>
          <label class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-green-300 bg-green-50 p-6 text-center hover:bg-green-100">
            <img v-if="preview" :src="preview" class="h-40 rounded-lg object-cover" alt="Pratinjau foto" />
            <template v-else><span class="text-3xl">📷</span><span class="text-sm text-gray-600">Ketuk untuk unggah foto (JPG/PNG, maks 2 MB)</span></template>
            <input type="file" accept="image/jpeg,image/png" class="hidden" @change="onFile" />
          </label>
          <p v-if="fileError || form.errors.foto_bukti" class="mt-1 text-sm text-red-600">{{ fileError || form.errors.foto_bukti }}</p>
        </div>

        <div v-if="terpilih.length" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
          <p class="font-semibold">Estimasi nilai: {{ rp(estimasi) }} <span class="font-normal">(jumlah harga per 1 kg tiap jenis)</span></p>
          <p class="mt-1 text-xs">Ini hanya perkiraan. Nilai pasti ditetapkan setelah sampah ditimbang.</p>
        </div>
      </section>

      <!-- Langkah 2: metode -->
      <section v-if="step === 2" class="card space-y-5">
        <h2 class="font-bold text-green-900">Metode penyerahan</h2>
        <div class="grid gap-3 sm:grid-cols-2">
          <button v-for="m in [['dijemput_kurir', '🛵 Dijemput Kurir', 'Kurir bank sampah datang ke alamat Anda'], ['antar_sendiri', '🚶 Antar Sendiri', 'Anda datang langsung ke bank sampah']]" :key="m[0]" type="button"
            class="rounded-xl border-2 p-4 text-left" :class="form.metode_penyerahan === m[0] ? 'border-green-600 bg-green-50' : 'border-green-100'" @click="form.metode_penyerahan = m[0]">
            <p class="font-semibold">{{ m[1] }}</p><p class="text-sm text-gray-500">{{ m[2] }}</p>
          </button>
        </div>

        <div v-if="form.metode_penyerahan === 'dijemput_kurir'" class="space-y-3">
          <div>
            <label class="text-sm font-medium">Alamat penjemputan <span class="text-red-500">*</span></label>
            <textarea v-model="form.alamat_jemput" rows="3" class="input mt-1" placeholder="Nama jalan, nomor rumah, patokan…" />
            <p v-if="form.errors.alamat_jemput" class="mt-1 text-sm text-red-600">{{ form.errors.alamat_jemput }}</p>
          </div>
          <div>
            <label class="text-sm font-medium">Jadwal penjemputan <span class="text-red-500">*</span></label>
            <div class="mt-1 flex flex-wrap gap-2">
              <button v-for="j in jadwalOpsi" :key="j" type="button" class="rounded-lg px-4 py-2 text-sm font-medium" :class="form.jadwal_jemput === j ? 'bg-green-700 text-white' : 'bg-green-50 text-green-900 ring-1 ring-green-200'" @click="form.jadwal_jemput = j">{{ j }}</button>
            </div>
            <p v-if="form.errors.jadwal_jemput" class="mt-1 text-sm text-red-600">{{ form.errors.jadwal_jemput }}</p>
          </div>
        </div>

        <div v-else class="space-y-3">
          <MapView :markers="markers" :selected-id="bank.id" height="220px" />
          <div class="rounded-xl bg-green-50 p-4 text-sm">
            <p class="font-semibold text-green-900">{{ bank.nama }}</p>
            <p>{{ bank.alamat }}</p><p class="text-gray-500">Jam operasional {{ bank.jam }} · {{ bank.kontak }}</p>
          </div>
          <p v-if="bankTerdekat" class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">
            ℹ️ Bank sampah terdekat dari lokasi Anda adalah <b>{{ bankTerdekat.nama }}</b> ({{ bankTerdekat.jarak }} km, penanda biru). Setoran tetap ditujukan ke <b>{{ bank.nama }}</b>.
          </p>
        </div>
      </section>

      <!-- Langkah 3: konfirmasi -->
      <section v-if="step === 3" class="card space-y-4">
        <h2 class="font-bold text-green-900">Periksa & konfirmasi setoran</h2>
        <ul class="divide-y divide-green-50 text-sm">
          <li v-for="j in terpilih" :key="j.id" class="flex justify-between py-2"><span>{{ j.ikon }} {{ j.nama }}</span><span>{{ rp(j.harga) }}/kg</span></li>
          <li class="flex justify-between py-2 font-bold"><span>Estimasi nilai</span><span class="text-green-700">{{ rp(estimasi) }}</span></li>
        </ul>
        <img v-if="preview" :src="preview" class="h-32 rounded-lg object-cover" alt="Foto sampah" />
        <p class="text-sm"><b>Metode:</b> {{ form.metode_penyerahan === 'dijemput_kurir' ? 'Dijemput kurir, ' + form.jadwal_jemput : 'Antar sendiri' }}</p>
        <p v-if="form.metode_penyerahan === 'dijemput_kurir'" class="text-sm"><b>Alamat:</b> {{ form.alamat_jemput }}</p>
        <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">Nilai di atas adalah estimasi. Nilai final dihitung dari berat aktual hasil penimbangan petugas.</p>
        <p v-if="Object.keys(form.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">Periksa kembali data setoran Anda.</p>
      </section>

      <div class="flex justify-between">
        <button v-if="step > 1" type="button" class="btn btn-outline" @click="step--">Kembali</button>
        <Link v-else :href="route('customer.bank.index')" class="btn btn-outline">Ganti Lokasi</Link>
        <button v-if="step < 3" type="button" class="btn btn-primary" :disabled="!bisaLanjut" @click="step++">Lanjut</button>
        <button v-else type="button" class="btn btn-primary" :disabled="form.processing" @click="kirim">Konfirmasi Setoran</button>
      </div>
    </div>
  </AppLayout>
</template>

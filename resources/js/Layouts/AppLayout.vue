<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

defineProps({ title: { type: String, default: 'SIRKULA' } })

const page = usePage()
const user = computed(() => page.props.auth.user)
const isSupplier = computed(() => user.value?.role === 'supplier')
const notifCount = computed(() => page.props.notifBelumDibaca ?? 0)
const flash = computed(() => page.props.flash ?? {})

// Menu mengikuti role (PRD 3.2): Customer dan Supplier tidak berbagi menu.
const menuCustomer = [
  { label: 'Beranda', route: 'customer.beranda', match: 'customer.beranda', icon: '🏠' },
  { label: 'Cari', route: 'customer.bank.index', match: 'customer.bank.*', icon: '📍' },
  { label: 'Setor', route: 'customer.setoran.create', match: 'customer.setoran.*', icon: '♻️' },
  { label: 'Data', route: 'customer.riwayat', match: 'customer.riwayat', icon: '🕘' },
]
const menuSupplier = [
  { label: 'Beranda', route: 'supplier.beranda', match: 'supplier.beranda', icon: '🏠', utama: true },
  { label: 'Setoran', route: 'supplier.setoran.index', match: 'supplier.setoran.*', icon: '⚖️', utama: true },
  { label: 'Olah', route: 'supplier.pengolahan.index', match: 'supplier.pengolahan.*', icon: '♻️', utama: true },
  { label: 'Penjualan', route: 'supplier.penjualan.index', match: 'supplier.penjualan.*', icon: '🧾', utama: true },
  { label: 'Statistik', route: 'supplier.dashboard', match: 'supplier.dashboard', icon: '📊' },
  { label: 'Produk', route: 'supplier.produk.index', match: 'supplier.produk.*', icon: '📦' },
  { label: 'Mitra', route: 'supplier.mitra.index', match: 'supplier.mitra.*', icon: '🤝' },
  { label: 'Pencairan', route: 'supplier.pencairan.index', match: 'supplier.pencairan.*', icon: '💸' },
  { label: 'Jenis & Harga', route: 'supplier.jenis.index', match: 'supplier.jenis.*', icon: '🏷️' },
  { label: 'Laporan', route: 'supplier.laporan.index', match: 'supplier.laporan.*', icon: '📑' },
  { label: 'Profil Bank', route: 'supplier.profil', match: 'supplier.profil', icon: '🏢' },
]
const menus = computed(() => (isSupplier.value ? menuSupplier : menuCustomer))
const menuMobile = computed(() => (isSupplier.value ? menuSupplier.filter((m) => m.utama) : menuCustomer))
const menuLainnya = computed(() => (isSupplier.value ? menuSupplier.filter((m) => !m.utama) : []))
const active = (m) => route().current(m)
const rutaNotif = computed(() => route(isSupplier.value ? 'supplier.notifikasi' : 'customer.notifikasi'))
const tanggal = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })

const sheet = ref(false)
const toast = ref(null)
let timer
watch(
  flash,
  (f) => {
    const t = f.success ? { tipe: 'ok', pesan: f.success } : f.error ? { tipe: 'err', pesan: f.error } : null
    toast.value = t
    clearTimeout(timer)
    if (t) timer = setTimeout(() => (toast.value = null), 5000)
  },
  { immediate: true, deep: true },
)
</script>

<template>
  <Head :title="title" />
  <div class="min-h-screen bg-green-50/70 text-gray-800">
    <!-- Sidebar desktop -->
    <aside class="fixed inset-y-0 left-0 hidden w-64 flex-col overflow-y-auto border-r border-green-100 bg-white p-4 md:flex">
      <Link :href="route('dashboard')" class="flex items-center gap-3 px-2 py-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-700 text-xl text-white">♻</span>
        <span><b class="block leading-tight text-green-900">SIRKULA</b><small class="text-gray-500">Bank Sampah Sirkular</small></span>
      </Link>
      <p class="mb-2 mt-6 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ isSupplier ? 'Menu Supplier' : 'Menu Customer' }}</p>
      <nav class="space-y-1">
        <Link v-for="m in menus" :key="m.route" :href="route(m.route)"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
          :class="active(m.match) ? 'bg-green-700 text-white shadow' : 'text-gray-600 hover:bg-green-50'">
          <span>{{ m.icon }}</span>{{ m.label }}
        </Link>
      </nav>
      <div class="mt-auto pt-6">
        <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-xl bg-green-50 p-3 hover:bg-green-100">
          <span class="flex h-9 w-9 items-center justify-center rounded-full bg-green-700 font-bold text-white">{{ user.nama?.[0] }}</span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">{{ user.nama }}</p>
            <p class="text-xs capitalize text-gray-500">{{ user.role }}</p>
          </div>
        </Link>
        <Link :href="route('logout')" method="post" as="button" class="mt-2 w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Keluar</Link>
      </div>
    </aside>

    <div class="pb-24 md:ml-64 md:pb-0">
      <header class="sticky top-0 z-20 flex items-center justify-between border-b border-green-100 bg-white/90 px-4 py-3 backdrop-blur md:px-8">
        <div>
          <p class="text-xs text-gray-500">Sirkula</p>
          <h1 class="text-lg font-bold text-green-900">{{ title }}</h1>
        </div>
        <div class="flex items-center gap-2">
          <span class="hidden rounded-full bg-green-50 px-3 py-1 text-xs text-gray-600 lg:block">{{ tanggal }}</span>
          <Link :href="rutaNotif" class="relative flex h-10 w-10 items-center justify-center rounded-full bg-green-50 text-lg hover:bg-green-100" aria-label="Notifikasi">
            🔔
            <span v-if="notifCount" class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ notifCount > 9 ? '9+' : notifCount }}</span>
          </Link>
          <Link :href="route('profile.edit')" class="flex h-10 w-10 items-center justify-center rounded-full bg-green-700 font-bold text-white md:hidden" aria-label="Profil">{{ user.nama?.[0] }}</Link>
        </div>
      </header>

      <!-- Toast -->
      <transition enter-active-class="transition duration-200" enter-from-class="-translate-y-2 opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
        <div v-if="toast" class="fixed inset-x-4 top-16 z-40 mx-auto max-w-md rounded-xl px-4 py-3 text-sm font-medium shadow-lg md:top-4"
          :class="toast.tipe === 'ok' ? 'bg-green-700 text-white' : 'bg-red-600 text-white'" role="status">
          <div class="flex items-start justify-between gap-3"><span>{{ toast.pesan }}</span><button class="opacity-70 hover:opacity-100" @click="toast = null">✕</button></div>
        </div>
      </transition>

      <main class="mx-auto max-w-6xl p-4 md:p-8"><slot /></main>
    </div>

    <!-- Navigasi bawah (mobile-first) -->
    <nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-green-100 bg-white md:hidden">
      <Link v-for="m in menuMobile" :key="m.route" :href="route(m.route)"
        class="flex flex-1 flex-col items-center py-2 text-[11px] font-medium" :class="active(m.match) ? 'text-green-700' : 'text-gray-500'">
        <span class="text-lg">{{ m.icon }}</span>{{ m.label }}
      </Link>
      <button v-if="menuLainnya.length" type="button" class="flex flex-1 flex-col items-center py-2 text-[11px] font-medium text-gray-500" @click="sheet = true">
        <span class="text-lg">☰</span>Menu
      </button>
    </nav>

    <div v-if="sheet" class="fixed inset-0 z-40 md:hidden" @click.self="sheet = false">
      <div class="absolute inset-0 bg-black/40" @click="sheet = false" />
      <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 pb-8 shadow-2xl">
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-gray-200" />
        <div class="grid grid-cols-3 gap-3">
          <Link v-for="m in menuLainnya" :key="m.route" :href="route(m.route)" class="flex flex-col items-center rounded-2xl bg-green-50 p-3 text-xs font-medium text-green-900" @click="sheet = false">
            <span class="text-2xl">{{ m.icon }}</span>{{ m.label }}
          </Link>
        </div>
        <Link :href="route('logout')" method="post" as="button" class="mt-4 w-full rounded-xl border border-red-200 py-2.5 text-sm font-semibold text-red-600">Keluar</Link>
      </div>
    </div>
  </div>
</template>

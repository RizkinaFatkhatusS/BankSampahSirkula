export const rp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')
export const kg = (n) => Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg'
export const angka = (n) => Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })

export const metodeLabel = { dijemput_kurir: 'Dijemput kurir', antar_sendiri: 'Antar sendiri' }
export const olahanLabel = { pupuk_cair: 'Pupuk cair', kerajinan: 'Kerajinan', energi_lain: 'Energi lain' }
export const penjemputanLabel = { menunggu_penjemputan: 'Menunggu penjemputan', dalam_penjemputan: 'Dalam penjemputan', selesai: 'Selesai dijemput' }

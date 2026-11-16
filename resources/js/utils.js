export const rp = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID')
export const kg = (n) => Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg'
export const angka = (n) => Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 })

export const metodeLabel = { dijemput_kurir: 'Dijemput kurir', antar_sendiri: 'Antar sendiri' }
// Label bawaan; label terkini datang dari data master (props.master) bila tersedia
export const olahanLabel = { pupuk_cair: 'Pupuk cair', kerajinan: 'Kerajinan', energi_lain: 'Energi lain' }
export const penjemputanLabel = { menunggu_penjemputan: 'Menunggu penjemputan', dalam_penjemputan: 'Dalam penjemputan', selesai: 'Selesai dijemput' }
export const statusAkunLabel = { menunggu_aktivasi: 'Menunggu aktivasi', aktif: 'Aktif', ditangguhkan: 'Ditangguhkan', dalam_penyelesaian: 'Dalam penyelesaian', nonaktif: 'Nonaktif' }
export const roleLabel = { customer: 'Customer', supplier: 'Supplier', admin: 'Admin', super_admin: 'Super Admin' }

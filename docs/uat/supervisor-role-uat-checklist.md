# UAT Checklist - Supervisor Role PulseKPI

## Tujuan

Dokumen ini digunakan untuk memverifikasi bahwa fitur role Supervisor di PulseKPI sudah berjalan sesuai kebutuhan bisnis sebelum rilis ke production. Checklist ini ditujukan untuk tester non-teknis, HRD, business owner, dan project owner.

## Akun Uji yang Dibutuhkan

| Role | Example email | Expected hierarchy | Purpose of testing |
| --- | --- | --- | --- |
| Super Admin | superadmin@example.com | Akses global | Verifikasi akses penuh dan sanity check fitur global |
| HRD | hrd@example.com | Akses HRD | Verifikasi review, monitoring, dan akses dashboard HRD |
| Manager A | manager.a@example.com | Membawahi Supervisor A | Verifikasi Manager menilai Supervisor A |
| Manager B | manager.b@example.com | Membawahi Supervisor B | Verifikasi isolasi data antar manager |
| Supervisor A | supervisor.a@example.com | Membawahi Staff A1 dan Staff A2, melapor ke Manager A | Verifikasi alur utama Supervisor |
| Supervisor B | supervisor.b@example.com | Membawahi Staff B1, melapor ke Manager B | Verifikasi isolasi data antar supervisor |
| Staff A1 | staff.a1@example.com | Bawahan Supervisor A | Verifikasi direct staff Supervisor A |
| Staff A2 | staff.a2@example.com | Bawahan Supervisor A | Verifikasi direct staff Supervisor A |
| Staff B1 | staff.b1@example.com | Bawahan Supervisor B | Verifikasi data tidak boleh terlihat oleh Supervisor A |
| Approver | approver@example.com | Role approver final | Verifikasi approve dan lock tetap di role yang benar |

## Data Awal yang Harus Disiapkan Sebelum UAT

- Ada KPI period yang aktif.
- Ada KPI template yang sudah published dan active.
- Staff A1 dan Staff A2 berada di bawah Supervisor A.
- Staff B1 berada di bawah Supervisor B.
- Supervisor A berada di bawah Manager A.
- Supervisor B berada di bawah Manager B.
- KPI assignment sudah dibuat untuk Staff A1 dan Staff A2.
- KPI assignment sudah dibuat untuk Supervisor A.
- Ada minimal 1 assessment status draft.
- Ada minimal 1 assessment status submitted.
- Ada minimal 1 assessment status rejected.
- Ada minimal 1 assessment status locked/finalized.
- Ada minimal 1 data export/report jika ingin menguji akses terlarang.

## Legenda Hasil UAT

- `PASS` = sesuai harapan
- `FAIL` = tidak sesuai
- `BLOCKED` = tidak bisa diuji karena data/akses belum tersedia
- `NOTE` = ada catatan tetapi tidak menghalangi proses utama

## Scenario A - Login dan Redirect Dashboard

- Login sebagai Supervisor A.
- Buka `/admin`.
- Expected: diarahkan ke Dashboard Supervisor.
- Expected: judul halaman menampilkan Dashboard Supervisor.
- Expected: Supervisor A hanya melihat kartu ringkasan yang relevan untuk Supervisor.
- Expected: tidak ada menu dashboard HRD, Manager, atau Approver.

## Scenario B - Visibilitas Navigasi Supervisor

Supervisor seharusnya melihat:

- Dashboard Supervisor
- Team KPI / Assignment KPI
- Assessment Queue / Assessment KPI
- Profile / Logout

Supervisor seharusnya tidak melihat:

- HRD Dashboard
- Manager Dashboard
- Approver Dashboard
- Master Data
- Template KPI management
- Periode KPI management
- Reports / Laporan KPI
- Exports
- Approval final actions

| Menu | Expected | Result | Notes |
| --- | --- | --- | --- |
| Dashboard Supervisor | Terlihat |  |  |
| Team KPI / Assignment KPI | Terlihat |  |  |
| Assessment Queue / Assessment KPI | Terlihat |  |  |
| Profile / Logout | Terlihat |  |  |
| HRD Dashboard | Tidak terlihat |  |  |
| Manager Dashboard | Tidak terlihat |  |  |
| Approver Dashboard | Tidak terlihat |  |  |
| Master Data | Tidak terlihat |  |  |
| Template KPI management | Tidak terlihat |  |  |
| Periode KPI management | Tidak terlihat |  |  |
| Reports / Laporan KPI | Tidak terlihat |  |  |
| Exports | Tidak terlihat |  |  |
| Approval final actions | Tidak terlihat |  |  |

## Scenario C - Supervisor Hanya Melihat Direct Staff

Langkah:

- Login sebagai Supervisor A.
- Buka Dashboard Supervisor.
- Buka Team KPI / Assignment KPI.

Expected:

- Staff A1 terlihat.
- Staff A2 terlihat.
- Staff B1 tidak terlihat.
- Supervisor B tidak terlihat.
- Manager A tidak terlihat.
- HRD tidak terlihat.

## Scenario D - Alur Assessment Supervisor untuk Direct Staff

Langkah:

- Login sebagai Supervisor A.
- Buka assignment atau assessment milik Staff A1.
- Buat assessment jika belum ada.
- Isi score atau catatan jika form tersedia.
- Simpan sebagai draft.
- Submit assessment.

Expected:

- Create diperbolehkan.
- Edit draft diperbolehkan.
- Submit diperbolehkan.
- Status berubah sesuai alur workflow yang berlaku.
- Hanya data Staff A1 yang dapat diproses.
- Tidak ada tombol approval atau lock.

## Scenario E - Supervisor Tidak Boleh Menilai User yang Tidak Berwenang

Checklist:

- Supervisor A mencoba mengakses atau menilai Staff B1 di bawah Supervisor B.
- Supervisor A mencoba mengakses atau menilai Supervisor B.
- Supervisor A mencoba mengakses atau menilai Manager A.
- Supervisor A mencoba mengakses atau menilai HRD.
- Supervisor A mencoba mengakses atau menilai Approver.
- Supervisor A mencoba mengakses atau menilai dirinya sendiri.

Expected:

- Data tidak terlihat, akses ditolak, atau aksi tidak tersedia.
- Tidak ada kebocoran data user lain.

## Scenario F - Revisi Assessment Rejected

Langkah:

- Gunakan assessment rejected untuk Staff A1.
- Login sebagai Supervisor A.
- Buka assessment rejected tersebut.
- Edit atau revisi field yang diizinkan.
- Submit kembali.

Expected:

- Revisi diperbolehkan jika status workflow memang mengizinkan.
- Data locked/finalized tidak bisa diubah.
- Supervisor tidak bisa approve assessment miliknya sendiri.

## Scenario G - Proteksi Assessment Locked/Finalized

Langkah:

- Login sebagai Supervisor A.
- Buka assessment locked/final milik Staff A1.

Expected:

- Halaman hanya bisa dilihat jika memang diizinkan.
- Tombol edit tidak tersedia.
- Tombol submit tidak tersedia.
- Field bersifat read-only.
- Tidak ada aksi workflow destruktif.

## Scenario H - Manager Menilai Supervisor

Langkah:

- Login sebagai Manager A.
- Buka assessment untuk Supervisor A.
- Create, edit, dan submit assessment.

Expected:

- Manager A dapat menilai Supervisor A.
- Manager A tidak dapat menilai Supervisor B.
- Manager A tidak masuk ke Dashboard Supervisor sebagai role Supervisor.

## Scenario I - My KPI untuk Supervisor

Langkah:

- Login sebagai Supervisor A.
- Buka halaman My KPI.

Expected:

- Supervisor A dapat melihat KPI assignment atau assessment miliknya sendiri.
- Supervisor A tidak dapat melihat detail My KPI milik user lain.
- Tidak ada private file path yang tampil di halaman.

## Scenario J - Reports dan Exports Dilarang untuk Supervisor

Langkah:

- Login sebagai Supervisor A.
- Coba buka Reports / Laporan KPI jika URL diketahui.
- Coba buka Exports jika URL diketahui.

Expected:

- Menu tidak terlihat.
- Direct URL ditolak atau diarahkan keluar.
- Tidak ada file path export yang terlihat.
- Tidak ada path `storage/` atau `private/` yang terlihat.

## Scenario K - Otoritas Workflow Final

Supervisor tidak boleh melakukan:

- HRD Review
- Approve
- Reject final approval
- Lock
- Export reports

Expected:

- Tombol tidak terlihat atau aksi ditolak.

## Scenario L - Cross-Role Sanity Checks

Checklist:

- HRD masih bisa review assessment.
- Approver masih bisa approve dan lock.
- Manager dashboard tetap berjalan normal.
- Employee self-service tetap berjalan normal.
- Super Admin tetap bisa mengakses fitur global.
- Flow KPI template, assignment, dan assessment lama tetap berjalan normal.

## Scenario M - Visual QA

Checklist:

- Layout Dashboard Supervisor mudah dibaca.
- Summary cards mudah dibaca.
- Tabel mudah dibaca.
- Form mudah dibaca.
- Tombol mudah dibaca.
- Badge status mudah dibaca.
- Tampilan mobile masih dapat digunakan dengan baik.
- Logo tidak rusak.
- CSS tidak rusak.
- Tidak ada error JavaScript yang terlihat di browser console.

## Scenario N - Pengecekan Security / Private Path

Tester perlu memastikan halaman tidak menampilkan:

- `storage/`
- `private/`
- full server path
- raw export path
- raw evidence path
- `.env`
- debug info

## Format Pelaporan Issue

- Issue ID
- Tester
- Date
- Role used
- Page/URL
- Steps to reproduce
- Expected result
- Actual result
- Screenshot
- Severity: Critical / High / Medium / Low
- Notes

## Tabel Sign-Off UAT

| Tester name | Role tested | Result | Signature/date | Notes |
| --- | --- | --- | --- | --- |
|  |  |  |  |  |
|  |  |  |  |  |
|  |  |  |  |  |

## Keputusan Final Release

- [ ] Semua issue Critical sudah diperbaiki
- [ ] Semua issue High sudah diperbaiki atau sudah disetujui untuk ditindaklanjuti setelah rilis
- [ ] Akses Supervisor sudah terverifikasi
- [ ] Isolasi data antar supervisor sudah terverifikasi
- [ ] Workflow assessment sudah terverifikasi
- [ ] Larangan akses Reports dan Exports sudah terverifikasi
- [ ] Visual QA manual sudah lolos
- [ ] Product owner memberikan approval

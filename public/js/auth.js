/* ===== Auth Guard =====
   Proteksi halaman SEKARANG dilakukan di server (middleware `auth`
   Laravel, lihat routes/web.php) - request ke halaman yang butuh
   login otomatis diarahkan ke /login oleh server jika sesi belum
   ada, sebelum HTML halaman ini sempat dikirim ke browser.

   File ini sengaja dibiarkan kosong (no-op) supaya <script src=
   "js/auth.js"> di layout tidak perlu dihapus satu-satu di semua
   halaman, sekaligus mencegah pengecekan sessionStorage lama
   (yang tidak lagi diisi sejak login.js memakai sesi asli) membuat
   pengguna yang sudah login malah dilempar balik ke /login.
*/

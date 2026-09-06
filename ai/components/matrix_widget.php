<?php
/**
 * Komponen Widget Matriks Statistik Kehadiran (Firebase Connected)
 */
?>
<div id="ai-analytics-matrix" class="w-full grid grid-cols-3 gap-4 p-5 rounded-2xl bg-[#16161a] border border-[#BF953F]/20 text-center shadow-lg">
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Guru Hadir</p>
        <h3 id="matrix-hadir" class="text-xl font-black text-emerald-400 mt-1">0</h3>
    </div>
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Terlambat</p>
        <h3 id="matrix-terlambat" class="text-xl font-black text-amber-400 mt-1">0</h3>
    </div>
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Tidak Hadir</p>
        <h3 id="matrix-tidak-hadir" class="text-xl font-black text-rose-400 mt-1">0</h3>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('firebase_handler.php')
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('matrix-hadir').innerText = res.data.hadir;
                document.getElementById('matrix-terlambat').innerText = res.data.terlambat;
                document.getElementById('matrix-tidak-hadir').innerText = res.data.tidak_hadir;
            } else {
                console.error(res.message);
            }
        })
        .catch(err => {
            console.error('Gagal memuat data statistik:', err);
        });
});
</script>

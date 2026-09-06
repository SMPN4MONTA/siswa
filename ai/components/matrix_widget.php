<?php
/**
 * Komponen Widget Matriks Statistik Kehadiran
 * Dapat di-include pada dashboard utama atau halaman AI.
 */
?>
<div id="ai-analytics-matrix" class="w-full grid grid-cols-3 gap-4 p-5 rounded-2xl bg-[#16161a] border border-[#BF953F]/20 text-center">
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold">Guru Hadir</p>
        <h3 id="matrix-hadir" class="text-xl font-black text-emerald-400">0</h3>
    </div>
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold">Terlambat</p>
        <h3 id="matrix-terlambat" class="text-xl font-black text-amber-400">0</h3>
    </div>
    <div>
        <p class="text-[10px] text-gray-400 uppercase font-bold">Tidak Hadir</p>
        <h3 id="matrix-tidak-hadir" class="text-xl font-black text-rose-400">0</h3>
    </div>
</div>
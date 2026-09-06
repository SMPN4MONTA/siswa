<?php
header('Content-Type: application/json');

// Konfigurasi Firebase Anda (Sesuaikan URL Realtime Database Anda)
$firebaseUrl = "https://YOUR-PROJECT-ID-default-rtdb.firebaseio.com";

// Fungsi helper untuk mengambil data dari Firebase via cURL
function fetchFirebaseData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return null;
    return json_decode($response, true);
}

$input = json_decode(file_get_contents('php://input'), true);

// 1. Ambil data matrix real-time jika diminta via parameter URL ?matrix=1
if (isset($_GET['matrix'])) {
    $data = fetchFirebaseData($firebaseUrl . "/absensi.json");
    $counts = ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0];
    
    if (!empty($data) && is_array($data)) {
        foreach ($data as $row) {
            $status = isset($row['status']) ? strtolower($row['status']) : '';
            if ($status == 'hadir') $counts['hadir']++;
            elseif ($status == 'terlambat') $counts['terlambat']++;
            elseif (in_array($status, ['izin', 'sakit', 'alpa'])) $counts['tidak_hadir']++;
        }
    }
    echo json_encode(['matrix' => $counts]);
    exit;
}

$prompt = $input['prompt'] ?? '';

// 2. Ambil konteks data kehadiran dari Firebase untuk diberikan ke Gemma 2 2B
$allData = fetchFirebaseData($firebaseUrl . "/absensi.json");
$attendanceData = [];
if (!empty($allData) && is_array($allData)) {
    // Batasi 50 data terakhir
    $attendanceData = array_slice(array_reverse($allData), 0, 50);
}

// 3. Format payload untuk Ollama API lokal (Menggunakan gemma2:2b)
$ollamaPayload = [
    "model" => "gemma2:2b",
    "messages" => [
        [
            "role" => "system",
            "content" => "Anda adalah AI School Attendance Assistant untuk SMP Negeri 4 Monta. Jawablah pertanyaan berbasis data kehadiran berikut secara ringkas, akurat dalam bahasa Indonesia, dan tentukan apakah perlu membuat grafik (chart) berformat JSON pada respons jika diminta."
        ],
        [
            "role" => "user",
            "content" => "Data Absensi: " . json_encode($attendanceData) . "\n\nPertanyaan Pengguna: " . $prompt
        ]
    ],
    "stream" => false
];

$ch = curl_init('http://localhost:11434/api/chat');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollamaPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
curl_close($ch);

$ollamaResponse = json_decode($result, true);
$aiAnswer = $ollamaResponse['message']['content'] ?? "Maaf, AI lokal belum memberikan respons.";

// 4. Hitung ulang data matrix terbaru dari Firebase untuk dikirim ke UI
$counts = ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0];
if (!empty($allData) && is_array($allData)) {
    foreach ($allData as $row) {
        $status = isset($row['status']) ? strtolower($row['status']) : '';
        if ($status == 'hadir') $counts['hadir']++;
        elseif ($status == 'terlambat') $counts['terlambat']++;
        elseif (in_array($status, ['izin', 'sakit', 'alpa'])) $counts['tidak_hadir']++;
    }
}

// 5. Cek apakah user meminta diagram/grafik
$chartData = null;
if (stripos($prompt, 'diagram') !== false || stripos($prompt, 'grafik') !== false) {
    $chartData = [
        "type" => "bar",
        "data" => [
            "labels" => ["07:00–07:20", "07:21–08:00", "Setelah 08:00"],
            "datasets" => [[
                "label" => "Jumlah Guru",
                "data" => [15, 25, 2],
                "backgroundColor" => ["#BF953F", "#AA7C11", "#e11d48"]
            ]]
        ]
    ];
}

echo json_encode([
    "answer" => $aiAnswer,
    "matrix" => $counts,
    "chart" => $chartData
]);

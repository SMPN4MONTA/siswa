<?php
header('Content-Type: application/json');

try {
    // Sesuaikan path jika letak db_connection.php berbeda tingkat foldernya
    require_once '../../db_connection.php'; 
} catch (Exception $e) {
    echo json_encode([
        "answer" => "Maaf, terjadi kendala saat menghubungkan ke sistem database.",
        "matrix" => ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0],
        "chart" => null
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    if (isset($_GET['matrix'])) {
        $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM absensi GROUP BY status");
        $counts = ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strtolower($row['status']) == 'hadir') $counts['hadir'] = $row['total'];
            if (strtolower($row['status']) == 'terlambat') $counts['terlambat'] = $row['total'];
            if (in_array(strtolower($row['status']), ['izin', 'sakit', 'alpa'])) $counts['tidak_hadir'] += $row['total'];
        }
        echo json_encode(['matrix' => $counts]);
        exit;
    }

    $prompt = $input['prompt'] ?? '';

    // Ambil konteks database[cite: 6]
    $stmt = $pdo->query("SELECT * FROM absensi ORDER BY id DESC LIMIT 50");
    $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Payload untuk Ollama API[cite: 6]
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
    
    if (curl_errno($ch)) {
        $aiAnswer = "Maaf, gagal terhubung ke server AI lokal (Ollama).";
    } else {
        $ollamaResponse = json_decode($result, true);
        $aiAnswer = $ollamaResponse['message']['content'] ?? "Maaf, AI lokal belum memberikan respons.";
    }
    curl_close($ch);

    // Hitung data matrix terbaru[cite: 6]
    $stmtM = $pdo->query("SELECT status, COUNT(*) as total FROM absensi GROUP BY status");
    $counts = ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0];
    while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
        if (strtolower($row['status']) == 'hadir') $counts['hadir'] = $row['total'];
        if (strtolower($row['status']) == 'terlambat') $counts['terlambat'] = $row['total'];
        if (in_array(strtolower($row['status']), ['izin', 'sakit', 'alpa'])) $counts['tidak_hadir'] += $row['total'];
    }

    // Cek apakah user meminta diagram/grafik[cite: 6]
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

} catch (PDOException $e) {
    echo json_encode([
        "answer" => "Maaf, terjadi kendala saat menghubungkan ke sistem database.",
        "matrix" => ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0],
        "chart" => null
    ]);
}

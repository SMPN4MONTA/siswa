let recognition;
let isListening = false;
let chartInstance = null;

window.addEventListener('DOMContentLoaded', () => {
    initSpeechRecognition();
    fetchMatrixData();
    // Sambutan awal Text-to-Speech otomatis
    speakText("Selamat datang, Pak. Ada yang bisa saya bantu?");
});

function initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        alert("Browser Anda tidak mendukung Web Speech API.");
        return;
    }
    recognition = new SpeechRecognition();
    recognition.lang = 'id-ID';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
        isListening = true;
        document.getElementById('mic-label').innerText = "Saya sedang mendengarkan...";
        document.getElementById('mic-toggle-btn').classList.add('bg-rose-500', 'text-white');
    };

    recognition.onresult = async (event) => {
        const speechResult = event.results[0][0].transcript;
        document.getElementById('ai-response-text').innerText = `"${speechResult}"`;
        await sendCommandToBackend(speechResult);
    };

    recognition.onerror = () => { stopListening(); };
    recognition.onend = () => {
        stopListening();
        // Auto restart loop untuk voice assistant hands-free
        setTimeout(() => { if(!isListening) startListening(); }, 1000);
    };

    startListening();
}

function startListening() {
    try { recognition.start(); } catch(e) {}
}

function stopListening() {
    isListening = false;
    document.getElementById('mic-label').innerText = "Klik mikrofon untuk bicara";
    document.getElementById('mic-toggle-btn').classList.remove('bg-rose-500', 'text-white');
}

function toggleVoiceAssistant() {
    if (isListening) {
        recognition.stop();
    } else {
        startListening();
    }
}

function speakText(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'id-ID';
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        window.speechSynthesis.speak(utterance);
    }
}

async function sendCommandToBackend(command) {
    document.getElementById('ai-response-text').innerText = "Memproses data dari database...";
    try {
        const response = await fetch('api/query_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: command })
        });
        const data = await response.json();
        
        document.getElementById('ai-response-text').innerText = data.answer;
        speakText(data.answer);

        if (data.matrix) {
            document.getElementById('matrix-hadir').innerText = data.matrix.hadir || 0;
            document.getElementById('matrix-terlambat').innerText = data.matrix.terlambat || 0;
            document.getElementById('matrix-tidak-hadir').innerText = data.matrix.tidak_hadir || 0;
        }

        if (data.chart) {
            renderChart(data.chart);
        }
    } catch(e) {
        const errText = "Maaf, terjadi kendala saat menghubungkan ke sistem database.";
        document.getElementById('ai-response-text').innerText = errText;
        speakText(errText);
    }
}

async function fetchMatrixData() {
    try {
        const res = await fetch('api/query_handler.php?matrix=true');
        const data = await res.json();
        if (data.matrix) {
            document.getElementById('matrix-hadir').innerText = data.matrix.hadir || 0;
            document.getElementById('matrix-terlambat').innerText = data.matrix.terlambat || 0;
            document.getElementById('matrix-tidak-hadir').innerText = data.matrix.tidak_hadir || 0;
        }
    } catch(e) {}
}

function renderChart(chartConfig) {
    const wrapper = document.getElementById('chart-container-wrapper');
    wrapper.classList.remove('hidden');
    const ctx = document.getElementById('aiDynamicChart').getContext('2d');
    
    if (chartInstance) chartInstance.destroy();
    
    chartInstance = new Chart(ctx, {
        type: chartConfig.type || 'bar',
        data: chartConfig.data,
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#ffffff' } } },
            scales: {
                x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}
/**
 * Chart Component Handler
 * Mengelola pembuatan dan pembaruan grafik dinamis berbasis Chart.js
 */

let chartInstance = null;

function renderAIChart(chartConfig) {
    const wrapper = document.getElementById('chart-container-wrapper');
    if (!wrapper) return;
    
    wrapper.classList.remove('hidden');
    const canvasElement = document.getElementById('aiDynamicChart');
    if (!canvasElement) return;

    const ctx = canvasElement.getContext('2d');
    
    // Hancurkan chart sebelumnya jika ada untuk mencegah memory leak / duplikasi render
    if (chartInstance) {
        chartInstance.destroy();
    }
    
    chartInstance = new Chart(ctx, {
        type: chartConfig.type || 'bar',
        data: chartConfig.data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: '#ffffff' }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    ticks: { color: '#9ca3af' },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });
}
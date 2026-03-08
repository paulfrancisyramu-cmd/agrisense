// static/js/app.js - AgriSense Unified System Logic
let weatherChartInstance = null;
let readAlerts = new Set(); 

// ==========================================
// 1. SYSTEM ALERTS LOGIC (SMART MEMORY)
// ==========================================
function markAsRead(alertElement) {
    if (alertElement.classList.contains('alert-unread')) {
        alertElement.classList.remove('alert-unread');
        alertElement.classList.add('alert-read');
        
        const titleElement = alertElement.querySelector('h3');
        if (titleElement) {
            readAlerts.add(titleElement.innerText.trim());
        }

        const counterBadge = document.getElementById('unread-counter');
        if (counterBadge) {
            let count = parseInt(counterBadge.innerText);
            if (!isNaN(count) && count > 0) {
                count--;
                if (count === 0) {
                    counterBadge.innerText = "All Caught Up";
                    counterBadge.style.background = "#d8f3dc"; 
                    counterBadge.style.color = "#1b4332";      
                } else {
                    counterBadge.innerText = count + " Unread";
                }
            }
        }
    }
}

// ==========================================
// 2. LOGIN LOGIC
// ==========================================
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(event) {
        event.preventDefault(); 
        const userVal = document.getElementById('username').value;
        const passVal = document.getElementById('password').value;

        if (userVal === 'admin' && passVal === 'admin') {
            window.location.href = 'dashboard.php'; 
        } else {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) errorMessage.style.display = 'block';
            document.getElementById('password').value = '';
        }
    });
}

// ==========================================
// 3. SMART CHART LOGIC
// ==========================================
function updateOrCreateChart(newData, season) {
    const canvas = document.getElementById('weatherTrendChart');
    if (!canvas) return;

    const seasonPalette = {
        "Wet/Rainy": "#0077b6", 
        "Hot Dry": "#e67e22",   
        "Cool Dry": "#2d6a4f",   
        "Stable": "#8d99ae"      
    };
    const chartColor = seasonPalette[season] || "#8d99ae";

    const totalRain = newData.reduce((a, b) => a + b, 0).toFixed(1);

    if (weatherChartInstance) {
        weatherChartInstance.data.datasets[0].data = newData;
        weatherChartInstance.data.datasets[0].backgroundColor = chartColor; 
        weatherChartInstance.data.datasets[0].hoverBackgroundColor = chartColor; 
        weatherChartInstance.options.plugins.subtitle.text = `2-Week Total: ${totalRain} mm`;
        
        weatherChartInstance.update(); 
        return;
    }

    const ctx = canvas.getContext('2d');
    weatherChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: generateDateLabels(),
            datasets: [{
                label: 'Rainfall (mm)',
                data: newData,
                backgroundColor: chartColor,
                hoverBackgroundColor: chartColor,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { 
                y: { beginAtZero: true, suggestedMax: 2.0, grid: { color: '#f0f0f0' } }, 
                x: { grid: { display: false } } 
            },
            plugins: { 
                legend: { display: false },
                subtitle: {
                    display: true,
                    text: `2-Week Total: ${totalRain} mm`,
                    align: 'end',
                    font: { size: 12, weight: 'bold' }
                }
            }
        }
    });
}

function generateDateLabels() {
    const labels = [];
    const today = new Date();
    for (let i = 0; i < 14; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
    }
    return labels;
}

// ==========================================
// 4. LIVE DATA POLLING (AUTO-REFRESH)
// ==========================================
function enableLiveUpdates() {
    let path = window.location.pathname.split('/').pop();
    if (!path || path === '') path = 'dashboard.php';
    
    // Explicitly added data_logs.php to the list of live-reading pages
    const livePages = ['dashboard.php', 'recommendations.php', 'devices.php', 'alerts.php', 'data_logs.php'];

    if (!livePages.includes(path)) return;

    setInterval(() => {
        // Fetch current path PLUS search query (like ?date=2026-02-25) to preserve log filters
        fetch(window.location.pathname + window.location.search)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const currentContent = document.querySelector('.main-content');
                const newContent = newDoc.querySelector('.main-content');

                if (!currentContent || !newContent) return;

                if (path === 'dashboard.php') {
                    const newCanvas = newDoc.getElementById('weatherTrendChart');
                    if (newCanvas) {
                        const newData = JSON.parse(newCanvas.getAttribute('data-rain'));
                        const newSeason = newCanvas.getAttribute('data-season'); 
                        updateOrCreateChart(newData, newSeason);
                    }

                    const cardIds = ['#card-temp', '#card-hum', '#card-ideal-crop'];
                    cardIds.forEach(id => {
                        const oldCard = currentContent.querySelector(id);
                        const newCard = newDoc.querySelector(id);
                        if (oldCard && newCard && oldCard.innerHTML !== newCard.innerHTML) {
                            oldCard.innerHTML = newCard.innerHTML;
                        }
                    });

                    const oldHero = currentContent.querySelector('.hero-photo');
                    const newHero = newContent.querySelector('.hero-photo');
                    if (oldHero && newHero && oldHero.src !== newHero.src) {
                        oldHero.src = newHero.src;
                    }
                } 
                else if (path === 'data_logs.php') {
                    // Update only the table body so the page doesn't blink
                    const oldTable = currentContent.querySelector('.log-table tbody');
                    const newTable = newDoc.querySelector('.log-table tbody');
                    if (oldTable && newTable && oldTable.innerHTML !== newTable.innerHTML) {
                        oldTable.innerHTML = newTable.innerHTML;
                    }
                    
                    // If no data was logged and a table now exists, refresh the whole content area
                    if (!oldTable && newTable) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }
                }
                else {
                    // Full content swap for Recommendations, Devices, and Alerts
                    if (currentContent.innerHTML !== newContent.innerHTML) {
                        currentContent.innerHTML = newContent.innerHTML;
                    }

                    if (path === 'alerts.php') {
                        let currentUnread = 0;
                        document.querySelectorAll('.alert-card').forEach(card => {
                            const titleElement = card.querySelector('h3');
                            if (titleElement && readAlerts.has(titleElement.innerText.trim())) {
                                card.classList.remove('alert-unread');
                                card.classList.add('alert-read');
                            } else if (card.classList.contains('alert-unread')) {
                                currentUnread++;
                            }
                        });

                        const counterBadge = document.getElementById('unread-counter');
                        if (counterBadge) {
                            if (currentUnread === 0) {
                                counterBadge.innerText = "All Caught Up";
                                counterBadge.style.background = "#d8f3dc";
                                counterBadge.style.color = "#1b4332";
                            } else {
                                counterBadge.innerText = currentUnread + " Unread";
                                counterBadge.style.background = "#ffecd1";
                                counterBadge.style.color = "#cc5500";
                            }
                        }
                    }
                }
            })
            .catch(err => console.log('Polling paused...'));
    }, 3000); 
}

// ==========================================
// 5. INITIALIZATION
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    enableLiveUpdates();
    let path = window.location.pathname.split('/').pop();
    if (path === 'dashboard.php' || path === '') {
        const canvas = document.getElementById('weatherTrendChart');
        if (canvas) {
            const initialData = JSON.parse(canvas.getAttribute('data-rain'));
            const initialSeason = canvas.getAttribute('data-season');
            updateOrCreateChart(initialData, initialSeason); 
        }
    }
});
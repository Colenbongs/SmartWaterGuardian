/**
 * Smart Water Guardian - Dashboard JavaScript
 * Handles all dashboard functionality including charts, real-time data, and interactions
 */

let usageChart = null;
let currentUser = null;
let currentMeterId = null;
let realtimeInterval = null;

// ============================
// INITIALIZE DASHBOARD
// ============================
document.addEventListener('DOMContentLoaded', function() {
    // Check auth state
    auth.onAuthStateChanged(function(user) {
        if (user) {
            currentUser = user;
            console.log('👤 User logged in:', user.uid);
            initializeDashboard(user.uid);
        } else {
            // Redirect to login if not authenticated
            window.location.href = 'login.php';
        }
    });
});

function initializeDashboard(uid) {
    // Get user's properties
    const propertiesRef = database.ref('properties/' + uid);
    propertiesRef.once('value')
        .then((snapshot) => {
            const properties = snapshot.val();
            if (properties) {
                const firstProperty = Object.values(properties)[0];
                if (firstProperty && firstProperty.meterId) {
                    currentMeterId = firstProperty.meterId;
                    console.log('📟 Meter ID:', currentMeterId);
                    
                    // Listen to real-time meter data
                    const meterRef = database.ref('meters/' + currentMeterId + '/lastReading');
                    meterRef.on('value', function(snapshot) {
                        const data = snapshot.val();
                        if (data) {
                            updateDashboardUI(data);
                        } else {
                            showNoDataMessage();
                        }
                    });
                    
                    // Load historical data
                    loadHistoricalData(currentMeterId);
                    
                    // Load alerts
                    loadRecentAlerts(uid);
                    
                    // Load stats
                    loadStats(uid);
                }
            }
        })
        .catch((error) => {
            console.error('Error loading properties:', error);
            showToast('⚠️ Error loading your water meter data', 'error');
        });

    // Set up auto-refresh for real-time data
    if (realtimeInterval) {
        clearInterval(realtimeInterval);
    }
    realtimeInterval = setInterval(() => {
        if (currentMeterId) {
            refreshRealtimeData(currentMeterId);
        }
    }, 30000); // Refresh every 30 seconds
}

// ============================
// UPDATE DASHBOARD UI
// ============================
function updateDashboardUI(data) {
    // Update current usage
    const currentUsage = document.getElementById('current-usage');
    if (currentUsage) {
        currentUsage.innerHTML = (data.flow || 0).toFixed(1) + ' <small>L/min</small>';
    }
    
    // Update today's total
    const todayTotal = document.getElementById('today-total');
    if (todayTotal) {
        todayTotal.innerHTML = (data.volume || 0).toFixed(0) + ' <small>L</small>';
    }
    
    // Update device battery
    const deviceBattery = document.getElementById('device-battery');
    if (deviceBattery) {
        deviceBattery.innerHTML = (data.battery ? data.battery.toFixed(0) : '--') + ' <small>%</small>';
    }
    
    // Update device status
    const deviceStatus = document.getElementById('device-status');
    if (deviceStatus) {
        if (data.status === 'online') {
            deviceStatus.textContent = '🟢 Online';
            deviceStatus.style.color = '#48bb78';
        } else {
            deviceStatus.textContent = '🔴 Offline';
            deviceStatus.style.color = '#e53e3e';
        }
    }
    
    // Update usage change (mock calculation)
    const usageChange = document.getElementById('usage-change');
    if (usageChange) {
        const randomChange = (Math.random() * 20 - 5).toFixed(1);
        const isPositive = randomChange >= 0;
        usageChange.innerHTML = `${isPositive ? '📈' : '📉'} ${randomChange}% vs yesterday`;
        usageChange.style.color = isPositive ? '#e53e3e' : '#48bb78';
    }
    
    // Update daily target
    const dailyTarget = document.getElementById('daily-target');
    if (dailyTarget) {
        const target = 1000; // Default daily target in liters
        const current = data.volume || 0;
        const percentage = Math.min((current / target) * 100, 100);
        dailyTarget.textContent = `🎯 ${percentage.toFixed(0)}% of daily target`;
    }
    
    // Update gauge
    updateGauge(data.flow || 0);
    
    // Update flow indicator
    const flowDot = document.getElementById('flow-dot');
    const flowStatus = document.getElementById('flow-status');
    if (flowDot && flowStatus) {
        const flow = data.flow || 0;
        if (flow < 5) {
            flowDot.style.background = '#48bb78';
            flowStatus.textContent = '🟢 Low Flow - Normal';
        } else if (flow < 15) {
            flowDot.style.background = '#ecc94b';
            flowStatus.textContent = '🟡 Moderate Flow - Normal';
        } else {
            flowDot.style.background = '#e53e3e';
            flowStatus.textContent = '🔴 High Flow - Check for leaks';
        }
    }
    
    // Update last updated time
    const lastUpdated = document.getElementById('last-updated');
    if (lastUpdated && data.timestamp) {
        lastUpdated.textContent = '🕐 Updated: ' + formatDateTime(data.timestamp);
    }
}

function showNoDataMessage() {
    document.getElementById('realtime-usage').style.display = 'none';
    document.getElementById('no-data').style.display = 'block';
}

// ============================
// GAUGE UPDATE
// ============================
function updateGauge(flow) {
    const maxFlow = 30;
    const percentage = Math.min((flow / maxFlow) * 100, 100);
    const gaugeFill = document.getElementById('gauge-fill');
    const gaugeValue = document.getElementById('gauge-value');
    
    if (gaugeFill && gaugeValue) {
        const circumference = 2 * Math.PI * 80;
        const offset = circumference - (percentage / 100) * circumference;
        
        // Set stroke dasharray and offset
        gaugeFill.style.strokeDasharray = circumference;
        gaugeFill.style.strokeDashoffset = offset;
        
        // Update color based on flow
        let color = '#2b6cb0';
        if (flow > 20) color = '#e53e3e';
        else if (flow > 10) color = '#ecc94b';
        gaugeFill.style.stroke = color;
        
        gaugeValue.textContent = flow.toFixed(1);
    }
}

// ============================
// HISTORICAL DATA
// ============================
function loadHistoricalData(meterId) {
    const historyRef = database.ref('meters/' + meterId + '/history');
    historyRef.once('value')
        .then((snapshot) => {
            const data = snapshot.val();
            const labels = [];
            const values = [];
            const today = new Date();

            if (data) {
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    const dateStr = date.toISOString().split('T')[0];
                    
                    labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
                    
                    if (data[dateStr] && data[dateStr].hourly) {
                        const total = Object.values(data[dateStr].hourly).reduce((a, b) => a + b, 0);
                        values.push(Math.round(total));
                    } else {
                        values.push(0);
                    }
                }
            } else {
                // Generate sample data if no history exists
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
                    values.push(Math.round(Math.random() * 200 + 50));
                }
            }

            createUsageChart(labels, values);
        })
        .catch((error) => {
            console.error('Error loading history:', error);
        });
}

function createUsageChart(labels, values) {
    const ctx = document.getElementById('usageChart');
    if (!ctx) return;
    
    const canvasContext = ctx.getContext('2d');
    
    if (usageChart) {
        usageChart.destroy();
    }
    
    usageChart = new Chart(canvasContext, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Water Usage (L)',
                data: values,
                backgroundColor: values.map(v => {
                    if (v > 200) return 'rgba(229, 62, 62, 0.8)';
                    if (v > 100) return 'rgba(237, 137, 54, 0.8)';
                    return 'rgba(43, 108, 176, 0.8)';
                }),
                borderColor: values.map(v => {
                    if (v > 200) return '#e53e3e';
                    if (v > 100) return '#ed8936';
                    return '#2b6cb0';
                }),
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' Liters';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + 'L';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// ============================
// LOAD STATS
// ============================
function loadStats(uid) {
    // Load total usage for the week
    const historyRef = database.ref('meters/' + currentMeterId + '/history');
    historyRef.once('value')
        .then((snapshot) => {
            const data = snapshot.val();
            if (data) {
                let total = 0;
                const today = new Date();
                for (let i = 0; i < 7; i++) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    const dateStr = date.toISOString().split('T')[0];
                    if (data[dateStr] && data[dateStr].hourly) {
                        const dailyTotal = Object.values(data[dateStr].hourly).reduce((a, b) => a + b, 0);
                        total += dailyTotal;
                    }
                }
                
                // Update weekly total stat if element exists
                const weeklyTotal = document.getElementById('weekly-total');
                if (weeklyTotal) {
                    weeklyTotal.textContent = formatVolume(total);
                }
            }
        });

    // Load alerts count
    const alertsRef = database.ref('alerts/' + uid);
    alertsRef.on('value', function(snapshot) {
        const data = snapshot.val();
        const count = data ? Object.keys(data).filter(key => !data[key].isRead).length : 0;
        const alertCount = document.getElementById('alert-count');
        if (alertCount) {
            alertCount.textContent = count;
        }
    });
}

// ============================
// LOAD RECENT ALERTS
// ============================
function loadRecentAlerts(uid) {
    const alertsRef = database.ref('alerts/' + uid);
    alertsRef.limitToLast(5).on('value', function(snapshot) {
        const data = snapshot.val();
        const alertsContainer = document.getElementById('recent-alerts');
        
        if (alertsContainer) {
            if (data) {
                const alerts = Object.entries(data).map(([id, alert]) => ({
                    id,
                    ...alert
                })).reverse();
                
                let html = '';
                alerts.forEach(alert => {
                    const emojis = {
                        'leak': '💧',
                        'high_usage': '📈',
                        'low_battery': '🔋',
                        'device_offline': '📡'
                    };
                    const icon = emojis[alert.type] || '🔔';
                    const severityClass = alert.severity || 'info';
                    
                    html += `
                        <div class="alert-item ${severityClass}">
                            <span class="alert-icon">${icon}</span>
                            <div class="alert-content">
                                <p>${alert.message || 'Alert'}</p>
                                <small>${formatDateTime(alert.timestamp)}</small>
                            </div>
                            ${!alert.isRead ? '<span class="alert-badge">New</span>' : ''}
                        </div>
                    `;
                });
                alertsContainer.innerHTML = html;
            } else {
                alertsContainer.innerHTML = '<p class="no-alerts">🎉 No alerts</p>';
            }
        }
    });
}

// ============================
// REFRESH REALTIME DATA
// ============================
function refreshRealtimeData(meterId) {
    const meterRef = database.ref('meters/' + meterId + '/lastReading');
    meterRef.once('value')
        .then((snapshot) => {
            const data = snapshot.val();
            if (data) {
                updateDashboardUI(data);
            }
        })
        .catch((error) => {
            console.error('Error refreshing data:', error);
        });
}

// ============================
// SIMULATE DATA
// ============================
function simulateData() {
    if (!currentUser || !currentMeterId) {
        showToast('⚠️ Please wait for data to load', 'warning');
        return;
    }

    simulateMeterData(currentMeterId, currentUser.uid)
        .then((reading) => {
            // Create random alert with emojis
            const alertTypes = ['leak', 'high_usage', 'low_battery'];
            const randomAlert = alertTypes[Math.floor(Math.random() * alertTypes.length)];
            const alertMessages = {
                'leak': '💧 Possible leak detected - unusual continuous flow detected in your system',
                'high_usage': '📈 High water usage detected - your usage exceeds normal patterns today',
                'low_battery': '🔋 Low battery warning - your water meter battery needs replacement soon'
            };
            
            // Randomly create an alert (70% chance)
            if (Math.random() < 0.7) {
                database.ref('alerts/' + currentUser.uid).push({
                    type: randomAlert,
                    message: alertMessages[randomAlert],
                    severity: randomAlert === 'leak' ? 'critical' : 'warning',
                    timestamp: new Date().toISOString(),
                    isRead: false
                });
            }
            
            // Show success notification with details
            showToast(`✅ Data simulated!\n💧 Flow: ${reading.flow.toFixed(1)} L/min\n📊 Volume: ${reading.volume.toFixed(0)} L\n🔋 Battery: ${reading.battery.toFixed(0)}%`, 'success', 5000);
            
            // Update dashboard immediately
            updateDashboardUI(reading);
        })
        .catch((error) => {
            console.error('Error simulating data:', error);
            showToast('⚠️ Error simulating data: ' + error.message, 'error');
        });
}

// ============================
// QUICK ACTION FUNCTIONS
// ============================
function generateReport() {
    showToast('📄 Report generation coming soon! 🚀', 'info', 3000);
}

function viewHistory() {
    window.location.href = 'history.php';
}

function setThreshold() {
    window.location.href = 'thresholds.php';
}

function openSettings() {
    window.location.href = 'profile.php';
}

// ============================
// HANDLE CHART PERIOD CHANGE
// ============================
document.addEventListener('DOMContentLoaded', function() {
    const chartPeriod = document.getElementById('chart-period');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', function() {
            const days = parseInt(this.value);
            if (currentMeterId) {
                // Reload history with new period
                loadHistoricalData(currentMeterId);
            }
        });
    }
});

// ============================
// EXPOSE FUNCTIONS
// ============================
window.simulateData = simulateData;
window.generateReport = generateReport;
window.viewHistory = viewHistory;
window.setThreshold = setThreshold;
window.openSettings = openSettings;

console.log('📊 Dashboard module loaded successfully!');
// Analytics page functionality
let analyticsData = {
    habits: [],
    completions: {},
    stats: {}
};

let charts = {};

// Category colors mapping
const categoryColors = {
    health: '#22c55e',
    physical: '#f59e0b',
    learning: '#3b82f6',
    mindfulness: '#8b5cf6',
    creativity: '#ec4899',
    productivity: '#10b981',
    social: '#f97316',
    lifestyle: '#eab308'
};

// Initialize analytics
async function initAnalytics() {
    showLoading(true);
    await loadAnalyticsData();
    updateOverviewStats();
    createCharts();
    updateInsights();
    showLoading(false);
}

// Show/hide loading state
function showLoading(show) {
    const loadingState = document.getElementById('loadingState');
    const analyticsContent = document.getElementById('analyticsContent');
    
    if (show) {
        loadingState.style.display = 'flex';
        analyticsContent.style.display = 'none';
    } else {
        loadingState.style.display = 'none';
        analyticsContent.style.display = 'flex';
    }
}

// Load analytics data from server
async function loadAnalyticsData() {
    try {
        // Get data for the last 3 months for comprehensive analytics
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 3);
        
        const response = await fetch(`../../includes/get_analytics_data.php?start=${formatDate(startDate)}&end=${formatDate(endDate)}`);
        const data = await response.json();
        
        if (data.success) {
            analyticsData = data;
        } else {
            throw new Error(data.message || 'Failed to load analytics data');
        }
    } catch (error) {
        console.error('Error loading analytics data:', error);
        // Use mock data for demonstration
        analyticsData = generateMockData();
    }
}

// Generate mock data for demonstration
function generateMockData() {
    const habits = [
        { id: 1, name: 'Morning Exercise', category: 'physical', icon: 'fa-dumbbell' },
        { id: 2, name: 'Read 30 Minutes', category: 'learning', icon: 'fa-book' },
        { id: 3, name: 'Drink Water', category: 'health', icon: 'fa-tint' },
        { id: 4, name: 'Meditation', category: 'mindfulness', icon: 'fa-leaf' },
        { id: 5, name: 'Write Journal', category: 'creativity', icon: 'fa-pen' }
    ];
    
    const completions = {};
    const today = new Date();
    
    // Generate completion data for last 90 days
    for (let i = 0; i < 90; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateKey = formatDate(date);
        
        completions[dateKey] = {};
        habits.forEach(habit => {
            // Random completion with different success rates per habit
            const successRates = { 1: 0.8, 2: 0.7, 3: 0.9, 4: 0.6, 5: 0.5 };
            completions[dateKey][habit.id] = Math.random() < successRates[habit.id];
        });
    }
    
    return {
        habits: habits,
        completions: completions,
        stats: {
            currentStreak: 5,
            bestStreak: 12,
            totalDays: 90
        }
    };
}

// Format date for API
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

// Note: trackDailyVisit() is defined in dashboard.js (shared across all pages)

// Calculate active days (days when user visited the website)
function calculateActiveDays(startDate, endDate) {
    // Use server-side visit data if available
    if (analyticsData.visits) {
        const visitDates = Object.keys(analyticsData.visits).filter(visitDate => {
            const d = new Date(visitDate);
            return d >= startDate && d <= endDate;
        });
        return visitDates.length;
    }
    
    // Fallback: count days with habit interactions (for backward compatibility)
    const habitInteractionDays = Object.keys(analyticsData.completions).filter(date => {
        const d = new Date(date);
        if (d >= startDate && d <= endDate) {
            const dayData = analyticsData.completions[date];
            return dayData && Object.keys(dayData).length > 0;
        }
        return false;
    });
    
    return habitInteractionDays.length;
}

// Update overview stats
function updateOverviewStats() {
    const { habits, completions, stats } = analyticsData;
    
    // Calculate overall success rate
    let totalCompletions = 0;
    let totalPossible = 0;
    
    Object.values(completions).forEach(dayCompletions => {
        Object.values(dayCompletions).forEach(completed => {
            if (completed) totalCompletions++;
            totalPossible++;
        });
    });
    
    const overallRate = totalPossible > 0 ? Math.round((totalCompletions / totalPossible) * 100) : 0;
    
    // Update UI
    document.getElementById('totalHabits').textContent = habits.length;
    document.getElementById('overallRate').textContent = `${overallRate}%`;
    document.getElementById('currentStreak').textContent = `${stats.currentStreak} days`;
    document.getElementById('bestStreak').textContent = `${stats.bestStreak} days`;
}

// Create all charts
function createCharts() {
    createProgressChart('weekly'); // Start with weekly view
    createHabitPerformanceChart();
    createDaysOfWeekChart();
    createCategoryChart();
}

// Create progress chart (weekly or monthly)
function createProgressChart(period = 'weekly') {
    const ctx = document.getElementById('progressChart').getContext('2d');
    
    // Destroy existing chart if it exists
    if (charts.progress) {
        charts.progress.destroy();
    }
    
    let data = [];
    let labels = [];
    let title = '';
    let description = '';
    
    if (period === 'weekly') {
        // Calculate weekly completion rates for last 8 weeks
        for (let week = 7; week >= 0; week--) {
            const endDate = new Date();
            endDate.setDate(endDate.getDate() - (week * 7));
            const startDate = new Date(endDate);
            startDate.setDate(startDate.getDate() - 6);
            
            let weekCompletions = 0;
            let weekPossible = 0;
            
            for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
                const dateKey = formatDate(d);
                if (analyticsData.completions[dateKey]) {
                    Object.values(analyticsData.completions[dateKey]).forEach(completed => {
                        if (completed) weekCompletions++;
                        weekPossible++;
                    });
                }
            }
            
            const weekRate = weekPossible > 0 ? Math.round((weekCompletions / weekPossible) * 100) : 0;
            data.push(weekRate);
            labels.push(`Week ${8 - week}`);
        }
        title = 'Weekly Progress';
        description = 'Your completion rate over the last 8 weeks';
    } else {
        // Calculate monthly completion rates for last 6 months
        for (let month = 5; month >= 0; month--) {
            const date = new Date();
            date.setMonth(date.getMonth() - month);
            const startOfMonth = new Date(date.getFullYear(), date.getMonth(), 1);
            const endOfMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0);
            
            let monthCompletions = 0;
            let monthPossible = 0;
            
            for (let d = new Date(startOfMonth); d <= endOfMonth; d.setDate(d.getDate() + 1)) {
                const dateKey = formatDate(d);
                if (analyticsData.completions[dateKey]) {
                    Object.values(analyticsData.completions[dateKey]).forEach(completed => {
                        if (completed) monthCompletions++;
                        monthPossible++;
                    });
                }
            }
            
            const monthRate = monthPossible > 0 ? Math.round((monthCompletions / monthPossible) * 100) : 0;
            data.push(monthRate);
            
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            labels.push(monthNames[date.getMonth()]);
        }
        title = 'Monthly Progress';
        description = 'Your completion rate over the last 6 months';
    }
    
    // Update title and description
    document.getElementById('progressChartTitle').textContent = title;
    document.getElementById('progressChartDescription').textContent = description;
    
    charts.progress = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Completion Rate (%)',
                data: data,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Update progress chart when dropdown changes
function updateProgressChart() {
    const period = document.getElementById('progressPeriod').value;
    createProgressChart(period);
}

// Create habit performance chart
function createHabitPerformanceChart() {
    const ctx = document.getElementById('habitChart').getContext('2d');
    
    const habitRates = analyticsData.habits.map(habit => {
        let completed = 0;
        let total = 0;
        
        Object.values(analyticsData.completions).forEach(dayCompletions => {
            if (dayCompletions[habit.id] !== undefined) {
                if (dayCompletions[habit.id]) completed++;
                total++;
            }
        });
        
        return {
            name: habit.name,
            rate: total > 0 ? Math.round((completed / total) * 100) : 0,
            color: categoryColors[habit.category] || '#666'
        };
    });
    
    charts.habits = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: habitRates.map(h => h.name),
            datasets: [{
                label: 'Success Rate (%)',
                data: habitRates.map(h => h.rate),
                backgroundColor: habitRates.map(h => h.color + '80'),
                borderColor: habitRates.map(h => h.color),
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
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Create days of week chart
function createDaysOfWeekChart() {
    const ctx = document.getElementById('daysChart').getContext('2d');
    
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayRates = new Array(7).fill(0);
    const dayCounts = new Array(7).fill(0);
    
    Object.keys(analyticsData.completions).forEach(dateKey => {
        const date = new Date(dateKey);
        const dayOfWeek = date.getDay();
        
        let dayCompleted = 0;
        let dayTotal = 0;
        
        Object.values(analyticsData.completions[dateKey]).forEach(completed => {
            if (completed) dayCompleted++;
            dayTotal++;
        });
        
        if (dayTotal > 0) {
            dayRates[dayOfWeek] += (dayCompleted / dayTotal) * 100;
            dayCounts[dayOfWeek]++;
        }
    });
    
    // Calculate averages
    const avgRates = dayRates.map((rate, index) => 
        dayCounts[index] > 0 ? Math.round(rate / dayCounts[index]) : 0
    );
    
    charts.days = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: dayNames,
            datasets: [{
                label: 'Success Rate (%)',
                data: avgRates,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

// Create category performance chart
function createCategoryChart() {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    const categoryStats = {};
    
    // Initialize categories
    analyticsData.habits.forEach(habit => {
        if (!categoryStats[habit.category]) {
            categoryStats[habit.category] = { completed: 0, total: 0 };
        }
    });
    
    // Calculate category performance
    Object.values(analyticsData.completions).forEach(dayCompletions => {
        analyticsData.habits.forEach(habit => {
            if (dayCompletions[habit.id] !== undefined) {
                categoryStats[habit.category].total++;
                if (dayCompletions[habit.id]) {
                    categoryStats[habit.category].completed++;
                }
            }
        });
    });
    
    const categoryData = Object.keys(categoryStats).map(category => ({
        name: category.charAt(0).toUpperCase() + category.slice(1),
        rate: categoryStats[category].total > 0 ? 
            Math.round((categoryStats[category].completed / categoryStats[category].total) * 100) : 0,
        color: categoryColors[category] || '#666'
    }));
    
    charts.categories = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(c => c.name),
            datasets: [{
                data: categoryData.map(c => c.rate),
                backgroundColor: categoryData.map(c => c.color + '80'),
                borderColor: categoryData.map(c => c.color),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Update insights section
function updateInsights() {
    updateTopHabits();
    updateMonthlySummary();
}

// Update top performing habits
function updateTopHabits() {
    const habitRates = analyticsData.habits.map(habit => {
        let completed = 0;
        let total = 0;
        
        Object.values(analyticsData.completions).forEach(dayCompletions => {
            if (dayCompletions[habit.id] !== undefined) {
                if (dayCompletions[habit.id]) completed++;
                total++;
            }
        });
        
        return {
            ...habit,
            rate: total > 0 ? Math.round((completed / total) * 100) : 0
        };
    }).sort((a, b) => b.rate - a.rate);
    
    const topHabitsContainer = document.getElementById('topHabits');
    topHabitsContainer.innerHTML = '';
    
    habitRates.slice(0, 3).forEach(habit => {
        const habitElement = document.createElement('div');
        habitElement.className = 'habit-insight-item';
        
        habitElement.innerHTML = `
            <div class="habit-insight-icon" style="background: ${categoryColors[habit.category] || '#666'}">
                <i class="fas ${habit.icon}"></i>
            </div>
            <div class="habit-insight-info">
                <h4>${habit.name}</h4>
                <p>${habit.category.charAt(0).toUpperCase() + habit.category.slice(1)}</p>
            </div>
            <div class="habit-insight-rate rate-excellent">
                ${habit.rate}%
            </div>
        `;
        
        topHabitsContainer.appendChild(habitElement);
    });
}

// Removed struggling habits and recent achievements functions

// Update monthly summary
function updateMonthlySummary() {
    const summaryContainer = document.getElementById('monthlySummary');
    
    // Calculate this month's stats
    const thisMonth = new Date();
    const startOfMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth(), 1);
    const endOfMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth() + 1, 0);
    
    let monthlyCompletions = 0;
    let monthlyPossible = 0;
    let perfectDays = 0;
    
    for (let d = new Date(startOfMonth); d <= endOfMonth; d.setDate(d.getDate() + 1)) {
        const dateKey = formatDate(d);
        if (analyticsData.completions[dateKey]) {
            let dayCompleted = 0;
            let dayTotal = 0;
            
            Object.values(analyticsData.completions[dateKey]).forEach(completed => {
                if (completed) {
                    monthlyCompletions++;
                    dayCompleted++;
                }
                monthlyPossible++;
                dayTotal++;
            });
            
            if (dayCompleted === dayTotal && dayTotal > 0) {
                perfectDays++;
            }
        }
    }
    
    const monthlyRate = monthlyPossible > 0 ? Math.round((monthlyCompletions / monthlyPossible) * 100) : 0;
    const daysInMonth = endOfMonth.getDate();
    
    // Calculate active days: days when user visited and engaged with the website
    // For now, we'll track days with habit interaction, but this should be enhanced
    // to track actual daily visits/logins
    const activeDays = calculateActiveDays(startOfMonth, endOfMonth);
    
    const summaryItems = [
        { 
            label: 'Overall Progress', 
            value: `${monthlyRate}%`,
            tooltip: 'Percentage of habits completed this month'
        },
        { 
            label: 'Perfect Days', 
            value: perfectDays,
            tooltip: 'Days where all habits were completed'
        },
        { 
            label: 'Active Days', 
            value: `${activeDays}/${daysInMonth}`,
            tooltip: 'Days when you logged in and interacted with habits'
        },
        { 
            label: 'Current Streak', 
            value: `${analyticsData.stats.currentStreak} days`,
            tooltip: 'Consecutive days with habit activity'
        }
    ];
    
    summaryContainer.innerHTML = '';
    
    summaryItems.forEach(item => {
        const summaryElement = document.createElement('div');
        summaryElement.className = 'summary-item';
        summaryElement.title = item.tooltip; // Add tooltip
        
        summaryElement.innerHTML = `
            <span class="summary-label">${item.label}</span>
            <span class="summary-value">${item.value}</span>
        `;
        
        summaryContainer.appendChild(summaryElement);
    });
}

// Initialize analytics when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only run analytics code if we're on the analytics page
    if (document.getElementById('analyticsContent')) {
        initAnalytics();
    }
});
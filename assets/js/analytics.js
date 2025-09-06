/**
 * Analytics Page Functionality
 * 
 * This module handles all analytics calculations and visualizations for the HabitHub application.
 * 
 * IMPORTANT: All week calculations use Sunday-Saturday week structure for consistency
 * across the entire application (dashboard, calendar, and analytics pages).
 * 
 * Week Structure:
 * - Sunday = Day 0 (week start)
 * - Monday = Day 1
 * - Tuesday = Day 2
 * - Wednesday = Day 3
 * - Thursday = Day 4
 * - Friday = Day 5
 * - Saturday = Day 6 (week end)
 */

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
    
    // Validate data structure
    if (!analyticsData.habits || !analyticsData.completions || !analyticsData.stats) {
        console.error('Invalid analytics data structure:', analyticsData);
        analyticsData = generateMockData();
    }
    
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
        // Get ALL data since account creation for comprehensive analytics
        const endDate = new Date();

        // Don't specify start date to get all data since account creation
        const response = await fetch(`../../includes/get_analytics_data.php?end=${formatDate(endDate)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();

        if (data.success) {
            analyticsData = data;
            console.log('Analytics data loaded successfully:', analyticsData);
        } else {
            throw new Error(data.message || 'Failed to load analytics data');
        }
    } catch (error) {
        console.error('Error loading analytics data:', error);
        console.log('Using mock data for demonstration');
        // Use mock data for demonstration
        analyticsData = generateMockData();
    }
}

// Generate mock data for demonstration
function generateMockData() {
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    const habits = [
        { 
            id: 1, 
            name: 'Morning Exercise', 
            category: 'physical', 
            icon: 'fa-dumbbell',
            created_at: formatDate(thirtyDaysAgo)
        },
        { 
            id: 2, 
            name: 'Read 30 Minutes', 
            category: 'learning', 
            icon: 'fa-book',
            created_at: formatDate(thirtyDaysAgo)
        },
        { 
            id: 3, 
            name: 'Drink Water', 
            category: 'health', 
            icon: 'fa-tint',
            created_at: formatDate(thirtyDaysAgo)
        },
        { 
            id: 4, 
            name: 'Meditation', 
            category: 'mindfulness', 
            icon: 'fa-leaf',
            created_at: formatDate(thirtyDaysAgo)
        },
        { 
            id: 5, 
            name: 'Write Journal', 
            category: 'creativity', 
            icon: 'fa-pen',
            created_at: formatDate(thirtyDaysAgo)
        }
    ];

    const completions = {};

    // Generate completion data for last 90 days
    for (let i = 0; i < 90; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateKey = formatDate(date);

        completions[dateKey] = {};
        habits.forEach(habit => {
            // Only generate data for dates after habit creation
            const habitCreatedDate = new Date(habit.created_at);
            if (date >= habitCreatedDate) {
                // Random completion with different success rates per habit
                const successRates = { 1: 0.8, 2: 0.7, 3: 0.9, 4: 0.6, 5: 0.5 };
                completions[dateKey][habit.id] = Math.random() < successRates[habit.id];
            }
        });
    }

    // Generate visits data
    const visits = {};
    for (let i = 0; i < 30; i++) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateKey = formatDate(date);
        
        // Random visits (80% chance of visiting each day)
        if (Math.random() < 0.8) {
            visits[dateKey] = true;
        }
    }

    return {
        habits: habits,
        completions: completions,
        visits: visits,
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

// Calculate active days (days when user logged in)
function calculateActiveDays(startDate, endDate) {
    // Use server-side login data from user_login_days table
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



    // Calculate overall success rate properly
    let totalCompletions = 0;
    let totalPossible = 0;

    // Get date range: from first habit creation to today (inclusive)
    const today = new Date();
    const firstHabitDate = new Date(Math.min(...habits.map(h => new Date(h.created_at))));

    // Generate ALL dates from first habit to today (inclusive)
    const allDates = [];
    const startDate = new Date(firstHabitDate.getFullYear(), firstHabitDate.getMonth(), firstHabitDate.getDate());
    const endDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        allDates.push(d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0'));
    }



    allDates.forEach(date => {
        let dayCompletions = 0;
        let dayPossible = 0;

        // For each date, check all habits that existed on that date
        habits.forEach(habit => {
            // Check if habit existed on this date (compare only date parts, not time)
            const habitCreatedDate = new Date(habit.created_at);
            const currentDate = new Date(date);

            // Set both dates to start of day for proper comparison
            habitCreatedDate.setHours(0, 0, 0, 0);
            currentDate.setHours(23, 59, 59, 999); // End of day

            // Only count if habit existed on this date
            if (habitCreatedDate <= currentDate) {
                dayPossible++;
                totalPossible++;

                // Check if habit was completed on this date
                if (completions[date] && completions[date][habit.id]) {
                    dayCompletions++;
                    totalCompletions++;
                }
            }
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

/**
 * Create progress chart (weekly or monthly)
 * 
 * Weekly mode uses Sunday-Saturday week boundaries for consistency with
 * dashboard and calendar pages. All week calculations use the shared
 * week-utils.js functions to ensure uniform behavior.
 * 
 * @param {string} period - 'weekly' or 'monthly'
 */
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
    let weekTooltips = []; // Move this to broader scope

    if (period === 'weekly') {
        // Calculate weekly completion rates for last 8 weeks using Sunday-Saturday boundaries
        const weeks = getWeeksInRange(new Date(), 8);

        weeks.forEach((week, index) => {
            let weekCompletions = 0;
            let weekPossible = 0;

            // Get all dates in this Sunday-Saturday week
            const weekDateStrings = getWeekDateStrings(week.start);

            weekDateStrings.forEach(dateKey => {

                // Count ALL habits that existed on this date, not just completed ones
                analyticsData.habits.forEach(habit => {
                    const habitCreatedDate = new Date(habit.created_at);

                    // Set both dates to proper times for comparison
                    habitCreatedDate.setHours(0, 0, 0, 0);
                    const currentDate = new Date(dateKey);
                    currentDate.setHours(23, 59, 59, 999);

                    // Only count if habit existed on this date
                    if (habitCreatedDate <= currentDate) {
                        weekPossible++;

                        // Check if habit was completed on this date
                        if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                            weekCompletions++;
                        }
                    }
                });
            });

            const weekRate = weekPossible > 0 ? Math.round((weekCompletions / weekPossible) * 100) : 0;
            data.push(weekRate);

            // Use clean week labels for x-axis
            const weekLabel = `Week ${index + 1}`;
            labels.push(weekLabel);

            // Store date range for tooltip
            const dateRange = formatWeekRange(week.start, week.end);
            weekTooltips.push(dateRange);
        });

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

            // Loop through days in the month, but only count past and current days
            const today = new Date();
            for (let d = new Date(startOfMonth); d <= endOfMonth; d.setDate(d.getDate() + 1)) {
                const dateKey = formatDate(d);
                const currentDate = new Date(dateKey);

                // Only count days that have passed (not future days)
                if (currentDate <= today) {
                    // Count habits that existed on this date
                    analyticsData.habits.forEach(habit => {
                        const habitCreatedDate = new Date(habit.created_at);

                        // Set both dates to proper times for comparison
                        habitCreatedDate.setHours(0, 0, 0, 0);
                        const currentDate = new Date(dateKey);
                        currentDate.setHours(23, 59, 59, 999);

                        // Only count if habit existed on this date
                        if (habitCreatedDate <= currentDate) {
                            monthPossible++;

                            // Check if habit was completed on this date
                            if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                                monthCompletions++;
                            }
                        }
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
                },
                tooltip: {
                    callbacks: {
                        title: function (context) {
                            if (period === 'weekly' && weekTooltips) {
                                return weekTooltips[context[0].dataIndex];
                            }
                            return context[0].label;
                        },
                        label: function (context) {
                            return `Completion Rate: ${context.parsed.y}%`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function (value) {
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

        // Calculate from habit creation date to today
        const habitCreatedDate = new Date(habit.created_at);
        const today = new Date();
        
        // Loop through all days from habit creation to today
        for (let d = new Date(habitCreatedDate); d <= today; d.setDate(d.getDate() + 1)) {
            const dateKey = formatDate(d);
            
            // Set proper times for comparison
            const checkDate = new Date(d);
            checkDate.setHours(23, 59, 59, 999);
            
            // Only count days from habit creation onwards
            if (checkDate >= habitCreatedDate) {
                total++;
                
                // Check if habit was completed on this date
                if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                    completed++;
                }
            }
        }

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
                        callback: function (value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

/**
 * Create days of week chart
 * 
 * This chart shows performance by day of the week using Sunday-Saturday ordering.
 * The dayNames array and date.getDay() indexing both use Sunday=0, Saturday=6.
 */
function createDaysOfWeekChart() {
    const ctx = document.getElementById('daysChart').getContext('2d');

    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayRates = new Array(7).fill(0);
    const dayCounts = new Array(7).fill(0);

    // Get date range: this month only
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    // Loop through days in this month only
    for (let d = new Date(startOfMonth); d <= today; d.setDate(d.getDate() + 1)) {
        const dateKey = formatDate(d);
        const dayOfWeek = new Date(dateKey).getDay(); // Create fresh date from dateKey to avoid mutation issues
        
        let dayCompleted = 0;
        let dayTotal = 0;
        
        // Count ALL habits that existed on this date
        analyticsData.habits.forEach(habit => {
            const habitCreatedDate = new Date(habit.created_at);
            habitCreatedDate.setHours(0, 0, 0, 0);
            const currentDate = new Date(dateKey);
            currentDate.setHours(23, 59, 59, 999);
            

            
            // Only count if habit existed on this date
            if (habitCreatedDate <= currentDate) {
                dayTotal++;
                
                // Check if habit was completed on this date
                if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                    dayCompleted++;
                }
            }
        });
        

        
        if (dayTotal > 0) {
            const dayRate = (dayCompleted / dayTotal) * 100;
            dayRates[dayOfWeek] += dayRate;
            dayCounts[dayOfWeek]++;
        }
    }

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
                        callback: function (value) {
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

    // Calculate category performance properly - only count days when habits existed
    Object.keys(analyticsData.completions).forEach(dateKey => {
        const dayCompletions = analyticsData.completions[dateKey];
        
        analyticsData.habits.forEach(habit => {
            // Check if habit existed on this date
            const habitCreatedDate = new Date(habit.created_at);
            habitCreatedDate.setHours(0, 0, 0, 0);
            const currentDate = new Date(dateKey);
            currentDate.setHours(23, 59, 59, 999);
            
            // Only count if habit existed on this date
            if (habitCreatedDate <= currentDate) {
                categoryStats[habit.category].total++;
                
                // Check if habit was completed on this date
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
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const category = categoryData[context.dataIndex];
                            return `${category.name}: ${category.rate}%`;
                        }
                    }
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
    if (!analyticsData.habits || analyticsData.habits.length === 0) {
        const topHabitsContainer = document.getElementById('topHabits');
        topHabitsContainer.innerHTML = '<p class="no-data">No habits data available</p>';
        return;
    }

    const habitRates = analyticsData.habits.map(habit => {
        let completed = 0;
        let total = 0;

        // Calculate ALL days from habit creation to today (or the latest data date)
        const habitCreatedDate = new Date(habit.created_at);
        const today = new Date();
        
        // If we have completion data, use the latest date in the data, otherwise use today
        const allCompletionDates = Object.keys(analyticsData.completions || {});
        const latestDataDate = allCompletionDates.length > 0 
            ? new Date(Math.max(...allCompletionDates.map(d => new Date(d).getTime())))
            : today;
        
        // Count ALL days from habit creation to the latest date
        for (let d = new Date(habitCreatedDate); d <= latestDataDate; d.setDate(d.getDate() + 1)) {
            const dateKey = formatDate(d);
            total++;
            
            // Check if habit was completed on this date
            // If no completion data exists for this date, consider it as NOT completed
            if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                completed++;
            }
        }

        return {
            ...habit,
            rate: total > 0 ? Math.round((completed / total) * 100) : 0,
            totalDays: total,
            completedDays: completed
        };
    }).sort((a, b) => b.rate - a.rate);

    const topHabitsContainer = document.getElementById('topHabits');
    topHabitsContainer.innerHTML = '';

    habitRates.slice(0, 3).forEach(habit => {
        const habitElement = document.createElement('div');
        habitElement.className = 'habit-insight-item';

        // Determine rate class based on performance
        let rateClass = 'rate-poor';
        if (habit.rate >= 80) rateClass = 'rate-excellent';
        else if (habit.rate >= 60) rateClass = 'rate-good';
        else if (habit.rate >= 40) rateClass = 'rate-average';

        habitElement.innerHTML = `
            <div class="habit-insight-icon" style="background: ${categoryColors[habit.category] || '#666'}">
                <i class="fas ${habit.icon || 'fa-check'}"></i>
            </div>
            <div class="habit-insight-info">
                <h4>${habit.name}</h4>
                <p>${habit.category.charAt(0).toUpperCase() + habit.category.slice(1)} • ${habit.completedDays}/${habit.totalDays} days</p>
            </div>
            <div class="habit-insight-rate ${rateClass}">
                ${habit.rate}%
            </div>
        `;

        topHabitsContainer.appendChild(habitElement);
    });
}

// Removed struggling habits and recent achievements functions

/**
 * Update monthly summary
 * 
 * Calculates monthly statistics and includes "This Week" progress using
 * Sunday-Saturday week boundaries for consistency with other pages.
 */
function updateMonthlySummary() {
    const summaryContainer = document.getElementById('monthlySummary');

    if (!analyticsData.habits || !analyticsData.completions || !analyticsData.stats) {
        summaryContainer.innerHTML = '<p class="no-data">No summary data available</p>';
        return;
    }

    // Calculate this month's stats
    const thisMonth = new Date();
    const startOfMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth(), 1);
    const endOfMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth() + 1, 0);

    let monthlyCompletions = 0;
    let monthlyPossible = 0;
    let perfectDays = 0;

    // Iterate through each day of the month
    for (let d = new Date(startOfMonth); d <= endOfMonth; d.setDate(d.getDate() + 1)) {
        const dateKey = formatDate(d);
        let dayCompleted = 0;
        let dayTotal = 0;

        // For each day, check all habits that existed on that date
        analyticsData.habits.forEach(habit => {
            const habitCreatedDate = new Date(habit.created_at);
            const currentDate = new Date(dateKey);

            // Set both dates to proper times for comparison
            habitCreatedDate.setHours(0, 0, 0, 0);
            currentDate.setHours(23, 59, 59, 999);

            // Only count if habit existed on this date
            if (habitCreatedDate <= currentDate) {
                dayTotal++;
                monthlyPossible++;

                // Check if habit was completed on this date
                if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                    dayCompleted++;
                    monthlyCompletions++;
                }
            }
        });

        // Count perfect days (all habits completed)
        if (dayTotal > 0 && dayCompleted === dayTotal) {
            perfectDays++;
        }
    }

    const monthlyRate = monthlyPossible > 0 ? Math.round((monthlyCompletions / monthlyPossible) * 100) : 0;
    const daysInMonth = endOfMonth.getDate();

    // Calculate active days: days when user visited and engaged with the website
    const activeDays = calculateActiveDays(startOfMonth, endOfMonth);

    // Calculate this week's progress using Sunday-Saturday boundaries (only past days)
    let weeklyRate = 0;
    try {
        const currentWeekBoundaries = getCurrentWeekBoundaries();
        const weekDateStrings = getWeekDateStrings(currentWeekBoundaries.start);
        const today = new Date();
        let weekCompletions = 0;
        let weekPossible = 0;

        weekDateStrings.forEach(dateKey => {
            const checkDate = new Date(dateKey);
            
            // Only count days that have passed (not future days)
            if (checkDate <= today) {
                analyticsData.habits.forEach(habit => {
                    const habitCreatedDate = new Date(habit.created_at);
                    habitCreatedDate.setHours(0, 0, 0, 0);
                    const currentDate = new Date(dateKey);
                    currentDate.setHours(23, 59, 59, 999);

                    if (habitCreatedDate <= currentDate) {
                        weekPossible++;
                        if (analyticsData.completions[dateKey] && analyticsData.completions[dateKey][habit.id]) {
                            weekCompletions++;
                        }
                    }
                });
            }
        });

        weeklyRate = weekPossible > 0 ? Math.round((weekCompletions / weekPossible) * 100) : 0;
    } catch (error) {
        console.error('Error calculating weekly rate:', error);
        weeklyRate = 0;
    }

    const summaryItems = [
        {
            label: 'Overall Progress',
            value: `${monthlyRate}%`,
            tooltip: 'Percentage of habits completed this month'
        },
        {
            label: 'This Week',
            value: `${weeklyRate}%`,
            tooltip: 'Percentage of habits completed this week (Sunday-Saturday)'
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
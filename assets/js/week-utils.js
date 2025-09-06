/**
 * Week Utilities - Centralized Sunday-Saturday Week Calculations
 * 
 * This module provides consistent week calculation functions across all pages
 * of the HabitHub application. All functions use Sunday-Saturday week structure.
 */

/**
 * Get the Sunday that starts the week containing the given date
 * @param {Date} date - The date to find the week start for
 * @returns {Date} - Sunday at 00:00:00 that starts the week
 */
function getWeekStart(date) {
    const start = new Date(date);
    start.setDate(start.getDate() - start.getDay()); // getDay() returns 0 for Sunday
    start.setHours(0, 0, 0, 0);
    return start;
}

/**
 * Get the Saturday that ends the week containing the given date
 * @param {Date} date - The date to find the week end for
 * @returns {Date} - Saturday at 23:59:59 that ends the week
 */
function getWeekEnd(date) {
    const end = new Date(date);
    end.setDate(end.getDate() + (6 - end.getDay())); // 6 - getDay() gives days until Saturday
    end.setHours(23, 59, 59, 999);
    return end;
}

/**
 * Get both start and end boundaries for the week containing the given date
 * @param {Date} date - The date to find week boundaries for
 * @returns {Object} - {start: Date, end: Date} with Sunday start and Saturday end
 */
function getWeekBoundaries(date) {
    return {
        start: getWeekStart(date),
        end: getWeekEnd(date)
    };
}

/**
 * Format week range for display (e.g., "Aug 18-24, 2025")
 * @param {Date} startDate - Sunday start of week
 * @param {Date} endDate - Saturday end of week
 * @returns {String} - Formatted week range string
 */
function formatWeekRange(startDate, endDate) {
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    if (startDate.getMonth() === endDate.getMonth()) {
        return `${monthNames[startDate.getMonth()]} ${startDate.getDate()}-${endDate.getDate()}, ${startDate.getFullYear()}`;
    } else {
        return `${monthNames[startDate.getMonth()]} ${startDate.getDate()} - ${monthNames[endDate.getMonth()]} ${endDate.getDate()}, ${startDate.getFullYear()}`;
    }
}

/**
 * Get array of week boundaries for a specified number of weeks ending at endDate
 * @param {Date} endDate - The end date to work backwards from
 * @param {Number} weekCount - Number of weeks to include
 * @returns {Array} - Array of week boundary objects {start, end, label}
 */
function getWeeksInRange(endDate, weekCount) {
    const weeks = [];
    const currentDate = new Date(endDate);

    for (let i = 0; i < weekCount; i++) {
        const weekBoundaries = getWeekBoundaries(currentDate);
        const label = `Week ${weekCount - i}`;

        weeks.unshift({
            start: weekBoundaries.start,
            end: weekBoundaries.end,
            label: label,
            displayRange: formatWeekRange(weekBoundaries.start, weekBoundaries.end)
        });

        // Move to previous week
        currentDate.setDate(currentDate.getDate() - 7);
    }

    return weeks;
}

/**
 * Check if a date falls within a specific week
 * @param {Date} date - Date to check
 * @param {Date} weekDate - Any date within the target week
 * @returns {Boolean} - True if date is in the same week as weekDate
 */
function isDateInWeek(date, weekDate) {
    const weekBoundaries = getWeekBoundaries(weekDate);
    return date >= weekBoundaries.start && date <= weekBoundaries.end;
}

/**
 * Get the current week boundaries (Sunday-Saturday containing today)
 * @returns {Object} - {start: Date, end: Date} for current week
 */
function getCurrentWeekBoundaries() {
    return getWeekBoundaries(new Date());
}

/**
 * Format a date as YYYY-MM-DD string for database/API usage
 * @param {Date} date - Date to format
 * @returns {String} - Date in YYYY-MM-DD format
 */
function formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Get all dates within a week as an array of date strings
 * @param {Date} weekDate - Any date within the target week
 * @returns {Array} - Array of date strings (YYYY-MM-DD) for all 7 days in the week
 */
function getWeekDateStrings(weekDate) {
    const weekBoundaries = getWeekBoundaries(weekDate);
    const dates = [];

    for (let d = new Date(weekBoundaries.start); d <= weekBoundaries.end; d.setDate(d.getDate() + 1)) {
        dates.push(formatDateKey(d));
    }

    return dates;
}

// Export functions for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    // Node.js environment
    module.exports = {
        getWeekStart,
        getWeekEnd,
        getWeekBoundaries,
        formatWeekRange,
        getWeeksInRange,
        isDateInWeek,
        getCurrentWeekBoundaries,
        formatDateKey,
        getWeekDateStrings
    };
}


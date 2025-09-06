# Design Document

## Overview

This design standardizes the week definition across all pages of the HabitHub application to use Sunday-Saturday as the consistent week structure. The calendar and dashboard pages already implement Sunday-Saturday weeks correctly, but the analytics page has inconsistent week calculations that need to be aligned. This design will create a centralized week utility and update the analytics page to use proper Sunday-Saturday week boundaries.

## Architecture

### Current State Analysis

**Calendar Page (calender.js):**
- ✅ Already uses Sunday-Saturday weeks correctly
- ✅ Has `getWeekStart()` function that returns Sunday as week start
- ✅ Uses proper day names array: `['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']`

**Dashboard Page (dashboard.js):**
- ✅ Already uses Sunday-Saturday weeks correctly
- ✅ Uses `new Date().getDay()` properly (0 = Sunday, 6 = Saturday)
- ✅ Week progress displays use Sunday-Saturday structure

**Analytics Page (analytics.js):**
- ❌ Weekly progress chart uses incorrect week boundaries
- ❌ Week calculation logic doesn't align weeks to Sunday-Saturday
- ✅ Days of week chart already uses correct day names array
- ❌ Weekly aggregation doesn't use proper week start dates

### Target Architecture

```
Shared Week Utilities (new)
├── getWeekStart(date) - Returns Sunday of the week containing the date
├── getWeekEnd(date) - Returns Saturday of the week containing the date  
├── getWeekBoundaries(date) - Returns {start, end} for the week
├── formatWeekRange(startDate, endDate) - Formats week display strings
└── getWeeksInRange(startDate, endDate) - Returns array of week boundaries

Analytics Page Updates
├── createProgressChart() - Use proper Sunday-Saturday week boundaries
├── Weekly calculation loops - Align to Sunday-Saturday periods
└── Week labeling - Use consistent week formatting
```

## Components and Interfaces

### 1. Shared Week Utility Module

**Location:** `assets/js/week-utils.js`

**Functions:**
```javascript
// Get the Sunday that starts the week containing the given date
function getWeekStart(date) {
    const start = new Date(date);
    start.setDate(start.getDate() - start.getDay());
    start.setHours(0, 0, 0, 0);
    return start;
}

// Get the Saturday that ends the week containing the given date  
function getWeekEnd(date) {
    const end = new Date(date);
    end.setDate(end.getDate() + (6 - end.getDay()));
    end.setHours(23, 59, 59, 999);
    return end;
}

// Get both start and end boundaries for a week
function getWeekBoundaries(date) {
    return {
        start: getWeekStart(date),
        end: getWeekEnd(date)
    };
}

// Format week range for display (e.g., "Aug 18-24, 2025")
function formatWeekRange(startDate, endDate) {
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    if (startDate.getMonth() === endDate.getMonth()) {
        return `${monthNames[startDate.getMonth()]} ${startDate.getDate()}-${endDate.getDate()}, ${startDate.getFullYear()}`;
    } else {
        return `${monthNames[startDate.getMonth()]} ${startDate.getDate()} - ${monthNames[endDate.getMonth()]} ${endDate.getDate()}, ${startDate.getFullYear()}`;
    }
}

// Get array of week boundaries for a date range
function getWeeksInRange(startDate, endDate, weekCount) {
    const weeks = [];
    const currentDate = new Date(endDate);
    
    for (let i = 0; i < weekCount; i++) {
        const weekBoundaries = getWeekBoundaries(currentDate);
        weeks.unshift(weekBoundaries);
        currentDate.setDate(currentDate.getDate() - 7);
    }
    
    return weeks;
}
```

### 2. Analytics Page Updates

**File:** `assets/js/analytics.js`

**Changes Required:**

1. **Import Week Utilities:**
   - Add script tag for week-utils.js in analytics.html
   - Use centralized functions instead of custom week logic

2. **Update createProgressChart() Function:**
   - Replace current week calculation with `getWeeksInRange()`
   - Ensure week boundaries align to Sunday-Saturday
   - Use proper week labeling with `formatWeekRange()`

3. **Fix Weekly Aggregation Logic:**
   - Use `getWeekStart()` and `getWeekEnd()` for date ranges
   - Ensure all weekly calculations respect Sunday-Saturday boundaries

## Data Models

### Week Boundary Object
```javascript
{
    start: Date,    // Sunday 00:00:00
    end: Date,      // Saturday 23:59:59
    label: String   // "Week 1", "Aug 18-24", etc.
}
```

### Weekly Analytics Data
```javascript
{
    weekBoundaries: WeekBoundary,
    completions: Number,
    possible: Number,
    rate: Number,           // Percentage (0-100)
    habits: Array<Object>   // Habit-specific data for the week
}
```

## Error Handling

### Date Validation
- Validate input dates before week calculations
- Handle edge cases around month/year boundaries
- Ensure consistent timezone handling

### Fallback Behavior
- If week utilities fail, fall back to current date
- Log errors for debugging but don't break user experience
- Provide default week labels if formatting fails

## Testing Strategy

### Unit Tests
1. **Week Utility Functions:**
   - Test `getWeekStart()` with various dates (Sunday, Monday, Saturday)
   - Verify `getWeekEnd()` returns correct Saturday
   - Test month boundary edge cases
   - Validate timezone consistency

2. **Analytics Integration:**
   - Test weekly progress chart with known data
   - Verify week boundaries align with calendar page
   - Test weekly aggregation accuracy

### Integration Tests
1. **Cross-Page Consistency:**
   - Compare week calculations between analytics and dashboard
   - Verify calendar and analytics show same week ranges
   - Test navigation between pages maintains week context

### Manual Testing
1. **User Experience:**
   - Navigate between analytics, dashboard, and calendar
   - Verify weekly data appears consistent across pages
   - Test with different date ranges and time periods

## Implementation Notes

### Migration Strategy
1. Create shared week utilities first
2. Update analytics page to use new utilities
3. Optionally refactor calendar and dashboard to use shared utilities (for consistency)
4. Add comprehensive testing

### Performance Considerations
- Week calculations are lightweight and don't impact performance
- Cache week boundaries when possible to avoid recalculation
- Use efficient date manipulation methods

### Browser Compatibility
- Use standard Date methods supported in all modern browsers
- Avoid timezone-specific operations that might cause inconsistencies
- Test across different locales and time zones
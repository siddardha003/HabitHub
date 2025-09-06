# Implementation Plan

- [x] 1. Create shared week utility module




  - Create `assets/js/week-utils.js` with centralized week calculation functions
  - Implement `getWeekStart()`, `getWeekEnd()`, `getWeekBoundaries()`, `formatWeekRange()`, and `getWeeksInRange()` functions
  - Add comprehensive JSDoc documentation for all utility functions
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 2. Update analytics HTML to include week utilities




  - Add script tag for `week-utils.js` in `pages/dashboard/analytics.html`
  - Ensure proper loading order before analytics.js
  - _Requirements: 4.4_

- [x] 3. Fix analytics weekly progress chart calculations




  - Replace custom week calculation logic in `createProgressChart()` function with `getWeeksInRange()`
  - Update week boundary calculations to use Sunday-Saturday alignment
  - Ensure weekly completion rate calculations respect proper week boundaries
  - _Requirements: 1.1, 1.2, 2.1, 2.4_

- [x] 4. Update analytics weekly aggregation logic




  - Modify weekly data aggregation loops to use `getWeekStart()` and `getWeekEnd()`
  - Fix date range calculations in weekly statistics
  - Ensure all weekly calculations align with dashboard and calendar pages
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 5. Standardize week labeling in analytics




  - Update week labels to use consistent Sunday-Saturday format
  - Implement proper week range formatting using `formatWeekRange()`
  - Ensure week labels match calendar page conventions
  - _Requirements: 1.4, 4.3_

- [x] 6. Verify days of week chart consistency




  - Confirm days of week chart already uses correct Sunday-first ordering
  - Ensure day indexing aligns with `getDay()` method (0=Sunday, 6=Saturday)
  - Test that day aggregation respects Sunday-Saturday week structure
  - _Requirements: 1.3, 3.1_

- [x] 7. Update monthly summary calculations




  - Fix "this week" calculations in `updateMonthlySummary()` to use proper week boundaries
  - Ensure weekly statistics align with other pages
  - Update active days calculation to respect Sunday-Saturday weeks
  - _Requirements: 3.2, 3.4_

- [x] 8. Add unit tests for week utilities




  - Create test cases for `getWeekStart()` with various input dates
  - Test `getWeekEnd()` returns correct Saturday for different weeks
  - Verify `getWeekBoundaries()` handles month and year boundaries correctly
  - Test `formatWeekRange()` produces consistent output format
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 9. Test cross-page week consistency





  - Verify analytics weekly data matches dashboard weekly progress
  - Compare week ranges between analytics and calendar pages
  - Test navigation between pages maintains consistent week context
  - Validate that all pages show same week boundaries for current date
  - _Requirements: 2.1, 3.1, 3.3_




- [-] 10. Update documentation and comments

  - Add code comments explaining Sunday-Saturday week structure
  - Update any existing comments that reference incorrect week definitions
  - Document the centralized week utility usage in analytics code
  - _Requirements: 4.1, 4.4_
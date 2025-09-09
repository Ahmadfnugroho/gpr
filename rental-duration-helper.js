import dayjs from 'dayjs';

/**
 * Helper functions for rental duration calculation
 * Consistent with Laravel backend inclusive date logic
 */

/**
 * Calculate rental duration in days (inclusive)
 * Backend logic: 2025-09-09 to 2025-09-10 = 1 day (inclusive)
 * 
 * @param {string|Date|dayjs} startDate - Start date
 * @param {string|Date|dayjs} endDate - End date
 * @returns {number} Duration in days (inclusive), or 0 if invalid
 */
export const getRentalDays = (startDate, endDate) => {
  if (!startDate || !endDate) {
    return 0;
  }

  try {
    const start = dayjs(startDate);
    const end = dayjs(endDate);

    // Validate dates
    if (!start.isValid() || !end.isValid()) {
      console.warn('Invalid date provided to getRentalDays:', { startDate, endDate });
      return 0;
    }

    // Check if start date is after end date
    if (start.isAfter(end)) {
      console.warn('Start date is after end date:', { startDate, endDate });
      return 0;
    }

    // Calculate inclusive duration
    // Formula: end.diff(start, 'day') + 1 for inclusive calculation
    const duration = end.diff(start, 'day') + 1;
    
    return Math.max(0, duration);
  } catch (error) {
    console.error('Error calculating rental days:', error);
    return 0;
  }
};

/**
 * Format duration for display
 * 
 * @param {number} days - Number of days
 * @returns {string} Formatted duration string
 */
export const formatRentalDuration = (days) => {
  if (days <= 0) return '0 hari';
  if (days === 1) return '1 hari';
  return `${days} hari`;
};

/**
 * Calculate rental end date from start date and duration
 * 
 * @param {string|Date|dayjs} startDate - Start date
 * @param {number} durationDays - Duration in days
 * @returns {dayjs} End date
 */
export const calculateEndDate = (startDate, durationDays) => {
  if (!startDate || durationDays <= 0) {
    return null;
  }

  try {
    const start = dayjs(startDate);
    if (!start.isValid()) {
      return null;
    }

    // For inclusive calculation: end_date = start_date + (duration - 1) days
    return start.add(durationDays - 1, 'day');
  } catch (error) {
    console.error('Error calculating end date:', error);
    return null;
  }
};

/**
 * Validate date range for rental
 * 
 * @param {string|Date|dayjs} startDate - Start date
 * @param {string|Date|dayjs} endDate - End date
 * @returns {Object} Validation result
 */
export const validateRentalDates = (startDate, endDate) => {
  const result = {
    isValid: false,
    errors: [],
    duration: 0
  };

  if (!startDate) {
    result.errors.push('Tanggal mulai harus diisi');
  }

  if (!endDate) {
    result.errors.push('Tanggal selesai harus diisi');
  }

  if (!startDate || !endDate) {
    return result;
  }

  try {
    const start = dayjs(startDate);
    const end = dayjs(endDate);

    if (!start.isValid()) {
      result.errors.push('Tanggal mulai tidak valid');
    }

    if (!end.isValid()) {
      result.errors.push('Tanggal selesai tidak valid');
    }

    if (!start.isValid() || !end.isValid()) {
      return result;
    }

    // Check if start date is in the past (optional, uncomment if needed)
    // const today = dayjs().startOf('day');
    // if (start.isBefore(today)) {
    //   result.errors.push('Tanggal mulai tidak boleh di masa lalu');
    // }

    if (start.isAfter(end)) {
      result.errors.push('Tanggal mulai tidak boleh setelah tanggal selesai');
    } else {
      result.duration = getRentalDays(startDate, endDate);
      result.isValid = result.errors.length === 0;
    }

  } catch (error) {
    result.errors.push('Terjadi kesalahan saat validasi tanggal');
  }

  return result;
};

/**
 * Example React component usage:
 * 
 * ```jsx
 * import { getRentalDays, formatRentalDuration, validateRentalDates } from './rental-duration-helper';
 * import dayjs from 'dayjs';
 * 
 * const RentalForm = () => {
 *   const [startDate, setStartDate] = useState('');
 *   const [endDate, setEndDate] = useState('');
 *   
 *   const duration = getRentalDays(startDate, endDate);
 *   const validation = validateRentalDates(startDate, endDate);
 *   
 *   return (
 *     <div>
 *       <input 
 *         type="date" 
 *         value={startDate}
 *         onChange={(e) => setStartDate(e.target.value)}
 *       />
 *       <input 
 *         type="date" 
 *         value={endDate}
 *         onChange={(e) => setEndDate(e.target.value)}
 *       />
 *       
 *       {validation.isValid ? (
 *         <p>Durasi sewa: {formatRentalDuration(duration)}</p>
 *       ) : (
 *         <ul>
 *           {validation.errors.map((error, index) => (
 *             <li key={index} style={{color: 'red'}}>{error}</li>
 *           ))}
 *         </ul>
 *       )}
 *     </div>
 *   );
 * };
 * ```
 */

/**
 * Get formatted date range string
 * 
 * @param {string|Date|dayjs} startDate - Start date
 * @param {string|Date|dayjs} endDate - End date
 * @param {string} format - Date format (default: 'DD/MM/YYYY')
 * @returns {string} Formatted date range
 */
export const formatDateRange = (startDate, endDate, format = 'DD/MM/YYYY') => {
  if (!startDate || !endDate) {
    return '';
  }

  try {
    const start = dayjs(startDate);
    const end = dayjs(endDate);

    if (!start.isValid() || !end.isValid()) {
      return '';
    }

    const formattedStart = start.format(format);
    const formattedEnd = end.format(format);
    
    if (formattedStart === formattedEnd) {
      return formattedStart;
    }

    return `${formattedStart} - ${formattedEnd}`;
  } catch (error) {
    console.error('Error formatting date range:', error);
    return '';
  }
};

/**
 * Check if two date ranges overlap
 * Useful for checking availability conflicts
 * 
 * @param {Object} range1 - First date range {start, end}
 * @param {Object} range2 - Second date range {start, end}
 * @returns {boolean} True if ranges overlap
 */
export const dateRangesOverlap = (range1, range2) => {
  try {
    const start1 = dayjs(range1.start);
    const end1 = dayjs(range1.end);
    const start2 = dayjs(range2.start);
    const end2 = dayjs(range2.end);

    if (!start1.isValid() || !end1.isValid() || !start2.isValid() || !end2.isValid()) {
      return false;
    }

    // Two ranges overlap if:
    // start1 <= end2 && start2 <= end1
    return start1.isSameOrBefore(end2) && start2.isSameOrBefore(end1);
  } catch (error) {
    console.error('Error checking date range overlap:', error);
    return false;
  }
};

// Default export for the main function
export default getRentalDays;

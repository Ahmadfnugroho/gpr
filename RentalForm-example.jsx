import React, { useState, useEffect } from 'react';
import dayjs from 'dayjs';
import { 
  getRentalDays, 
  formatRentalDuration, 
  validateRentalDates,
  formatDateRange,
  calculateEndDate 
} from './rental-duration-helper';

/**
 * Example React component for rental form with consistent date calculation
 * This component demonstrates how to use the rental duration helper
 */
const RentalForm = () => {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [duration, setDuration] = useState(0);
  const [validation, setValidation] = useState({ isValid: false, errors: [] });
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [availability, setAvailability] = useState([]);
  const [loading, setLoading] = useState(false);

  // Calculate duration and validate dates whenever dates change
  useEffect(() => {
    const newDuration = getRentalDays(startDate, endDate);
    const newValidation = validateRentalDates(startDate, endDate);
    
    setDuration(newDuration);
    setValidation(newValidation);
  }, [startDate, endDate]);

  // Fetch product availability when dates change
  useEffect(() => {
    if (startDate && endDate && validation.isValid) {
      fetchProductAvailability();
    } else {
      setAvailability([]);
    }
  }, [startDate, endDate, validation.isValid]);

  /**
   * Fetch product availability for the selected date range
   */
  const fetchProductAvailability = async () => {
    try {
      setLoading(true);
      
      const response = await fetch(`/api/products?start_date=${startDate}&end_date=${endDate}`);
      const data = await response.json();
      
      setAvailability(data.data || []);
    } catch (error) {
      console.error('Error fetching product availability:', error);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Handle start date change
   */
  const handleStartDateChange = (e) => {
    const newStartDate = e.target.value;
    setStartDate(newStartDate);
    
    // Auto-adjust end date if it becomes invalid
    if (endDate && newStartDate && dayjs(newStartDate).isAfter(dayjs(endDate))) {
      setEndDate('');
    }
  };

  /**
   * Handle end date change
   */
  const handleEndDateChange = (e) => {
    setEndDate(e.target.value);
  };

  /**
   * Handle duration change (alternative input method)
   */
  const handleDurationChange = (e) => {
    const newDuration = parseInt(e.target.value) || 0;
    
    if (startDate && newDuration > 0) {
      const calculatedEndDate = calculateEndDate(startDate, newDuration);
      if (calculatedEndDate) {
        setEndDate(calculatedEndDate.format('YYYY-MM-DD'));
      }
    }
  };

  /**
   * Get minimum date (today)
   */
  const getMinDate = () => {
    return dayjs().format('YYYY-MM-DD');
  };

  /**
   * Get minimum end date (day after start date)
   */
  const getMinEndDate = () => {
    if (!startDate) return getMinDate();
    return startDate; // Same day is allowed for 1-day rental
  };

  return (
    <div className="rental-form">
      <h2>Form Sewa Kamera</h2>
      
      {/* Date Selection */}
      <div className="date-section">
        <h3>Pilih Tanggal Sewa</h3>
        
        <div className="date-inputs">
          <div className="input-group">
            <label htmlFor="start-date">Tanggal Mulai:</label>
            <input
              id="start-date"
              type="date"
              value={startDate}
              min={getMinDate()}
              onChange={handleStartDateChange}
              className={validation.errors.some(error => error.includes('mulai')) ? 'error' : ''}
            />
          </div>

          <div className="input-group">
            <label htmlFor="end-date">Tanggal Selesai:</label>
            <input
              id="end-date"
              type="date"
              value={endDate}
              min={getMinEndDate()}
              onChange={handleEndDateChange}
              className={validation.errors.some(error => error.includes('selesai')) ? 'error' : ''}
            />
          </div>

          <div className="input-group">
            <label htmlFor="duration">Durasi (hari):</label>
            <input
              id="duration"
              type="number"
              value={duration || ''}
              min="1"
              max="30"
              onChange={handleDurationChange}
              placeholder="Masukkan durasi..."
            />
          </div>
        </div>

        {/* Date Range Summary */}
        {validation.isValid && (
          <div className="date-summary">
            <p><strong>Periode Sewa:</strong> {formatDateRange(startDate, endDate)}</p>
            <p><strong>Durasi:</strong> {formatRentalDuration(duration)}</p>
          </div>
        )}

        {/* Validation Errors */}
        {!validation.isValid && validation.errors.length > 0 && (
          <div className="validation-errors">
            <ul>
              {validation.errors.map((error, index) => (
                <li key={index} style={{ color: '#e74c3c' }}>
                  {error}
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>

      {/* Product Availability */}
      {validation.isValid && (
        <div className="availability-section">
          <h3>Ketersediaan Produk</h3>
          
          {loading ? (
            <div className="loading">
              <p>Memuat ketersediaan produk...</p>
            </div>
          ) : availability.length > 0 ? (
            <div className="product-grid">
              {availability.map((product) => (
                <div
                  key={product.id}
                  className={`product-card ${product.available_quantity > 0 ? 'available' : 'unavailable'}`}
                  onClick={() => product.available_quantity > 0 && setSelectedProduct(product)}
                >
                  <img
                    src={product.thumbnail || '/placeholder-camera.jpg'}
                    alt={product.name}
                    className="product-image"
                  />
                  <div className="product-info">
                    <h4>{product.name}</h4>
                    <p className="price">Rp {product.price?.toLocaleString('id-ID')}/hari</p>
                    <p className={`availability ${product.available_quantity > 0 ? 'available' : 'unavailable'}`}>
                      {product.available_quantity > 0 
                        ? `${product.available_quantity} unit tersedia`
                        : 'Tidak tersedia'
                      }
                    </p>
                    {product.available_quantity > 0 && (
                      <button className="select-btn">Pilih</button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="no-products">
              <p>Tidak ada produk yang tersedia untuk periode yang dipilih.</p>
              <p>Coba ubah tanggal sewa Anda.</p>
            </div>
          )}
        </div>
      )}

      {/* Selected Product Summary */}
      {selectedProduct && validation.isValid && (
        <div className="booking-summary">
          <h3>Ringkasan Pesanan</h3>
          <div className="summary-item">
            <span>Produk:</span>
            <span>{selectedProduct.name}</span>
          </div>
          <div className="summary-item">
            <span>Periode:</span>
            <span>{formatDateRange(startDate, endDate)}</span>
          </div>
          <div className="summary-item">
            <span>Durasi:</span>
            <span>{formatRentalDuration(duration)}</span>
          </div>
          <div className="summary-item">
            <span>Harga per hari:</span>
            <span>Rp {selectedProduct.price?.toLocaleString('id-ID')}</span>
          </div>
          <div className="summary-item total">
            <span>Total Harga:</span>
            <span>Rp {(selectedProduct.price * duration)?.toLocaleString('id-ID')}</span>
          </div>
          
          <button className="book-btn">
            Lanjutkan Pemesanan
          </button>
        </div>
      )}

      <style jsx>{`
        .rental-form {
          max-width: 1200px;
          margin: 0 auto;
          padding: 20px;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .date-section {
          margin-bottom: 30px;
          padding: 20px;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          background: #f9f9f9;
        }

        .date-inputs {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 15px;
          margin-bottom: 15px;
        }

        .input-group {
          display: flex;
          flex-direction: column;
        }

        .input-group label {
          margin-bottom: 5px;
          font-weight: 600;
          color: #333;
        }

        .input-group input {
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
        }

        .input-group input.error {
          border-color: #e74c3c;
        }

        .date-summary {
          background: #e8f5e8;
          padding: 15px;
          border-radius: 4px;
          border-left: 4px solid #27ae60;
        }

        .validation-errors {
          background: #ffeaea;
          padding: 15px;
          border-radius: 4px;
          border-left: 4px solid #e74c3c;
        }

        .validation-errors ul {
          margin: 0;
          padding-left: 20px;
        }

        .availability-section {
          margin-bottom: 30px;
        }

        .product-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: 20px;
          margin-top: 15px;
        }

        .product-card {
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          overflow: hidden;
          background: white;
          cursor: pointer;
          transition: all 0.2s;
        }

        .product-card:hover {
          shadow: 0 4px 12px rgba(0,0,0,0.1);
          transform: translateY(-2px);
        }

        .product-card.unavailable {
          opacity: 0.6;
          cursor: not-allowed;
        }

        .product-image {
          width: 100%;
          height: 200px;
          object-fit: cover;
        }

        .product-info {
          padding: 15px;
        }

        .product-info h4 {
          margin: 0 0 10px 0;
          font-size: 16px;
        }

        .price {
          color: #27ae60;
          font-weight: 600;
          font-size: 14px;
        }

        .availability.available {
          color: #27ae60;
        }

        .availability.unavailable {
          color: #e74c3c;
        }

        .select-btn, .book-btn {
          background: #3498db;
          color: white;
          border: none;
          padding: 10px 15px;
          border-radius: 4px;
          cursor: pointer;
          font-weight: 600;
          width: 100%;
          margin-top: 10px;
        }

        .select-btn:hover, .book-btn:hover {
          background: #2980b9;
        }

        .booking-summary {
          background: #f8f9fa;
          padding: 20px;
          border-radius: 8px;
          border: 1px solid #e0e0e0;
        }

        .summary-item {
          display: flex;
          justify-content: space-between;
          margin-bottom: 10px;
          padding-bottom: 8px;
          border-bottom: 1px solid #eee;
        }

        .summary-item.total {
          font-weight: 600;
          font-size: 16px;
          border-bottom: 2px solid #3498db;
          color: #2c3e50;
        }

        .loading {
          text-align: center;
          padding: 40px;
          color: #666;
        }

        .no-products {
          text-align: center;
          padding: 40px;
          color: #666;
          background: #f9f9f9;
          border-radius: 8px;
        }
      `}</style>
    </div>
  );
};

export default RentalForm;

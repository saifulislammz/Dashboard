-- =============================================================
-- Invoice Currency Migration
-- Tables: invoice_currencies
-- =============================================================

CREATE TABLE IF NOT EXISTS `invoice_currencies` (
  `code` VARCHAR(10) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `flag` VARCHAR(10) DEFAULT '',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default currencies
INSERT IGNORE INTO `invoice_currencies` (`code`, `name`, `symbol`, `flag`) VALUES
('BDT', 'Bangladeshi Taka', '৳', '🇧🇩'),
('USD', 'US Dollar', '$', '🇺🇸'),
('EUR', 'Euro', '€', '🇪🇺'),
('GBP', 'British Pound', '£', '🇬🇧'),
('AUD', 'Australian Dollar', 'A$', '🇦🇺'),
('CAD', 'Canadian Dollar', 'C$', '🇨🇦'),
('SGD', 'Singapore Dollar', 'S$', '🇸🇬'),
('SAR', 'Saudi Riyal', '﷼', '🇸🇦'),
('AED', 'UAE Dirham', 'د.إ', '🇦🇪'),
('MYR', 'Malaysian Ringgit', 'RM', '🇲🇾'),
('INR', 'Indian Rupee', '₹', '🇮🇳'),
('JPY', 'Japanese Yen', '¥', '🇯🇵'),
('CNY', 'Chinese Yuan', '¥', '🇨🇳'),
('KWD', 'Kuwaiti Dinar', 'KD', '🇰🇼'),
('QAR', 'Qatari Riyal', 'QR', '🇶🇦'),
('TRY', 'Turkish Lira', '₺', '🇹🇷');

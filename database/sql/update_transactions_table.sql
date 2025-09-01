-- SQL script untuk update tabel transactions
-- Menambah kolom fee dan update enum status values

-- 1. Tambah kolom-kolom fee baru
ALTER TABLE `transactions` 
ADD COLUMN `additional_fee_1_name` VARCHAR(255) NULL AFTER `note`,
ADD COLUMN `additional_fee_1_amount` INT UNSIGNED NULL AFTER `additional_fee_1_name`,
ADD COLUMN `additional_fee_2_name` VARCHAR(255) NULL AFTER `additional_fee_1_amount`,
ADD COLUMN `additional_fee_2_amount` INT UNSIGNED NULL AFTER `additional_fee_2_name`,
ADD COLUMN `additional_fee_3_name` VARCHAR(255) NULL AFTER `additional_fee_2_amount`,
ADD COLUMN `additional_fee_3_amount` INT UNSIGNED NULL AFTER `additional_fee_3_name`,
ADD COLUMN `cancellation_fee` INT UNSIGNED NULL AFTER `additional_fee_3_amount`;

-- 2. Update existing data untuk mengubah nilai status lama ke nilai baru
UPDATE `transactions` SET `booking_status` = 'booking' WHERE `booking_status` = 'pending';
UPDATE `transactions` SET `booking_status` = 'on_rented' WHERE `booking_status` = 'rented';
UPDATE `transactions` SET `booking_status` = 'done' WHERE `booking_status` = 'finished';
UPDATE `transactions` SET `booking_status` = 'cancel' WHERE `booking_status` = 'cancelled';

-- 3. Ubah enum constraint untuk booking_status
ALTER TABLE `transactions` 
MODIFY COLUMN `booking_status` ENUM('booking', 'paid', 'on_rented', 'done', 'cancel') DEFAULT 'booking';

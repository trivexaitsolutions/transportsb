-- XYZ TRANSPORT - acknowledgement / LR attachment update for phpMyAdmin.
-- Use this only when Laravel migrations cannot be run on hosting.

-- The latest live dump already has vouchers.other_charges but does not record
-- its Laravel migration. Mark it complete so a later artisan migrate is safe.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_17_000030_add_other_charges_to_vouchers_table', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`)
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_17_000030_add_other_charges_to_vouchers_table'
);

CREATE TABLE IF NOT EXISTS `voucher_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_path` varchar(500) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT 0,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_attachments_voucher_id_created_at_index` (`voucher_id`,`created_at`),
  KEY `voucher_attachments_created_by_foreign` (`created_by`),
  CONSTRAINT `voucher_attachments_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_000040_create_voucher_attachments_table', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`)
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_19_000040_create_voucher_attachments_table'
);

ALTER TABLE `sales_orders`
  ADD COLUMN IF NOT EXISTS `tax_mode` varchar(20) NOT NULL DEFAULT 'rcm' AFTER `gst_rate_id`;

UPDATE `sales_orders`
SET `tax_mode` = CASE WHEN `gst_amount` > 0 THEN 'hiring' ELSE 'rcm' END,
    `gst_rate_id` = CASE WHEN `gst_amount` > 0 THEN `gst_rate_id` ELSE NULL END,
    `gst_rate` = CASE WHEN `gst_amount` > 0 THEN `gst_rate` ELSE 0 END,
    `gst_amount` = CASE WHEN `gst_amount` > 0 THEN `gst_amount` ELSE 0 END;

ALTER TABLE `invoice_batches`
  ADD COLUMN IF NOT EXISTS `tax_mode` varchar(20) NOT NULL DEFAULT 'rcm' AFTER `sales_order_id`,
  ADD COLUMN IF NOT EXISTS `rcm_rate` decimal(7,2) NOT NULL DEFAULT 0 AFTER `gst_amount`,
  ADD COLUMN IF NOT EXISTS `rcm_amount` decimal(15,2) NOT NULL DEFAULT 0 AFTER `rcm_rate`;

UPDATE `invoice_batches`
SET `tax_mode` = CASE WHEN `gst_amount` > 0 THEN 'hiring' ELSE 'rcm' END,
    `rcm_rate` = CASE WHEN `gst_amount` > 0 THEN 0 ELSE 5 END,
    `rcm_amount` = CASE WHEN `gst_amount` > 0 THEN 0 ELSE ROUND(`customer_freight` * 0.05, 2) END,
    `gst_rate` = CASE WHEN `gst_amount` > 0 THEN `gst_rate` ELSE 0 END,
    `gst_amount` = CASE WHEN `gst_amount` > 0 THEN `gst_amount` ELSE 0 END;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_000050_add_tax_mode_to_sales_orders_and_invoices', (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`)
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_19_000050_add_tax_mode_to_sales_orders_and_invoices'
);

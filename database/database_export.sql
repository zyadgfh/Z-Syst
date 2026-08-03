-- Z-Syst Database Export for MySQL
CREATE DATABASE IF NOT EXISTS `if0_41922398_pharmasy_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `if0_41922398_pharmasy_db`;

-- Table structure for `audit_logs`
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `user_id` integer NOT NULL, `action` VARCHAR(255) NOT NULL, `description` LONGTEXT NOT NULL, `ip_address` varchar, `user_agent` LONGTEXT, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE);


-- Table structure for `auto_order_rules`
DROP TABLE IF EXISTS `auto_order_rules`;
CREATE TABLE `auto_order_rules` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `enabled` tinyint(1) NOT NULL default '1', `min_stock_level` numeric, `max_stock_level` numeric, `reorder_point` numeric, `safety_stock` numeric, `lead_TIME_days` integer, `min_order_qty` numeric, `max_order_qty` numeric, `order_multiple` numeric, `preferred_supplier_id` integer, `auto_approve` tinyint(1) NOT NULL default '0', `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, foreign key(`preferred_supplier_id`) references `parties`(`id`) on delete set null);


-- Table structure for `auto_order_suggestions`
DROP TABLE IF EXISTS `auto_order_suggestions`;
CREATE TABLE `auto_order_suggestions` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `preferred_supplier_id` integer, `predicted_demand` numeric NOT NULL default '0', `current_stock` numeric NOT NULL default '0', `pending_purchases` numeric NOT NULL default '0', `suggested_order_qty` numeric NOT NULL default '0', `confidence_score` numeric, `priority` VARCHAR(255) NOT NULL default 'medium', `status` VARCHAR(255) NOT NULL default 'pending', `approved_by` integer, `approved_at` DATETIME, `converted_purchase_id` integer, `converted_at` DATETIME, `notes` LONGTEXT, `reasoning` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, foreign key(`preferred_supplier_id`) references `parties`(`id`) on delete set null, foreign key(`approved_by`) references `users`(`id`) on delete set null, foreign key(`converted_purchase_id`) references `purchases`(`id`) on delete set null);


-- Table structure for `banners`
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `imageUrl` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `banners` (`id`, `name`, `imageUrl`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Special offers', 'uploads/25/02/1739422328-162.svg', '1', '2025-02-12 22:54:44', '2025-02-13 10:52:08');
INSERT INTO `banners` (`id`, `name`, `imageUrl`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Super Sales Up to 30% OFF', 'uploads/25/02/1739379295-429.svg', '1', '2025-02-12 22:54:55', '2025-02-13 10:41:00');
INSERT INTO `banners` (`id`, `name`, `imageUrl`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Pharmaceutical Medicine', 'uploads/25/02/1739422308-651.svg', '1', '2025-02-12 22:55:08', '2025-02-13 10:51:49');

-- Table structure for `blogs`
DROP TABLE IF EXISTS `blogs`;
CREATE TABLE `blogs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `user_id` integer NOT NULL, `title` VARCHAR(255) NOT NULL, `slug` VARCHAR(255) NOT NULL, `image` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL default '1', `descriptions` LONGTEXT, `tags` LONGTEXT, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE);


-- Table structure for `box_sizes`
DROP TABLE IF EXISTS `box_sizes`;
CREATE TABLE `box_sizes` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `name` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `business_categories`
DROP TABLE IF EXISTS `business_categories`;
CREATE TABLE `business_categories` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `description` LONGTEXT, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Pharmacy Store', 'Retail pharmacy for medicines and healthcare products.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Medical Supply Store', 'Store for medical equipment and supplies.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Health & Wellness Store', 'Retail store for vitamins, supplements, and health products.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Cosmetic & Beauty Store', 'Store for skincare, cosmetics, and beauty products.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Home Care Store', 'Store offering home healthcare products and essentials.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Diagnostic Center', 'Center for diagnostic and lab tests.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Herbal & Natural Store', 'Store for herbal and natural healthcare products.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `business_categories` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Pet Pharmacy', 'Store for pet medications and healthcare products.', '1', '2026-08-03 03:38:18', '2026-08-03 03:38:18');

-- Table structure for `businesses`
DROP TABLE IF EXISTS `businesses`;
CREATE TABLE `businesses` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `plan_subscribe_id` integer, `business_category_id` integer NOT NULL, `companyName` VARCHAR(255) NOT NULL, `will_expire` DATE, `address` varchar, `phoneNumber` varchar, `pictureUrl` varchar, `subscriptionDATE` DATETIME, `remainingShopBalance` float NOT NULL default '0', `shopOpeningBalance` float NOT NULL default '0', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_category_id`) REFERENCES `business_categories`(`id`) ON DELETE CASCADE);


-- Table structure for `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `categoryName` VARCHAR(255) NOT NULL, `description` varchar, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `comments`
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `blog_id` integer NOT NULL, `comment_id` integer, `name` VARCHAR(255) NOT NULL, `email` VARCHAR(255) NOT NULL, `comment` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE, FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE);


-- Table structure for `currencies`
DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `country_name` varchar, `code` VARCHAR(255) NOT NULL, `rate` float, `symbol` varchar, `position` varchar, `status` tinyint(1) NOT NULL default '1', `is_default` tinyint(1) NOT NULL default '0', `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('1', 'Afghanistan Afghani', 'Afghanistan', 'AFN', '67.271', '؋', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('2', 'Albania Lek', 'Albania', 'ALL', '91.47', 'L', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('3', 'Algerian Dinar', 'Algeria', 'DZD', '133.08', 'دج', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('4', 'US Dollar', 'United States of America', 'USD', '1', '$', 'left', '1', '1', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('5', 'Angolan Kwanza', 'Angola', 'AOA', '910.982', 'Kz', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('6', 'Argentine Peso', 'Argentina', 'ARS', '1007.685', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('7', 'Armenian Dram', 'Armenia', 'AMD', '389.25', '֏', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('8', 'Aruban Guilder', 'Aruba', 'AWG', '1.79', 'ƒ', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('9', 'Australian Dollar', 'Australia', 'AUD', '1.54', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('10', 'Euro', 'Austria', 'EUR', '0.948', '€', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('11', 'Azerbaijan Manat', 'Azerbaijan', 'AZN', '1.699', '₼', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('12', 'Bahamian Dollar', 'Bahamas', 'BSD', '1', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('13', 'Bahraini Dinar', 'Bahrain', 'BHD', '0.377', '.د.ب', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('14', 'Bangladesh Taka', 'Bangladesh', 'BDT', '119', '৳', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('15', 'Barbados Dollar', 'Barbados', 'BBD', '2', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('16', 'Belarusian Ruble', 'Belarus', 'BYN', '3.427', 'Br', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('17', 'Belize Dollar', 'Belize', 'BZD', '2', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('18', 'Bermuda Dollar', 'Bermuda', 'BMD', '1', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('19', 'Bhutan Ngultrum', 'Bhutan', 'BTN', '84.45', 'Nu.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('20', 'Bolivia Boliviano', 'Bolivia', 'BOB', '6.857', 'Bs.', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('21', 'Bosnia and Herzegovina Convertible Mark', 'Bosnia and Herzegovina', 'BAM', '1.854', 'KM', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('22', 'Botswana Pula', 'Botswana', 'BWP', '13.55', 'P', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('23', 'Brazilian Real', 'Brazil', 'BRL', '5.817', 'R$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('24', 'Brunei Dollar', 'Brunei', 'BND', '1.342', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('25', 'Bulgarian Lev', 'Bulgaria', 'BGN', '1.854', 'лв', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('26', 'Burundi Franc', 'Burundi', 'BIF', '2885.728', 'FBu', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('27', 'Cambodian Riel', 'Cambodia', 'KHR', '4017', '៛', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('28', 'Canadian Dollar', 'Canada', 'CAD', '1.405', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('29', 'Cape Verde Escudo', 'Cape Verde', 'CVE', '104.497', '$', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('30', 'Cayman Is. Dollar', 'Cayman Islands', 'KYD', '0.82', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('31', 'Chilean Peso', 'Chile', 'CLP', '973', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('32', 'Chinese Renminbi', 'China', 'CNY', '7.245', '¥', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('33', 'Colombian Peso', 'Colombia', 'COP', '4413', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('34', 'Comoros Franc', 'Comoros', 'KMF', '466.232', 'CF', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('35', 'Congo Franc, Dem. Rep.of', 'Congo, Dem. Rep.', 'CDF', '2843.075', 'FC', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('36', 'Costa Rica Colon', 'Costa Rica', 'CRC', '509.02', '₡', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('37', 'Cuban Peso', 'Cuba', 'CUP', '24', '₱', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('38', 'Czech Koruna', 'Czech Republic', 'CZK', '23.97', 'Kč', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('39', 'Danish Krone', 'Denmark', 'DKK', '7.068', 'kr', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('40', 'Djibouti Francs', 'Djibouti', 'DJF', '177', 'Fdj', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('41', 'E.C. Dollar', 'Anguilla', 'XCD', '2.7', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('42', 'Dominican Peso', 'Dominican Republic', 'DOP', '60.167', 'RD$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('43', 'Egyptian Pound', 'Egypt', 'EGP', '49.61', '£', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('44', 'Eritrea Nakfa', 'Eritrea', 'ERN', '15', 'Nfk', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('45', 'Lilangeni', 'Eswatini, Kingdom of', 'SZL', '18.155', 'E', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('46', 'Ethiopian Birr', 'Ethiopia', 'ETB', '121.808', 'Br', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('47', 'Fiji Dollar', 'Fiji', 'FJD', '2.25', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('48', 'Gambian Dalasi', 'Gambia', 'GMD', '70.409', 'D', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('49', 'Georgian Lari', 'Georgia', 'GEL', '2.743', '₾', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('50', 'Ghana Cedi', 'Ghana', 'GHS', '15.5', '₵', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('51', 'Gibraltar Pound', 'Gibraltar', 'GIP', '0.791', '£', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('52', 'Guatemala Quetzal(es)', 'Guatemala', 'GTQ', '7.709', 'Q', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('53', 'Guinean Franc', 'Guinea', 'GNF', '8552.17', 'FG', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('54', 'Guyana Dollar', 'Guyana', 'GYD', '207.9', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('55', 'Haiti Gourde', 'Haiti', 'HTG', '130.93', 'G', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('56', 'Honduras Lempira', 'Honduras', 'HNL', '24.957', 'L', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('57', 'HongKong Dollar', 'Hong Kong', 'HKD', '7.782', 'HK$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('58', 'Hungary Forint', 'Hungary', 'HUF', '391.5', 'Ft', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('59', 'Iceland Krona', 'Iceland', 'ISK', '137.04', 'kr', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('60', 'Indian Rupee', 'India', 'INR', '84.45', '₹', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('61', 'Indonesia Rupiah', 'Indonesia', 'IDR', '15930', 'Rp', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('62', 'Iranian Rial', 'Iran', 'IRR', '512897', '﷼', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('63', 'Iraqi Dinar', 'Iraq', 'IQD', '1310', 'ع.د', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('64', 'Israel Shekel', 'Israel', 'ILS', '3.647', '₪', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('65', 'Jamaican Dollar', 'Jamaica', 'JMD', '157.347', 'J$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('66', 'Japanese Yen', 'Japan', 'JPY', '151.42', '¥', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('67', 'Jordanian Dinar', 'Jordan', 'JOD', '0.708', 'د.ا', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('68', 'Kazakhstan Tenge', 'Kazakhstan', 'KZT', '502.23', '₸', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('69', 'Kenyan Shilling', 'Kenya', 'KES', '129', 'KSh', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('70', 'Korean Won, North Korea', 'Korea, D.P.R. of', 'KPW', '110', '₩', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('71', 'Korean Won, South Korea', 'Korea, Republic of', 'KRW', '1392.51', '₩', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('72', 'Kuwaiti Dinar', 'Kuwait', 'KWD', '0.31', 'د.ك', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('73', 'Kyrgyzstan Som', 'Kyrgyzstan', 'KGS', '86.25', 'с', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('74', 'Laos Kip', 'Lao, People''s Dem. Rep.', 'LAK', '22020', '₭', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('75', 'Lebanese Pound', 'Lebanon', 'LBP', '89500', 'ل.ل', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('76', 'Lesotho Loti', 'Lesotho', 'LSL', '18.155', 'M', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('77', 'Liberian Dollar', 'Liberia', 'LRD', '179.389', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('78', 'Libyan Dinar', 'Libyan Arab Jamahiriya', 'LYD', '4.894', 'د.ل', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('79', 'Macao Pataca', 'Macao', 'MOP', '8.015', 'P', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('80', 'Malagasy Ariary', 'Madagascar', 'MGA', '4667.74', 'Ar', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('81', 'Malawi Kwacha', 'Malawi', 'MWK', '1751', 'MK', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('82', 'Malaysia Ringgit', 'Malaysia', 'MYR', '4.44', 'RM', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('83', 'Maldives Rufiyaa', 'Maldives', 'MVR', '15', 'Rf', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('84', 'Mauritania Ouguiya', 'Mauritania', 'MRU', '39.77', 'UM', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('85', 'Mauritius Rupee', 'Mauritius', 'MUR', '46.64', '₨', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('86', 'Mexican Peso', 'Mexico', 'MXN', '20.68', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('87', 'Moldovan Leu', 'Moldova, Republic of', 'MDL', '18.225', 'L', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('88', 'Mongolia Tugrik', 'Mongolia', 'MNT', '3406', '₮', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('89', 'Morocco Dirham', 'Morocco', 'MAD', '10.004', 'د.م.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('90', 'Mozambique Metical', 'Mozambique', 'MZN', '63.25', 'MT', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('91', 'Myanmar Kyat', 'Myanmar', 'MMK', '4100', 'K', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('92', 'Namibia Dollar', 'Namibia', 'NAD', '18.155', 'N$', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('93', 'Nepalese Rupee', 'Nepal', 'NPR', '135.109', '₨', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('94', 'Netherlands Antilles Guilder', 'Netherlands Antilles', 'ANG', '1.79', 'ƒ', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('95', 'CFP Franc', 'New Caledonia', 'XPF', '113.089', '₣', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('96', 'New Zealand Dollar', 'New Zealand', 'NZD', '1.694', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('97', 'Nicaragua Cordoba Oro', 'Nicaragua', 'NIO', '36.55', 'C$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('98', 'Nigeria Naira', 'Nigeria', 'NGN', '1680.29', '₦', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('99', 'Denar', 'North Macedonia, Rep. of', 'MKD', '58.11', 'ден', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('100', 'Norwegian Krone', 'Norway', 'NOK', '11.072', 'kr', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('101', 'Oman Rial', 'Oman', 'OMR', '0.385', 'ر.ع.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('102', 'Pakistani Rupee', 'Pakistan', 'PKR', '277.771', '₨', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('103', 'Panama Balboa', 'Panama', 'PAB', '1', 'B/.', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('104', 'Kina', 'Papua New Guinea', 'PGK', '3.954', 'K', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('105', 'Paraguay Guarani', 'Paraguay', 'PYG', '7804', '₲', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('106', 'Sol', 'Peru', 'PEN', '3.758', 'S/', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('107', 'Philippine Peso', 'Philippines', 'PHP', '58.7', '₱', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('108', 'Poland Zloty', 'Poland', 'PLN', '4.079', 'zł', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('109', 'Qatari Rial', 'Qatar', 'QAR', '3.646', 'ر.ق.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('110', 'Romanian Leu', 'Romania', 'RON', '4.715', 'lei', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('111', 'Russian Rouble', 'Russian Federation', 'RUB', '111.6', '₽', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('112', 'Rwanda Franc', 'Rwanda', 'RWF', '1360.482', 'FRw', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('113', 'St. Helena Pound', 'Saint Helena', 'SHP', '0.791', '£', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('114', 'Samoa Tala', 'Samoa', 'WST', '2.752', 'T', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('115', 'Sao Tome Principe Dobra', 'Sao Tome and Principe', 'STN', '23.277', 'Db', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('116', 'Saudi Riyal', 'Saudi Arabia', 'SAR', '3.756', 'ر.س.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('117', 'CFA Franc', 'Senegal', 'XOF', '621.642', 'Fr', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('118', 'Serbian Dinar', 'Serbia', 'RSD', '110.802', 'дин.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('119', 'Seychelles Rupee', 'Seychelles', 'SCR', '13.654', '₨', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('120', 'Sierra Leone Leone', 'Sierra Leone', 'SLE', '23.39', 'Le', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('121', 'Singapore Dollar', 'Singapore', 'SGD', '1.342', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('122', 'Solomon Is. Dollar', 'Solomon Islands', 'SBD', '8.285', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('123', 'Somali Shilling', 'Somalia', 'SOS', '24300', 'S', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('124', 'South Africa Rand', 'South Africa', 'ZAR', '18.155', 'R', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('125', 'South Sudanese Pound', 'South Sudan', 'SSP', '3613.144', 'SSP', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('126', 'Sri Lanka Rupee', 'Sri Lanka', 'LKR', '290.57', 'Rs', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('127', 'Sudanese Pound', 'Sudan', 'SDG', '1987', 'ج.س.', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('128', 'Surinamese Dollar', 'Suriname', 'SRD', '35.517', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('129', 'Swedish Krona', 'Sweden', 'SEK', '10.918', 'kr', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('130', 'Swiss Franc', 'Switzerland', 'CHF', '0.882', 'CHF', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('131', 'Syrian Pound', 'Syrian Arab Republic', 'SYP', '13600', '£S', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('132', 'Tajikistan Somoni', 'Tajikistan', 'TJS', '10.67', 'SM', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('133', 'Tanzania Shilling', 'Tanzania, United Rep. of', 'TZS', '2640', 'TSh', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('134', 'Thai Baht', 'Thailand', 'THB', '34.43', '฿', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('135', 'Tonga Pa''anga', 'Tonga', 'TOP', '2.346', 'T$', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('136', 'Trinidad and Tobago Dollar', 'Trinidad and Tobago', 'TTD', '6.754', 'TT$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('137', 'Tunisian Dinar', 'Tunisia', 'TND', '3.146', 'د.ت', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('138', 'Turkish Lira', 'Turkey', 'TRY', '34.656', '₺', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('139', 'Turkmenistan Manat', 'Turkmenistan', 'TMT', '3.5', 'm', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('140', 'Uganda Shilling', 'Uganda', 'UGX', '3682', 'Sh', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('141', 'Ukraine Hryvnia', 'Ukraine', 'UAH', '41.5', '₴', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('142', 'United Arab Emirates Dirham', 'United Arab Emirates', 'AED', '3.673', 'د.إ', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('143', 'U.K. Pound', 'United Kingdom', 'GBP', '0.791', '£', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('144', 'Uruguay Peso', 'Uruguay', 'UYU', '42.87', '$', 'left', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('145', 'Uzbekistan Sum', 'Uzbekistan', 'UZS', '12830', 'сўм', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('146', 'Vanuatu Vatu', 'Vanuatu', 'VUV', '117.74', 'Vt', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('147', 'Bolivar Digital', 'Venezuela', 'VES', '46.327', 'Bs.S', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('148', 'Viet Nam Dong', 'Viet Nam', 'VND', '25384', '₫', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('149', 'Yemeni Rial', 'Yemen, Republic of', 'YER', '528.74', '﷼', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('150', 'Zambia Kwacha', 'Zambia', 'ZMW', '27.324', 'ZK', 'right', '1', '0', NULL, NULL);
INSERT INTO `currencies` (`id`, `name`, `country_name`, `code`, `rate`, `symbol`, `position`, `status`, `is_default`, `created_at`, `updated_at`) VALUES ('151', 'Zimbabwe Gold', 'Zimbabwe', 'ZWG', '25.333', 'ZWL', 'right', '1', '0', NULL, NULL);

-- Table structure for `drug_interactions`
DROP TABLE IF EXISTS `drug_interactions`;
CREATE TABLE `drug_interactions` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer, `drug_a_name` VARCHAR(255) NOT NULL, `drug_b_name` VARCHAR(255) NOT NULL, `severity` varchar) NOT NULL default 'moderate', `description` LONGTEXT NOT NULL, `mechanism` LONGTEXT, `recommendation` LONGTEXT, `source` varchar, `category` varchar, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);

INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('1', NULL, 'Warfarin', 'Aspirin', 'contraindicated', 'Increased risk of bleeding. Aspirin inhibits platelet aggregation and warfarin inhibits clotting factors, leading to synergistic anticoagulant effect.', 'Pharmacodynamic interaction - additive anticoagulation', 'Avoid concomitant use. If necessary, monitor INR closely and adjust warfarin dose. Consider alternative analgesic/antiplatelet therapy.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('2', NULL, 'Warfarin', 'NSAIDs', 'contraindicated', 'Significantly increased risk of gastrointestinal bleeding. NSAIDs inhibit COX-1, reducing gastric mucosal protection while warfarin impairs coagulation.', 'Pharmacodynamic interaction - additive anticoagulation + mucosal damage', 'Avoid concomitant use. If unavoidable, use lowest effective NSAID dose for shortest duration, add PPI for gastric protection, and monitor INR frequently.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('3', NULL, 'MAO Inhibitors', 'SSRIs', 'contraindicated', 'Risk of serotonin syndrome (agitation, hyperthermia, autonomic instability, neuromuscular abnormalities). Both drugs increase serotonin levels through different mechanisms.', 'Pharmacodynamic interaction - excessive serotonergic activity', 'Allow at least 14 days between discontinuing MAOI and starting SSRI. If serotonin syndrome occurs, discontinue both and provide supportive care.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('4', NULL, 'Cisapride', 'Erythromycin', 'contraindicated', 'Increased risk of life-threatening cardiac arrhythmias (QT prolongation, torsades de pointes). Both drugs can prolong QT interval.', 'Pharmacodynamic interaction - additive QT prolongation + CYP3A4 inhibition', 'Contraindicated. Use alternative prokinetic agent or alternative antibiotic.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('5', NULL, 'Statins', 'Gemfibrozil', 'contraindicated', 'Increased risk of myopathy and rhabdomyolysis. Gemfibrozil inhibits glucuronidation of statins, increasing statin plasma concentrations.', 'Pharmacokinetic interaction - inhibition of statin metabolism', 'Avoid combination. If combination therapy needed, use fenofibrate instead of gemfibrozil and monitor for muscle symptoms.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('6', NULL, 'ACE Inhibitors', 'Potassium-Sparing Diuretics', 'severe', 'Increased risk of hyperkalemia. ACE inhibitors decrease aldosterone production while potassium-sparing diuretics reduce potassium excretion.', 'Pharmacodynamic interaction - additive potassium retention', 'Monitor serum potassium regularly, especially in elderly patients and those with renal impairment. Consider alternative antihypertensive.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('7', NULL, 'Digoxin', 'Amiodarone', 'severe', 'Increased digoxin plasma concentration leading to digoxin toxicity (nausea, arrhythmias, visual disturbances). Amiodarone inhibits P-glycoprotein-mediated digoxin clearance.', 'Pharmacokinetic interaction - P-glycoprotein inhibition', 'Reduce digoxin dose by 50% when starting amiodarone. Monitor digoxin levels and renal function.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('8', NULL, 'Clopidogrel', 'Omeprazole', 'severe', 'Reduced clopidogrel effectiveness due to CYP2C19 inhibition by omeprazole, decreasing conversion to active metabolite.', 'Pharmacokinetic interaction - CYP2C19 inhibition', 'Use pantoprazole or other PPI not metabolized by CYP2C19 instead of omeprazole or esomeprazole.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('9', NULL, 'Methotrexate', 'Trimethoprim-Sulfamethoxazole', 'severe', 'Increased risk of methotrexate toxicity (bone marrow suppression, hepatotoxicity). Additive antifolate effect and reduced renal clearance.', 'Pharmacodynamic + pharmacokinetic - additive antifolate effect', 'Avoid concomitant use if possible. If necessary, monitor CBC, LFTs closely and consider folinic acid rescue.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('10', NULL, 'Lithium', 'NSAIDs', 'severe', 'Increased lithium plasma concentration leading to lithium toxicity. NSAIDs reduce renal lithium clearance by inhibiting prostaglandin synthesis.', 'Pharmacokinetic interaction - reduced renal clearance', 'Monitor lithium levels closely when starting/stopping NSAIDs. May need to reduce lithium dose. Prefer acetaminophen for pain.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('11', NULL, 'Metformin', 'Contrast Dye (Iodinated)', 'moderate', 'Increased risk of lactic acidosis, particularly in patients with renal impairment. Contrast dye can cause acute kidney injury reducing metformin clearance.', 'Pharmacokinetic interaction', 'Discontinue metformin at time of or before contrast procedure, hold for 48 hours, and restart after renal function confirmed normal.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('12', NULL, 'SSRIs', 'NSAIDs', 'moderate', 'Increased risk of upper gastrointestinal bleeding. SSRIs impair platelet function by depleting serotonin in platelets, while NSAIDs cause gastric mucosal damage.', 'Pharmacodynamic interaction - additive effect on bleeding risk', 'Consider adding PPI for gastric protection. Monitor for signs of GI bleeding. Consider alternative antidepressant or analgesic.', 'PubMed / Clinical Studies', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('13', NULL, 'Theophylline', 'Ciprofloxacin', 'moderate', 'Increased theophylline concentration causing toxicity (nausea, tachycardia, seizures). Ciprofloxacin inhibits CYP1A2-mediated theophylline metabolism.', 'Pharmacokinetic interaction - CYP1A2 inhibition', 'Monitor theophylline levels and reduce dose as needed. Consider alternative antibiotic.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('14', NULL, 'Phenytoin', 'Fluconazole', 'moderate', 'Increased phenytoin concentration causing toxicity. Fluconazole inhibits CYP2C9-mediated phenytoin metabolism.', 'Pharmacokinetic interaction - CYP2C9 inhibition', 'Monitor phenytoin levels and reduce dose if necessary. Monitor for phenytoin toxicity (nystagmus, ataxia, slurred speech).', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('15', NULL, 'Calcium Channel Blockers', 'Beta Blockers', 'moderate', 'Increased risk of bradycardia, heart block, and hypotension. Both drugs have negative chronotropic and inotropic effects on the heart.', 'Pharmacodynamic interaction - additive cardiovascular depression', 'Monitor heart rate and blood pressure. Particularly caution with verapamil/diltiazem combined with beta blockers.', 'FDA Label', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('16', NULL, 'Antacids', 'Tetracyclines', 'minor', 'Reduced tetracycline absorption. Antacids containing aluminum, calcium, or magnesium chelate with tetracyclines reducing bioavailability.', 'Pharmacokinetic interaction - chelation in GI tract', 'Separate administration by at least 2 hours. Take tetracycline 1 hour before or 2 hours after antacids.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('17', NULL, 'Vitamin K Antagonists', 'Green Tea', 'minor', 'Reduced anticoagulant effect. Green tea contains vitamin K which can antagonize warfarin effect.', 'Pharmacodynamic interaction - vitamin K content', 'Maintain consistent intake of green tea. Monitor INR if consumption changes significantly.', 'PubMed / Clinical Studies', 'pharmacodynamic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('18', NULL, 'Iron Supplements', 'Levothyroxine', 'minor', 'Reduced levothyroxine absorption. Iron forms insoluble complexes with levothyroxine in the GI tract.', 'Pharmacokinetic interaction - GI chelation', 'Separate administration by at least 4 hours. Monitor thyroid function tests if starting/stopping iron.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('19', NULL, 'Caffeine', 'Quinolones', 'minor', 'Increased caffeine effects (nervousness, insomnia, heart palpitations). Quinolones inhibit CYP1A2 reducing caffeine metabolism.', 'Pharmacokinetic interaction - CYP1A2 inhibition', 'Limit caffeine intake during quinolone therapy. Educate patient about potential increased caffeine sensitivity.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');
INSERT INTO `drug_interactions` (`id`, `business_id`, `drug_a_name`, `drug_b_name`, `severity`, `description`, `mechanism`, `recommendation`, `source`, `category`, `meta`, `created_at`, `updated_at`) VALUES ('20', NULL, 'Grapefruit Juice', 'Statins', 'minor', 'Increased statin plasma concentration. Grapefruit juice inhibits CYP3A4 in the gut wall, increasing bioavailability of statins like simvastatin and atorvastatin.', 'Pharmacokinetic interaction - CYP3A4 inhibition', 'Limit grapefruit juice intake (less than 1 quart/day). Avoid grapefruit juice with simvastatin and lovastatin. Rosuvastatin and pravastatin are minimally affected.', 'FDA Label', 'pharmacokinetic', NULL, '2026-08-03 03:38:21', '2026-08-03 03:38:21');

-- Table structure for `due_collects`
DROP TABLE IF EXISTS `due_collects`;
CREATE TABLE `due_collects` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `party_id` integer, `user_id` integer, `sale_id` integer, `purchase_id` integer, `invoiceNumber` varchar, `totalDue` float NOT NULL default '0', `dueAmountAfterPay` float NOT NULL default '0', `payDueAmount` float NOT NULL default '0', `paymentType` VARCHAR(255) NOT NULL default 'Cash', `paymentDATE` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`party_id`) references `parties`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null, foreign key(`sale_id`) references `sales`(`id`) on delete set null, FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE);


-- Table structure for `expense_categories`
DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `categoryName` VARCHAR(255) NOT NULL, `business_id` integer NOT NULL, `categoryDescription` LONGTEXT, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `expenses`
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `amount` float NOT NULL, `expense_category_id` integer, `user_id` integer, `business_id` integer NOT NULL, `expanseFor` varchar, `paymentType` VARCHAR(255) NOT NULL default 'Cash', `referenceNo` varchar, `note` LONGTEXT, `expenseDATE` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, foreign key(`expense_category_id`) references `expense_categories`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `failed_jobs`
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `uuid` VARCHAR(255) NOT NULL, `connection` LONGTEXT NOT NULL, `queue` LONGTEXT NOT NULL, `payload` LONGTEXT NOT NULL, `exception` LONGTEXT NOT NULL, `failed_at` DATETIME NOT NULL default CURRENT_TIMESTAMP);


-- Table structure for `features`
DROP TABLE IF EXISTS `features`;
CREATE TABLE `features` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `title` VARCHAR(255) NOT NULL, `bg_color` varchar, `image` varchar, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME);


-- Table structure for `fefo_logs`
DROP TABLE IF EXISTS `fefo_logs`;
CREATE TABLE `fefo_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `stock_id` integer, `sale_id` integer, `sale_detail_id` integer, `batch_no` varchar, `expire_DATE` DATE, `quantity_deducted` integer NOT NULL default '0', `quantity_remaining_after` integer NOT NULL default '0', `action_type` VARCHAR(255) NOT NULL default 'sale_deduction', `notes` LONGTEXT, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, foreign key(`stock_id`) references `stocks`(`id`) on delete set null, foreign key(`sale_id`) references `sales`(`id`) on delete set null, foreign key(`sale_detail_id`) references `sale_details`(`id`) on delete set null);


-- Table structure for `fefo_settings`
DROP TABLE IF EXISTS `fefo_settings`;
CREATE TABLE `fefo_settings` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `fefo_enabled` tinyint(1) NOT NULL default '1', `deduction_mode` varchar) NOT NULL default 'automatic', `expiry_grace_days` integer NOT NULL default '30', `auto_deduct_expired_stock` tinyint(1) NOT NULL default '0', `notify_on_fefo_deduction` tinyint(1) NOT NULL default '1', `min_stock_for_fefo` integer NOT NULL default '0', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `financial_audit_logs`
DROP TABLE IF EXISTS `financial_audit_logs`;
CREATE TABLE `financial_audit_logs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `user_id` integer, `audit_number` VARCHAR(255) NOT NULL, `audit_type` varchar) NOT NULL default 'monthly', `start_DATE` DATE NOT NULL, `end_DATE` DATE NOT NULL, `status` varchar) NOT NULL default 'pending', `opening_balance` float NOT NULL default '0', `total_revenue` float NOT NULL default '0', `total_expenses` float NOT NULL default '0', `closing_balance` float NOT NULL default '0', `variance` float NOT NULL default '0', `notes` LONGTEXT, `metadata` LONGTEXT, `completed_at` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`user_id`) references `users`(`id`) on delete set null);


-- Table structure for `gateways`
DROP TABLE IF EXISTS `gateways`;
CREATE TABLE `gateways` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `currency_id` integer NOT NULL, `mode` VARCHAR(255) NOT NULL, `status` VARCHAR(255) NOT NULL, `charge` tinyint(1) NOT NULL default '0', `image` varchar, `data` LONGTEXT, `manual_data` LONGTEXT, `is_manual` tinyint(1) NOT NULL default '0', `accept_img` tinyint(1) NOT NULL default '0', `namespace` varchar, `phone_required` integer NOT NULL default '0', `instructions` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE);

INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('1', 'Stripe', '4', '1', '1', '2', NULL, '{"stripe_key":"pk_test_6rhnZv1NmRtSp5DfziBO8YFb00X65CfFwq","stripe_secret":"sk_test_YKyuAoMHjXaUADW4SuKuXeIn0079Pu1OSD"}', NULL, '0', '0', 'App\Library\StripeGateway', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('2', 'Mollie', '4', '1', '1', '2', NULL, '{"api_key":"test_WqUGsP9qywy3eRVvWMRayxmVB5dx2r"}', NULL, '0', '0', 'App\Library\Mollie', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('3', 'Paypal', '4', '1', '1', '2', NULL, '{"client_id":"ARKsbdD1qRpl3WEV6XCLuTUsvE1_5NnQuazG2Rvw1NkMG3owPjCeAaia0SXSvoKPYNTrh55jZieVW7xv","client_secret":"EJed2cGACzB2SJFQwSannKAA1gyBjKkwlKh1o8G75zQHYzAgLQ3n7f9EfeNCZgtfPDMxyFzfp6oQWPia"}', NULL, '0', '0', 'App\Library\Paypal', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('4', 'Paystack', '98', '1', '1', '2', NULL, '{"public_key":"pk_test_84d91b79433a648f2cd0cb69287527f1cb81b53d","secret_key":"sk_test_cf3a234b923f32194fb5163c9d0ab706b864cc3e"}', NULL, '0', '0', 'App\Library\Paystack', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('5', 'Razorpay', '60', '1', '1', '2', NULL, '{"key_id":"rzp_test_siWkeZjPLsYGSi","key_secret":"jmIzYyrRVMLkC9BwqCJ0wbmt"}', NULL, '0', '0', 'App\Library\Razorpay', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('6', 'Instamojo', '60', '1', '1', '2', NULL, '{"x_api_key":"test_0027bc9da0a955f6d33a33d4a5d","x_auth_token":"test_211beaba149075c9268a47f26c6"}', NULL, '0', '0', 'App\Library\Instamojo', '1', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('7', 'Toyyibpay', '82', '1', '1', '2', NULL, '{"user_secret_key":"v4nm8x50-bfb4-7f8y-evrs-85flcysx5b9p","cateogry_code":"5cc45t69"}', NULL, '0', '0', 'App\Library\Toyyibpay', '1', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('8', 'Flutterwave', '98', '1', '1', '2', NULL, '{"public_key":"FLWPUBK_TEST-f448f625c416f69a7c08fc6028ebebbf-X","secret_key":"FLWSECK_TEST-561fa94f45fc758339b1e54b393f3178-X","encryption_key":"FLWSECK_TEST498417c2cc01","payment_options":"card"}', NULL, '0', '0', 'App\Library\Flutterwave', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('9', 'Thawani', '101', '1', '1', '2', NULL, '{"secret_key":"rRQ26GcsZzoEhbrP2HZvLYDbn9C9et","publishable_key":"HGvTMLDssJghr9tlN9gr4DVYt0qyBy"}', NULL, '0', '0', 'App\Library\Thawani', '1', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('10', 'Mercadopago', '4', '1', '1', '2', NULL, '{"secret_key":"TEST-1884511374835248-071019-698f8465954d5983722e8b4d7a05f1ca-370993848","public_key":"TEST-7d239fd1-3c41-4dc0-96eb-f759b7d2adab"}', NULL, '0', '0', 'App\Library\Mercado', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('11', 'Paytm', '60', '1', '1', '2', NULL, '{"merchant_id":"MhjqFc42556626519745","merchant_key":"0dC_Dq!nif6e1Kie","channel":"WEB","industry_type":"Retail","website":"WEBSTAGING"}', NULL, '0', '0', 'App\Library\Paytm', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('12', 'Tap Payment', '116', '1', '1', '2', NULL, '{"secret_key":"sk_test_KbfoWyw7tzhDHQSF62ICavZx","currency":"SAR"}', NULL, '0', '0', 'App\Library\TapPayment', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:54:44');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('13', 'Sslcommerz', '14', '1', '1', '1', NULL, '{"store_id":"maant62a8633caf4a3","store_password":"maant62a8633caf4a3@ssl"}', NULL, '0', '0', 'App\Library\SslCommerz', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 17:45:52');
INSERT INTO `gateways` (`id`, `name`, `currency_id`, `mode`, `status`, `charge`, `image`, `data`, `manual_data`, `is_manual`, `accept_img`, `namespace`, `phone_required`, `instructions`, `created_at`, `updated_at`) VALUES ('14', 'Manual', '4', '1', '1', '0', NULL, '', '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}', '1', '1', 'App\Library\StripeGateway', '0', NULL, '2024-02-18 17:45:52', '2024-02-18 18:24:39');

-- Table structure for `income_categories`
DROP TABLE IF EXISTS `income_categories`;
CREATE TABLE `income_categories` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `categoryName` VARCHAR(255) NOT NULL, `business_id` integer NOT NULL, `categoryDescription` LONGTEXT, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `incomes`
DROP TABLE IF EXISTS `incomes`;
CREATE TABLE `incomes` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `amount` float NOT NULL, `income_category_id` integer, `user_id` integer, `business_id` integer NOT NULL, `incomeFor` varchar, `paymentType` VARCHAR(255) NOT NULL default 'Cash', `referenceNo` varchar, `note` LONGTEXT, `incomeDATE` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, foreign key(`income_category_id`) references `income_categories`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `insurance_claims`
DROP TABLE IF EXISTS `insurance_claims`;
CREATE TABLE `insurance_claims` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `insurance_company_id` integer NOT NULL, `insurance_policy_id` integer NOT NULL, `sale_id` integer, `prescription_id` integer, `customer_id` integer, `user_id` integer, `claim_number` VARCHAR(255) NOT NULL, `service_DATE` DATE NOT NULL, `submission_DATE` DATE, `total_amount` numeric NOT NULL, `covered_amount` numeric NOT NULL default '0', `patient_responsibility` numeric NOT NULL default '0', `approved_amount` numeric, `paid_amount` numeric NOT NULL default '0', `rejected_amount` numeric NOT NULL default '0', `status` varchar) NOT NULL default 'draft', `rejection_reason` varchar, `external_reference` varchar, `settlement_DATE` DATE, `notes` LONGTEXT, `line_items` LONGTEXT, `metadata` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies`(`id`) ON DELETE CASCADE, FOREIGN KEY (`insurance_policy_id`) REFERENCES `insurance_policies`(`id`) ON DELETE CASCADE, foreign key(`sale_id`) references `sales`(`id`) on delete set null, foreign key(`prescription_id`) references `prescriptions`(`id`) on delete set null, foreign key(`customer_id`) references `parties`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null);


-- Table structure for `insurance_companies`
DROP TABLE IF EXISTS `insurance_companies`;
CREATE TABLE `insurance_companies` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `name` VARCHAR(255) NOT NULL, `code` VARCHAR(255) NOT NULL, `contact_person` varchar, `phone` varchar, `email` varchar, `address` LONGTEXT, `city` varchar, `country` varchar, `tax_id` varchar, `status` varchar) NOT NULL default 'active', `integration_type` varchar) NOT NULL default 'manual', `api_endpoint` varchar, `api_credentials` LONGTEXT, `default_coverage_percent` numeric NOT NULL default '80', `default_copay_percent` numeric NOT NULL default '20', `settlement_days` integer NOT NULL default '30', `notes` LONGTEXT, `metadata` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `insurance_coverages`
DROP TABLE IF EXISTS `insurance_coverages`;
CREATE TABLE `insurance_coverages` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `insurance_company_id` integer NOT NULL, `product_id` integer, `category_id` integer, `coverage_code` varchar, `scope` varchar) NOT NULL default 'all', `coverage_percent` numeric NOT NULL default '100', `copay_percent` numeric NOT NULL default '0', `max_amount_per_claim` numeric, `max_amount_per_year` numeric, `requires_preauthorization` tinyint(1) NOT NULL default '0', `is_active` tinyint(1) NOT NULL default '1', `effective_from` DATE, `effective_to` DATE, `notes` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies`(`id`) ON DELETE CASCADE, foreign key(`product_id`) references `products`(`id`) on delete set null, foreign key(`category_id`) references `categories`(`id`) on delete set null);


-- Table structure for `insurance_policies`
DROP TABLE IF EXISTS `insurance_policies`;
CREATE TABLE `insurance_policies` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `insurance_company_id` integer NOT NULL, `customer_id` integer, `policy_number` VARCHAR(255) NOT NULL, `member_id` varchar, `card_number` varchar, `holder_name` VARCHAR(255) NOT NULL, `holder_dob` DATE, `holder_gender` varchar), `holder_phone` varchar, `holder_email` varchar, `holder_address` LONGTEXT, `plan_type` varchar) NOT NULL default 'individual', `status` varchar) NOT NULL default 'active', `start_DATE` DATE NOT NULL, `end_DATE` DATE NOT NULL, `annual_limit` numeric, `used_amount` numeric NOT NULL default '0', `remaining_limit` numeric, `coverage_percent` numeric, `copay_percent` numeric, `notes` LONGTEXT, `metadata` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies`(`id`) ON DELETE CASCADE, foreign key(`customer_id`) references `parties`(`id`) on delete set null);


-- Table structure for `inventory_turnover_reports`
DROP TABLE IF EXISTS `inventory_turnover_reports`;
CREATE TABLE `inventory_turnover_reports` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `report_type` varchar) NOT NULL default 'monthly', `period_start` DATE NOT NULL, `period_end` DATE NOT NULL, `total_cogs` numeric NOT NULL default '0', `average_inventory_value` numeric NOT NULL default '0', `inventory_turnover_ratio` numeric NOT NULL default '0', `days_inventory_outstanding` numeric NOT NULL default '0', `total_products_analyzed` integer NOT NULL default '0', `slow_moving_count` integer NOT NULL default '0', `dead_stock_count` integer NOT NULL default '0', `total_slow_moving_value` numeric NOT NULL default '0', `total_dead_stock_value` numeric NOT NULL default '0', `total_inventory_value` numeric NOT NULL default '0', `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `jobs`
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `queue` VARCHAR(255) NOT NULL, `payload` LONGTEXT NOT NULL, `attempts` integer NOT NULL, `reserved_at` integer, `available_at` integer NOT NULL, `created_at` integer NOT NULL);


-- Table structure for `languages`
DROP TABLE IF EXISTS `languages`;
CREATE TABLE `languages` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `icon` varchar, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Comming Soon', 'uploads/24/04/1713263132-74.png', '1', '2024-04-16 16:25:32', '2024-04-16 16:25:32');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Portuguese (BR)', 'uploads/24/04/1713263352-609.png', '1', '2024-04-16 16:29:12', '2024-04-16 16:29:12');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Chinese (TW)', 'uploads/24/04/1713263390-316.png', '1', '2024-04-16 16:29:50', '2024-04-16 16:29:50');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('4', 'Chinese (CN)', 'uploads/24/04/1713263431-511.png', '1', '2024-04-16 16:30:31', '2024-04-16 16:30:31');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('5', 'Azerbaijani', 'uploads/24/04/1713263469-306.png', '1', '2024-04-16 16:31:09', '2024-04-16 16:31:09');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('6', 'Kazakhastan', 'uploads/24/04/1713263502-600.png', '1', '2024-04-16 16:31:42', '2024-04-16 16:31:42');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('7', 'Tigrinya', 'uploads/24/04/1713263543-203.png', '1', '2024-04-16 16:32:23', '2024-04-16 16:32:23');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('8', 'Burmuse', 'uploads/24/04/1713263573-197.png', '1', '2024-04-16 16:32:53', '2024-04-16 16:32:53');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('9', 'Swahili', 'uploads/24/04/1713263661-746.png', '1', '2024-04-16 16:33:31', '2024-04-16 16:34:21');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('10', 'Slovak', 'uploads/24/04/1713263705-676.png', '1', '2024-04-16 16:35:05', '2024-04-16 16:35:05');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('11', 'Albanian', 'uploads/24/04/1713263748-483.png', '1', '2024-04-16 16:35:48', '2024-04-16 16:35:48');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('12', 'Urdu', 'uploads/24/04/1713263785-829.png', '1', '2024-04-16 16:36:25', '2024-04-16 16:36:25');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('13', 'Danish', 'uploads/24/04/1713263817-546.png', '1', '2024-04-16 16:36:57', '2024-04-16 16:36:57');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('14', 'Swedish', 'uploads/24/04/1713263851-61.png', '1', '2024-04-16 16:37:31', '2024-04-16 16:37:31');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('15', 'Marathi', 'uploads/24/04/1713263883-87.png', '1', '2024-04-16 16:38:03', '2024-04-16 16:38:03');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('16', 'Kannada', 'uploads/24/04/1713263922-810.png', '1', '2024-04-16 16:38:42', '2024-04-16 16:38:42');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('17', 'Czech', 'uploads/24/04/1713263954-737.png', '1', '2024-04-16 16:39:14', '2024-04-16 16:39:14');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('18', 'Русский', 'uploads/24/04/1713263985-750.png', '1', '2024-04-16 16:39:45', '2024-04-16 16:39:45');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('19', 'Lao', 'uploads/24/04/1713264024-299.png', '1', '2024-04-16 16:40:24', '2024-04-16 16:40:24');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('20', 'Ukrainian', 'uploads/24/04/1713264051-664.png', '1', '2024-04-16 16:40:51', '2024-04-16 16:40:51');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('21', 'Khmer', 'uploads/24/04/1713264076-901.png', '1', '2024-04-16 16:41:16', '2024-04-16 16:41:16');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('22', 'Serbian', 'uploads/24/04/1713264104-342.png', '1', '2024-04-16 16:41:44', '2024-04-16 16:41:44');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('23', 'Turkish', 'uploads/24/04/1713264131-167.png', '1', '2024-04-16 16:42:11', '2024-04-16 16:42:11');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('24', 'Persian', 'uploads/24/04/1713264160-560.png', '1', '2024-04-16 16:42:40', '2024-04-16 16:42:40');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('25', 'Indonesian', 'uploads/24/04/1713264189-370.png', '1', '2024-04-16 16:43:09', '2024-04-16 16:43:09');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('26', 'Malay', 'uploads/24/04/1713264218-608.png', '1', '2024-04-16 16:43:38', '2024-04-16 16:43:38');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('27', 'Korean', 'uploads/24/04/1713264250-943.png', '1', '2024-04-16 16:44:10', '2024-04-16 16:44:10');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('28', 'Greek', 'uploads/24/04/1713264276-755.png', '1', '2024-04-16 16:44:37', '2024-04-16 16:44:37');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('29', 'Finland', 'uploads/24/04/1713264306-829.png', '1', '2024-04-16 16:45:06', '2024-04-16 16:45:06');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('30', 'Hungarian', 'uploads/24/04/1713264331-326.png', '1', '2024-04-16 16:45:31', '2024-04-16 16:45:31');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('31', 'Polish', 'uploads/24/04/1713264358-886.png', '1', '2024-04-16 16:45:58', '2024-04-16 16:45:58');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('32', 'Bengali', 'uploads/24/04/1713264388-157.png', '1', '2024-04-16 16:46:28', '2024-04-16 16:46:28');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('33', 'Portuguese', 'uploads/24/04/1713264423-206.png', '1', '2024-04-16 16:47:03', '2024-04-16 16:47:03');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('34', 'Hebrew', 'uploads/24/04/1713264450-677.png', '1', '2024-04-16 16:47:30', '2024-04-16 16:47:30');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('35', 'Dutch', 'uploads/24/04/1713264476-832.png', '1', '2024-04-16 16:47:56', '2024-04-16 16:47:56');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('36', 'Bosnian', 'uploads/24/04/1713264505-83.png', '1', '2024-04-16 16:48:25', '2024-04-16 16:48:25');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('37', 'Thai', 'uploads/24/04/1713264534-163.png', '1', '2024-04-16 16:48:54', '2024-04-16 16:48:54');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('38', 'Italian', 'uploads/24/04/1713264559-834.png', '1', '2024-04-16 16:49:19', '2024-04-16 16:49:19');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('39', 'Vietnamese', 'uploads/24/04/1713264586-161.png', '1', '2024-04-16 16:49:46', '2024-04-16 16:49:46');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('40', 'German', 'uploads/24/04/1713264610-223.png', '1', '2024-04-16 16:50:10', '2024-04-16 16:50:10');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('41', 'Romanian', 'uploads/24/04/1713264637-599.png', '1', '2024-04-16 16:50:37', '2024-04-16 16:50:37');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('42', 'Arabic', 'uploads/24/04/1713264667-831.png', '1', '2024-04-16 16:51:07', '2024-04-16 16:51:07');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('43', 'Japanese', 'uploads/24/04/1713264693-992.png', '1', '2024-04-16 16:51:33', '2024-04-16 16:51:33');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('44', 'Spanish', 'uploads/24/04/1713264720-829.png', '1', '2024-04-16 16:52:00', '2024-04-16 16:52:00');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('45', 'French', 'uploads/24/04/1713264745-349.png', '1', '2024-04-16 16:52:25', '2024-04-16 16:52:25');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('46', 'Hindi', 'uploads/24/04/1713264770-181.png', '1', '2024-04-16 16:52:50', '2024-04-16 16:52:50');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('47', 'Chinese', 'uploads/24/04/1713264810-300.png', '1', '2024-04-16 16:53:30', '2024-04-16 16:53:30');
INSERT INTO `languages` (`id`, `name`, `icon`, `status`, `created_at`, `updated_at`) VALUES ('48', 'English', 'uploads/24/04/1713264836-549.png', '1', '2024-04-16 16:53:56', '2024-04-16 16:53:56');

-- Table structure for `manufacturers`
DROP TABLE IF EXISTS `manufacturers`;
CREATE TABLE `manufacturers` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `name` VARCHAR(255) NOT NULL, `description` varchar, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `medicine_types`
DROP TABLE IF EXISTS `medicine_types`;
CREATE TABLE `medicine_types` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `name` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `messages`
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `phone` VARCHAR(255) NOT NULL, `email` VARCHAR(255) NOT NULL, `company_name` varchar, `message` VARCHAR(255) NOT NULL, `created_at` DATETIME, `upDATEd_at` DATETIME);


-- Table structure for `migrations`
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `migration` VARCHAR(255) NOT NULL, `batch` integer NOT NULL);

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '2014_10_10_000001_create_plans_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '2014_10_11_000001_create_business_categories_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '2014_10_11_000001_create_businesses_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2014_10_11_000001_create_currencies_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2014_10_11_000001_create_gateways_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2014_10_11_000002_create_plan_subscribes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2014_10_12_000003_create_users_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2014_10_12_100000_create_password_reset_tokens_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2014_10_12_100000_create_password_resets_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('10', '2019_08_19_000000_create_failed_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('11', '2019_12_14_000001_create_personal_access_tokens_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('12', '2023_05_20_040815_create_notifications_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('13', '2023_07_10_104133_create_options_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('14', '2023_12_24_162456_create_categories_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('15', '2023_12_24_162619_create_parties_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('16', '2023_12_24_164558_create_expense_categories_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('17', '2023_12_24_164659_create_expenses_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('18', '2023_12_24_170916_create_units_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('19', '2023_12_24_170917_create_manufacturers_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('20', '2023_12_24_171613_create_taxes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('21', '2023_12_24_171614_create_box_sizes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('22', '2023_12_24_171614_create_medicine_types_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('23', '2023_12_24_171614_create_products_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('24', '2023_12_26_161841_create_purchases_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('25', '2023_12_26_163232_create_purchase_details_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('26', '2023_12_26_170106_create_sales_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('27', '2023_12_26_170111_create_sale_details_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('28', '2023_12_27_114439_create_banners_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('29', '2023_12_27_131216_create_due_collects_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('30', '2023_12_28_160816_create_permission_tables', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('31', '2024_04_15_110711_create_languages_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('32', '2024_08_06_174713_create_jobs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('33', '2024_09_26_104816_create_income_categories_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('34', '2024_09_26_110707_create_incomes_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('35', '2024_10_21_105615_create_sale_returns_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('36', '2024_10_21_105624_create_sale_return_details_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('37', '2024_11_03_090747_create_purchase_returns_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('38', '2024_11_03_090801_create_purchase_return_details_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('39', '2024_12_05_115812_create_stocks_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('40', '2025_01_10_000001_create_prescriptions_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('41', '2025_01_13_092753_create_features_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('42', '2025_01_13_092800_create_blogs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('43', '2025_01_13_092835_create_comments_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('44', '2025_01_13_092905_create_testimonials_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('45', '2025_01_13_092913_create_messages_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('46', '2025_01_13_092921_create_pos_app_interfaces_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('47', '2025_05_18_000001_create_drug_interactions_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('48', '2025_06_01_000001_create_fefo_settings_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('49', '2025_06_01_000002_create_fefo_logs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('50', '2025_06_10_000001_create_prediction_settings_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('51', '2025_06_10_000002_create_sales_forecasts_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('52', '2025_06_10_000003_create_auto_order_rules_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('53', '2025_06_10_000004_create_auto_order_suggestions_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('54', '2025_06_10_000005_create_inventory_turnover_reports_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('55', '2025_06_10_000006_create_product_inventory_analysis_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('56', '2026_07_28_154839_create_stock_audits_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('57', '2026_07_28_154847_create_stock_audit_details_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('58', '2026_07_28_154855_create_stock_reconciliations_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('59', '2026_07_28_154903_create_financial_audit_logs_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('60', '2026_07_28_154913_create_stock_movements_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('61', '2026_07_29_000001_create_insurance_companies_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('62', '2026_07_29_000002_create_insurance_policies_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('63', '2026_07_29_000003_create_insurance_claims_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('64', '2026_07_29_000004_create_insurance_coverages_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('65', '2026_08_01_000001_create_audit_logs_table', '1');

-- Table structure for `model_has_permissions`
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (`permission_id` integer NOT NULL, `model_type` VARCHAR(255) NOT NULL, `model_id` integer NOT NULL, FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE, primary key (`permission_id`, `model_id`, `model_type`));


-- Table structure for `model_has_roles`
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (`role_id` integer NOT NULL, `model_type` VARCHAR(255) NOT NULL, `model_id` integer NOT NULL, FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE, primary key (`role_id`, `model_id`, `model_type`));

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES ('1', 'App\Models\User', '1');
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES ('2', 'App\Models\User', '2');
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES ('3', 'App\Models\User', '3');

-- Table structure for `notifications`
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (`id` VARCHAR(255) NOT NULL, `type` VARCHAR(255) NOT NULL, `notifiable_type` VARCHAR(255) NOT NULL, `notifiable_id` integer NOT NULL, `data` LONGTEXT NOT NULL, `read_at` DATETIME, `deleted_at` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, primary key (`id`));


-- Table structure for `options`
DROP TABLE IF EXISTS `options`;
CREATE TABLE `options` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `key` VARCHAR(255) NOT NULL, `value` LONGTEXT NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `options` (`id`, `key`, `value`, `status`, `created_at`, `updated_at`) VALUES ('1', 'general', '{"title":"Z-Syst Pharmacy","copy_right":"\u00a9 2025 Z-Syst, all rights reserved.","admin_footer_text":"Development By","admin_footer_link_text":"Z-Syst","admin_footer_link":"https:\/\/z-syst.com\/","favicon":"assets/images/logo/favicon.png","admin_logo":"assets/images/logo/logo.png","frontend_logo":"assets/images/logo/logo.png"}', '1', '2024-04-15 12:55:07', '2025-02-11 15:01:11');
INSERT INTO `options` (`id`, `key`, `value`, `status`, `created_at`, `updated_at`) VALUES ('3', 'manage-pages', '{"headings":{"slider_title":"Modern Online software for","slider_btn1":"Buy Now","slider_btn2":"Watch Video","slider_btn2_link":"https:\/\/www.youtube.com\/embed\/EEryt_-M-6Q?si=8iBjlbj8ip6BrqPm","slider_description":"Z-Syst is a fully featured pharmacy management platform with modern tools, intuitive workflows, and reliable business operations support.","silder_shop_text":["Retail medicine store","Wholesale medicine store","Dealer medicine store"],"header_btn_text":"Login","header_btn_link":"login","feature_title_start":"Our Amazing","feature_title_end":"Features","interface_title_start":"Beautiful Interface Of Our","interface_title_end":"Pharmacy App","interface_description":"The world''s largest creative network for showcasing and discovering creative work. Our best search experience is on our mobile app.","get_app_title_start":"Get The","get_app_title_middle":"Z-Syst Pharmacy","get_app_title_end":"Mobile App","get_apple_app_link":null,"get_google_play_app_link":null,"get_app_description":"Access your business anytime, anywhere with the Z-Syst Pharmacy app for smarter operations and faster control.","pricing_short_title_start":"Our","pricing_short_title_middle":"Pricing","pricing_short_title_end":"Plans","pricing_title":"We Offer flexible pricing plans to suit the diverse needs of our clients","pricing_btn_link":"https:\/\/possaasweb.z-syst.com\/","watch_title_start":"How to Use","watch_title_middle":"Pharmacy App","watch_title_end":"With Flutter app, Admin Panel.","download_watch_btn_text":"Download APK","download_watch_btn_link":null,"watch_btn_link":"https:\/\/www.youtube.com\/embed\/EEryt_-M-6Q?si=8iBjlbj8ip6BrqPm","watch_description":"Can help viewers understand the functionality and features of your app. Here\u2019s a general script outline that you can see video","payment_title_start":"14+ Multiple","payment_title_middle":"Payment","payment_title_end":"Methods","payment_desc":"Access your business anytime, anywhere with the Z-Syst Pharmacy app for smarter operations and faster control.","blog_title_start":"Our Latest","blog_title_end":"Blog Posts","blog_btn_text":"Read More","blog_view_all_btn_text":"View All","blog_view_all_btn_link":"\/blogs","testimonial_title_start":"What our","testimonial_title_end":"Customer Say","about_short_title":"About us","about_title":"Work with us to play the game & Earning.","about_desc_one":"Lorem Ipsum\u00a0is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry''s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum ived not only five centuries","about_desc_two":"Lorem Ipsum\u00a0is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry''s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled","about_us_options_text":["Qhen an unknown printer took a galley of type.","It was popularised in the 1960s with the release.","Aldus PageMaker including versions."],"term_of_service_title":"Terms And Conditions","privacy_title":"Privacy Policy","contact_us_title":"Lets connect with us","contact_us_btn_text":"Continue","contact_us_description":"We will help a client''s problmes to develop the products they have with high quality change the appearance.","footer_short_title":"Maan theme- We Are Theme, Web, Plugin and App Development Agency","right_footer_one":"Sales","right_footer_link_one":null,"right_footer_two":"Parties","right_footer_link_two":null,"right_footer_three":"Purchase","right_footer_link_three":null,"right_footer_four":"Products","right_footer_link_four":null,"right_footer_five":"Due List","right_footer_link_five":null,"right_footer_six":"Income","right_footer_link_six":null,"middle_footer_one":"Expense","middle_footer_link_one":null,"middle_footer_two":"Stock","middle_footer_link_two":null,"middle_footer_three":"Loss\/Profit","middle_footer_link_three":null,"middle_footer_four":"Report","middle_footer_link_four":null,"middle_footer_five":"47+ Languages","middle_footer_link_five":null,"middle_footer_six":"Dashboard","middle_footer_link_six":null,"left_footer_one":"About us","left_footer_link_one":"\/about-us","left_footer_two":"Contact Us","left_footer_link_two":"\/contact-us","left_footer_three":"Terms And Conditions","left_footer_link_three":"\/terms-conditions","left_footer_four":"Privacy Policy","left_footer_link_four":"\/privacy-policy","footer_socials_links":["https:\/\/twitter.com\/","https:\/\/www.instagram.com\/zsyst\/","https:\/\/www.facebook.com\/zsyst","https:\/\/www.youtube.com\/"]},"watch_image":"uploads\/25\/02\/1738840341-711.svg","slider_image":"uploads\/25\/02\/1739761543-376.svg","contact_us_icon":"uploads\/25\/02\/1738828709-254.svg","about_image":"uploads\/25\/02\/1738828709-721.svg","footer_image":"uploads\/25\/02\/1738828709-349.svg","payment_image":"uploads\/25\/02\/1738828709-854.svg","get_app_icon":"uploads\/25\/02\/1738828709-124.svg","get_apple_app_image":"uploads\/25\/02\/1738828709-381.svg","get_google_app_image":"uploads\/25\/02\/1738828709-332.svg","footer_socials_icons":["uploads\/25\/02\/1740035756_67b6d6ac1e907.svg","uploads\/25\/02\/1738983843_67a6c9a308c7d.svg","uploads\/25\/02\/1738983843_67a6c9a309871.svg","uploads\/25\/02\/1738983843_67a6c9a30a609.svg"]}', '1', '2024-04-15 13:39:56', '2025-02-09 13:54:25');
INSERT INTO `options` (`id`, `key`, `value`, `status`, `created_at`, `updated_at`) VALUES ('4', 'term-condition', '{"description":"<p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Z-Syst built the Z-Syst app as a Commercial app. This SERVICE is provided by Z-Syst and is intended for use as is.. This page is used to inform visitors regarding my policies with the collection, use, and disclosure of Personal Information if anyone decided to use my Service.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">If you choose to use my Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that I collect is used for providing and improving the Service. I will not use or share your information with anyone except as described in this Privacy Policy.<\/p><h5 style=\"margin-bottom: 15px; font-weight: 600; line-height: 34px; color: rgb(3, 3, 3); font-size: 24px; font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Information Collection and Use<\/h5><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">For a better experience, while using our Service, I may require you to provide us with certain personally identifiable information, including but not limited to location,number,email. The information that I request will be retained on your device and is not collected by me in any way.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">The app does use third party services that may collect information used to identify you.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Link to privacy policy of third party service providers used by the app<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Google Play Services<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Google Analytics for Firebase<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Firebase Crashlytics<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">I want to inform you that whenever you use my Service, in a case of an error in the app I collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (\u201cIP\u201d) address, device name, operating system version, the configuration of the app when utilizing my Service, the time and date of your use of the Service, and other statistics.<\/p><h5 style=\"margin-bottom: 15px; font-weight: 600; line-height: 34px; color: rgb(3, 3, 3); font-size: 24px; font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Why is this user data collected?<\/h5><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">This collected data is processed ephemerally<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">App functionality:&nbsp;Used for features in your app, for example, to enable functionality or authenticate users.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Analytics:&nbsp;Used to collect data about how users use the app, or how the app performs. For example, to see how many users are using a particular feature, to monitor app health, to diagnose and fix bugs or crashes, or to make future performance improvements.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Developer communications:&nbsp;Used to send news or notifications about tthe Z-Syst app. For example, sending a push notification to inform users about an important security update or informing users about new features in the Z-Syst app.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Fraud prevention, security, and compliance:&nbsp;Used for fraud prevention, security, or compliance with laws. For example, monitoring failed login attempts to identify possible fraudulent activity.<\/p><p class=\"mb-0\" style=\"font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Account management:&nbsp;Used for the setup or management of user accounts. For example, to enable users to create accounts, add information to accounts that you provide for use across our services, log in to the Z-Syst app, or verify their credentials.<\/p>"}', '1', '2025-01-08 12:57:27', '2025-01-08 12:57:27');
INSERT INTO `options` (`id`, `key`, `value`, `status`, `created_at`, `updated_at`) VALUES ('5', 'privacy-policy', '{"description":"<p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Z-Syst built the Z-Syst app as a Commercial app. This SERVICE is provided by Z-Syst and is intended for use as is.. This page is used to inform visitors regarding my policies with the collection, use, and disclosure of Personal Information if anyone decided to use my Service.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">If you choose to use my Service, then you agree to the collection and use of information in relation to this policy. The Personal Information that I collect is used for providing and improving the Service. I will not use or share your information with anyone except as described in this Privacy Policy.<\/p><h5 style=\"margin-bottom: 15px; font-weight: 600; line-height: 34px; color: rgb(3, 3, 3); font-size: 24px; font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Information Collection and Use<\/h5><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">For a better experience, while using our Service, I may require you to provide us with certain personally identifiable information, including but not limited to location,number,email. The information that I request will be retained on your device and is not collected by me in any way.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">The app does use third party services that may collect information used to identify you.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Link to privacy policy of third party service providers used by the app<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Google Play Services<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Google Analytics for Firebase<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Firebase Crashlytics<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">I want to inform you that whenever you use my Service, in a case of an error in the app I collect data and information (through third party products) on your phone called Log Data. This Log Data may include information such as your device Internet Protocol (\u201cIP\u201d) address, device name, operating system version, the configuration of the app when utilizing my Service, the time and date of your use of the Service, and other statistics.<\/p><h5 style=\"margin-bottom: 15px; font-weight: 600; line-height: 34px; color: rgb(3, 3, 3); font-size: 24px; font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Why is this user data collected?<\/h5><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">This collected data is processed ephemerally<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">App functionality:&nbsp;Used for features in your app, for example, to enable functionality or authenticate users.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Analytics:&nbsp;Used to collect data about how users use the app, or how the app performs. For example, to see how many users are using a particular feature, to monitor app health, to diagnose and fix bugs or crashes, or to make future performance improvements.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Developer communications:&nbsp;Used to send news or notifications about tthe Z-Syst app. For example, sending a push notification to inform users about an important security update or informing users about new features in the Z-Syst app.<\/p><p style=\"margin-bottom: 1rem; font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Fraud prevention, security, and compliance:&nbsp;Used for fraud prevention, security, or compliance with laws. For example, monitoring failed login attempts to identify possible fraudulent activity.<\/p><p class=\"mb-0\" style=\"font-size: 16px; color: rgb(77, 77, 77); font-family: Inter, sans-serif; scrollbar-width: thin !important;\">Account management:&nbsp;Used for the setup or management of user accounts. For example, to enable users to create accounts, add information to accounts that you provide for use across our services, log in to the Z-Syst app, or verify their credentials.<\/p>"}', '1', '2025-01-08 12:57:49', '2025-01-08 12:57:49');

-- Table structure for `parties`
DROP TABLE IF EXISTS `parties`;
CREATE TABLE `parties` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` varchar, `business_id` integer NOT NULL, `email` varchar, `type` VARCHAR(255) NOT NULL default 'Retailer', `phone` varchar, `due` float NOT NULL default '0', `opening_balance` float NOT NULL default '0', `address` varchar, `image` varchar, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `password_reset_tokens`
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (`email` VARCHAR(255) NOT NULL, `token` VARCHAR(255) NOT NULL, `created_at` DATETIME, primary key (`email`));


-- Table structure for `password_resets`
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (`email` VARCHAR(255) NOT NULL, `token` VARCHAR(255) NOT NULL, `created_at` DATETIME);


-- Table structure for `permissions`
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `guard_name` VARCHAR(255) NOT NULL, `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('1', 'dashboard-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('2', 'users-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('3', 'users-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('4', 'users-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('5', 'users-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('6', 'banners-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('7', 'banners-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('8', 'banners-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('9', 'banners-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('10', 'business-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('11', 'business-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('12', 'business-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('13', 'business-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('14', 'business-categories-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('15', 'business-categories-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('16', 'business-categories-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('17', 'business-categories-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('18', 'plans-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('19', 'plans-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('20', 'plans-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('21', 'plans-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('22', 'subscription-reports-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('23', 'blogs-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('24', 'blogs-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('25', 'blogs-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('26', 'blogs-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('27', 'testimonials-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('28', 'testimonials-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('29', 'testimonials-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('30', 'testimonials-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('31', 'interfaces-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('32', 'interfaces-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('33', 'interfaces-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('34', 'interfaces-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('35', 'features-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('36', 'features-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('37', 'features-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('38', 'features-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('39', 'term-condition-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('40', 'term-condition-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('41', 'privacy-policy-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('42', 'privacy-policy-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('43', 'messages-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('44', 'messages-create', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('45', 'messages-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('46', 'messages-delete', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('47', 'manual-payment-reports-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('48', 'active-store-reports-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('49', 'expired-store-reports-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('50', 'sms-settings-read', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('51', 'sms-settings-update', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('52', 'gateways-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('53', 'gateways-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('54', 'currencies-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('55', 'currencies-create', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('56', 'currencies-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('57', 'currencies-delete', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('58', 'settings-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('59', 'settings-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('60', 'web-settings-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('61', 'web-settings-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('62', 'roles-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('63', 'roles-create', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('64', 'roles-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('65', 'roles-delete', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('66', 'permissions-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('67', 'permissions-create', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('68', 'notifications-read', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('69', 'notifications-update', 'web', '2026-08-03 03:38:19', '2026-08-03 03:38:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('70', 'addons-read', 'web', '2026-08-03 03:38:20', '2026-08-03 03:38:20');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('71', 'addons-create', 'web', '2026-08-03 03:38:20', '2026-08-03 03:38:20');

-- Table structure for `personal_access_tokens`
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `tokenable_type` VARCHAR(255) NOT NULL, `tokenable_id` integer NOT NULL, `name` VARCHAR(255) NOT NULL, `token` VARCHAR(255) NOT NULL, `abilities` LONGTEXT, `last_used_at` DATETIME, `expires_at` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME);


-- Table structure for `plan_subscribes`
DROP TABLE IF EXISTS `plan_subscribes`;
CREATE TABLE `plan_subscribes` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `plan_id` integer NOT NULL, `business_id` integer NOT NULL, `gateway_id` integer, `price` float NOT NULL default '0', `payment_status` VARCHAR(255) NOT NULL default 'unpaid', `duration` integer NOT NULL default '0', `notes` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`gateway_id`) REFERENCES `gateways`(`id`) ON DELETE CASCADE);


-- Table structure for `plans`
DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `subscriptionName` VARCHAR(255) NOT NULL, `duration` integer NOT NULL default '0', `offerPrice` float, `subscriptionPrice` float NOT NULL default '0', `status` tinyint(1) NOT NULL default '1', `features` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `plans` (`id`, `subscriptionName`, `duration`, `offerPrice`, `subscriptionPrice`, `status`, `features`, `created_at`, `updated_at`) VALUES ('1', 'Free', '7', NULL, '0', '1', '{"features_features_features_features_0":["Lifetime Free  Update","1"],"features_features_features_features_1":["Permitted for 1 domain","1"],"features_features_features_features_2":["6 months technical support","1"],"features_features_features_features_3":["WhatsApp & Skype support","1"],"features_features_features_features_4":["Live support","1"],"features_features_features_features_5":["Free installation"],"features_features_features_features_6":["Free installation Cpanel"],"features_features_features_features_7":["Advance Remote Support"]}', '2024-06-04 18:08:12', '2024-06-05 09:58:15');
INSERT INTO `plans` (`id`, `subscriptionName`, `duration`, `offerPrice`, `subscriptionPrice`, `status`, `features`, `created_at`, `updated_at`) VALUES ('2', 'Standard', '30', NULL, '10', '1', '{"features_features_features_0":["Lifetime Free  Update","1"],"features_features_features_1":["Permitted for 1 domain","1"],"features_features_features_2":["6 months technical support","1"],"features_features_features_3":["WhatsApp & Skype support","1"],"features_features_features_4":["Live support","1"],"features_features_features_5":["Free installation","1"],"features_features_features_6":["Free installation Cpanel"],"features_features_features_7":["Advance Remote Support"]}', '2024-06-04 18:08:12', '2024-06-05 09:58:24');
INSERT INTO `plans` (`id`, `subscriptionName`, `duration`, `offerPrice`, `subscriptionPrice`, `status`, `features`, `created_at`, `updated_at`) VALUES ('3', 'Premium', '180', '50', '60', '1', '{"features_features_0":["Lifetime Free  Update","1"],"features_features_1":["Permitted for 1 domain","1"],"features_features_2":["6 months technical support","1"],"features_features_3":["WhatsApp & Skype support","1"],"features_features_4":["Live support","1"],"features_features_5":["Free installation","1"],"features_features_6":["Free installation Cpanel","1"],"features_features_7":["Advance Remote Support","1"]}', '2024-06-04 18:08:12', '2024-06-05 09:58:32');

-- Table structure for `pos_app_interfaces`
DROP TABLE IF EXISTS `pos_app_interfaces`;
CREATE TABLE `pos_app_interfaces` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `image` VARCHAR(255) NOT NULL, `status` tinyint(1) NOT NULL, `created_at` DATETIME, `upDATEd_at` DATETIME);


-- Table structure for `prediction_settings`
DROP TABLE IF EXISTS `prediction_settings`;
CREATE TABLE `prediction_settings` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `prediction_enabled` tinyint(1) NOT NULL default '1', `forecast_period` VARCHAR(255) NOT NULL default 'daily', `forecast_days` integer NOT NULL default '30', `historical_months` integer NOT NULL default '6', `prediction_method` varchar) NOT NULL default 'combined', `seasonal_adjustment` tinyint(1) NOT NULL default '1', `safety_stock_multiplier` numeric NOT NULL default '1.5', `lead_TIME_days` integer NOT NULL default '7', `confidence_threshold` numeric NOT NULL default '0.7', `auto_order_enabled` tinyint(1) NOT NULL default '0', `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `prescriptions`
DROP TABLE IF EXISTS `prescriptions`;
CREATE TABLE `prescriptions` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `sale_id` integer, `party_id` integer, `image` VARCHAR(255) NOT NULL, `notes` LONGTEXT, `status` varchar) NOT NULL default 'pending', `prescription_number` varchar, `review_status` VARCHAR(255) NOT NULL default 'pending', `review_notes` LONGTEXT, `reviewed_by` integer, `reviewed_at` DATETIME, `expires_at` DATE, `patient_name` varchar, `patient_phone` varchar, `doctor_name` varchar, `doctor_license` varchar, `used_at` DATETIME, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`sale_id`) references `sales`(`id`) on delete set null, foreign key(`party_id`) references `parties`(`id`) on delete set null, foreign key(`reviewed_by`) references `users`(`id`) on delete set null);


-- Table structure for `product_inventory_analysis`
DROP TABLE IF EXISTS `product_inventory_analysis`;
CREATE TABLE `product_inventory_analysis` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `report_id` integer, `total_quantity_sold` numeric NOT NULL default '0', `total_sales_value` numeric NOT NULL default '0', `total_cogs` numeric NOT NULL default '0', `average_stock_level` numeric NOT NULL default '0', `turnover_ratio` numeric NOT NULL default '0', `days_inventory_outstanding` numeric NOT NULL default '0', `abc_category` varchar), `movement_category` varchar) NOT NULL default 'medium', `current_stock_value` numeric NOT NULL default '0', `current_stock_qty` numeric NOT NULL default '0', `stock_velocity` numeric NOT NULL default '0', `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, FOREIGN KEY (`report_id`) REFERENCES `inventory_turnover_reports`(`id`) ON DELETE CASCADE);


-- Table structure for `products`
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `productName` VARCHAR(255) NOT NULL, `business_id` integer NOT NULL, `category_id` integer, `unit_id` integer, `type_id` integer, `manufacturer_id` integer, `box_size_id` integer, `purchase_without_tax` float NOT NULL default '0', `purchase_with_tax` float NOT NULL default '0', `profit_percent` float NOT NULL default '0', `sales_price` float NOT NULL default '0', `alert_qty` integer NOT NULL default '0', `wholesale_price` float NOT NULL default '0', `productCode` varchar, `images` LONGTEXT, `tax_id` integer, `tax_type` VARCHAR(255) NOT NULL default 'exclusive', `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE, FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE, FOREIGN KEY (`type_id`) REFERENCES `medicine_types`(`id`) ON DELETE CASCADE, FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers`(`id`) ON DELETE CASCADE, FOREIGN KEY (`box_size_id`) REFERENCES `box_sizes`(`id`) ON DELETE CASCADE, FOREIGN KEY (`tax_id`) REFERENCES `taxes`(`id`) ON DELETE CASCADE);


-- Table structure for `purchase_details`
DROP TABLE IF EXISTS `purchase_details`;
CREATE TABLE `purchase_details` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `purchase_id` integer NOT NULL, `product_id` integer NOT NULL, `purchase_without_tax` float NOT NULL default '0', `purchase_with_tax` float NOT NULL default '0', `profit_percent` float NOT NULL default '0', `sales_price` float NOT NULL default '0', `wholesale_price` float NOT NULL default '0', `quantities` integer NOT NULL default '0', `batch_no` varchar, `expire_DATE` DATE, FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE);


-- Table structure for `purchase_return_details`
DROP TABLE IF EXISTS `purchase_return_details`;
CREATE TABLE `purchase_return_details` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `purchase_return_id` integer NOT NULL, `purchase_detail_id` integer NOT NULL, `return_amount` float NOT NULL default '0', `return_qty` integer NOT NULL, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns`(`id`) ON DELETE CASCADE, FOREIGN KEY (`purchase_detail_id`) REFERENCES `purchase_details`(`id`) ON DELETE CASCADE);


-- Table structure for `purchase_returns`
DROP TABLE IF EXISTS `purchase_returns`;
CREATE TABLE `purchase_returns` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `purchase_id` integer NOT NULL, `invoice_no` varchar, `return_DATE` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE);


-- Table structure for `purchases`
DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `party_id` integer, `business_id` integer NOT NULL, `user_id` integer, `tax_id` integer, `discountAmount` float NOT NULL default '0', `tax_amount` float NOT NULL default '0', `dueAmount` float NOT NULL default '0', `paidAmount` float NOT NULL default '0', `totalAmount` float NOT NULL default '0', `invoiceNumber` varchar, `isPaid` tinyint(1) NOT NULL default '0', `paymentType` VARCHAR(255) NOT NULL default 'Cash', `purchaseDATE` DATETIME, `purchase_data` LONGTEXT, `note` varchar, `created_at` DATETIME, `upDATEd_at` DATETIME, foreign key(`party_id`) references `parties`(`id`) on delete set null, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`user_id`) references `users`(`id`) on delete set null, foreign key(`tax_id`) references `taxes`(`id`) on delete set null);


-- Table structure for `role_has_permissions`
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (`permission_id` integer NOT NULL, `role_id` integer NOT NULL, FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE, FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE, primary key (`permission_id`, `role_id`));

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('1', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('2', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('3', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('4', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('5', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('6', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('7', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('8', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('9', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('10', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('11', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('12', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('13', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('14', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('15', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('16', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('17', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('18', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('19', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('20', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('21', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('22', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('23', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('24', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('25', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('26', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('27', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('28', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('29', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('30', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('31', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('32', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('33', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('34', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('35', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('36', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('37', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('38', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('39', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('40', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('41', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('42', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('43', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('44', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('45', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('46', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('47', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('48', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('49', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('50', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('51', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('52', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('53', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('54', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('55', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('56', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('57', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('58', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('59', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('60', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('61', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('62', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('63', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('64', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('65', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('66', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('67', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('68', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('69', '1');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('1', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('2', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('3', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('4', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('5', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('6', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('7', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('8', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('9', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('10', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('11', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('12', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('13', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('14', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('15', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('16', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('17', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('18', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('19', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('20', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('21', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('22', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('50', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('51', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('70', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('71', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('52', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('53', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('54', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('55', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('56', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('57', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('68', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('69', '2');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('1', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('2', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('3', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('4', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('5', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('6', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('7', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('8', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('9', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('10', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('11', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('12', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('13', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('14', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('15', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('16', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('17', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('18', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('19', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('20', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('21', '3');
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES ('22', '3');

-- Table structure for `roles`
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `guard_name` VARCHAR(255) NOT NULL, `created_at` DATETIME, `upDATEd_at` DATETIME);

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('1', 'superadmin', 'web', '2026-08-03 03:38:18', '2026-08-03 03:38:18');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('2', 'admin', 'web', '2026-08-03 03:38:20', '2026-08-03 03:38:20');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES ('3', 'manager', 'web', '2026-08-03 03:38:20', '2026-08-03 03:38:20');

-- Table structure for `sale_details`
DROP TABLE IF EXISTS `sale_details`;
CREATE TABLE `sale_details` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `sale_id` integer NOT NULL, `product_id` integer NOT NULL, `price` float NOT NULL default '0', `purchase_price` float NOT NULL default '0', `lossProfit` float NOT NULL default '0', `batch_no` varchar, `expire_DATE` DATE, `quantities` integer NOT NULL default '0', FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE);


-- Table structure for `sale_return_details`
DROP TABLE IF EXISTS `sale_return_details`;
CREATE TABLE `sale_return_details` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `sale_return_id` integer NOT NULL, `sale_detail_id` integer NOT NULL, `return_amount` float NOT NULL default '0', `return_qty` integer NOT NULL, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns`(`id`) ON DELETE CASCADE, FOREIGN KEY (`sale_detail_id`) REFERENCES `sale_details`(`id`) ON DELETE CASCADE);


-- Table structure for `sale_returns`
DROP TABLE IF EXISTS `sale_returns`;
CREATE TABLE `sale_returns` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `sale_id` integer NOT NULL, `invoice_no` varchar, `return_DATE` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE);


-- Table structure for `sales`
DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `party_id` integer, `user_id` integer, `tax_id` integer, `discountAmount` float NOT NULL default '0', `dueAmount` float NOT NULL default '0', `isPaid` tinyint(1) NOT NULL default '0', `tax_amount` float NOT NULL default '0', `paidAmount` float NOT NULL default '0', `totalAmount` float NOT NULL default '0', `lossProfit` float NOT NULL default '0', `paymentType` varchar, `invoiceNumber` varchar, `saleDATE` DATETIME, `sale_data` LONGTEXT, `meta` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`party_id`) references `parties`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null, foreign key(`tax_id`) references `taxes`(`id`) on delete set null);


-- Table structure for `sales_forecasts`
DROP TABLE IF EXISTS `sales_forecasts`;
CREATE TABLE `sales_forecasts` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `forecast_DATE` DATE NOT NULL, `predicted_quantity` numeric NOT NULL default '0', `predicted_revenue` numeric NOT NULL default '0', `confidence_score` numeric, `lower_bound` numeric, `upper_bound` numeric, `method_used` varchar, `factors` LONGTEXT, `is_active` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE);


-- Table structure for `stock_audit_details`
DROP TABLE IF EXISTS `stock_audit_details`;
CREATE TABLE `stock_audit_details` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `stock_audit_id` integer NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `stock_id` integer, `batch_no` varchar, `expire_DATE` DATE, `system_quantity` integer NOT NULL default '0', `physical_quantity` integer NOT NULL default '0', `variance` integer NOT NULL default '0', `unit_cost` float NOT NULL default '0', `variance_value` float NOT NULL default '0', `variance_type` varchar) NOT NULL default 'none', `notes` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`stock_audit_id`) REFERENCES `stock_audits`(`id`) ON DELETE CASCADE, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, FOREIGN KEY (`stock_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE);


-- Table structure for `stock_audits`
DROP TABLE IF EXISTS `stock_audits`;
CREATE TABLE `stock_audits` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `user_id` integer, `audit_number` VARCHAR(255) NOT NULL, `audit_type` varchar) NOT NULL default 'manual', `status` varchar) NOT NULL default 'pending', `audit_DATE` DATETIME, `completed_at` DATETIME, `notes` LONGTEXT, `metadata` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`user_id`) references `users`(`id`) on delete set null);


-- Table structure for `stock_movements`
DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE `stock_movements` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `stock_id` integer, `user_id` integer, `movement_type` varchar) NOT NULL default 'in', `quantity` integer NOT NULL default '0', `before_quantity` integer NOT NULL default '0', `after_quantity` integer NOT NULL default '0', `batch_no` varchar, `expire_DATE` DATE, `reference_type` varchar, `reference_id` integer, `notes` LONGTEXT, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, FOREIGN KEY (`stock_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE, foreign key(`user_id`) references `users`(`id`) on delete set null);


-- Table structure for `stock_reconciliations`
DROP TABLE IF EXISTS `stock_reconciliations`;
CREATE TABLE `stock_reconciliations` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `stock_audit_id` integer, `user_id` integer, `product_id` integer NOT NULL, `stock_id` integer, `batch_no` varchar, `expire_DATE` DATE, `adjustment_type` varchar) NOT NULL default 'no_change', `previous_quantity` integer NOT NULL default '0', `new_quantity` integer NOT NULL default '0', `adjustment_quantity` integer NOT NULL default '0', `unit_cost` float NOT NULL default '0', `adjustment_value` float NOT NULL default '0', `reference_type` varchar, `reference_id` integer, `reason` LONGTEXT, `is_posted` tinyint(1) NOT NULL default '0', `posted_at` DATETIME, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, foreign key(`stock_audit_id`) references `stock_audits`(`id`) on delete set null, foreign key(`user_id`) references `users`(`id`) on delete set null, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE, FOREIGN KEY (`stock_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE);


-- Table structure for `stocks`
DROP TABLE IF EXISTS `stocks`;
CREATE TABLE `stocks` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer NOT NULL, `product_id` integer NOT NULL, `productStock` integer NOT NULL default '0', `batch_no` varchar, `expire_DATE` DATE, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE, FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE);


-- Table structure for `taxes`
DROP TABLE IF EXISTS `taxes`;
CREATE TABLE `taxes` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `name` VARCHAR(255) NOT NULL, `business_id` integer NOT NULL, `rate` float NOT NULL default '0', `sub_tax` LONGTEXT, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `testimonials`
DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `LONGTEXT` LONGTEXT, `star` integer NOT NULL, `client_name` VARCHAR(255) NOT NULL, `client_image` varchar, `work_at` VARCHAR(255) NOT NULL, `created_at` DATETIME, `upDATEd_at` DATETIME);


-- Table structure for `units`
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `unitName` VARCHAR(255) NOT NULL, `business_id` integer NOT NULL, `status` tinyint(1) NOT NULL default '1', `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);


-- Table structure for `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (`id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL, `business_id` integer, `email` varchar, `name` varchar, `role` VARCHAR(255) NOT NULL default 'shop-owner', `phone` varchar, `image` varchar, `lang` varchar, `visibility` LONGTEXT, `password` varchar, `status` varchar, `email_verified_at` DATETIME, `remember_token` varchar, `created_at` DATETIME, `upDATEd_at` DATETIME, FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE CASCADE);

INSERT INTO `users` (`id`, `business_id`, `email`, `name`, `role`, `phone`, `image`, `lang`, `visibility`, `password`, `status`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES ('1', NULL, 'superadmin@z-syst.com', 'Super Admin', 'superadmin', NULL, 'assets/images/profile/superadmin.svg', NULL, NULL, '$2y$10$E1Q1ZoaFJFq.tRBHg1PBjORJ84/gX/O5Scti9iZqyrgmUeW5R2/ym', NULL, NULL, 'k2vWUgWnVkn2FY6y9yOPDgDE6ZEtMJY22eawS2EyGBxa6PN2Hcfw4DeoM0rM', '2026-08-03 03:38:20', '2026-08-03 03:38:20');
INSERT INTO `users` (`id`, `business_id`, `email`, `name`, `role`, `phone`, `image`, `lang`, `visibility`, `password`, `status`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES ('2', NULL, 'admin@z-syst.com', 'Admin', 'admin', NULL, 'assets/images/profile/admin.svg', NULL, NULL, '$2y$10$PPJ9Ggnb9PfmMaweA7G6pOQVNEdeZFNH2vjkJc.fKbk86MWY61O9.', NULL, NULL, NULL, '2026-08-03 03:38:20', '2026-08-03 03:38:20');
INSERT INTO `users` (`id`, `business_id`, `email`, `name`, `role`, `phone`, `image`, `lang`, `visibility`, `password`, `status`, `email_verified_at`, `remember_token`, `created_at`, `updated_at`) VALUES ('3', NULL, 'manager@z-syst.com', 'Manager', 'manager', NULL, 'assets/images/profile/manager.svg', NULL, NULL, '$2y$10$vblEtic16slkRVqilS/.u.taDKTXG1V5wJtMmNf/Ef6CHSGZfM1wq', NULL, NULL, NULL, '2026-08-03 03:38:20', '2026-08-03 03:38:20');


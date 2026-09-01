-- =====================================================================
--  LUMEN CAPITAL — Digital Asset Investment Platform
--  Database Schema + Demo Seed Data
--  Engine: MySQL 5.7+ / MariaDB 10.3+   Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `lumen_capital` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lumen_capital`;

-- ---------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name`           VARCHAR(120)      NOT NULL,
  `email`               VARCHAR(160)      NOT NULL,
  `phone`               VARCHAR(30)       DEFAULT NULL,
  `country`             VARCHAR(80)       DEFAULT NULL,
  `password_hash`       VARCHAR(255)      NOT NULL,
  `referral_code`       VARCHAR(12)       NOT NULL,
  `referred_by`         INT UNSIGNED      DEFAULT NULL,
  `role`                ENUM('user','admin') NOT NULL DEFAULT 'user',
  `status`              ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `balance`             DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `total_deposited`     DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `total_invested`      DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `total_earned`        DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `total_withdrawn`     DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `payout_wallet`       VARCHAR(160)      DEFAULT NULL,
  `avatar_seed`         VARCHAR(40)       DEFAULT NULL,
  `email_verified_at`   DATETIME          DEFAULT NULL,
  `last_login_at`       DATETIME          DEFAULT NULL,
  `last_login_ip`       VARCHAR(45)       DEFAULT NULL,
  `failed_attempts`     TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  `locked_until`        DATETIME          DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_referral_code` (`referral_code`),
  KEY `idx_users_referred_by` (`referred_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- plans  (investment products)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `plans`;
CREATE TABLE `plans` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`                VARCHAR(80)       NOT NULL,
  `slug`                VARCHAR(80)       NOT NULL,
  `tagline`             VARCHAR(160)      DEFAULT NULL,
  `description`         TEXT              DEFAULT NULL,
  `icon`                VARCHAR(10)       DEFAULT '📈',
  `theme_color`         VARCHAR(20)       NOT NULL DEFAULT 'emerald',
  `min_amount`          DECIMAL(15,2)     NOT NULL,
  `max_amount`          DECIMAL(15,2)     NOT NULL,
  `roi_percent`         DECIMAL(6,3)      NOT NULL COMMENT 'percent credited per payout period',
  `payout_type`         ENUM('daily','end_of_term') NOT NULL DEFAULT 'daily',
  `duration_days`       SMALLINT UNSIGNED NOT NULL,
  `featured`            TINYINT(1)        NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- investments
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `investments`;
CREATE TABLE `investments` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `plan_id`             INT UNSIGNED      NOT NULL,
  `plan_name`           VARCHAR(80)       NOT NULL COMMENT 'snapshot at time of purchase',
  `amount`              DECIMAL(15,2)     NOT NULL,
  `roi_percent`         DECIMAL(6,3)      NOT NULL,
  `payout_type`         ENUM('daily','end_of_term') NOT NULL,
  `duration_days`       SMALLINT UNSIGNED NOT NULL,
  `start_date`          DATETIME          NOT NULL,
  `maturity_date`       DATETIME          NOT NULL,
  `status`              ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `expected_return`     DECIMAL(15,2)     NOT NULL COMMENT 'total profit expected over full term',
  `paid_out`            DECIMAL(15,2)     NOT NULL DEFAULT 0.00 COMMENT 'profit already credited',
  `last_payout_at`      DATETIME          DEFAULT NULL,
  `next_payout_at`      DATETIME          DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_inv_user` (`user_id`),
  KEY `idx_inv_status` (`status`),
  KEY `idx_inv_next_payout` (`next_payout_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- transactions (immutable ledger)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `type`                ENUM('deposit','withdrawal','investment','roi_payout','maturity_payout','referral_bonus','admin_credit','admin_debit') NOT NULL,
  `amount`              DECIMAL(15,2)     NOT NULL COMMENT 'signed: positive = credit, negative = debit',
  `balance_after`       DECIMAL(15,2)     NOT NULL,
  `reference_type`      VARCHAR(30)       DEFAULT NULL,
  `reference_id`        INT UNSIGNED      DEFAULT NULL,
  `description`         VARCHAR(255)      DEFAULT NULL,
  `status`               ENUM('pending','completed','rejected','reversed') NOT NULL DEFAULT 'completed',
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tx_user` (`user_id`),
  KEY `idx_tx_type` (`type`),
  KEY `idx_tx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- deposits (pending funding requests awaiting admin approval)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `deposits`;
CREATE TABLE `deposits` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `amount`              DECIMAL(15,2)     NOT NULL,
  `currency_code`       VARCHAR(20)       NOT NULL,
  `method_label`        VARCHAR(60)       NOT NULL,
  `address_used`        VARCHAR(160)      DEFAULT NULL,
  `txn_reference`       VARCHAR(160)      DEFAULT NULL,
  `note`                VARCHAR(255)      DEFAULT NULL,
  `status`              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_id`            INT UNSIGNED      DEFAULT NULL,
  `admin_note`          VARCHAR(255)      DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`        DATETIME          DEFAULT NULL,
  KEY `idx_dep_user` (`user_id`),
  KEY `idx_dep_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- withdrawals
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `withdrawals`;
CREATE TABLE `withdrawals` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `amount`              DECIMAL(15,2)     NOT NULL,
  `fee`                 DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
  `net_amount`          DECIMAL(15,2)     NOT NULL,
  `method_label`        VARCHAR(60)       NOT NULL,
  `destination`         VARCHAR(160)      NOT NULL,
  `status`              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_id`            INT UNSIGNED      DEFAULT NULL,
  `admin_note`          VARCHAR(255)      DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at`        DATETIME          DEFAULT NULL,
  KEY `idx_wd_user` (`user_id`),
  KEY `idx_wd_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- payment_methods (admin-managed deposit addresses shown to users)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `payment_methods`;
CREATE TABLE `payment_methods` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `currency_code`       VARCHAR(20)       NOT NULL,
  `currency_name`       VARCHAR(60)       NOT NULL,
  `network`             VARCHAR(60)       DEFAULT NULL,
  `address`             VARCHAR(200)      NOT NULL,
  `instructions`        VARCHAR(255)      DEFAULT NULL,
  `is_active`           TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `title`               VARCHAR(120)      NOT NULL,
  `message`             VARCHAR(255)      NOT NULL,
  `type`                ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `is_read`             TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- settings (key/value site configuration)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key`         VARCHAR(60)       NOT NULL PRIMARY KEY,
  `setting_value`       TEXT              DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- support_messages (public contact form + logged-in support tickets)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE `support_messages` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      DEFAULT NULL,
  `name`                VARCHAR(120)      NOT NULL,
  `email`               VARCHAR(160)      NOT NULL,
  `subject`             VARCHAR(160)      NOT NULL,
  `message`             TEXT              NOT NULL,
  `status`              ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  `admin_reply`         TEXT              DEFAULT NULL,
  `replied_at`          DATETIME          DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sup_user` (`user_id`),
  KEY `idx_sup_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- activity_log (admin audit trail)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id`            INT UNSIGNED      DEFAULT NULL,
  `action`              VARCHAR(120)      NOT NULL,
  `target_type`         VARCHAR(40)       DEFAULT NULL,
  `target_id`           INT UNSIGNED      DEFAULT NULL,
  `details`             VARCHAR(255)      DEFAULT NULL,
  `ip_address`          VARCHAR(45)       DEFAULT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_log_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- password_resets
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `token`               VARCHAR(100)      NOT NULL,
  `expires_at`          DATETIME          NOT NULL,
  `used`                TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- email_verifications
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`             INT UNSIGNED      NOT NULL,
  `token`               VARCHAR(100)      NOT NULL,
  `expires_at`          DATETIME          NOT NULL,
  `used`                TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ev_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- sent_emails (simulated outbound mail log — no SMTP required for demo)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `sent_emails`;
CREATE TABLE `sent_emails` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `to_email`            VARCHAR(160)      NOT NULL,
  `subject`             VARCHAR(200)      NOT NULL,
  `body`                TEXT              NOT NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Settings ---------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'Lumen Capital'),
('site_tagline', 'Invest with clarity.'),
('site_currency', 'USD'),
('support_email', 'support@lumencapital.test'),
('referral_percent', '5'),
('withdrawal_fee_percent', '2'),
('min_withdrawal', '50'),
('min_deposit', '50'),
('maintenance_mode', '0');

-- Plans --------------------------------------------------------------
INSERT INTO `plans` (`id`, `name`, `slug`, `tagline`, `description`, `icon`, `theme_color`, `min_amount`, `max_amount`, `roi_percent`, `payout_type`, `duration_days`, `featured`, `is_active`, `sort_order`) VALUES
(1, 'Starter Vault', 'starter-vault', 'A confident first step into digital assets.', 'Designed for new investors who want a low-risk introduction to daily-yield investing. Short duration, transparent daily payouts, full principal returned at maturity.', '🌱', 'emerald', 100.00, 999.00, 1.200, 'daily', 14, 0, 1, 1),
(2, 'Growth Fund', 'growth-fund', 'Our most popular balanced portfolio.', 'A balanced allocation across major digital assets, tuned for steady compounding growth over a one-month term. The plan most of our members start with.', '🚀', 'indigo', 1000.00, 4999.00, 1.800, 'daily', 30, 1, 1, 2),
(3, 'Premium Yield', 'premium-yield', 'Accelerated returns for serious capital.', 'A higher-conviction allocation strategy for members ready to commit larger capital over a two-month horizon, with priority support and daily payouts.', '💎', 'amber', 5000.00, 19999.00, 2.500, 'daily', 60, 0, 1, 3),
(4, 'Elite Portfolio', 'elite-portfolio', 'Our flagship strategy for private clients.', 'The full Lumen Capital strategy suite, reserved for private clients. Ninety-day term, dedicated relationship manager, and our strongest daily yield.', '👑', 'rose', 20000.00, 100000.00, 3.200, 'daily', 90, 0, 1, 4);

-- Payment methods ------------------------------------------------------
INSERT INTO `payment_methods` (`currency_code`, `currency_name`, `network`, `address`, `instructions`, `is_active`, `sort_order`) VALUES
('BTC', 'Bitcoin', 'Bitcoin Network', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', 'Send only BTC to this address. Minimum 1 network confirmation required before submitting.', 1, 1),
('ETH', 'Ethereum', 'ERC-20', '0x71C7656EC7ab88b098defB751B7401B5f6d8976', 'Send only ETH to this address on the Ethereum mainnet.', 1, 2),
('USDT', 'Tether USD', 'TRC-20', 'TXYZ9ab8LMNopQRstUvWxyz1234567890AbCdEf', 'USDT on the TRON (TRC-20) network only. Sending on another network may result in loss of funds.', 1, 3),
('WIRE', 'Bank Wire Transfer', 'SWIFT', 'IBAN: GB29 LUMN 6016 1331 9268 19  •  SWIFT: LUMNGB2L', 'Include your account email as the payment reference so we can match your transfer.', 1, 4);

-- Demo users -----------------------------------------------------------
-- admin@lumencapital.test / Admin@2026!
-- demo@lumencapital.test  / Demo@2026!
-- sarah@lumencapital.test / Sarah@2026!  (referred by demo)
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `country`, `password_hash`, `referral_code`, `referred_by`, `role`, `status`, `balance`, `total_deposited`, `total_invested`, `total_earned`, `total_withdrawn`, `payout_wallet`, `avatar_seed`, `email_verified_at`, `created_at`) VALUES
(1, 'Lumen Administrator', 'admin@lumencapital.test', '+1 415 555 0100', 'United States', '$2y$12$CgHZUTWj9UF74O2w6xLXx.xEBPcGZd.BZ0ythzBeWw2EYSawv62iW', 'ADMIN0001', NULL, 'admin', 'active', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 'admin', NOW(), NOW() - INTERVAL 200 DAY),
(2, 'Jordan Ellis', 'demo@lumencapital.test', '+1 415 555 0142', 'United States', '$2y$12$U11x/0wdW06RuNy1GyuckObJ1l1Bj0U.IiEzvZoixR1BI2IOyuFAS', 'JORDAN42', NULL, 'user', 'active', 3245.60, 6500.00, 5500.00, 1245.60, 2500.00, 'bc1qdemoWalletAddressForPayoutsXYZ', 'jordan', NOW() - INTERVAL 40 DAY, NOW() - INTERVAL 42 DAY),
(3, 'Sarah Whitfield', 'sarah@lumencapital.test', '+1 212 555 0187', 'Canada', '$2y$12$4LVBixvcM00aaduIZ0y3keEFQRlLeoC6UVYaktbkkJVcBT4cNnAiS', 'SARAH88', 2, 'user', 'active', 1180.25, 2000.00, 1000.00, 180.25, 0.00, 'bc1qsarahWalletAddressForPayoutsXYZ', 'sarah', NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 25 DAY);

-- Jordan's investments ---------------------------------------------------
-- #1: Growth Fund, active, started 10 days ago, 30 day term (daily payouts already applied for 10 days)
INSERT INTO `investments` (`id`, `user_id`, `plan_id`, `plan_name`, `amount`, `roi_percent`, `payout_type`, `duration_days`, `start_date`, `maturity_date`, `status`, `expected_return`, `paid_out`, `last_payout_at`, `next_payout_at`, `created_at`) VALUES
(1, 2, 2, 'Growth Fund', 3000.00, 1.800, 'daily', 30, NOW() - INTERVAL 10 DAY, NOW() + INTERVAL 20 DAY, 'active', 1620.00, 540.00, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 23 HOUR, NOW() - INTERVAL 10 DAY),
-- #2: Starter Vault, completed 5 days ago (14 day term finished)
(2, 2, 1, 'Starter Vault', 2500.00, 1.200, 'daily', 14, NOW() - INTERVAL 19 DAY, NOW() - INTERVAL 5 DAY, 'completed', 420.00, 420.00, NOW() - INTERVAL 5 DAY, NULL, NOW() - INTERVAL 19 DAY);

-- Sarah's investment ------------------------------------------------------
INSERT INTO `investments` (`id`, `user_id`, `plan_id`, `plan_name`, `amount`, `roi_percent`, `payout_type`, `duration_days`, `start_date`, `maturity_date`, `status`, `expected_return`, `paid_out`, `last_payout_at`, `next_payout_at`, `created_at`) VALUES
(3, 3, 1, 'Starter Vault', 1000.00, 1.200, 'daily', 14, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 1 DAY, 'active', 168.00, 156.25, NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 6 HOUR, NOW() - INTERVAL 15 DAY);

-- Transactions ledger -------------------------------------------------
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `balance_after`, `reference_type`, `reference_id`, `description`, `status`, `created_at`) VALUES
(2, 'deposit', 4000.00, 4000.00, 'deposit', 1, 'Deposit via Bitcoin approved', 'completed', NOW() - INTERVAL 20 DAY),
(2, 'investment', -2500.00, 1500.00, 'investment', 2, 'Invested in Starter Vault', 'completed', NOW() - INTERVAL 19 DAY),
(2, 'roi_payout', 30.00, 1530.00, 'investment', 2, 'Daily ROI — Starter Vault', 'completed', NOW() - INTERVAL 18 DAY),
(2, 'maturity_payout', 2920.00, 4450.00, 'investment', 2, 'Starter Vault matured — principal + profit', 'completed', NOW() - INTERVAL 5 DAY),
(2, 'deposit', 2500.00, 6950.00, 'deposit', 2, 'Deposit via Ethereum approved', 'completed', NOW() - INTERVAL 11 DAY),
(2, 'investment', -3000.00, 3950.00, 'investment', 1, 'Invested in Growth Fund', 'completed', NOW() - INTERVAL 10 DAY),
(2, 'roi_payout', 54.00, 4004.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 9 DAY),
(2, 'roi_payout', 54.00, 4058.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 8 DAY),
(2, 'roi_payout', 54.00, 4112.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 7 DAY),
(2, 'roi_payout', 54.00, 4166.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 6 DAY),
(2, 'roi_payout', 54.00, 4220.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 5 DAY),
(2, 'withdrawal', -2500.00, 1720.00, 'withdrawal', 1, 'Withdrawal to external wallet', 'completed', NOW() - INTERVAL 4 DAY),
(2, 'roi_payout', 54.00, 1774.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 4 DAY),
(2, 'roi_payout', 54.00, 1828.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 3 DAY),
(2, 'referral_bonus', 100.00, 1928.00, 'user', 3, 'Referral bonus — Sarah Whitfield first deposit', 'completed', NOW() - INTERVAL 25 DAY),
(2, 'roi_payout', 54.00, 1982.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 2 DAY),
(2, 'roi_payout', 54.00, 2036.00, 'investment', 1, 'Daily ROI — Growth Fund', 'completed', NOW() - INTERVAL 1 DAY),
(2, 'deposit', 1200.00, 3236.00, 'deposit', 4, 'Deposit via USDT approved', 'completed', NOW() - INTERVAL 12 HOUR),
(3, 'deposit', 2000.00, 2000.00, 'deposit', 3, 'Deposit via Bitcoin approved', 'completed', NOW() - INTERVAL 24 DAY),
(3, 'investment', -1000.00, 1000.00, 'investment', 3, 'Invested in Starter Vault', 'completed', NOW() - INTERVAL 15 DAY),
(3, 'roi_payout', 12.00, 1012.00, 'investment', 3, 'Daily ROI — Starter Vault', 'completed', NOW() - INTERVAL 14 DAY),
(3, 'roi_payout', 12.00, 1024.00, 'investment', 3, 'Daily ROI — Starter Vault', 'completed', NOW() - INTERVAL 10 DAY),
(3, 'roi_payout', 12.00, 1036.00, 'investment', 3, 'Daily ROI — Starter Vault', 'completed', NOW() - INTERVAL 5 DAY),
(3, 'roi_payout', 12.00, 1048.00, 'investment', 3, 'Daily ROI — Starter Vault', 'completed', NOW() - INTERVAL 2 DAY);

-- Pending deposit awaiting admin approval (for admin queue demo) --------
INSERT INTO `deposits` (`id`, `user_id`, `amount`, `currency_code`, `method_label`, `address_used`, `txn_reference`, `note`, `status`, `created_at`) VALUES
(1, 2, 4000.00, 'BTC', 'Bitcoin', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', '3b1f7a9c8e2d4f6a', NULL, 'approved', NOW() - INTERVAL 20 DAY),
(2, 2, 2500.00, 'ETH', 'Ethereum', '0x71C7656EC7ab88b098defB751B7401B5f6d8976', '0x9f8e7d6c5b4a3f2e1d0c', NULL, 'approved', NOW() - INTERVAL 11 DAY),
(3, 3, 2000.00, 'BTC', 'Bitcoin', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh', '7c2b9e1a4d6f8c3b', NULL, 'approved', NOW() - INTERVAL 24 DAY),
(4, 2, 1200.00, 'USDT', 'Tether USD', 'TXYZ9ab8LMNopQRstUvWxyz1234567890AbCdEf', 'a1b2c3d4e5f6', NULL, 'approved', NOW() - INTERVAL 12 HOUR),
(5, 3, 850.00, 'USDT', 'Tether USD', 'TXYZ9ab8LMNopQRstUvWxyz1234567890AbCdEf', 'f6e5d4c3b2a1', 'Sent from Coinbase, should confirm shortly.', 'pending', NOW() - INTERVAL 3 HOUR);

-- Withdrawal history ------------------------------------------------------
INSERT INTO `withdrawals` (`id`, `user_id`, `amount`, `fee`, `net_amount`, `method_label`, `destination`, `status`, `created_at`, `processed_at`) VALUES
(1, 2, 2500.00, 50.00, 2450.00, 'Bitcoin', 'bc1qdemoWalletAddressForPayoutsXYZ', 'approved', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY + INTERVAL 2 HOUR),
(2, 2, 600.00, 12.00, 588.00, 'Ethereum', '0xdemoPayoutAddress0000000000000000001', 'pending', NOW() - INTERVAL 5 HOUR, NULL);

-- Notifications -------------------------------------------------------
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(2, 'Deposit approved', 'Your deposit of $1,200.00 via USDT has been approved and credited.', 'success', 0, NOW() - INTERVAL 11 HOUR),
(2, 'Daily payout received', 'You earned $54.00 from your Growth Fund investment.', 'success', 1, NOW() - INTERVAL 1 DAY),
(2, 'Withdrawal submitted', 'Your withdrawal request of $600.00 is pending review.', 'info', 0, NOW() - INTERVAL 5 HOUR),
(3, 'Deposit pending', 'Your deposit of $850.00 via USDT is awaiting confirmation.', 'warning', 0, NOW() - INTERVAL 3 HOUR),
(3, 'Investment matured soon', 'Your Starter Vault investment reaches maturity within 24 hours.', 'info', 0, NOW() - INTERVAL 6 HOUR);

-- Support messages ------------------------------------------------------
INSERT INTO `support_messages` (`user_id`, `name`, `email`, `subject`, `message`, `status`, `admin_reply`, `replied_at`, `created_at`) VALUES
(NULL, 'Michael Tran', 'michael.tran@example.com', 'Question about Elite Portfolio minimum', 'Hi, is the $20,000 minimum for the Elite Portfolio a hard requirement or can it be phased in over time? Thanks!', 'answered', 'Hi Michael — the $20,000 minimum is required upfront to open an Elite Portfolio position. You are welcome to start with our Premium Yield plan and upgrade later. Let us know if you have more questions!', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 3 DAY),
(3, 'Sarah Whitfield', 'sarah@lumencapital.test', 'Deposit confirmation time', 'How long does it usually take for a USDT deposit to be confirmed and credited?', 'open', NULL, NULL, NOW() - INTERVAL 2 HOUR);

-- Sent-email log (simulated outbound mail, e.g. password reset) --------
INSERT INTO `sent_emails` (`to_email`, `subject`, `body`, `created_at`) VALUES
('demo@lumencapital.test', 'Welcome to Lumen Capital', 'Hi Jordan, thanks for creating your Lumen Capital account. Start exploring investment plans from your dashboard.', NOW() - INTERVAL 42 DAY);

-- Activity log ----------------------------------------------------------
INSERT INTO `activity_log` (`admin_id`, `action`, `target_type`, `target_id`, `details`, `created_at`) VALUES
(1, 'Approved deposit', 'deposit', 1, 'Approved $4,000.00 BTC deposit for Jordan Ellis', NOW() - INTERVAL 20 DAY),
(1, 'Approved deposit', 'deposit', 2, 'Approved $2,500.00 ETH deposit for Jordan Ellis', NOW() - INTERVAL 11 DAY),
(1, 'Approved deposit', 'deposit', 3, 'Approved $2,000.00 BTC deposit for Sarah Whitfield', NOW() - INTERVAL 24 DAY),
(1, 'Approved withdrawal', 'withdrawal', 1, 'Approved $2,500.00 withdrawal for Jordan Ellis', NOW() - INTERVAL 4 DAY),
(1, 'Approved deposit', 'deposit', 4, 'Approved $1,200.00 USDT deposit for Jordan Ellis', NOW() - INTERVAL 12 HOUR),
(1, 'Replied to support message', 'support_message', 1, 'Replied to Michael Tran', NOW() - INTERVAL 2 DAY);

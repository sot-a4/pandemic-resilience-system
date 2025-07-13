-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 20 Μάη 2025 στις 23:26:58
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `prs_database`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `entity_affected` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `entity_affected`, `record_id`, `timestamp`) VALUES
(1, 1, 'CREATE', 'vaccination_record', 1, '2025-05-20 21:25:18'),
(2, 1, 'UPLOAD', 'document', 1, '2025-05-20 21:25:18'),
(3, 2, 'CREATE', 'vaccination_record', 2, '2025-05-20 21:25:18'),
(4, 2, 'UPLOAD', 'document', 3, '2025-05-20 21:25:18'),
(5, 3, 'VIEW', 'vaccination_record', 3, '2025-05-20 21:25:18');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_path` text NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `related_record_id` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `documents`
--

INSERT INTO `documents` (`document_id`, `file_type`, `file_path`, `uploaded_by`, `related_record_id`, `upload_date`) VALUES
(1, 'PDF', '/documents/vaccination_cert_001.pdf', 1, 1, '2025-05-20 21:25:08'),
(2, 'Image', '/documents/id_proof_001.jpg', 1, NULL, '2025-05-20 21:25:08'),
(3, 'PDF', '/documents/medical_report_002.pdf', 2, 2, '2025-05-20 21:25:08'),
(4, 'Image', '/documents/vaccination_photo_003.jpg', 3, 3, '2025-05-20 21:25:08');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `encryption_keys`
--

CREATE TABLE `encryption_keys` (
  `key_id` int(11) NOT NULL,
  `key_type` varchar(50) NOT NULL,
  `key_value` text NOT NULL,
  `owner` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `encryption_keys`
--

INSERT INTO `encryption_keys` (`key_id`, `key_type`, `key_value`, `owner`, `user_id`, `created_date`) VALUES
(1, 'AES-256', '5f4dcc3b5aa765d61d8327deb882cf99', 'System', 1, '2025-05-20 21:25:38'),
(2, 'RSA-2048', 'e10adc3949ba59abbe56e057f20f883e', 'Application', 2, '2025-05-20 21:25:38'),
(3, 'ECDSA', '25d55ad283aa400af464c76d713c07ad', 'User', 3, '2025-05-20 21:25:38');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Government Official', 'Can monitor vaccination and supply chain'),
(2, 'Merchant', 'Can manage pandemic-related supplies'),
(3, 'Public Member', 'Can track vaccination records');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `national_id` varchar(50) NOT NULL,
  `prs_id` varchar(50) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `phone`, `national_id`, `prs_id`, `role_id`, `created_at`) VALUES
(1, 'Alice Johnson', 'alice@gov.org', 'e0e6097a6f8af07daf5fc7244336ba37133713a8fc7345c36d667dfa513fabaa', '+1234567890', 'GOV12345', 'PRS001', 1, '2025-05-20 21:24:57'),
(2, 'Bob Merchant', 'bob@merchant.com', '06b4893a19b07695fdd7795b51e43804407467cb534802f682b66998791fe2ae', '+1987654321', 'MER12345', 'PRS002', 2, '2025-05-20 21:24:57'),
(3, 'Charlie Public', 'charlie@example.com', '41ffc271fb84b154e9da09980db78dc4364b9d3c89eda86bb6f7c18888fc9dbd', '+1122334455', 'PUB12345', 'PRS003', 3, '2025-05-20 21:24:57');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `record_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vaccine_name` varchar(255) NOT NULL,
  `date_administered` date NOT NULL,
  `dose_number` int(11) NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `lot_number` varchar(50) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `vaccination_records`
--

INSERT INTO `vaccination_records` (`record_id`, `user_id`, `vaccine_name`, `date_administered`, `dose_number`, `provider`, `lot_number`, `expiration_date`, `created_at`) VALUES
(1, 1, 'COVID-19 Vaccine AstraZeneca', '2023-01-10', 1, 'City Health Clinic', 'AZ-12345', '2023-12-31', '2025-05-20 21:25:02'),
(2, 2, 'COVID-19 Vaccine Pfizer', '2023-02-15', 1, 'Downtown Medical Center', 'PF-67890', '2023-11-30', '2025-05-20 21:25:02'),
(3, 3, 'COVID-19 Vaccine Moderna', '2023-03-05', 1, 'Central Hospital', 'MD-54321', '2023-10-31', '2025-05-20 21:25:02');

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `related_record_id` (`related_record_id`);

--
-- Ευρετήρια για πίνακα `encryption_keys`
--
ALTER TABLE `encryption_keys`
  ADD PRIMARY KEY (`key_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Ευρετήρια για πίνακα `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD UNIQUE KEY `prs_id` (`prs_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Ευρετήρια για πίνακα `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT για πίνακα `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT για πίνακα `encryption_keys`
--
ALTER TABLE `encryption_keys`
  MODIFY `key_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT για πίνακα `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT για πίνακα `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`related_record_id`) REFERENCES `vaccination_records` (`record_id`) ON DELETE SET NULL;

--
-- Περιορισμοί για πίνακα `encryption_keys`
--
ALTER TABLE `encryption_keys`
  ADD CONSTRAINT `encryption_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;

--
-- Περιορισμοί για πίνακα `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

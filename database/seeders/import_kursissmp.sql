-- ==============================================================================
-- SISURAT: IMPORT SCRIPT ARSIP DIVISI KURSISSMP
-- Database Compatibility: MySQL 8.0+ / MariaDB 10.4+
-- ==============================================================================

START TRANSACTION;

-- 1. Pastikan Divisi KURSISSMP Terdaftar
INSERT INTO `divisions` (`uuid`, `code`, `name`, `status`, `created_at`, `updated_at`)
SELECT UUID(), 'KURSISSMP', 'Kurikulum dan Kesiswaan SMP', 'active', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `divisions` WHERE `code` = 'KURSISSMP');

SET @div_id = (SELECT `id` FROM `divisions` WHERE `code` = 'KURSISSMP' LIMIT 1);
SET @admin_id = (SELECT `id` FROM `users` WHERE `role` = 'super_admin' LIMIT 1);

-- ==============================================================================
-- 2. IMPORT SURAT MASUK & LAMPIRAN GOOGLE DRIVE (22 DOKUMEN)
-- ==============================================================================

-- 1
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'B-PM.02.04.55.06.26.474', 'Badan POM / Instansi Eksternal', '2026-06-26', '2026-06-26', 'Undangan Kegiatan Gebyar SAPA Sekolah dalam rangka World Food Safety Day', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), 'B-PM.02.04.55.06.26.474.pdf', 'application/pdf', 0, 1, 'migrated', '17MbPKQMJwNL1ukPeIcMPgjdavMSip6gX', 'https://drive.google.com/uc?export=view&id=17MbPKQMJwNL1ukPeIcMPgjdavMSip6gX', @admin_id, NOW(), NOW());

-- 2
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'B/047/N/KA/PROO/2026', 'BNNP Jawa Tengah', '2026-05-21', '2026-05-21', 'Rakor Akselerasi Implementasi P4GN', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), 'B-047-N-KA-PROO-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1jzMGL2T8pMVimpnY6Zk4bhQKM07ZbZlN', 'https://drive.google.com/uc?export=view&id=1jzMGL2T8pMVimpnY6Zk4bhQKM07ZbZlN', @admin_id, NOW(), NOW());

-- 3
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '400.3.5/500/2026', 'Instansi Pengirim', '2026-06-09', '2026-06-09', 'undangan rapat tentang bantuan', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '400.3.5-500-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1JLOUTJ-BbuyaHP27L_gnT8fPhb-IyMaC', 'https://drive.google.com/uc?export=view&id=1JLOUTJ-BbuyaHP27L_gnT8fPhb-IyMaC', @admin_id, NOW(), NOW());

-- 4
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'B/047/N/KA/PROO/2026-02', 'BNNP Jawa Tengah', '2026-05-21', '2026-05-21', 'Rakor Akselerasi Implementasi P4GN (Lampiran II)', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), 'B-047-N-KA-PROO-2026-02.pdf', 'application/pdf', 0, 1, 'migrated', '1nAo6BgadcV5uBp3OTg3Ubuj5Y6IEJ_5e', 'https://drive.google.com/uc?export=view&id=1nAo6BgadcV5uBp3OTg3Ubuj5Y6IEJ_5e', @admin_id, NOW(), NOW());

-- 5
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0419/BAN.PDM.Jateng/TU/2026', 'BAN PDM Jateng', '2026-05-21', '2026-05-21', 'Pemberitahuan Pengajuan Reakreditasi', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0419-BAN.PDM.Jateng-TU-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1oDlmfWgwg73saVkSDxawB3JYhtNrzba4', 'https://drive.google.com/open?id=1oDlmfWgwg73saVkSDxawB3JYhtNrzba4', @admin_id, NOW(), NOW());

-- 6
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '1852/B/C4/DM.00.02', 'Kemendikbudristek Ditjen PDM', '2026-06-02', '2026-06-02', 'Pendampingan sekolah model implementasi PM dan KKA Region I', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '1852-B-C4-DM.00.02.pdf', 'application/pdf', 0, 1, 'migrated', '1LlpfBlJOlfSBuhNbliKAvCJ2I32hidCh', 'https://drive.google.com/open?id=1LlpfBlJOlfSBuhNbliKAvCJ2I32hidCh', @admin_id, NOW(), NOW());

-- 7
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'B/0471/V/KA/PR.00/2026/BNNP', 'BNNP Jawa Tengah', '2026-06-04', '2026-06-04', 'Undangan Rapat Rakor Akselerasi Implementasi P4GN', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), 'B-0471-V-KA-PR.00-2026-BNNP.pdf', 'application/pdf', 0, 1, 'migrated', '1Mu2LtKQ9sAClzN-P_jEAOxzBiBYq5sdC', 'https://drive.google.com/open?id=1Mu2LtKQ9sAClzN-P_jEAOxzBiBYq5sdC', @admin_id, NOW(), NOW());

-- 8
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '500.14/513/2026', 'Instansi Pengirim', '2026-05-25', '2026-05-25', 'Permohonan KAK dan Uptade data Spasial', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '500.14-513-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1jS_A_J2qn2zwYK7y4x7jPeAw4Pw6aF-M', 'https://drive.google.com/open?id=1jS_A_J2qn2zwYK7y4x7jPeAw4Pw6aF-M', @admin_id, NOW(), NOW());

-- 9
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0535/B/C6.3/DM.00.02', 'Kemendikbudristek Ditjen PDM', '2026-05-24', '2026-05-24', 'Pendataan dan Pemetaan ULD Bid. Pendidikan Tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0535-B-C6.3-DM.00.02.pdf', 'application/pdf', 0, 1, 'migrated', '1fRNRkoG91YajbeOyke9M6HQHthVXnr88', 'https://drive.google.com/open?id=1fRNRkoG91YajbeOyke9M6HQHthVXnr88', @admin_id, NOW(), NOW());

-- 10
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0562/B/c6.3/DM.002/2026', 'Kemendikbudristek Ditjen PDM', '2026-06-02', '2026-06-02', 'Pelaksana Gugus kendali Mutu (GKM)', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0562-B-c6.3-DM.002-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1zT6ax9suAKsKxKanr5MYyEwnQcEurA7e', 'https://drive.google.com/open?id=1zT6ax9suAKsKxKanr5MYyEwnQcEurA7e', @admin_id, NOW(), NOW());

-- 11
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '04/KUDA EMAS/VI/2026', 'Kuda Emas', '2026-06-01', '2026-06-01', 'Mohon bantuan menyebarluaskan', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '04-KUDA-EMAS-VI-2026.pdf', 'application/pdf', 0, 1, 'migrated', '10r77oPm5BP7mCffpIVAliPoRpg_vAN-L', 'https://drive.google.com/open?id=10r77oPm5BP7mCffpIVAliPoRpg_vAN-L', @admin_id, NOW(), NOW());

-- 12
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '421.7/114/smp/.N 3/V/2026', 'SMP Negeri 3', '2026-05-26', '2026-05-26', 'Pemberitahuan kegiatan Studi wisata', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '421.7-114-smp.N-3-V-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1G3bIdEpkZ3RpX-ZQrXQIxXK0Xag7w_hp', 'https://drive.google.com/open?id=1G3bIdEpkZ3RpX-ZQrXQIxXK0Xag7w_hp', @admin_id, NOW(), NOW());

-- 13
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '1003/09568', 'Setda Kab. Semarang', '2026-06-05', '2026-06-05', 'Rapat penyusunan rancangan peraturan bupati semarang', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '1003-09568.pdf', 'application/pdf', 0, 1, 'migrated', '157nnSn0JBnFftGwq5On9y5INWjyVP5jJ', 'https://drive.google.com/open?id=157nnSn0JBnFftGwq5On9y5INWjyVP5jJ', @admin_id, NOW(), NOW());

-- 14
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '421.2/066/26', 'Instansi Pengirim', '2026-06-02', '2026-06-02', 'Permohonan bantuan perawatan perbaikan mushola', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '421.2-066-26.pdf', 'application/pdf', 0, 1, 'migrated', '1OZlQvYE1AbmQfH1YgMXCO_D4_gmG4wrg', 'https://drive.google.com/open?id=1OZlQvYE1AbmQfH1YgMXCO_D4_gmG4wrg', @admin_id, NOW(), NOW());

-- 15
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0552/B/C6.3/DM.00.02/2026', 'Dinas Pendidikan Provinsi Jateng', '2026-05-29', '2026-05-29', 'Kegiatan pemetaan mutu pendidikan Prov.Jateng', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0552-B-C6.3-DM.00.02-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1hcBP4n9k-bOUHuLVnOywJqPt5KVy0J0F', 'https://drive.google.com/open?id=1hcBP4n9k-bOUHuLVnOywJqPt5KVy0J0F', @admin_id, NOW(), NOW());

-- 16
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '003/WWMR/VI', 'Panitia Wasana Warsa', '2026-06-08', '2026-06-08', 'Permohonan sambutan wasana warsa', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '003-WWMR-VI.pdf', 'application/pdf', 0, 1, 'migrated', '1GbNqLvv0mQwc6WHx8iibQz70vpWdC4WK', 'https://drive.google.com/open?id=1GbNqLvv0mQwc6WHx8iibQz70vpWdC4WK', @admin_id, NOW(), NOW());

-- 17
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '36/FBBD/26', 'FBBD', '2026-03-30', '2026-03-30', 'Permohonan Ijin Praktik pengalaman lapangan', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '36-FBBD-26.pdf', 'application/pdf', 0, 1, 'migrated', '1sdx4cj50fkxTsSoV4ix5uyIGlNyJta-0', 'https://drive.google.com/open?id=1sdx4cj50fkxTsSoV4ix5uyIGlNyJta-0', @admin_id, NOW(), NOW());

-- 18
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0014/08560', 'Setda Kab. Semarang', '2026-05-19', '2026-05-19', 'Rakor Sinergitas pelaksanaan kebijakan bid . Kersa pelayanan dasar dan kementasan kemiskinan', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0014-08560.pdf', 'application/pdf', 0, 1, 'migrated', '1vAc8w5M5FUZPZM9N3IAnesdO9DCNalLB', 'https://drive.google.com/open?id=1vAc8w5M5FUZPZM9N3IAnesdO9DCNalLB', @admin_id, NOW(), NOW());

-- 19
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '0014/08856', 'Panitia Peringatan Hari Besar Islam', '2026-05-22', '2026-05-22', 'Sholat idul Adha', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '0014-08856.pdf', 'application/pdf', 0, 1, 'migrated', '1hoZsZqGkNY7srh7CFSISs1oYAvJ4GCKx', 'https://drive.google.com/open?id=1hoZsZqGkNY7srh7CFSISs1oYAvJ4GCKx', @admin_id, NOW(), NOW());

-- 20
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '25.5.26', 'SMP Literasi Istiqomah', '2026-05-25', '2026-05-25', 'Proposal smp literasi istiqomah', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '25.5.26.pdf', 'application/pdf', 0, 1, 'migrated', '1FJtznTKNEdkWiKRMYlbXdef51bJkfIAg', 'https://drive.google.com/open?id=1FJtznTKNEdkWiKRMYlbXdef51bJkfIAg', @admin_id, NOW(), NOW());

-- 21
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, '421.2/306/2026', 'SMP Pengirim', '2026-05-22', '2026-05-22', 'Pemberitahuan Outing Class', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), '421.2-306-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1aRuhpeouVFxtG-cgJRX1yvoSWgJmHefM', 'https://drive.google.com/open?id=1aRuhpeouVFxtG-cgJRX1yvoSWgJmHefM', @admin_id, NOW(), NOW());

-- 22
INSERT INTO `incoming_letters` (`uuid`, `division_id`, `letter_number`, `origin_agency`, `letter_date`, `received_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'UND-1/PK/PK.3', 'Kementerian Keuangan RI', '2026-05-21', '2026-05-21', 'Sosialisasi PMK Nomor 25 tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\IncomingLetter', LAST_INSERT_ID(), 'UND-1-PK-PK.3.pdf', 'application/pdf', 0, 1, 'migrated', '1iYsb5WXh1dYCW9GoLRhK6zbJ5MgvH-ez', 'https://drive.google.com/open?id=1iYsb5WXh1dYCW9GoLRhK6zbJ5MgvH-ez', @admin_id, NOW(), NOW());

-- ==============================================================================
-- 3. IMPORT SURAT KELUAR & LAMPIRAN GOOGLE DRIVE (18 DOKUMEN)
-- ==============================================================================

-- 23
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '005/2289/2026', '2026-06-25', '2026-06-25', 'Undangan sosialisasi Penerbitan Ijazah', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '005-2289-2026.pdf', 'application/pdf', 0, 1, 'migrated', '10fyvA1YNQTLYAZspBSTPR70rHaSfAhbM', 'https://drive.google.com/uc?export=view&id=10fyvA1YNQTLYAZspBSTPR70rHaSfAhbM', @admin_id, NOW(), NOW());

-- 24
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/800/2026', '2026-06-15', '2026-06-15', 'Pengelolaan Dokumen Murid Kelas IX Tahun Ajaran 2025/2026 Jenjang SMP', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-800-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1tFButrp773wAbN5PpTNswwJMRqbitixH', 'https://drive.google.com/uc?export=view&id=1tFButrp773wAbN5PpTNswwJMRqbitixH', @admin_id, NOW(), NOW());

-- 25
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '800.1.11.1/2179/2026', '2026-06-17', '2026-06-17', 'Surat Tugas Melaksanakan tugas keabsahan O2SN Tingkat Provinsi Jawa Tengah 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '800.1.11.1-2179-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1rtQKpjsCWKJXQ3AkLYyzqm7zMnJPuQdE', 'https://drive.google.com/open?id=1rtQKpjsCWKJXQ3AkLYyzqm7zMnJPuQdE', @admin_id, NOW(), NOW());

-- 26
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '800.1.11.1/2175/2026', '2026-06-17', '2026-06-17', 'Surat Tugas Rapat Koordinasi Program Prioritas Makan Bergizi Gratis(MBG) Tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '800.1.11.1-2175-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1VhFBKS1WZGlxD1yE5XCVDVYajCs3tzXJ', 'https://drive.google.com/open?id=1VhFBKS1WZGlxD1yE5XCVDVYajCs3tzXJ', @admin_id, NOW(), NOW());

-- 27
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3/1261/2026', '2026-04-16', '2026-04-16', 'Surat Izin Operasional Sekolah', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3-1261-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1rRJjr-JPDYpofS3ew4MB06zpj2tL4AEk', 'https://drive.google.com/open?id=1rRJjr-JPDYpofS3ew4MB06zpj2tL4AEk', @admin_id, NOW(), NOW());

-- 28 (Disambiguasi Salinan karena nomor sama persis dengan baris 24)
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/800/2026-DUPLIKAT', '2026-06-15', '2026-06-15', 'Pengelolaan Dokumen Murid Kelas IX Tahun Ajaran 2025/2026 Jenjang SMP (Salinan Arsip)', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-800-2026-duplikat.pdf', 'application/pdf', 0, 1, 'migrated', '1tFButrp773wAbN5PpTNswwJMRqbitixH', 'https://drive.google.com/uc?export=view&id=1tFButrp773wAbN5PpTNswwJMRqbitixH', @admin_id, NOW(), NOW());

-- 29
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/2109/2026', '2026-06-10', '2026-06-10', 'Pelaksanaan Gugus Kendali Mutu Bulan Juni', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-2109-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1DUPxnbZHGxd5tZgxPbsdoD-qMHzc7t0i', 'https://drive.google.com/open?id=1DUPxnbZHGxd5tZgxPbsdoD-qMHzc7t0i', @admin_id, NOW(), NOW());

-- 30
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/2030/2026', '2026-06-08', '2026-06-08', 'Permohonan Tenaga Medis  GSI Jenjang SMP Tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-2030-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1LD2g6ZaTGT-CMYrusEt88AcyIqrN9jUu', 'https://drive.google.com/open?id=1LD2g6ZaTGT-CMYrusEt88AcyIqrN9jUu', @admin_id, NOW(), NOW());

-- 31
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '800.1.11.1/2032/2026', '2026-06-08', '2026-06-08', 'Untuk melaksanakan kegiatan GSI', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '800.1.11.1-2032-2026.pdf', 'application/pdf', 0, 1, 'migrated', '11TBOHcKNuhGJQdB9BsZNDvIynsVQm0F3', 'https://drive.google.com/open?id=11TBOHcKNuhGJQdB9BsZNDvIynsVQm0F3', @admin_id, NOW(), NOW());

-- 32
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '800.1.11.1/2031/2026', '2026-06-08', '2026-06-08', 'Untuk Menjadi Juri dan Panitia lomba GSI Jenjang SMP Tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '800.1.11.1-2031-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1WrahVDBjP3E6fnonwDdc-ENWukclITZ3', 'https://drive.google.com/open?id=1WrahVDBjP3E6fnonwDdc-ENWukclITZ3', @admin_id, NOW(), NOW());

-- 33
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/2033/2026', '2026-06-05', '2026-06-05', 'Surat Edaran GSI Jenjang SMP  Tahun 2026', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-2033-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1PPCzedX9gFQWOFCB6ZUnqVWGTp9wq6op', 'https://drive.google.com/open?id=1PPCzedX9gFQWOFCB6ZUnqVWGTp9wq6op', @admin_id, NOW(), NOW());

-- 34
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/1882/2026', '2026-05-26', '2026-05-26', 'Permohonan Personil Dukcapil', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-1882-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1oJaUN8DEFa8ZOG0AXuintEqL8VUGKaV5', 'https://drive.google.com/open?id=1oJaUN8DEFa8ZOG0AXuintEqL8VUGKaV5', @admin_id, NOW(), NOW());

-- 35
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/1881/2026', '2026-05-26', '2026-05-26', 'Permohonan Personil Pusdatin', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-1881-2026.pdf', 'application/pdf', 0, 1, 'migrated', '15iXXjgrht8sWYU0y1_qngLGPRzbRcGhW', 'https://drive.google.com/open?id=15iXXjgrht8sWYU0y1_qngLGPRzbRcGhW', @admin_id, NOW(), NOW());

-- 36
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3.5/1880/2026', '2026-05-26', '2026-05-26', 'Permohonan Personil Dinas Sosial Kabupaten Semarang', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3.5-1880-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1iJCoWHQTIVjS6lUG_5-de_QRc28YQ62c', 'https://drive.google.com/open?id=1iJCoWHQTIVjS6lUG_5-de_QRc28YQ62c', @admin_id, NOW(), NOW());

-- 37
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, 'ND/PEMINJAMAN/2026', '2026-05-19', '2026-05-19', 'Permohonan Peminjaman Tempat', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), 'ND-PEMINJAMAN-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1WG4sQ3DCH44m2zBZc9yGrJxcHTuaQMce', 'https://drive.google.com/open?id=1WG4sQ3DCH44m2zBZc9yGrJxcHTuaQMce', @admin_id, NOW(), NOW());

-- 38
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3/1538.A/2026', '2026-05-04', '2026-05-04', 'Undangan', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3-1538.A-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1kp_7kgrQ3o3yiWll5mo3wYr7EeEHcYEv', 'https://drive.google.com/open?id=1kp_7kgrQ3o3yiWll5mo3wYr7EeEHcYEv', @admin_id, NOW(), NOW());

-- 39
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, 'ND/INTERNET/2026', '2026-05-20', '2026-05-20', 'Permohonan optimalisasi Internet', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), 'ND-INTERNET-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1yc6Xy3Mu8WdJzdSbFPRetSeonULwVEfj', 'https://drive.google.com/open?id=1yc6Xy3Mu8WdJzdSbFPRetSeonULwVEfj', @admin_id, NOW(), NOW());

-- 40
INSERT INTO `outgoing_letters` (`uuid`, `division_id`, `letter_year`, `letter_number`, `letter_date`, `disseminated_date`, `subject`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 2026, '400.3/1502/2026', '2026-05-25', '2026-05-25', 'Surat Piagam FLS3N', @admin_id, NOW(), NOW());
INSERT INTO `attachments` (`uuid`, `division_id`, `attachable_type`, `attachable_id`, `original_name`, `mime_type`, `file_size_bytes`, `is_missing`, `migration_status`, `legacy_drive_file_id`, `legacy_drive_url`, `created_by_user_id`, `created_at`, `updated_at`)
VALUES (UUID(), @div_id, 'App\\Models\\OutgoingLetter', LAST_INSERT_ID(), '400.3-1502-2026.pdf', 'application/pdf', 0, 1, 'migrated', '1RMiAbPoD-2JqUysDE8J0sr-eKw2SrrpS', 'https://drive.google.com/file/d/1RMiAbPoD-2JqUysDE8J0sr-eKw2SrrpS/view?usp=sharing', @admin_id, NOW(), NOW());

COMMIT;

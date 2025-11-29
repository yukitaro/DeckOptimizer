/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `card_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `official_set_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_multiverse_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `types` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colors` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorIdentities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mana_cost` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mana_value` int NOT NULL DEFAULT '0',
  `printings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `rarity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci,
  `power` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toughness` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_data_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `card_data_from_set_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_data_from_set_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `magic_set_data_id` bigint unsigned NOT NULL,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_in_set` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_multiverse_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `types` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colors` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `colorIdentities` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mana_cost` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mana_value` int NOT NULL DEFAULT '0',
  `printings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `rarity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` mediumtext COLLATE utf8mb4_unicode_ci,
  `power` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toughness` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `card_metadata_id` bigint unsigned DEFAULT NULL,
  `set_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_normalized_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_data_from_set_data_name_index` (`name`),
  KEY `card_data_from_set_data_magic_set_data_id_foreign` (`magic_set_data_id`),
  KEY `card_data_from_set_data_name_set_name_index` (`name`,`set_name`),
  KEY `card_data_from_set_data_card_metadata_id_index` (`card_metadata_id`),
  KEY `card_data_from_set_data_set_name_index` (`set_name`),
  KEY `idx_card_data_metadata_id` (`card_metadata_id`),
  KEY `idx_card_data_set_name` (`set_name`),
  KEY `idx_card_data_set_name_number` (`set_name`,`number_in_set`),
  KEY `idx_card_data_set_name_name` (`set_name`,`name`),
  KEY `card_data_from_set_data_slug_index` (`slug`),
  FULLTEXT KEY `card_data_name_fulltext_index` (`name`),
  CONSTRAINT `card_data_from_set_data_card_metadata_id_foreign` FOREIGN KEY (`card_metadata_id`) REFERENCES `card_metadata` (`id`),
  CONSTRAINT `card_data_from_set_data_magic_set_data_id_foreign` FOREIGN KEY (`magic_set_data_id`) REFERENCES `magic_set_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `card_data_normalized`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_data_normalized` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `image_url_to_use` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `printings` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_printing` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `set_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_printing_id` bigint unsigned DEFAULT NULL,
  `normalized_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `card_data_normalized_source_printing_id_foreign` (`source_printing_id`),
  CONSTRAINT `card_data_normalized_source_printing_id_foreign` FOREIGN KEY (`source_printing_id`) REFERENCES `card_data_from_set_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `card_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `card_metadata` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cardKingdomId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multiverseId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scryfallId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tcgplayerProductId` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tcgplayerPurchaseUrl` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_data_from_set_data_id` bigint unsigned DEFAULT NULL,
  `purchaseUrls` json DEFAULT NULL,
  `identifiers` json DEFAULT NULL,
  `normalized_attributes` json DEFAULT NULL,
  `normalized_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borderColor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `isFullArt` tinyint(1) DEFAULT '0',
  `frameVersion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `hasFoil` tinyint(1) DEFAULT '0',
  `hasNonFoil` tinyint(1) DEFAULT '0',
  `logic_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_enriched_at` timestamp NULL DEFAULT NULL,
  `scryfall_id` varchar(36) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`identifiers`,_utf8mb4'$.scryfallId'))) STORED,
  PRIMARY KEY (`id`),
  KEY `card_metadata_card_data_from_set_data_id_foreign` (`card_data_from_set_data_id`),
  KEY `card_metadata_normalized_name_index` (`normalized_name`),
  KEY `idx_card_metadata_multiverse_id` (`multiverseId`),
  KEY `idx_card_metadata_tcgplayer_product_id` (`tcgplayerProductId`),
  KEY `idx_scryfall_id` (`scryfall_id`),
  CONSTRAINT `card_metadata_card_data_from_set_data_id_foreign` FOREIGN KEY (`card_data_from_set_data_id`) REFERENCES `card_data_from_set_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cards_in_deck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cards_in_deck` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `card_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_count` int NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `deck_management_id` bigint unsigned NOT NULL,
  `card_data_normalized_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `mtg_deck_board_group_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cards_in_deck_mtg_deck_board_group_id_foreign` (`mtg_deck_board_group_id`),
  CONSTRAINT `cards_in_deck_mtg_deck_board_group_id_foreign` FOREIGN KEY (`mtg_deck_board_group_id`) REFERENCES `mtg_deck_board_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collected_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collected_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `is_foil` tinyint(1) NOT NULL DEFAULT '0',
  `set_in_collection_id` bigint unsigned NOT NULL,
  `card_data_id` bigint unsigned NOT NULL,
  `card_count` int NOT NULL DEFAULT '0',
  `condition` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `printing_variant` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` int DEFAULT NULL,
  `storage_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collected_cards_set_in_collection_id_foreign` (`set_in_collection_id`),
  KEY `collected_cards_card_data_id_foreign` (`card_data_id`),
  CONSTRAINT `collected_cards_card_data_id_foreign` FOREIGN KEY (`card_data_id`) REFERENCES `card_data_from_set_data` (`id`),
  CONSTRAINT `collected_cards_set_in_collection_id_foreign` FOREIGN KEY (`set_in_collection_id`) REFERENCES `sets_in_collection` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collected_cards_from_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collected_cards_from_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `card_count` int NOT NULL,
  `sets_in_collection_id` bigint unsigned NOT NULL,
  `card_data_from_set_data_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `collected_cards_sets_idx` (`sets_in_collection_id`),
  KEY `collected_cards_card_idx` (`card_data_from_set_data_id`),
  KEY `collected_cards_composite_idx` (`sets_in_collection_id`,`card_data_from_set_data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_management` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `import_status` enum('pending','processing','complete','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `collection_management_owner_id_foreign` (`owner_id`),
  CONSTRAINT `collection_management_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_management_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_management_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_management_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `can_share` tinyint(1) NOT NULL DEFAULT '0',
  `granted_at` timestamp NULL DEFAULT NULL,
  `granted_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cm_user_unique` (`collection_management_id`,`user_id`),
  KEY `collection_management_user_user_id_foreign` (`user_id`),
  KEY `collection_management_user_granted_by_foreign` (`granted_by`),
  CONSTRAINT `collection_management_user_collection_management_id_foreign` FOREIGN KEY (`collection_management_id`) REFERENCES `collection_management` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collection_management_user_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `collection_management_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collection_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collection_user` (
  `collection_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `can_edit` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `collection_user_collection_id_foreign` (`collection_id`),
  KEY `collection_user_user_id_foreign` (`user_id`),
  CONSTRAINT `collection_user_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collection_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `collection_set_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deck_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deck_management` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deck_owner_id` bigint unsigned NOT NULL,
  `visibility` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `deck_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `num_cards` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archetype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archetype_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deck_management_archetype_id_foreign` (`archetype_id`),
  KEY `deck_management_deck_owner_id_foreign` (`deck_owner_id`),
  CONSTRAINT `deck_management_archetype_id_foreign` FOREIGN KEY (`archetype_id`) REFERENCES `mtg_archetypes` (`id`),
  CONSTRAINT `deck_management_deck_owner_id_foreign` FOREIGN KEY (`deck_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deck_optimizer_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deck_optimizer_issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `issue_creator_id` bigint unsigned NOT NULL,
  `issue_assignee_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `issue_type_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deck_optimizer_issues_issue_creator_id_foreign` (`issue_creator_id`),
  KEY `deck_optimizer_issues_issue_assignee_id_foreign` (`issue_assignee_id`),
  KEY `deck_optimizer_issues_priority_index` (`priority`),
  KEY `deck_optimizer_issues_status_index` (`status`),
  KEY `deck_optimizer_issues_issue_type_id_foreign` (`issue_type_id`),
  CONSTRAINT `deck_optimizer_issues_issue_assignee_id_foreign` FOREIGN KEY (`issue_assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deck_optimizer_issues_issue_creator_id_foreign` FOREIGN KEY (`issue_creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deck_optimizer_issues_issue_type_id_foreign` FOREIGN KEY (`issue_type_id`) REFERENCES `enum_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deck_owner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deck_owner` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_login` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `free_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deck_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `enum_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enum_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `version` int unsigned NOT NULL DEFAULT '1',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enum_values_domain_slug_unique` (`domain`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invitation_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitation_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitation_tokens_email_unique` (`email`),
  UNIQUE KEY `invitation_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issue_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `sort` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `magic_set_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `magic_set_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `official_set_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `release_date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_cards` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cards_populated` tinyint(1) NOT NULL DEFAULT '0',
  `imported_from_mtgjson` tinyint(1) DEFAULT '0',
  `date_of_json_used_for_import` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `magic_set_data_official_set_code_index` (`official_set_code`),
  KEY `magic_set_data_set_name_index` (`set_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `magic_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `magic_sets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `official_set_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_year` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_cards` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_archetypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_archetypes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `criteria` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archetype_source_site` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'mtgdecks',
  `archetype_source_site_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mtg_archetypes_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_bulk_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_bulk_prices` (
  `scryfall_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oracle_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collector_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usd` decimal(8,2) DEFAULT NULL,
  `usd_foil` decimal(8,2) DEFAULT NULL,
  `rarity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `released_at` date DEFAULT NULL,
  `image_uri` text COLLATE utf8mb4_unicode_ci,
  `price_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`scryfall_id`),
  KEY `mtg_bulk_prices_oracle_id_index` (`oracle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_card_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_card_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `card_metadata_id` bigint unsigned NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usd',
  `price` decimal(8,2) DEFAULT NULL,
  `is_foil` tinyint(1) NOT NULL DEFAULT '0',
  `price_date` date NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scryfall',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_price_dim` (`card_metadata_id`,`currency`,`is_foil`,`price_date`),
  KEY `mtg_card_prices_price_date_currency_index` (`price_date`,`currency`),
  CONSTRAINT `mtg_card_prices_card_metadata_id_foreign` FOREIGN KEY (`card_metadata_id`) REFERENCES `card_metadata` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_card_to_set_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_card_to_set_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `card_metadata_id` bigint unsigned NOT NULL,
  `card_data_id` bigint unsigned NOT NULL,
  `magic_set_data_id` bigint unsigned NOT NULL,
  `currency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usd',
  `price` decimal(8,2) DEFAULT '0.00',
  `is_foil` tinyint(1) NOT NULL DEFAULT '0',
  `price_date` date NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scryfall',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mtg_card_to_set_prices_card_metadata_id_foreign` (`card_metadata_id`),
  KEY `mtg_card_to_set_prices_card_data_id_foreign` (`card_data_id`),
  KEY `mtg_card_to_set_prices_magic_set_data_id_foreign` (`magic_set_data_id`),
  CONSTRAINT `mtg_card_to_set_prices_card_data_id_foreign` FOREIGN KEY (`card_data_id`) REFERENCES `card_data_from_set_data` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mtg_card_to_set_prices_card_metadata_id_foreign` FOREIGN KEY (`card_metadata_id`) REFERENCES `card_metadata` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mtg_card_to_set_prices_magic_set_data_id_foreign` FOREIGN KEY (`magic_set_data_id`) REFERENCES `magic_set_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_deck_board_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_deck_board_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deck_id` bigint unsigned NOT NULL,
  `board_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `num_cards` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mtg_deck_board_groups_deck_id_foreign` (`deck_id`),
  CONSTRAINT `mtg_deck_board_groups_deck_id_foreign` FOREIGN KEY (`deck_id`) REFERENCES `deck_management` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_image_lookups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_image_lookups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `card_uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `canonical_image_url_back` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scryfall_image_uris` json DEFAULT NULL,
  `hydrated_via_command` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mtg_image_lookups_card_uuid_unique` (`card_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mtg_json_import_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtg_json_import_candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `set_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `set_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `total_cards` int DEFAULT NULL,
  `metadata_count` int DEFAULT NULL,
  `normalized_count` int DEFAULT NULL,
  `image_count` int DEFAULT NULL,
  `metadata_pct` double DEFAULT NULL,
  `normalization_pct` double DEFAULT NULL,
  `image_pct` double DEFAULT NULL,
  `json_file_size_bytes` bigint DEFAULT NULL,
  `json_file_date` date DEFAULT NULL,
  `imported_into_database` tinyint(1) NOT NULL DEFAULT '0',
  `ready_for_import` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mtg_json_import_candidates_set_code_index` (`set_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_user` (
  `role_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scryfall_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scryfall_imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_count` int DEFAULT NULL,
  `imported_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `set_enrichment_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `set_enrichment_status` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `set_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enriched_at` timestamp NULL DEFAULT NULL,
  `logic_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flags` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `set_enrichment_status_set_code_unique` (`set_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sets_in_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sets_in_collection` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_management_id` bigint unsigned NOT NULL,
  `set_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sets_in_collection_set_id_foreign` (`set_id`),
  KEY `sets_in_collection_collection_id_foreign` (`collection_management_id`),
  CONSTRAINT `sets_in_collection_collection_id_foreign` FOREIGN KEY (`collection_management_id`) REFERENCES `collection_management` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sets_in_collection_set_id_foreign` FOREIGN KEY (`set_id`) REFERENCES `magic_set_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sets_in_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sets_in_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `collected_cards_from_sets_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sets_collections_composite_idx` (`collection_id`,`collected_cards_from_sets_id`),
  KEY `sets_collections_collection_idx` (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_superuser` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_alias_unique` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_09_01_171617_create_cards_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_09_01_171633_create_collection_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_09_05_153249_create_deck_management_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_09_06_230802_create_set_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_09_07_161148_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_09_07_200901_modify_columns_for_deck_management',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_09_07_213522_modify_columns_for_cards_in_deck',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_09_24_072734_add_archetype_to_decks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_09_24_073312_add_printings_to_normalized_card_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_09_24_234847_add_source_printing_to_card_data_normalized_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_09_24_235929_add_adtl_fields_printing_to_magic_set_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_09_25_000240_create_card_metadata_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_09_25_003205_add_new_fields_to_card_data_from_set_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_09_25_030941_add_source_printing_id_to_card_data_normalized_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_09_25_031436_add_foreign_key_to_card_metadata_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_09_25_031750_add_fields_to_card_data_normalized_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_09_25_050041_drop_cards_in_deck_id_from_deck_management',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_09_25_050610_add_timestamps_to_cards_in_deck_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_09_26_065240_create_collection_owners_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_09_27_054129_update_collection_sets_default_on_collections-table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_09_27_155951_create_mtg_image_lookups_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_09_27_185023_add_back_to_mtg_image_lookups_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_09_28_015728_add_additional_metadata_to_card_metadata_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_09_28_045010_create_set_enrichment_status_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_09_28_050451_add_cards_populated_to_magic_set_data_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_09_28_053348_add_logic_version_to_card_metadata_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_09_28_073234_add_import_performance_indexes',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_09_28_212610_add_image_normalized_at_to_card_data_from_set_data',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_10_03_223455_create_mtg_card_prices_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_10_05_213436_update_columns_in_card_data_normalized_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_10_06_044436_create_board_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_10_06_063344_create_telescope_entries_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_10_06_155208_create_mtg_card_to_set_prices_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_10_07_054959_create_mtg_bulk_prices_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_10_08_013624_add_additional_columns_to_mtg_image_lookups_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_10_08_033559_add_scryfall_id_to_card_metadata_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_10_09_011512_remove_sets_in_collection_id_from_collection_management_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_10_09_031454_add_job_status_to_collection_management_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_10_09_081427_rename_collection_id_on_sets_in_collection_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_10_09_182547_create_scryfall_imports_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_10_13_214520_add_fields_to_scryfall_imports_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_10_17_002819_add_json_import_date_to_magic_set_data',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_10_17_211301_create_mtg_json_import_candidates_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_10_22_220330_create_mtg_archetypes_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_10_22_220939_add_columns_to_mtg_archetypes_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_10_25_152501_add_slug_to_card_data_from_set_data_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_10_25_200816_add_full_text_index_to_card_data_from_set_data_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_30_231820_create_invitiation_tokens_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_11_02_082628_add_alias_to_users_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_11_02_091735_add_visibility_to_deck_management_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_11_04_194116_create_deck_optimizer_issues_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_11_14_212853_create_roles_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_11_16_165940_create_collection_user_pivot_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_11_16_205521_add_collection_management_user_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_11_23_192426_create_issue_type_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_11_23_225403_create_enum_values_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_11_24_220254_add_foreign_key_for_issue_type_to_deck_optimizer_issues_table',44);

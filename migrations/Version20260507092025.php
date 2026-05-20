<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507092025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE merchant_categories (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, icon VARCHAR(100) DEFAULT NULL, color VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_589C81B85E237E06 (name), UNIQUE INDEX UNIQ_589C81B8989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notifications (id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, merchant_id VARCHAR(36) DEFAULT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(30) NOT NULL, channels JSON NOT NULL, is_read TINYINT(1) DEFAULT 0 NOT NULL, read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', external_message_id VARCHAR(255) DEFAULT NULL, metadata JSON DEFAULT NULL, INDEX IDX_6000B0D39395C3F3 (customer_id), INDEX IDX_6000B0D36796D554 (merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payments (id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, merchant_id VARCHAR(36) DEFAULT NULL, transaction_id VARCHAR(100) NOT NULL, payment_gateway VARCHAR(20) NOT NULL, payment_gateway_id VARCHAR(100) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) DEFAULT \'INR\' NOT NULL, payment_type VARCHAR(30) NOT NULL, status VARCHAR(20) DEFAULT \'INITIATED\' NOT NULL, payment_method VARCHAR(20) DEFAULT NULL, failure_reason VARCHAR(500) DEFAULT NULL, retry_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', webhook_received_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', receipt_url VARCHAR(500) DEFAULT NULL, metadata JSON DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, UNIQUE INDEX UNIQ_65D29B322FC0CB0F (transaction_id), UNIQUE INDEX UNIQ_65D29B3262890FD5 (payment_gateway_id), INDEX IDX_65D29B329395C3F3 (customer_id), INDEX IDX_65D29B326796D554 (merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reward_redemptions (id VARCHAR(36) NOT NULL, reward_id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, stamp_card_id VARCHAR(36) DEFAULT NULL, approved_by_merchant_id VARCHAR(36) DEFAULT NULL, redeemed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) DEFAULT \'PENDING\' NOT NULL, redeem_code VARCHAR(50) NOT NULL, voucher_url VARCHAR(500) DEFAULT NULL, merchant_approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes VARCHAR(500) DEFAULT NULL, metadata JSON DEFAULT NULL, UNIQUE INDEX UNIQ_ACD0329FE27523C5 (redeem_code), INDEX IDX_ACD0329FE466ACA1 (reward_id), INDEX IDX_ACD0329F9395C3F3 (customer_id), INDEX IDX_ACD0329F31404D9A (stamp_card_id), INDEX IDX_ACD0329F651A9233 (approved_by_merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rewards (id VARCHAR(36) NOT NULL, merchant_id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, reward_type VARCHAR(20) NOT NULL, value NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) DEFAULT \'INR\' NOT NULL, stamp_requirement INT DEFAULT 10 NOT NULL, max_redemptions INT DEFAULT NULL, current_redemptions INT DEFAULT 0 NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', image_url VARCHAR(255) DEFAULT NULL, terms LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'DRAFT\' NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', metadata JSON DEFAULT NULL, INDEX IDX_E9221E376796D554 (merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stamp_cards (id VARCHAR(36) NOT NULL, merchant_id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, card_number VARCHAR(50) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, total_slots_required INT DEFAULT 10 NOT NULL, current_stamp_count INT DEFAULT 0 NOT NULL, status VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_stamp_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', bonus_stamps INT DEFAULT 0 NOT NULL, metadata JSON NOT NULL, is_digital TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_20434598E4AF4C20 (card_number), INDEX IDX_204345986796D554 (merchant_id), INDEX IDX_204345989395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stamps (id VARCHAR(36) NOT NULL, stamp_card_id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) NOT NULL, merchant_id VARCHAR(36) NOT NULL, stamp_sequence INT NOT NULL, is_bonus TINYINT(1) DEFAULT 0 NOT NULL, collected_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(10) DEFAULT \'ACTIVE\' NOT NULL, transaction_id VARCHAR(100) DEFAULT NULL, metadata JSON NOT NULL, notes VARCHAR(500) DEFAULT NULL, INDEX IDX_158009A631404D9A (stamp_card_id), INDEX IDX_158009A69395C3F3 (customer_id), INDEX IDX_158009A66796D554 (merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transactions (id VARCHAR(36) NOT NULL, customer_id VARCHAR(36) DEFAULT NULL, merchant_id VARCHAR(36) DEFAULT NULL, transaction_type VARCHAR(30) NOT NULL, amount NUMERIC(10, 2) DEFAULT NULL, stamps INT DEFAULT NULL, reference_id VARCHAR(36) DEFAULT NULL, status VARCHAR(20) DEFAULT \'COMPLETED\' NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', description VARCHAR(500) NOT NULL, metadata JSON DEFAULT NULL, INDEX IDX_EAA81A4C9395C3F3 (customer_id), INDEX IDX_EAA81A4C6796D554 (merchant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id VARCHAR(36) NOT NULL, category_id VARCHAR(36) DEFAULT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, status VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL, is_email_verified TINYINT(1) DEFAULT 0 NOT NULL, is_phone_verified TINYINT(1) DEFAULT 0 NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_type VARCHAR(255) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, profile_picture_url VARCHAR(255) DEFAULT NULL, date_of_birth DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', gender VARCHAR(10) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, state VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT \'IN\', latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, preferred_language VARCHAR(10) DEFAULT \'en\', total_stamps_collected INT DEFAULT 0, total_rewards_redeemed INT DEFAULT 0, total_spent NUMERIC(10, 2) DEFAULT \'0.00\', referral_code VARCHAR(20) DEFAULT NULL, referral_count INT DEFAULT 0, last_active_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', preferences JSON DEFAULT NULL, business_name VARCHAR(255) DEFAULT NULL, business_description LONGTEXT DEFAULT NULL, business_logo_url VARCHAR(255) DEFAULT NULL, subcategory VARCHAR(100) DEFAULT NULL, address LONGTEXT DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, phone_for_business VARCHAR(20) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, tax_id VARCHAR(30) DEFAULT NULL, bank_account_number VARCHAR(255) DEFAULT NULL, bank_ifsc_code VARCHAR(20) DEFAULT NULL, bank_account_holder_name VARCHAR(255) DEFAULT NULL, commission_rate NUMERIC(5, 4) DEFAULT \'0.0500\', average_rating NUMERIC(3, 1) DEFAULT NULL, total_customers INT DEFAULT 0, total_stamps_issued INT DEFAULT 0, total_rewards_given NUMERIC(12, 2) DEFAULT \'0.00\', is_verified TINYINT(1) DEFAULT 0, verification_documents JSON DEFAULT NULL, onboarding_status VARCHAR(20) DEFAULT \'PENDING\', api_key VARCHAR(255) DEFAULT NULL, api_secret VARCHAR(255) DEFAULT NULL, webhook_url VARCHAR(500) DEFAULT NULL, last_api_access_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', terms_accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9444F97DD (phone), UNIQUE INDEX UNIQ_1483A5E96447454A (referral_code), UNIQUE INDEX UNIQ_1483A5E9B2A824D8 (tax_id), UNIQUE INDEX UNIQ_1483A5E9C912ED9D (api_key), INDEX IDX_1483A5E912469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D39395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D36796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B329395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B326796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reward_redemptions ADD CONSTRAINT FK_ACD0329FE466ACA1 FOREIGN KEY (reward_id) REFERENCES rewards (id)');
        $this->addSql('ALTER TABLE reward_redemptions ADD CONSTRAINT FK_ACD0329F9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reward_redemptions ADD CONSTRAINT FK_ACD0329F31404D9A FOREIGN KEY (stamp_card_id) REFERENCES stamp_cards (id)');
        $this->addSql('ALTER TABLE reward_redemptions ADD CONSTRAINT FK_ACD0329F651A9233 FOREIGN KEY (approved_by_merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE rewards ADD CONSTRAINT FK_E9221E376796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE stamp_cards ADD CONSTRAINT FK_204345986796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE stamp_cards ADD CONSTRAINT FK_204345989395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE stamps ADD CONSTRAINT FK_158009A631404D9A FOREIGN KEY (stamp_card_id) REFERENCES stamp_cards (id)');
        $this->addSql('ALTER TABLE stamps ADD CONSTRAINT FK_158009A69395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE stamps ADD CONSTRAINT FK_158009A66796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C9395C3F3 FOREIGN KEY (customer_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C6796D554 FOREIGN KEY (merchant_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E912469DE2 FOREIGN KEY (category_id) REFERENCES merchant_categories (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D39395C3F3');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D36796D554');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B329395C3F3');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B326796D554');
        $this->addSql('ALTER TABLE reward_redemptions DROP FOREIGN KEY FK_ACD0329FE466ACA1');
        $this->addSql('ALTER TABLE reward_redemptions DROP FOREIGN KEY FK_ACD0329F9395C3F3');
        $this->addSql('ALTER TABLE reward_redemptions DROP FOREIGN KEY FK_ACD0329F31404D9A');
        $this->addSql('ALTER TABLE reward_redemptions DROP FOREIGN KEY FK_ACD0329F651A9233');
        $this->addSql('ALTER TABLE rewards DROP FOREIGN KEY FK_E9221E376796D554');
        $this->addSql('ALTER TABLE stamp_cards DROP FOREIGN KEY FK_204345986796D554');
        $this->addSql('ALTER TABLE stamp_cards DROP FOREIGN KEY FK_204345989395C3F3');
        $this->addSql('ALTER TABLE stamps DROP FOREIGN KEY FK_158009A631404D9A');
        $this->addSql('ALTER TABLE stamps DROP FOREIGN KEY FK_158009A69395C3F3');
        $this->addSql('ALTER TABLE stamps DROP FOREIGN KEY FK_158009A66796D554');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C9395C3F3');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C6796D554');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E912469DE2');
        $this->addSql('DROP TABLE merchant_categories');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE reward_redemptions');
        $this->addSql('DROP TABLE rewards');
        $this->addSql('DROP TABLE stamp_cards');
        $this->addSql('DROP TABLE stamps');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE users');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260522063712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add trial and subscription tracking fields to merchants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD trial_started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD trial_ends_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD subscription_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Backfill trial dates for existing merchants using their account creation date
        $this->addSql("UPDATE users SET trial_started_at = created_at, trial_ends_at = DATE_ADD(created_at, INTERVAL 30 DAY) WHERE user_type = 'merchant'");

        // Give existing ACTIVE merchants a 6-month grace period so they aren't immediately locked out
        $this->addSql("UPDATE users SET subscription_expires_at = DATE_ADD(NOW(), INTERVAL 6 MONTH) WHERE user_type = 'merchant' AND status = 'ACTIVE'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP trial_started_at, DROP trial_ends_at, DROP subscription_expires_at');
    }
}

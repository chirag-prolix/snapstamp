<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260518112347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payments CHANGE customer_id customer_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD referred_by_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9758C8114 FOREIGN KEY (referred_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E9758C8114 ON users (referred_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payments CHANGE customer_id customer_id VARCHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9758C8114');
        $this->addSql('DROP INDEX IDX_1483A5E9758C8114 ON users');
        $this->addSql('ALTER TABLE users DROP referred_by_id');
    }
}

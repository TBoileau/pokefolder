<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506113501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create card table (catalog mirror of TCGdex), with unique index on (setId, numberInSet, variant, language).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE card (id UUID NOT NULL, set_id VARCHAR(64) NOT NULL, number_in_set VARCHAR(32) NOT NULL, variant VARCHAR(64) NOT NULL, language VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, rarity VARCHAR(64) NOT NULL, image_url VARCHAR(500) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX card_functional_identity_uniq ON card (set_id, number_in_set, variant, language)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE card');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506181100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE binder_slot (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, page_number INT NOT NULL, face VARCHAR(8) NOT NULL, row_index INT NOT NULL, col_index INT NOT NULL, binder_id UUID NOT NULL, owned_card_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_26A02D311A30F0E0 ON binder_slot (binder_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_binder_slot_position ON binder_slot (binder_id, page_number, face, row_index, col_index)');
        $this->addSql('CREATE UNIQUE INDEX uniq_binder_slot_owned_card ON binder_slot (owned_card_id)');
        $this->addSql('ALTER TABLE binder_slot ADD CONSTRAINT FK_26A02D311A30F0E0 FOREIGN KEY (binder_id) REFERENCES binder (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE binder_slot ADD CONSTRAINT FK_26A02D3119602704 FOREIGN KEY (owned_card_id) REFERENCES owned_card (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE binder_slot DROP CONSTRAINT FK_26A02D311A30F0E0');
        $this->addSql('ALTER TABLE binder_slot DROP CONSTRAINT FK_26A02D3119602704');
        $this->addSql('DROP TABLE binder_slot');
    }
}

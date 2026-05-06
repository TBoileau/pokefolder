<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506223718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE binder (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(100) NOT NULL, page_count INT NOT NULL, cols INT NOT NULL, rows INT NOT NULL, double_sided BOOLEAN DEFAULT true NOT NULL, description VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE binder_slot (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, page_number INT NOT NULL, face VARCHAR(8) NOT NULL, row_index INT NOT NULL, col_index INT NOT NULL, binder_id UUID NOT NULL, owned_card_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_26A02D311A30F0E0 ON binder_slot (binder_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_binder_slot_position ON binder_slot (binder_id, page_number, face, row_index, col_index)');
        $this->addSql('CREATE UNIQUE INDEX uniq_binder_slot_owned_card ON binder_slot (owned_card_id)');
        $this->addSql('CREATE TABLE card (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, number_in_set VARCHAR(32) NOT NULL, variant VARCHAR(64) NOT NULL, language VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, image_url VARCHAR(500) DEFAULT NULL, pokemon_set_id VARCHAR(64) NOT NULL, rarity_code VARCHAR(64) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_161498D37C12714C ON card (pokemon_set_id)');
        $this->addSql('CREATE INDEX IDX_161498D33A057AFC ON card (rarity_code)');
        $this->addSql('CREATE UNIQUE INDEX card_functional_identity_uniq ON card (pokemon_set_id, number_in_set, variant, language)');
        $this->addSql('CREATE TABLE owned_card (id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, condition VARCHAR(4) NOT NULL, card_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_553D1AC54ACC9A20 ON owned_card (card_id)');
        $this->addSql('CREATE TABLE pokemon_set (id VARCHAR(64) NOT NULL, logo VARCHAR(500) DEFAULT NULL, symbol VARCHAR(500) DEFAULT NULL, release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, card_count_total INT DEFAULT NULL, card_count_official INT DEFAULT NULL, legal_standard BOOLEAN DEFAULT NULL, legal_expanded BOOLEAN DEFAULT NULL, tcg_online_id VARCHAR(64) DEFAULT NULL, serie_id VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4C8EB7F8D94388BD ON pokemon_set (serie_id)');
        $this->addSql('CREATE TABLE pokemon_set_translation (id UUID NOT NULL, language VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, abbreviation_official VARCHAR(32) DEFAULT NULL, abbreviation_normal VARCHAR(32) DEFAULT NULL, pokemon_set_id VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1E8FCDDA7C12714C ON pokemon_set_translation (pokemon_set_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_set_translation_lang ON pokemon_set_translation (pokemon_set_id, language)');
        $this->addSql('CREATE TABLE rarity (code VARCHAR(64) NOT NULL, PRIMARY KEY (code))');
        $this->addSql('CREATE TABLE rarity_translation (id UUID NOT NULL, language VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, rarity_code VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6CCD13F13A057AFC ON rarity_translation (rarity_code)');
        $this->addSql('CREATE UNIQUE INDEX uniq_rarity_translation_lang ON rarity_translation (rarity_code, language)');
        $this->addSql('CREATE TABLE serie (id VARCHAR(64) NOT NULL, logo VARCHAR(500) DEFAULT NULL, release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE serie_translation (id UUID NOT NULL, language VARCHAR(8) NOT NULL, name VARCHAR(255) NOT NULL, serie_id VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B355773CD94388BD ON serie_translation (serie_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_serie_translation_lang ON serie_translation (serie_id, language)');
        $this->addSql('ALTER TABLE binder_slot ADD CONSTRAINT FK_26A02D311A30F0E0 FOREIGN KEY (binder_id) REFERENCES binder (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE binder_slot ADD CONSTRAINT FK_26A02D3119602704 FOREIGN KEY (owned_card_id) REFERENCES owned_card (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D37C12714C FOREIGN KEY (pokemon_set_id) REFERENCES pokemon_set (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT FK_161498D33A057AFC FOREIGN KEY (rarity_code) REFERENCES rarity (code) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE owned_card ADD CONSTRAINT FK_553D1AC54ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id) ON DELETE RESTRICT NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pokemon_set ADD CONSTRAINT FK_4C8EB7F8D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE pokemon_set_translation ADD CONSTRAINT FK_1E8FCDDA7C12714C FOREIGN KEY (pokemon_set_id) REFERENCES pokemon_set (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE rarity_translation ADD CONSTRAINT FK_6CCD13F13A057AFC FOREIGN KEY (rarity_code) REFERENCES rarity (code) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE serie_translation ADD CONSTRAINT FK_B355773CD94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE binder_slot DROP CONSTRAINT FK_26A02D311A30F0E0');
        $this->addSql('ALTER TABLE binder_slot DROP CONSTRAINT FK_26A02D3119602704');
        $this->addSql('ALTER TABLE card DROP CONSTRAINT FK_161498D37C12714C');
        $this->addSql('ALTER TABLE card DROP CONSTRAINT FK_161498D33A057AFC');
        $this->addSql('ALTER TABLE owned_card DROP CONSTRAINT FK_553D1AC54ACC9A20');
        $this->addSql('ALTER TABLE pokemon_set DROP CONSTRAINT FK_4C8EB7F8D94388BD');
        $this->addSql('ALTER TABLE pokemon_set_translation DROP CONSTRAINT FK_1E8FCDDA7C12714C');
        $this->addSql('ALTER TABLE rarity_translation DROP CONSTRAINT FK_6CCD13F13A057AFC');
        $this->addSql('ALTER TABLE serie_translation DROP CONSTRAINT FK_B355773CD94388BD');
        $this->addSql('DROP TABLE binder');
        $this->addSql('DROP TABLE binder_slot');
        $this->addSql('DROP TABLE card');
        $this->addSql('DROP TABLE owned_card');
        $this->addSql('DROP TABLE pokemon_set');
        $this->addSql('DROP TABLE pokemon_set_translation');
        $this->addSql('DROP TABLE rarity');
        $this->addSql('DROP TABLE rarity_translation');
        $this->addSql('DROP TABLE serie');
        $this->addSql('DROP TABLE serie_translation');
    }
}

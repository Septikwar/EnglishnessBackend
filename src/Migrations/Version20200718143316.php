<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200718143316 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE word_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE word_group_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE wordgroup_word_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE word (id INT NOT NULL DEFAULT nextval(\'word_id_seq\'), en VARCHAR(255) NOT NULL, ru VARCHAR(255) DEFAULT NULL, add_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE word_group (id INT NOT NULL DEFAULT nextval(\'word_group_id_seq\'), name VARCHAR(255) NOT NULL UNIQUE, image VARCHAR(255), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE wordgroup_word (id INT NOT NULL DEFAULT nextval(\'wordgroup_word_id_seq\'), id_word INT NOT NULL, id_group INT NOT NULL, PRIMARY KEY(id))');

        $this->addSql('ALTER TABLE wordgroup_word ADD CONSTRAINT wordgroup_word_fk FOREIGN KEY (id_word) REFERENCES word(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wordgroup_word ADD CONSTRAINT wordgroup_word_fk_1 FOREIGN KEY (id_group) REFERENCES word_group(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE word_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE word_group_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE wordgroup_word_id_seq CASCADE');
        $this->addSql('DROP TABLE word CASCADE');
        $this->addSql('DROP TABLE word_group CASCADE');
        $this->addSql('DROP TABLE wordgroup_word CASCADE');
    }
}

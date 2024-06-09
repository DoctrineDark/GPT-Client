<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240326222221 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE gpt_request_options (id INT AUTO_INCREMENT NOT NULL, gpt_service VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, temperature DOUBLE PRECISION DEFAULT NULL, max_tokens INT DEFAULT NULL, prompt_token_limit INT DEFAULT NULL, frequency_penalty DOUBLE PRECISION DEFAULT NULL, presence_penalty DOUBLE PRECISION DEFAULT NULL, system_message LONGTEXT DEFAULT NULL, entry_template LONGTEXT DEFAULT NULL, lists_message_template LONGTEXT DEFAULT NULL, checkboxes_message_template LONGTEXT DEFAULT NULL, client_message_template LONGTEXT DEFAULT NULL, raw_request_template LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE gpt_request_options');
    }
}

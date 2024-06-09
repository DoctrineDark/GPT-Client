<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240326000543 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE gpt_summarize_options (id INT AUTO_INCREMENT NOT NULL, gpt_service VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, temperature DOUBLE PRECISION DEFAULT NULL, max_tokens INT DEFAULT NULL, prompt_token_limit INT DEFAULT NULL, frequency_penalty DOUBLE PRECISION DEFAULT NULL, presence_penalty DOUBLE PRECISION DEFAULT NULL, system_message LONGTEXT DEFAULT NULL, main_prompt_entry_template LONGTEXT DEFAULT NULL, main_prompt_body_template LONGTEXT DEFAULT NULL, chunk_summarize_prompt_template LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
   }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE gpt_summarize_options');
    }
}

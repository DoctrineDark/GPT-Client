<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241004191027 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gpt_request_options ADD top_p DOUBLE PRECISION DEFAULT NULL AFTER presence_penalty, ADD top_k INT DEFAULT NULL AFTER top_p, CHANGE temperature temperature DOUBLE PRECISION DEFAULT NULL, CHANGE max_tokens max_tokens INT DEFAULT NULL, CHANGE prompt_token_limit prompt_token_limit INT DEFAULT NULL, CHANGE frequency_penalty frequency_penalty DOUBLE PRECISION DEFAULT NULL, CHANGE presence_penalty presence_penalty DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gpt_request_options DROP top_p, DROP top_k, CHANGE temperature temperature DOUBLE PRECISION DEFAULT NULL, CHANGE max_tokens max_tokens INT DEFAULT NULL, CHANGE prompt_token_limit prompt_token_limit INT DEFAULT NULL, CHANGE frequency_penalty frequency_penalty DOUBLE PRECISION DEFAULT NULL, CHANGE presence_penalty presence_penalty DOUBLE PRECISION DEFAULT NULL');
    }
}

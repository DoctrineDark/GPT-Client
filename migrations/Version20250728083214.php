<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728083214 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE opensearch_indexes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, dimensions INT NOT NULL, analyzer VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE opensearch_vectors (id INT AUTO_INCREMENT NOT NULL, opensearch_index_id INT DEFAULT NULL, article_id INT DEFAULT NULL, article_paragraph_id INT DEFAULT NULL, template_id INT DEFAULT NULL, vector_id VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_4588DF631074C9C5 (opensearch_index_id), INDEX IDX_4588DF637294869C (article_id), INDEX IDX_4588DF63581C2EEE (article_paragraph_id), INDEX IDX_4588DF635DA0FB8 (template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE opensearch_indexes');
        $this->addSql('DROP TABLE opensearch_vectors');
    }
}

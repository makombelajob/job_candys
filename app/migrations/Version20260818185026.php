<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818185026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY `FK_8244AA3A680882CF`');
        $this->addSql('DROP INDEX IDX_8244AA3A680882CF ON companies');
        $this->addSql('ALTER TABLE companies DROP freelance_propositions_id');
        $this->addSql('ALTER TABLE freelance_propositions ADD companies_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE freelance_propositions ADD CONSTRAINT FK_F483A8106AE4741E FOREIGN KEY (companies_id) REFERENCES companies (id)');
        $this->addSql('CREATE INDEX IDX_F483A8106AE4741E ON freelance_propositions (companies_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE companies ADD freelance_propositions_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE companies ADD CONSTRAINT `FK_8244AA3A680882CF` FOREIGN KEY (freelance_propositions_id) REFERENCES freelance_propositions (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8244AA3A680882CF ON companies (freelance_propositions_id)');
        $this->addSql('ALTER TABLE freelance_propositions DROP FOREIGN KEY FK_F483A8106AE4741E');
        $this->addSql('DROP INDEX IDX_F483A8106AE4741E ON freelance_propositions');
        $this->addSql('ALTER TABLE freelance_propositions DROP companies_id');
    }
}

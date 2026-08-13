<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813084338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE applications (id INT AUTO_INCREMENT NOT NULL, cv_used VARCHAR(255) NOT NULL, letter_used VARCHAR(255) NOT NULL, status TINYINT NOT NULL, sent_at DATETIME NOT NULL, profils_id INT DEFAULT NULL, companies_id INT DEFAULT NULL, INDEX IDX_F7C966F0B9881AFB (profils_id), INDEX IDX_F7C966F06AE4741E (companies_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, web_site VARCHAR(100) DEFAULT NULL, carre_page VARCHAR(100) DEFAULT NULL, trusted_score VARCHAR(255) DEFAULT NULL, last_check DATETIME DEFAULT NULL, linkedin VARCHAR(100) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, address VARCHAR(255) NOT NULL, siret VARCHAR(20) NOT NULL, full_name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE company_contacts (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_2BD7001E979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE contacts_admin (id INT AUTO_INCREMENT NOT NULL, subject VARCHAR(100) NOT NULL, message LONGTEXT NOT NULL, sent_at DATETIME NOT NULL, profils_id INT DEFAULT NULL, INDEX IDX_9AB98583B9881AFB (profils_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, profils_id INT DEFAULT NULL, INDEX IDX_6000B0D3B9881AFB (profils_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE profils (id INT AUTO_INCREMENT NOT NULL, phone VARCHAR(20) NOT NULL, city VARCHAR(50) NOT NULL, default_cv VARCHAR(255) NOT NULL, default_letter VARCHAR(255) NOT NULL, linkedin VARCHAR(100) NOT NULL, website VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, create_at DATETIME NOT NULL, is_verified TINYINT NOT NULL, last_login DATETIME DEFAULT NULL, reset_token VARCHAR(64) DEFAULT NULL, updated_at DATETIME NOT NULL, sender_email VARCHAR(255) NOT NULL, profils_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9B9881AFB (profils_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE users_companies (users_id INT NOT NULL, companies_id INT NOT NULL, INDEX IDX_E439D0DB67B3B43D (users_id), INDEX IDX_E439D0DB6AE4741E (companies_id), PRIMARY KEY (users_id, companies_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F0B9881AFB FOREIGN KEY (profils_id) REFERENCES profils (id)');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F06AE4741E FOREIGN KEY (companies_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE company_contacts ADD CONSTRAINT FK_2BD7001E979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE contacts_admin ADD CONSTRAINT FK_9AB98583B9881AFB FOREIGN KEY (profils_id) REFERENCES profils (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3B9881AFB FOREIGN KEY (profils_id) REFERENCES profils (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9B9881AFB FOREIGN KEY (profils_id) REFERENCES profils (id)');
        $this->addSql('ALTER TABLE users_companies ADD CONSTRAINT FK_E439D0DB67B3B43D FOREIGN KEY (users_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_companies ADD CONSTRAINT FK_E439D0DB6AE4741E FOREIGN KEY (companies_id) REFERENCES companies (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F0B9881AFB');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F06AE4741E');
        $this->addSql('ALTER TABLE company_contacts DROP FOREIGN KEY FK_2BD7001E979B1AD6');
        $this->addSql('ALTER TABLE contacts_admin DROP FOREIGN KEY FK_9AB98583B9881AFB');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3B9881AFB');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9B9881AFB');
        $this->addSql('ALTER TABLE users_companies DROP FOREIGN KEY FK_E439D0DB67B3B43D');
        $this->addSql('ALTER TABLE users_companies DROP FOREIGN KEY FK_E439D0DB6AE4741E');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE company_contacts');
        $this->addSql('DROP TABLE contacts_admin');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE profils');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE users_companies');
        $this->addSql('DROP TABLE messenger_messages');
    }
}

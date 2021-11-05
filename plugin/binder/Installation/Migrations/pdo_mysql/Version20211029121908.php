<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/10/29 12:19:11
 */
class Version20211029121908 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder_tab CHANGE binder_id binder_id INT DEFAULT NULL, 
            CHANGE document_id document_id INT DEFAULT NULL, 
            CHANGE title title VARCHAR(255) DEFAULT NULL, 
            CHANGE backgroundColor backgroundColor VARCHAR(255) DEFAULT NULL, 
            CHANGE textColor textColor VARCHAR(255) DEFAULT NULL, 
            CHANGE borderColor borderColor VARCHAR(255) DEFAULT NULL, 
            CHANGE icon icon VARCHAR(255) DEFAULT NULL, 
            CHANGE details details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', 
            CHANGE translations translations LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            ADD is_upload_destination TINYINT(1) NOT NULL, 
            CHANGE requirements_node_id requirements_node_id INT DEFAULT NULL, 
            CHANGE resourceNode_id resourceNode_id INT DEFAULT NULL, 
            CHANGE translations translations LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', 
            CHANGE sortBy sortBy VARCHAR(255) DEFAULT NULL, 
            CHANGE searchMode searchMode VARCHAR(255) DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder CHANGE resourceNode_id resourceNode_id INT DEFAULT NULL, 
            CHANGE sortBy sortBy VARCHAR(255) DEFAULT NULL, 
            CHANGE searchMode searchMode VARCHAR(255) DEFAULT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder CHANGE sortBy sortBy VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE searchMode searchMode VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE resourceNode_id resourceNode_id INT DEFAULT NULL
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab CHANGE binder_id binder_id INT DEFAULT NULL, 
            CHANGE document_id document_id INT DEFAULT NULL, 
            CHANGE title title VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE backgroundColor backgroundColor VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE textColor textColor VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE borderColor borderColor VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE icon icon VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE details details LONGTEXT CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json_array)', 
            CHANGE translations translations LONGTEXT CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json)'
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            DROP is_upload_destination, 
            CHANGE requirements_node_id requirements_node_id INT DEFAULT NULL, 
            CHANGE translations translations LONGTEXT CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json)', 
            CHANGE sortBy sortBy VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE searchMode searchMode VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE resourceNode_id resourceNode_id INT DEFAULT NULL
        ");
    }
}

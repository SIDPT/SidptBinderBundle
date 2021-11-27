<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/11/24 01:30:38
 */
class Version20211124133036 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE sidpt_widget_resources_search (
                id INT AUTO_INCREMENT NOT NULL, 
                searchFormConfiguration LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                maxResults INT DEFAULT NULL, 
                filterable TINYINT(1) NOT NULL, 
                sortable TINYINT(1) NOT NULL, 
                paginated TINYINT(1) NOT NULL, 
                columnsFilterable TINYINT(1) NOT NULL, 
                count TINYINT(1) NOT NULL, 
                actions TINYINT(1) NOT NULL, 
                sortBy VARCHAR(255) DEFAULT NULL, 
                availableSort LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                pageSize INT NOT NULL, 
                availablePageSizes LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                display VARCHAR(255) NOT NULL, 
                availableDisplays LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                searchMode VARCHAR(255) DEFAULT NULL, 
                filters LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                availableFilters LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                availableColumns LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                displayedColumns LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                columnsCustomization LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                card LONGTEXT NOT NULL COMMENT '(DC2Type:json)', 
                widgetInstance_id INT NOT NULL, 
                INDEX IDX_39DE62A1AB7B5A55 (widgetInstance_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE sidpt_widget_resources_search 
            ADD CONSTRAINT FK_39DE62A1AB7B5A55 FOREIGN KEY (widgetInstance_id) 
            REFERENCES claro_widget_instance (id) 
            ON DELETE CASCADE
        ");
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
            ALTER TABLE sidpt__document CHANGE requirements_node_id requirements_node_id INT DEFAULT NULL, 
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
            DROP TABLE sidpt_widget_resources_search
        ");
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
            ALTER TABLE sidpt__document CHANGE requirements_node_id requirements_node_id INT DEFAULT NULL, 
            CHANGE translations translations LONGTEXT CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci` COMMENT '(DC2Type:json)', 
            CHANGE sortBy sortBy VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE searchMode searchMode VARCHAR(255) CHARACTER SET utf8 DEFAULT 'NULL' COLLATE `utf8_unicode_ci`, 
            CHANGE resourceNode_id resourceNode_id INT DEFAULT NULL
        ");
    }
}

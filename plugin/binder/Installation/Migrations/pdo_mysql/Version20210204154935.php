<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/02/04 03:49:38
 */
class Version20210204154935 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE sidpt__binder_tab (
                id INT AUTO_INCREMENT NOT NULL, 
                owner_id INT NOT NULL, 
                binder_id INT DEFAULT NULL, 
                document_id INT DEFAULT NULL, 
                position SMALLINT NOT NULL, 
                type VARCHAR(255) NOT NULL, 
                title VARCHAR(255) DEFAULT NULL, 
                backgroundColor VARCHAR(255) DEFAULT NULL, 
                textColor VARCHAR(255) DEFAULT NULL, 
                borderColor VARCHAR(255) DEFAULT NULL, 
                icon VARCHAR(255) DEFAULT NULL, 
                is_visible TINYINT(1) NOT NULL, 
                details LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)', 
                translations LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', 
                uuid VARCHAR(36) NOT NULL, 
                UNIQUE INDEX UNIQ_BC9F1F3AD17F50A6 (uuid), 
                INDEX IDX_BC9F1F3A7E3C61F9 (owner_id), 
                INDEX IDX_BC9F1F3A1A30F0E0 (binder_id), 
                INDEX IDX_BC9F1F3AC33F7837 (document_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE sidpt__binder_tab_roles (
                bindertab_id INT NOT NULL, 
                role_id INT NOT NULL, 
                INDEX IDX_5230104585B62573 (bindertab_id), 
                INDEX IDX_52301045D60322AC (role_id), 
                PRIMARY KEY(bindertab_id, role_id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql("
            CREATE TABLE sidpt__binder (
                id INT AUTO_INCREMENT NOT NULL, 
                uuid VARCHAR(36) NOT NULL, 
                resourceNode_id INT DEFAULT NULL, 
                UNIQUE INDEX UNIQ_BC995DCDD17F50A6 (uuid), 
                UNIQUE INDEX UNIQ_BC995DCDB87FAB32 (resourceNode_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab 
            ADD CONSTRAINT FK_BC9F1F3A7E3C61F9 FOREIGN KEY (owner_id) 
            REFERENCES sidpt__binder (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab 
            ADD CONSTRAINT FK_BC9F1F3A1A30F0E0 FOREIGN KEY (binder_id) 
            REFERENCES sidpt__binder (id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab 
            ADD CONSTRAINT FK_BC9F1F3AC33F7837 FOREIGN KEY (document_id) 
            REFERENCES sidpt__document (id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab_roles 
            ADD CONSTRAINT FK_5230104585B62573 FOREIGN KEY (bindertab_id) 
            REFERENCES sidpt__binder_tab (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab_roles 
            ADD CONSTRAINT FK_52301045D60322AC FOREIGN KEY (role_id) 
            REFERENCES claro_role (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder 
            ADD CONSTRAINT FK_BC995DCDB87FAB32 FOREIGN KEY (resourceNode_id) 
            REFERENCES claro_resource_node (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            DROP FOREIGN KEY FK_F4A7C431B87FAB32
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            ADD translations LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'
        ");
        $this->addSql("
            DROP INDEX uniq_f4a7c431d17f50a6 ON sidpt__document
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_1E3DD56BD17F50A6 ON sidpt__document (uuid)
        ");
        $this->addSql("
            DROP INDEX uniq_f4a7c431b87fab32 ON sidpt__document
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_1E3DD56BB87FAB32 ON sidpt__document (resourceNode_id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            ADD CONSTRAINT FK_F4A7C431B87FAB32 FOREIGN KEY (resourceNode_id) 
            REFERENCES claro_resource_node (id) ON UPDATE NO ACTION 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_21883A0C581122C3
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_21883A0CC33F7837
        ");
        $this->addSql("
            DROP INDEX idx_21883a0cc33f7837 ON sidpt__document_widgets
        ");
        $this->addSql("
            CREATE INDEX IDX_FA5EA823C33F7837 ON sidpt__document_widgets (document_id)
        ");
        $this->addSql("
            DROP INDEX uniq_21883a0c581122c3 ON sidpt__document_widgets
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_FA5EA823581122C3 ON sidpt__document_widgets (widget_container_id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_21883A0C581122C3 FOREIGN KEY (widget_container_id) 
            REFERENCES claro_widget_container (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_21883A0CC33F7837 FOREIGN KEY (document_id) 
            REFERENCES sidpt__document (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE sidpt__binder_tab_roles 
            DROP FOREIGN KEY FK_5230104585B62573
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab 
            DROP FOREIGN KEY FK_BC9F1F3A7E3C61F9
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab 
            DROP FOREIGN KEY FK_BC9F1F3A1A30F0E0
        ");
        $this->addSql("
            DROP TABLE sidpt__binder_tab
        ");
        $this->addSql("
            DROP TABLE sidpt__binder_tab_roles
        ");
        $this->addSql("
            DROP TABLE sidpt__binder
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            DROP FOREIGN KEY FK_1E3DD56BB87FAB32
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            DROP translations
        ");
        $this->addSql("
            DROP INDEX uniq_1e3dd56bb87fab32 ON sidpt__document
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F4A7C431B87FAB32 ON sidpt__document (resourceNode_id)
        ");
        $this->addSql("
            DROP INDEX uniq_1e3dd56bd17f50a6 ON sidpt__document
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_F4A7C431D17F50A6 ON sidpt__document (uuid)
        ");
        $this->addSql("
            ALTER TABLE sidpt__document 
            ADD CONSTRAINT FK_1E3DD56BB87FAB32 FOREIGN KEY (resourceNode_id) 
            REFERENCES claro_resource_node (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_FA5EA823C33F7837
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_FA5EA823581122C3
        ");
        $this->addSql("
            DROP INDEX idx_fa5ea823c33f7837 ON sidpt__document_widgets
        ");
        $this->addSql("
            CREATE INDEX IDX_21883A0CC33F7837 ON sidpt__document_widgets (document_id)
        ");
        $this->addSql("
            DROP INDEX uniq_fa5ea823581122c3 ON sidpt__document_widgets
        ");
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_21883A0C581122C3 ON sidpt__document_widgets (widget_container_id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_FA5EA823C33F7837 FOREIGN KEY (document_id) 
            REFERENCES sidpt__document (id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_FA5EA823581122C3 FOREIGN KEY (widget_container_id) 
            REFERENCES claro_widget_container (id)
        ");
    }
}

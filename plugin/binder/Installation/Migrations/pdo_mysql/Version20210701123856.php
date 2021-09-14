<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/07/01 12:38:59
 */
class Version20210701123856 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            ADD is_cover TINYINT(1) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE sidpt__document
            ADD requirements_node_id INT DEFAULT NULL,
            ADD overview TINYINT(1) NOT NULL,
            ADD widgets_pagination TINYINT(1) NOT NULL
        ");
        $this->addSql("
            ALTER TABLE sidpt__document
            ADD CONSTRAINT FK_1E3DD56B3106CFFE FOREIGN KEY (requirements_node_id)
            REFERENCES claro_resource_node (id)
            ON DELETE SET NULL
        ");
        $this->addSql("
            CREATE INDEX IDX_1E3DD56B3106CFFE ON sidpt__document (requirements_node_id)
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder
            ADD display_tabs TINYINT(1) NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder
            DROP display_tabs
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            DROP is_cover
        ");
        $this->addSql("
            ALTER TABLE sidpt__document
            DROP FOREIGN KEY FK_1E3DD56B3106CFFE
        ");
        $this->addSql("
            DROP INDEX IDX_1E3DD56B3106CFFE ON sidpt__document
        ");
        $this->addSql("
            ALTER TABLE sidpt__document
            DROP requirements_node_id,
            DROP overview,
            DROP widgets_pagination
        ");
    }
}

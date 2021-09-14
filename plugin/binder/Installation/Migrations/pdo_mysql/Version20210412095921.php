<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/04/12 09:59:22
 */
class Version20210412095921 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            DROP FOREIGN KEY FK_BC9F1F3A1A30F0E0
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            DROP FOREIGN KEY FK_BC9F1F3AC33F7837
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            ADD CONSTRAINT FK_BC9F1F3A1A30F0E0 FOREIGN KEY (binder_id)
            REFERENCES sidpt__binder (id)
            ON DELETE SET NULL
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            ADD CONSTRAINT FK_BC9F1F3AC33F7837 FOREIGN KEY (document_id)
            REFERENCES sidpt__document (id)
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            DROP FOREIGN KEY FK_BC9F1F3A1A30F0E0
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            DROP FOREIGN KEY FK_BC9F1F3AC33F7837
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            ADD CONSTRAINT FK_BC9F1F3A1A30F0E0 FOREIGN KEY (binder_id)
            REFERENCES sidpt__binder (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ");
        $this->addSql("
            ALTER TABLE sidpt__binder_tab
            ADD CONSTRAINT FK_BC9F1F3AC33F7837 FOREIGN KEY (document_id)
            REFERENCES sidpt__document (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ");
    }
}

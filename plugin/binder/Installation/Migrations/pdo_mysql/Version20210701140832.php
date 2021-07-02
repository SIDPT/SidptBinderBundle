<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/07/01 02:08:35
 */
class Version20210701140832 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_21883A0C581122C3
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_FA5EA823581122C3 FOREIGN KEY (widget_container_id) 
            REFERENCES claro_widget_container (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            DROP FOREIGN KEY FK_FA5EA823581122C3
        ");
        $this->addSql("
            ALTER TABLE sidpt__document_widgets 
            ADD CONSTRAINT FK_21883A0C581122C3 FOREIGN KEY (widget_container_id) 
            REFERENCES claro_widget_container (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        ");
    }
}

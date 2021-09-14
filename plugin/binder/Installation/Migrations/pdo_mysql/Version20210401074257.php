<?php

namespace Sidpt\BinderBundle\Installation\Migrations\pdo_mysql;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2021/04/01 07:42:59
 */
class Version20210401074257 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__document
            ADD filterable TINYINT(1) NOT NULL,
            ADD sortable TINYINT(1) NOT NULL,
            ADD paginated TINYINT(1) NOT NULL,
            ADD columnsFilterable TINYINT(1) NOT NULL,
            ADD count TINYINT(1) NOT NULL,
            ADD actions TINYINT(1) NOT NULL,
            ADD sortBy VARCHAR(255) DEFAULT NULL,
            ADD availableSort LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD pageSize INT NOT NULL,
            ADD availablePageSizes LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD display VARCHAR(255) NOT NULL,
            ADD availableDisplays LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD searchMode VARCHAR(255) DEFAULT NULL,
            ADD filters LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD availableFilters LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD availableColumns LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD displayedColumns LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
            ADD card LONGTEXT NOT NULL COMMENT '(DC2Type:json)'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE sidpt__document
            DROP filterable,
            DROP sortable,
            DROP paginated,
            DROP columnsFilterable,
            DROP count,
            DROP actions,
            DROP sortBy,
            DROP availableSort,
            DROP pageSize,
            DROP availablePageSizes,
            DROP display,
            DROP availableDisplays,
            DROP searchMode,
            DROP filters,
            DROP availableFilters,
            DROP availableColumns,
            DROP displayedColumns,
            DROP card
        ");
    }
}

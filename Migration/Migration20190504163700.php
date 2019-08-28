<?php namespace Ewll\MailerBundle\Migration;

use Ewll\DBBundle\Migration\MigrationInterface;

class Migration20190504163700 implements MigrationInterface
{
    public function getDescription(): string
    {
        return 'letter';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE `letter` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `userId` INT(10) UNSIGNED NULL,
    `email` VARCHAR(64) NOT NULL,
    `subject` VARCHAR(256) NOT NULL,
    `body` TEXT NOT NULL,
    `statusId` TINYINT(3) UNSIGNED NOT NULL,
    `createdTs` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `userId` (`userId`)
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;
SQL;
    }

    public function down(): string
    {
        return <<<SQL
DROP TABLE `letter`;
SQL;
    }
}

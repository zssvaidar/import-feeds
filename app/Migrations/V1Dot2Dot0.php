<?php
/*
 * Import Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Import\Migrations;

class V1Dot2Dot0 extends V1Dot0Dot13
{
    public function up(): void
    {
        $this->execute("DELETE FROM `import_feed` WHERE 1");
        $this->execute("DELETE FROM `scheduled_job` WHERE `job`='ImportScheduledJob'");
        $this->execute("DELETE FROM `job` WHERE `name`='ImportScheduledJob'");
    }

    public function down(): void
    {
        $this->up();
    }
}

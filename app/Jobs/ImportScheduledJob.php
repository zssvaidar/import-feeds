<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Import\Jobs;

use Espo\Core\Exceptions\Error;
use Treo\Entities\Attachment;
use Import\Entities\ImportCronJob;

/**
 * Class ImportScheduledJob
 */
class ImportScheduledJob extends \Espo\Core\Jobs\Base
{
    /**
     * Run cron job
     *
     * @return bool
     */
    public function run($data = [], $targetId = null, $targetType = null): bool
    {
        if (!empty($cronJob = $this->getImportCronJob((string)$targetId))) {
            // create import job
            $this->createImport($cronJob);
        }

        return true;
    }

    /**
     * @param ImportCronJob $cronJob
     */
    protected function createImport(ImportCronJob $cronJob): void
    {
        // create log
        $log = $this->getEntityManager()->getEntity('ImportCronJobLog');
        $log->set('importCronJobId', $cronJob->get('id'));
        $log->set('executionTime', date('Y-m-d H:i:s'));
        $log->set('status', 'error');

        if (!empty($link = $cronJob->get('link'))) {
            try {
                // create import job
                $this
                    ->getServiceFactory()
                    ->create('ImportFeed')
                    ->runImport($cronJob->get('importFeedId'), $this->uploadLinkFile((string)$link)->get('id'));

                // set to log
                $log->set('status', 'success');
            } catch (\Exception | \Error $e) {
                // set to log
                $log->set('description', $e->getMessage());
            }
        } else {
            // set to log
            $log->set('description', 'Download link is empty');
        }

        // save log
        $this->getEntityManager()->saveEntity($log);
    }

    /**
     * @param string $id
     *
     * @return ImportCronJob|null
     */
    protected function getImportCronJob(string $id): ?ImportCronJob
    {
        // get scheduled job
        $scheduledJob = $this->getEntityManager()->getEntity('ScheduledJob', $id);

        return (!is_null($scheduledJob)) ? $scheduledJob->get('importCronJob') : null;
    }

    /**
     * Upload link file
     *
     * @param string $link
     *
     * @return Attachment
     * @throws Error
     */
    protected function uploadLinkFile(string $link): Attachment
    {
        // get link content
        $content = file_get_contents($link);
        if (!empty($content)) {
            // create attachment
            $attachment = $this->getEntityManager()->getEntity('Attachment');
            $attachment->set('name', 'import_cron_job_' . date('YmdHis') . '.csv');
            $attachment->set('type', 'text/csv');
            $attachment->set('relatedType', 'ImportCronJob');
            $attachment->set('role', 'Attachment');

            // store file
            $attachment->set('contents', $content);


            $this->getEntityManager()->saveEntity($attachment);

            return $attachment;
        }

        throw new Error("File content can't be empty");
    }
}

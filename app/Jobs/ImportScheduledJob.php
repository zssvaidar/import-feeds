<?php

declare(strict_types=1);

namespace Import\Jobs;

use Espo\Core\Exceptions\Error;
use Treo\Entities\Attachment;
use Import\Entities\ImportCronJob;

/**
 * Class ImportScheduledJob
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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

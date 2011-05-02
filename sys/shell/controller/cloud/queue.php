<?
uses('sys.cloud.queue.message_queue');

class CloudQueueController
{
    public function listqueues()
    {
        $q=MessageQueue::GetQueue('sqs');
        vomit($q->list_queues());
    }


    public function addqueue($queue)
    {
        $q=MessageQueue::GetQueue('sqs');
        $q->create_queue($queue);
        vomit($q->list_queues());
    }
}
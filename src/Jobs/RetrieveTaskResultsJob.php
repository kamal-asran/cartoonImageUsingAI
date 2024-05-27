<?php
namespace AcmeCorp\CartoonGenerator\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AcmeCorp\CartoonGenerator\Models\CartoonTask;

class RetrieveTaskResultsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $task_id;
    protected $user_id;
    protected $mode;
    protected $source_file;

    public function __construct($task_id, $user_id, $mode, $source_file)
    {
        $this->task_id = $task_id;
        $this->user_id = $user_id;
        $this->mode = $mode;
        $this->source_file = $source_file;
    }

    public function handle()
    {
        $client = new Client();

        try {
            $response = $client->request('GET', 'https://ai-cartoon-generator.p.rapidapi.com/api/rapidapi/query-async-task-result', [
                'query' => ['task_id' => $this->task_id],
                'headers' => [
                    'X-RapidAPI-Host' => config('cartoon_generator.rapidapi_host'),
                    'X-RapidAPI-Key' => config('cartoon_generator.rapidapi_key'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['error_code'] === 0 && $data['task_status'] === 0) {
                $result_url = $data['data']['result_url'];

                $cartoonTask = CartoonTask::where('user_id', $this->user_id)
                                          ->where('source_file', $this->source_file)
                                          ->first();
                $cartoonTask->update([
                    'output' => $result_url
                ]);
            } else {
                // Handle the error appropriately, possibly retrying the job or logging the error
            }

        } catch (RequestException $e) {
            // Handle exception
        }
    }
}

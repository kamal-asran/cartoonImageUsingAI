<?php
namespace AcmeCorp\CartoonGenerator\Http\Controllers;

use App\Http\Controllers\Controller;
use AcmeCorp\CartoonGenerator\Models\CartoonTask;
use AcmeCorp\CartoonGenerator\Jobs\RetrieveTaskResultsJob;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CartoonGeneratorController extends Controller
{
    public function generate(Request $request)
    {
        if (!$request->hasFile('image')) {
            return response()->json(['error' => 'Please upload an image'], 400);
        }
        if (!$request->has('index')) {
            return response()->json(['error' => 'Please choose style'], 400);
        }

        $client = new Client();
        try 
        {
            $image_path = $request->file('image')->store('images');
            $image_contents = file_get_contents(storage_path('app/' . $image_path));

            if ($image_contents === false) {
                return response()->json(['error' => 'Failed to read the image from the provided path.'], 500);
            }

            $multipart = [
                [
                    'name'     => 'image',
                    'filename' => $request->file('image')->getClientOriginalName(),
                    'contents' => $image_contents,
                    'headers'  => [
                        'Content-Type' => 'application/octet-stream'
                    ]
                ],
                [
                    'name'     => 'index',
                    'contents' => $request->index
                ]
            ];

            Log::info('Multipart data being sent:', $multipart);

            $response = $client->request('POST', 'https://ai-cartoon-generator.p.rapidapi.com/image/effects/generate_cartoonized_image', [
                'multipart' => $multipart,
                'headers' => [
                    'X-RapidAPI-Host' => config('cartoon_generator.rapidapi_host'),
                    'X-RapidAPI-Key' => config('cartoon_generator.rapidapi_key'),
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            if ($data['error_code'] !== 0) {
                return response()->json(['error' => 'Error in generating cartoonized image.'], 500);
            }

            $task_id = $data['task_id'];

            $cartoonTask = CartoonTask::create([
                'user_id' => $request->user()->id,
                'mode' => $request->index,
                'source_file' => $image_path,
            ]);

            $job = (new RetrieveTaskResultsJob($task_id, $request->user()->id, $request->index, $image_path))->delay(600);
            dispatch($job);

        } catch (RequestException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response ? $response->getBody()->getContents() : 'No response body';
            return response()->json(['error' => 'Failed to upload image.', 'message' => $responseBodyAsString], 500);
        }

        return response()->json(['message' => 'Image uploaded successfully. Task is processing.'], 200);
    }
}

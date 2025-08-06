<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGisWebhookJob;
use App\Models\Call;
use App\Models\Diary;
use App\Models\WebhookEntry;
use Illuminate\Http\Request;

class WebhookGisController extends Controller
{
    public bool $afterCommit = true;

    public function __invoke(Request $request)
    {
        context([
            'gis_api' => true,
        ]);

        $validate = \Validator::make($request->all(), [
            'key' => 'required|in:'.config('app.phonecall.in_api_key'),
            'action' => 'required',
            'original_call_id' => 'required',
            'is_outgoing' => 'required|in:incoming,outgoing',
            'from_phone' => 'required',
            'target_phone' => 'required',
            'extension' => 'nullable',
            'vendor' => 'in:gis',
        ]);

        $webhook = WebhookEntry::create([
            'url' => $request->path(),
            'body' => $request->all(),
            'headers' => $request->header(),
        ]);

        if ($validate->fails()) {
            $webhook->setError([
                'error' => 'Validation failed',
                'messages' => $validate->errors(),
            ]);
            return response()->json(['error' => $validate->errors()], 400);
        }

        try {
            $data = $request->all();

            //Fix after the changed action name by GIS system
            if($data['action'] === 'hangup') {
                $data['action'] = 'ended';
            }

            // Dispatch the job to process the webhook data
            ProcessGisWebhookJob::dispatch($data, $webhook->id)->afterCommit();

            return 'Webhook received and processing';
        } catch (\Exception $e) {
            $webhook->setError([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            return 'Error processing webhook';
        }
    }

    static public function updateAllDiaries(Call $call)
    {
        $diaries = Diary::where('data->call_id', $call->id)
            ->where('type', 'call')
            ->get();

        $duration = 0;

        if($call->duration > 0 && $diaries->isNotEmpty()){
            $duration = (int) ($call->duration / $diaries->count());
        }

        foreach ($diaries as $diary) {
            $diary->update([
                'data' => array_merge($diary->data, [
                    'file' => $call->audio_url,
                    'duration' => $duration,
                ]),
            ]);
        }
    }
}


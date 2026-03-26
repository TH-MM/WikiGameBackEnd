<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Round;
use App\Models\Score;
use App\Services\AiRoundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GameController extends Controller
{

    private function fetchRandomWikiPage()
    {
        $lang = 'en';
        $response = Http::withoutVerifying()
            ->withHeaders(['User-Agent' => 'ArabicWikiGame/1.0'])
            ->get("https://{$lang}.wikipedia.org/w/api.php", [
            'action' => 'query',
            'list' => 'random',
            'rnnamespace' => 0,
            'rnlimit' => 1,
            'format' => 'json'
        ]);
        return $response->json()['query']['random'][0]['title'];
    }



    public function currentRound(Request $request, AiRoundService $aiRoundService)
    {
        $lang = 'en';
        $duration = 165; // 15s prep + 150s play
        $baseTimeUnix = 1704067200; // 2024-01-01 00:00:00 UTC
        $nowUnix = now()->timestamp;
        
        $elapsed = $nowUnix - $baseTimeUnix;
        $slotStartUnix = (int) (floor($elapsed / $duration) * $duration) + $baseTimeUnix;
        $slotStart = \Carbon\Carbon::createFromTimestamp($slotStartUnix);
        $slotEnd = $slotStart->copy()->addSeconds($duration);

        // Find existing round for this slot
        $round = Round::where('language', $lang)
            ->where('start_time', $slotStart)
            ->first();

        // If no round exists for this global clock slot, create it
        if (!$round) {
            // Try AI selection first
            $aiResult = $aiRoundService->generateRound();

            if (!empty($aiResult)) {
                $round = Round::create([
                    'language' => $lang,
                    'start_genre' => null,
                    'target_genre' => null,
                    'difficulty' => $aiResult['difficulty'],
                    'start_page' => $aiResult['start_page'],
                    'target_page' => $aiResult['target_page'],
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ]);
            } else {
                // Fallback to random if AI fails
                \Illuminate\Support\Facades\Log::info("AI Round generation failed, falling back to random Wikipedia pages.");
                $startPage = $this->fetchRandomWikiPage($lang);
                $targetPage = $this->fetchRandomWikiPage($lang);

                while ($startPage === $targetPage) {
                    $targetPage = $this->fetchRandomWikiPage($lang);
                }

                $round = Round::create([
                    'language' => $lang,
                    'start_genre' => null,
                    'target_genre' => null,
                    'difficulty' => 'standard',
                    'start_page' => $startPage,
                    'target_page' => $targetPage,
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ]);
            }
        }

        return response()->json([
            'round' => $round,
            'time_remaining' => max(0, (int) now()->diffInSeconds($round->end_time, false)),
            'is_active' => now()->lessThanOrEqualTo($round->end_time)
        ]);
    }

    public function join(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $player = Player::firstOrCreate(['name' => $request->name]);

        return response()->json(['player' => $player]);
    }

    public function updateProgress(Request $request)
    {
        $request->validate([
            'player_id' => 'required|exists:players,id',
            'round_id' => 'required|exists:rounds,id',
            'clicks' => 'required|integer',
            'finished' => 'required|boolean'
        ]);

        $round = Round::findOrFail($request->round_id);

        if (now()->greaterThan($round->end_time)) {
            return response()->json(['error' => 'Round has ended'], 400);
        }

        $scoreRecord = Score::firstOrCreate([
            'player_id' => $request->player_id,
            'round_id' => $round->id,
        ]);

        // Don't update if already finished
        if ($scoreRecord->finished) {
            return response()->json(['message' => 'Already finished', 'score' => $scoreRecord]);
        }

        $actualStartTime = $round->start_time->copy()->addSeconds(15);
        $timeTaken = $actualStartTime->diffInSeconds(now(), false);
        $timeTaken = max(0, (int) $timeTaken);
        $clicks = $request->clicks;
        
        $score = 0;
        if ($request->finished) {
            // Calculate base score
            $score = max(0, 1000 - ($timeTaken * 2) - ($clicks * 10));
            
            // Check if first
            $finishedCount = Score::where('round_id', $round->id)->where('finished', true)->count();
            if ($finishedCount === 0) {
                $score += 200; // First place bonus!
            }
        }

        $scoreRecord->update([
            'clicks' => $clicks,
            'time_taken' => $timeTaken,
            'score' => $score,
            'finished' => $request->finished,
        ]);

        return response()->json(['score' => $scoreRecord]);
    }

    public function leaderboard(Request $request)
    {
        $lang = 'en';
        $duration = 165;
        $baseTimeUnix = 1704067200;
        $nowUnix = now()->timestamp;
        
        $elapsed = $nowUnix - $baseTimeUnix;
        $slotStartUnix = (int) (floor($elapsed / $duration) * $duration) + $baseTimeUnix;
        $slotStart = \Carbon\Carbon::createFromTimestamp($slotStartUnix);

        $round = Round::where('language', $lang)
            ->where('start_time', $slotStart)
            ->first();

        if (!$round) {
            return response()->json(['leaderboard' => []]);
        }

        $scores = Score::with('player')
            ->where('round_id', $round->id)
            ->get()
            ->sortBy([
                fn ($a, $b) => $b->finished <=> $a->finished, // Finished first
                fn ($a, $b) => $a->time_taken <=> $b->time_taken, // Faster time
                fn ($a, $b) => $a->clicks <=> $b->clicks, // Fewer clicks
            ])->values();

        return response()->json([
            'round_id' => $round->id,
            'leaderboard' => $scores,
            'is_active' => now()->lessThanOrEqualTo($round->end_time),
            'time_remaining' => max(0, (int) now()->diffInSeconds($round->end_time, false))
        ]);
    }
}

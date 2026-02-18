<?php

namespace App\Services;

use App\Models\ClipCategory;
use App\Models\Video;
use App\Models\VideoClip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LongoMatchXmlParser
{
    /**
     * Parse LongoMatch XML content
     *
     * @return array ['categories' => [...], 'clips' => [...], 'session_info' => [...]]
     *
     * @throws \Exception
     */
    public function parse(string $xmlContent): array
    {
        // Remove BOM if present
        $xmlContent = preg_replace('/^\xEF\xBB\xBF/', '', $xmlContent);

        // Suppress XML errors and handle them manually
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMsg = ! empty($errors) ? $errors[0]->message : 'Unknown XML error';
            throw new \Exception('Error parsing XML: '.trim($errorMsg));
        }

        $result = [
            'session_info' => $this->parseSessionInfo($xml),
            'categories' => $this->parseCategories($xml),
            'clips' => $this->parseInstances($xml),
        ];

        // Map category colors to clips
        $categoryColors = [];
        foreach ($result['categories'] as $cat) {
            $categoryColors[$cat['code']] = $cat['color'];
        }

        foreach ($result['clips'] as &$clip) {
            $clip['color'] = $categoryColors[$clip['code']] ?? '#666666';
        }

        return $result;
    }

    /**
     * Parse session info from XML
     */
    private function parseSessionInfo(\SimpleXMLElement $xml): array
    {
        $sessionInfo = [];

        if (isset($xml->SESSION_INFO)) {
            if (isset($xml->SESSION_INFO->start_time)) {
                $sessionInfo['start_time'] = (string) $xml->SESSION_INFO->start_time;
            }
        }

        return $sessionInfo;
    }

    /**
     * Parse categories (ROWS) from XML
     */
    private function parseCategories(\SimpleXMLElement $xml): array
    {
        $categories = [];

        if (! isset($xml->ROWS->row)) {
            return $categories;
        }

        foreach ($xml->ROWS->row as $row) {
            $code = (string) $row->code;

            if (empty($code)) {
                continue;
            }

            $r = isset($row->R) ? (int) $row->R : 0;
            $g = isset($row->G) ? (int) $row->G : 0;
            $b = isset($row->B) ? (int) $row->B : 0;

            $categories[] = [
                'code' => $code,
                'color' => $this->convertColor($r, $g, $b),
            ];
        }

        return $categories;
    }

    /**
     * Parse instances (clips) from XML
     */
    private function parseInstances(\SimpleXMLElement $xml): array
    {
        $clips = [];

        if (! isset($xml->ALL_INSTANCES->instance)) {
            return $clips;
        }

        foreach ($xml->ALL_INSTANCES->instance as $instance) {
            // Skip empty instances
            if (! isset($instance->ID) || ! isset($instance->code)) {
                continue;
            }

            $start = isset($instance->start) ? (float) $instance->start : 0;
            $end = isset($instance->end) ? (float) $instance->end : 0;

            // Skip invalid clips (start >= end or both 0)
            if ($start >= $end && ! ($start == 0 && $end == 0)) {
                continue;
            }

            // Skip clips with 0 duration (like "Sustituciones" placeholder)
            if ($start == 0 && $end == 0) {
                continue;
            }

            $clip = [
                'id' => (int) $instance->ID,
                'start' => $start,
                'end' => $end,
                'code' => (string) $instance->code,
                'labels' => [],
            ];

            // Parse labels if present
            if (isset($instance->label)) {
                foreach ($instance->label as $label) {
                    $clip['labels'][] = [
                        'text' => isset($label->text) ? (string) $label->text : '',
                        'group' => isset($label->group) ? (string) $label->group : '',
                    ];
                }
            }

            $clips[] = $clip;
        }

        // Sort by start time
        usort($clips, fn ($a, $b) => $a['start'] <=> $b['start']);

        return $clips;
    }

    /**
     * Convert 16-bit RGB to HEX color
     *
     * @param  int  $r  Red (0-65535)
     * @param  int  $g  Green (0-65535)
     * @param  int  $b  Blue (0-65535)
     * @return string HEX color (#RRGGBB)
     */
    private function convertColor(int $r, int $g, int $b): string
    {
        // Convert from 16-bit (0-65535) to 8-bit (0-255)
        $r8 = (int) floor($r / 257);
        $g8 = (int) floor($g / 257);
        $b8 = (int) floor($b / 257);

        // Clamp values to 0-255
        $r8 = max(0, min(255, $r8));
        $g8 = max(0, min(255, $g8));
        $b8 = max(0, min(255, $b8));

        return sprintf('#%02X%02X%02X', $r8, $g8, $b8);
    }

    /**
     * Import parsed data to a video
     *
     * Clips are created and assigned to existing organization-scope categories
     * (case-insensitive match by name). No new categories/buttons are created.
     *
     * @param  bool  $replaceExisting  Whether to replace existing clips
     * @return array ['clips_created' => int, 'clips_replaced' => int]
     */
    public function importToVideo(Video $video, array $parsedData, bool $replaceExisting = true): array
    {
        $organizationId = $video->organization_id;
        $userId = Auth::id();

        $stats = [
            'clips_created' => 0,
            'clips_replaced' => 0,
        ];

        DB::beginTransaction();

        try {
            // If replacing, delete existing clips for this video
            if ($replaceExisting) {
                $stats['clips_replaced'] = VideoClip::where('video_id', $video->id)->count();
                VideoClip::where('video_id', $video->id)->delete();
            }

            // Get unique categories used in clips
            $usedCategoryCodes = array_unique(array_column($parsedData['clips'], 'code'));

            // Load all org-scope categories for this organization (case-insensitive match)
            $orgCategories = ClipCategory::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('scope', ClipCategory::SCOPE_ORGANIZATION)
                ->get()
                ->keyBy(fn ($cat) => strtolower($cat->name));

            // Build category map (code => category_id or null)
            $categoryMap = [];

            foreach ($usedCategoryCodes as $code) {
                $match = $orgCategories->get(strtolower($code));
                $categoryMap[$code] = $match ? $match->id : null;
            }

            // Create clips
            foreach ($parsedData['clips'] as $clipData) {
                $categoryId = $categoryMap[$clipData['code']] ?? null;

                // Build tags from labels
                $tags = [];
                $notes = '';

                if (! empty($clipData['labels'])) {
                    foreach ($clipData['labels'] as $label) {
                        if (! empty($label['text'])) {
                            $tags[] = $label['group'].':'.$label['text'];
                            $notes .= ($notes ? ', ' : '').$label['text'];
                        }
                    }
                }

                VideoClip::create([
                    'video_id' => $video->id,
                    'clip_category_id' => $categoryId,
                    'organization_id' => $organizationId,
                    'created_by' => $userId,
                    'start_time' => $clipData['start'],
                    'end_time' => $clipData['end'],
                    'title' => $clipData['code'].($notes ? " - $notes" : ''),
                    'notes' => $notes ?: null,
                    'players' => [],
                    'tags' => $tags,
                    'rating' => null,
                    'is_highlight' => false,
                ]);

                $stats['clips_created']++;
            }

            DB::commit();

            Log::info('LongoMatch XML imported', [
                'video_id' => $video->id,
                'stats' => $stats,
            ]);

            return $stats;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LongoMatch XML import failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate XML content before parsing
     *
     * @return array ['valid' => bool, 'error' => string|null, 'preview' => array|null]
     */
    public function validate(string $xmlContent): array
    {
        try {
            $parsed = $this->parse($xmlContent);

            return [
                'valid' => true,
                'error' => null,
                'preview' => [
                    'categories_count' => count($parsed['categories']),
                    'clips_count' => count($parsed['clips']),
                    'categories_used' => array_values(array_unique(array_column($parsed['clips'], 'code'))),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'preview' => null,
            ];
        }
    }
}

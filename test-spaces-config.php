<?php
/**
 * Test script to verify DigitalOcean Spaces configuration for public CDN access
 * Run with: php test-spaces-config.php
 */

// Load Laravel configuration
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration from .env
$cdnUrl = $_ENV['DO_SPACES_CDN_URL'] ?? null;
$bucket = $_ENV['DO_SPACES_BUCKET'] ?? null;
$endpoint = $_ENV['DO_SPACES_ENDPOINT'] ?? null;

echo "=== DIGITALOCEAN SPACES CDN TEST ===\n\n";

echo "CDN URL: $cdnUrl\n";
echo "Bucket: $bucket\n";
echo "Endpoint: $endpoint\n\n";

// Test 1: CDN Root Access
echo "Test 1: CDN Root Access\n";
$ch = curl_init($cdnUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: " . ($httpCode == 200 ? "✅ PASS" : "❌ FAIL") . " (HTTP $httpCode)\n\n";

// Test 2: Sample video path (this will fail if bucket is not public)
echo "Test 2: Sample Video Path Test\n";
$sampleVideoUrl = rtrim($cdnUrl, '/') . '/videos/sample.mp4';
echo "Testing: $sampleVideoUrl\n";

$ch = curl_init($sampleVideoUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: " . ($httpCode == 200 ? "✅ PASS" : "❌ FAIL") . " (HTTP $httpCode)\n";

if ($httpCode != 200) {
    echo "\n=== TROUBLESHOOTING ===\n";
    echo "If you get HTTP 403 or 404, your bucket is likely not configured for public access.\n";
    echo "\nTo fix this, follow these steps:\n\n";

    echo "1. Log into DigitalOcean Control Panel\n";
    echo "2. Go to Spaces → $bucket\n";
    echo "3. Click 'Settings' tab\n";
    echo "4. Under 'File Listing', enable 'Enable CDN' if not already enabled\n";
    echo "5. Under 'CORS Policy', add this configuration:\n\n";

    echo "CORS Configuration (JSON):\n";
    echo "[\n";
    echo "  {\n";
    echo "    \"AllowedHeaders\": [\"*\"],\n";
    echo "    \"AllowedMethods\": [\"GET\", \"HEAD\"],\n";
    echo "    \"AllowedOrigins\": [\"*\"],\n";
    echo "    \"MaxAgeSeconds\": 3000\n";
    echo "  }\n";
    echo "]\n\n";

    echo "6. Under 'Bucket Policy', add this policy to allow public read:\n\n";
    echo "Bucket Policy (JSON):\n";
    echo "{\n";
    echo "  \"Version\": \"2012-10-17\",\n";
    echo "  \"Statement\": [\n";
    echo "    {\n";
    echo "      \"Sid\": \"PublicReadGetObject\",\n";
    echo "      \"Effect\": \"Allow\",\n";
    echo "      \"Principal\": \"*\",\n";
    echo "      \"Action\": \"s3:GetObject\",\n";
    echo "      \"Resource\": \"arn:aws:s3:::$bucket/*\"\n";
    echo "    }\n";
    echo "  ]\n";
    echo "}\n\n";

    echo "7. Save the settings and wait 5-10 minutes for changes to propagate\n";
    echo "8. Re-run this test script to verify the fix\n";
}

echo "\n=== END TEST ===\n";
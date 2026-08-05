<?php

function check_domain(string $domain): ?string
{
    $ch = curl_init("https://{$domain}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    curl_setopt($ch, CURLOPT_NOBODY, true); // Head request is enough

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode > 0 && $httpCode < 400) {
        return "https://{$domain}";
    }

    return null;
}

function generate_variants(string $name): array
{
    $clean = strtolower(trim($name));
    $clean = str_replace("'", "", $clean);

    $words = preg_split('/\s+/', $clean);

    $variants = [];

    // Nom complet sans espaces
    $variants[] = str_replace(" ", "", $clean);

    // Nom complet avec tirets
    $variants[] = str_replace(" ", "-", $clean);

    // Premier mot seulement si suffisamment long
    if (!empty($words) && mb_strlen($words[0]) >= 4) {
        $variants[] = $words[0];
    }

    // Supprimer les doublons tout en préservant l'ordre
    return array_values(array_unique($variants));
}

function find_website(string $name): ?string
{
    $variants = generate_variants($name);

    $extensions = [
        ".com",
        ".fr",
        ".eu",
        ".bzh"
    ];

    foreach ($variants as $variant) {
        foreach ($extensions as $extension) {
            $domain = $variant . $extension;

            file_put_contents('php://stderr', "Test : {$domain}\n");

            $website = check_domain($domain);

            if ($website) {
                return $website;
            }
        }
    }

    return null;
}

// Execution CLI
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Aucun nom fourni\n";
        exit(1);
    }

    $company = $argv[1];
    $result = find_website($company);

    echo ($result ?: "Aucun site trouvé") . "\n";
}
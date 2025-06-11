<?php
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

echo "<h1>Test Riassunto AI - EduNote</h1>";

// Testo di esempio
$testText = "<p>L'intelligenza artificiale (IA) è un ramo dell'informatica che si occupa della creazione di sistemi in grado di eseguire compiti che normalmente richiederebbero l'intelligenza umana. Questi compiti includono il riconoscimento vocale, la traduzione linguistica, il processo decisionale e il riconoscimento visivo. L'IA può essere classificata in due categorie principali: IA debole, progettata per eseguire un compito specifico, e IA forte, che possiede capacità cognitive generali simili a quelle umane.</p>

<p>Gli algoritmi di machine learning sono una sottocategoria dell'IA che consentono ai computer di apprendere e migliorare automaticamente attraverso l'esperienza senza essere esplicitamente programmati. Il deep learning, una sottocategoria del machine learning, utilizza reti neurali artificiali con multiple layers per modellare e comprendere dati complessi. Queste tecnologie hanno rivoluzionato settori come la medicina, l'automotive, la finanza e l'intrattenimento.</p>

<p>Le applicazioni pratiche dell'IA sono in costante espansione. Nei veicoli autonomi, l'IA elabora dati da sensori e telecamere per navigare in sicurezza. Nella medicina, aiuta nella diagnosi precoce di malattie attraverso l'analisi di immagini mediche. Nel settore finanziario, rileva frodi e automatizza il trading. Tuttavia, l'implementazione dell'IA solleva anche questioni etiche relative alla privacy, al bias algoritmico e all'impatto sull'occupazione che devono essere attentamente considerate.</p>";

echo "<h3>Testo originale:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
echo $testText;
echo "</div>";

echo "<h3>Riassunto generato:</h3>";

try {
    $summary = generateSummary($testText);
    echo "<div style='border: 2px solid #28a745; padding: 10px; margin: 10px 0; background-color: #f8fff9;'>";
    echo "<strong>Riassunto:</strong><br>";
    echo $summary;
    echo "</div>";
    
    // Verifica se è un riassunto AI o semplice
    $simpleSummary = substr(strip_tags($testText), 0, 150) . '...';
    
    echo "<h3>Analisi:</h3>";
    if ($summary === $simpleSummary) {
        echo "<div style='border: 2px solid #dc3545; padding: 10px; margin: 10px 0; background-color: #fff5f5;'>";
        echo "<strong>⚠️ PROBLEMA:</strong> Il riassunto è identico al metodo semplice. L'AI non è stata utilizzata.";
        echo "</div>";
    } else {
        echo "<div style='border: 2px solid #28a745; padding: 10px; margin: 10px 0; background-color: #f8fff9;'>";
        echo "<strong>✅ SUCCESSO:</strong> Il riassunto è diverso dal metodo semplice. L'AI sta funzionando!";
        echo "</div>";
    }
    
    echo "<h3>Dettagli tecnici:</h3>";
    echo "<ul>";
    echo "<li>Lunghezza testo originale: " . strlen(strip_tags($testText)) . " caratteri</li>";
    echo "<li>Lunghezza riassunto: " . strlen($summary) . " caratteri</li>";
    echo "<li>API Key configurata: " . (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY) ? '✅ Sì' : '❌ No') . "</li>";
    echo "<li>AI abilitata: " . (defined('USE_AI_SUMMARY') && USE_AI_SUMMARY ? '✅ Sì' : '❌ No') . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='border: 2px solid #dc3545; padding: 10px; margin: 10px 0; background-color: #fff5f5;'>";
    echo "<strong>ERRORE:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>Log degli errori:</h3>";
echo "<div style='background-color: #f8f9fa; padding: 10px; font-family: monospace; white-space: pre-wrap; max-height: 300px; overflow-y: auto;'>";

// Mostra gli ultimi log
$logFile = __DIR__ . '/logs/error.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -20); // Ultimi 20 righe
    echo htmlspecialchars(implode("\n", $recentLines));
} else {
    echo "File di log non trovato. Creando directory logs...";
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
        echo "\nDirectory logs creata.";
    }
}
echo "</div>";

echo "<hr>";
echo "<p><strong>Per testare:</strong> Visita questa pagina, poi crea o modifica un appunto per vedere se il riassunto AI funziona.</p>";
?>
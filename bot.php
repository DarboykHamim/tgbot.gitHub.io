<?php
// Set up your bot token
$botToken = "7976549418:AAGRIswOhNDN9xfaROvIr9dqD99BN4nhdOI";
$apiUrl = "https://api.telegram.org/bot$botToken/";

// This is the API endpoint to get trading signals
$signalApiUrl = "https://pro-trader.vercel.app/v5";

// Get the updates from Telegram
$update = file_get_contents("php://input");
$updateArray = json_decode($update, true);

// Get the chat ID and message text
$chatId = $updateArray["message"]["chat"]["id"] ?? null;
$messageText = $updateArray["message"]["text"] ?? null;

// Function to send a message to a Telegram chat
function sendMessage($chatId, $text) {
    global $apiUrl;
    file_get_contents($apiUrl . "sendMessage?chat_id=" . $chatId . "&text=" . urlencode($text));
}

// Function to handle the signal API request and format response
function getTradingSignals($broker, $martingale, $accuracy, $timezone) {
    global $signalApiUrl;
    $params = [
        "par" => "EURUSD", // Example pair
        "timeframe" => 1,  // M1 timeframe
        "dias" => 3,       // 3 days
        "timezone" => $timezone,
        "martingale" => $martingale
    ];

    // Generate the full API URL with query parameters
    $urlWithParams = $signalApiUrl . "?" . http_build_query($params);

    // Get the response from the trading signals API
    $response = file_get_contents($urlWithParams);
    $decodedResponse = json_decode($response, true);

    // Check if the response is valid
    if (isset($decodedResponse['signals'])) {
        return $decodedResponse['signals'];
    } else {
        return []; // Return an empty array if no signals found or error in response
    }
}

// Start handling user input and responses
if ($messageText == "/start") {
    $welcomeMessage = "Welcome! Type /signal to get the latest trading signals.";
    sendMessage($chatId, $welcomeMessage);
} elseif ($messageText == "/signal") {
    // Prompt user to choose a broker
    $brokerMessage = "Choose a broker:\n1. Quotex";
    sendMessage($chatId, $brokerMessage);
} elseif (strpos($messageText, "Quotex") !== false) {
    // Prompt user to select Martingale steps
    sendMessage($chatId, "You selected Quotex. Choose Martingale Steps:\n1. Step 1\n2. Step 2");
} elseif (strpos($messageText, "Step 1") !== false) {
    // Prompt user to select accuracy level
    sendMessage($chatId, "You selected Martingale Step 1. Choose accuracy level:\n1. 80-90%\n2. 90-100%");
} elseif (strpos($messageText, "90-100%") !== false) {
    // Fetch signals using the API and parameters
    $signals = getTradingSignals("Quotex", 1, "90-100%", "Asia/Dhaka");

    // Format the signals for display
    if (!empty($signals)) {
        $formattedSignals = "Here are your signals:\n";
        foreach ($signals as $signal) {
            $formattedSignals .= $signal["time"] . " - " . $signal["pair"] . ": " . $signal["action"] . "\n";
        }
    } else {
        $formattedSignals = "No signals found or an error occurred while fetching signals.";
    }

    // Send the signals to the user
    sendMessage($chatId, $formattedSignals);
}

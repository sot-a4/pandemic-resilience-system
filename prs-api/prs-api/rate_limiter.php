
<?php
// rate_limiter.php - Rate limiting middleware


function checkRateLimit($conn, $endpoint, $limit = 10, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];

    // Delete old entries
    $stmt = $conn->prepare("DELETE FROM rate_limits WHERE ip = ? AND endpoint = ? AND timestamp < NOW() - INTERVAL ? SECOND");
    $stmt->bind_param("ssi", $ip, $endpoint, $timeWindow);
    $stmt->execute();

    // Count entries in timeframe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND endpoint = ?");
    $stmt->bind_param("ss", $ip, $endpoint);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // Check if rate limit is exceeded
    if ($count >= $limit) {
        header('HTTP/1.1 429 Too Many Requests');
        echo json_encode(["error" => "Rate limit exceeded. Try again later."]);
        exit;
    }

    // Log the current request
    $stmt = $conn->prepare("INSERT INTO rate_limits (ip, endpoint, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $ip, $endpoint);
    $stmt->execute();

    return true;
}
?>

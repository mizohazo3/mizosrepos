<?php
require_once 'includes/db.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if Ratchet is installed - otherwise provide installation instructions
if (!class_exists('\Ratchet\Server\IoServer')) {
    echo "Ratchet WebSocket library is required.\n";
    echo "Please install it using Composer:\n";
    echo "1. Install Composer (https://getcomposer.org/)\n";
    echo "2. Run: composer require cboden/ratchet\n";
    exit(1);
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * TimerWebSocketServer
 * Handles WebSocket connections and real-time timer updates
 */
class TimerWebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $conn;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->conn = getDbConnection();
        echo "WebSocket server started\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send initial timer data
        $this->sendTimerData($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!isset($data['action'])) {
            return;
        }
        
        $response = ['success' => false, 'message' => 'Unknown action'];
        
        switch ($data['action']) {
            case 'get_timers':
                $this->sendTimerData($from);
                break;
                
            case 'add_timer':
                if (isset($data['name']) && isset($data['category_id'])) {
                    $this->addTimer($data['name'], $data['category_id']);
                }
                break;
                
            case 'start_timer':
                if (isset($data['timer_id'])) {
                    $this->startTimer($data['timer_id']);
                }
                break;
                
            case 'stop_timer':
                if (isset($data['timer_id'])) {
                    $this->stopTimer($data['timer_id']);
                }
                break;
                
            case 'delete_timer':
                if (isset($data['timer_id'])) {
                    $this->deleteTimer($data['timer_id']);
                }
                break;
                
            case 'get_categories':
                $this->sendCategoryData($from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
    
    // Helper methods to interact with the database and broadcast updates
    
    protected function sendTimerData(ConnectionInterface $conn)
    {
        $stmt = $this->conn->prepare("SELECT t.*, c.name as category_name FROM timers t 
                                     JOIN categories c ON t.category_id = c.id 
                                     ORDER BY t.id DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $timers = [];
        while ($row = $result->fetch_assoc()) {
            $timers[] = $row;
        }
        
        $conn->send(json_encode([
            'type' => 'timers',
            'data' => [
                'success' => true,
                'timers' => $timers
            ]
        ]));
    }
    
    protected function sendCategoryData(ConnectionInterface $conn)
    {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $conn->send(json_encode([
            'type' => 'categories',
            'data' => [
                'success' => true,
                'categories' => $categories
            ]
        ]));
    }
    
    protected function addTimer($name, $category_id)
    {
        $stmt = $this->conn->prepare("INSERT INTO timers (name, category_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $category_id);
        
        if ($stmt->execute()) {
            $this->broadcastTimerUpdate();
        }
    }
    
    protected function startTimer($timer_id)
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->conn->prepare("UPDATE timers SET status = 'running', start_time = ?, last_paused_time = NULL WHERE id = ?");
        $stmt->bind_param("si", $now, $timer_id);
        
        if ($stmt->execute()) {
            $this->broadcastTimerUpdate();
        }
    }
    
    protected function stopTimer($timer_id)
    {
        $now = date('Y-m-d H:i:s');
        
        // Get current timer data
        $stmt = $this->conn->prepare("SELECT * FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timer = $result->fetch_assoc();
        
        if (!$timer) {
            return;
        }
        
        // Calculate elapsed time
        $elapsed = 0;
        if ($timer['status'] === 'running' && $timer['start_time']) {
            $start_time = new \DateTime($timer['start_time']);
            $current_time = new \DateTime($now);
            $interval = $start_time->diff($current_time);
            $elapsed = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
        }
        
        // Update total elapsed time
        $total_elapsed = $timer['total_elapsed_time'] + $elapsed;
        
        // Update timer status
        $stmt = $this->conn->prepare("UPDATE timers SET status = 'stopped', current_elapsed = 0, total_elapsed_time = ? WHERE id = ?");
        $stmt->bind_param("ii", $total_elapsed, $timer_id);
        
        if ($stmt->execute()) {
            $this->broadcastTimerUpdate();
        }
    }
    
    protected function deleteTimer($timer_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        
        if ($stmt->execute()) {
            $this->broadcastTimerUpdate();
        }
    }
    
    protected function broadcastTimerUpdate()
    {
        $stmt = $this->conn->prepare("SELECT t.*, c.name as category_name FROM timers t 
                                     JOIN categories c ON t.category_id = c.id 
                                     ORDER BY t.id DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $timers = [];
        while ($row = $result->fetch_assoc()) {
            $timers[] = $row;
        }
        
        // Send to all connected clients
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => 'timers',
                'data' => [
                    'success' => true,
                    'timers' => $timers
                ]
            ]));
        }
    }
}

// Run the server application
require __DIR__ . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new TimerWebSocketServer()
        )
    ),
    8080 // WebSocket port
);

echo "WebSocket server running on port 8080\n";
$server->run(); 
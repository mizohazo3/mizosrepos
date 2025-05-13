<?php
require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

// Firebase configuration
class FirebaseConfig {
    private static $firebase = null;
    private static $database = null;
    
    public static function initialize() {
        try {
            // Use Firebase credentials
            $serviceAccount = ServiceAccount::fromJsonFile(__DIR__ . '/secret/firebase-service-account.json');
            
            self::$firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->withDatabaseUri('https://reacttimer-7ed91-default-rtdb.europe-west1.firebasedatabase.app/')
                ->create();
            
            self::$database = self::$firebase->getDatabase();
            
            return true;
        } catch (Exception $e) {
            error_log('Firebase initialization error: ' . $e->getMessage());
            return false;
        }
    }
    
    public static function getDatabase() {
        if (self::$database === null) {
            self::initialize();
        }
        return self::$database;
    }

    // Update user balance when a medication is taken
    public static function updateBalance($userId, $medCost) {
        try {
            if (self::$database === null) {
                self::initialize();
            }
            
            // Reference to the user's balance
            $balanceRef = self::$database->getReference("users/{$userId}/balance");
            
            // Get current balance
            $currentBalance = $balanceRef->getValue();
            if ($currentBalance === null) {
                // If balance doesn't exist, initialize it
                $currentBalance = 0;
            }
            
            // Calculate new balance
            $newBalance = $currentBalance - floatval($medCost);
            
            // Update the balance
            $balanceRef->set($newBalance);
            
            // Log the transaction
            $transactionsRef = self::$database->getReference("users/{$userId}/transactions");
            $transactionsRef->push([
                'type' => 'medication_taken',
                'amount' => -floatval($medCost),
                'timestamp' => time(),
                'description' => 'Medication cost deduction'
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Firebase balance update error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Get user's current balance
    public static function getBalance($userId) {
        try {
            if (self::$database === null) {
                self::initialize();
            }
            
            $balanceRef = self::$database->getReference("users/{$userId}/balance");
            $balance = $balanceRef->getValue();
            
            return $balance !== null ? $balance : 0;
        } catch (Exception $e) {
            error_log('Firebase get balance error: ' . $e->getMessage());
            return 0;
        }
    }
} 
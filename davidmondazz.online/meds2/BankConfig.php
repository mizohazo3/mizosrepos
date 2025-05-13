<?php
/**
 * BankConfig Class
 * Handles all bank balance operations, exchange rate conversions, and purchase logs
 * Used by both meds2 and timer8 applications
 */
class BankConfig {
    private static $initialized = false;
    private static $timerDb = null;
    private static $exchangeRate = 50.59; // Default exchange rate

    /**
     * Initialize the BankConfig system
     * Establishes database connection and loads current exchange rate
     */
    public static function initialize() {
        if (self::$initialized) {
            return true;
        }

        try {
            // Connect to mcgkxyz_timer_app database
            self::$timerDb = new PDO(
                "mysql:host=localhost;dbname=mcgkxyz_timer_app",
                "mcgkxyz_masterpop",
                "aA0109587045",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Load current exchange rate
            self::loadExchangeRate();
            
            self::$initialized = true;
            return true;
        } catch (PDOException $e) {
            error_log("BankConfig initialization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load the current USD to EGP exchange rate
     */
    private static function loadExchangeRate() {
        try {
            $query = self::$timerDb->prepare("SELECT USDEGP FROM user_progress WHERE id = 1");
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['USDEGP'])) {
                self::$exchangeRate = (float)$result['USDEGP'];
            }
        } catch (PDOException $e) {
            error_log("Error loading exchange rate: " . $e->getMessage());
            // Keep using default rate
        }
    }

    /**
     * Get the current exchange rate
     */
    public static function getExchangeRate() {
        if (!self::$initialized) {
            self::initialize();
        }
        return self::$exchangeRate;
    }

    /**
     * Convert EGP amount to USD
     */
    public static function convertToUSD($egpAmount) {
        if (!self::$initialized) {
            self::initialize();
        }
        return round($egpAmount / self::$exchangeRate, 2);
    }

    /**
     * Convert USD amount to EGP
     */
    public static function convertToEGP($usdAmount) {
        if (!self::$initialized) {
            self::initialize();
        }
        return round($usdAmount * self::$exchangeRate, 2);
    }

    /**
     * Get current bank balance for a user
     */
    public static function getBalance($userId = 1) {
        if (!self::$initialized) {
            self::initialize();
        }

        try {
            $query = self::$timerDb->prepare("SELECT bank_balance FROM user_progress WHERE id = ?");
            $query->execute([$userId]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['bank_balance'])) {
                return $result['bank_balance'];
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Error getting balance: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update bank balance and add purchase log entry
     * @param int $userId - User ID
     * @param float $amount - Amount to deduct (in EGP)
     * @param string $itemName - Name of the item being purchased
     * @param float|null $amountUSD - Optional USD amount to use directly (if known)
     * @return bool - Success or failure
     */
    public static function updateBalance($userId = 1, $amount = 0, $itemName = "Unknown Item", $amountUSD = null) {
        if (!self::$initialized) {
            self::initialize();
        }

        if ($amount <= 0 && $amountUSD <= 0) {
            error_log("Invalid amount for updateBalance: EGP " . $amount . ", USD " . $amountUSD);
            return false;
        }

        try {
            // Start transaction
            self::$timerDb->beginTransaction();

            // Get current balance
            $query = self::$timerDb->prepare("SELECT bank_balance FROM user_progress WHERE id = ?");
            $query->execute([$userId]);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("User not found");
            }

            $currentBalance = $result['bank_balance'];
            
            // If we have a direct USD amount, use it, otherwise convert from EGP
            $usdAmount = $amountUSD !== null ? $amountUSD : self::convertToUSD($amount);
            $newBalance = $currentBalance - $usdAmount;

            // Update the balance
            $updateQuery = self::$timerDb->prepare("UPDATE user_progress SET bank_balance = ? WHERE id = ?");
            $updateQuery->execute([$newBalance, $userId]);

            // Add to purchase_logs
            self::addPurchaseLog($userId, $amount, "FastMeds: " . $itemName, $usdAmount); // Add FastMeds prefix

            // Commit transaction
            self::$timerDb->commit();
            return true;
        } catch (Exception $e) {
            // Roll back transaction on error
            if (self::$timerDb->inTransaction()) {
                self::$timerDb->rollBack();
            }
            error_log("Error updating balance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add entry to purchase_logs table
     * @param int $userId - User ID
     * @param float $amountEGP - Amount in EGP
     * @param string $itemName - Name of the item
     * @param float|null $amountUSD - Optional USD amount to use directly
     */
    private static function addPurchaseLog($userId, $amountEGP, $itemName, $amountUSD = null) {
        if (!self::$initialized) {
            self::initialize();
        }

        try {
            // Get a valid marketplace_items ID
            $validItemId = null;
            $checkItems = self::$timerDb->query("SELECT id FROM marketplace_items LIMIT 1");
            
            if ($checkItems->rowCount() > 0) {
                // Get the first item ID
                $item = $checkItems->fetch();
                $validItemId = $item['id'];
            } else {
                // Create a placeholder marketplace item for medication purchases
                $createItem = self::$timerDb->prepare("
                    INSERT INTO marketplace_items 
                    (name, description, price, is_active, stock) 
                    VALUES 
                    ('Medication Item', 'Placeholder item for medication purchases', 0, 1, -1)
                ");
                $createItem->execute();
                $validItemId = self::$timerDb->lastInsertId();
            }

            // Only proceed if we have a valid item ID
            if ($validItemId) {
                // Get the current time
                $now = date('Y-m-d H:i:s');
                
                // Convert EGP to USD for storage if not provided directly
                $finalAmountUSD = $amountUSD !== null ? $amountUSD : self::convertToUSD($amountEGP);
                
                // Insert into purchase_logs
                $sql = "INSERT INTO purchase_logs (item_id, item_name_snapshot, price_paid, purchase_time) 
                        VALUES (?, ?, ?, ?)";
                
                $insertPurchase = self::$timerDb->prepare($sql);
                $insertPurchase->execute([
                    $validItemId,
                    $itemName,  // Already prefixed with "FastMeds: " in the updateBalance method
                    $finalAmountUSD, // Store the USD amount
                    $now
                ]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error adding purchase log: " . $e->getMessage());
            return false;
        }
    }
} 
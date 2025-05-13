import { initializeApp } from 'firebase/app';
import {
  getDatabase,
  ref,
  set,
  get,
  push,
  remove,
  update,
  onValue,
  Database,
  DataSnapshot,
  connectDatabaseEmulator
} from 'firebase/database';
import { 
  getStorage, 
  ref as storageRef, 
  uploadString, 
  getDownloadURL,
  deleteObject
} from 'firebase/storage';
import { TimerData, TimerSession, MarketplaceItem, PurchaseHistory, Note, TodoItem } from '../types';
import { getLevelData, calculateLevelFromTime, calculateLevelProgress } from '../config/levels';
import { TODO_BASE_RATE, getCompletionsRequired, calculateReward } from '../components/Todo/TodoLevelInfoModal';
import { getAuth, Auth, GoogleAuthProvider, signInWithPopup, User, signOut, signInWithEmailAndPassword, createUserWithEmailAndPassword, updateProfile } from 'firebase/auth';

// Helper function to log session data for debugging
const debugLogSession = (session: any, id: string) => {
  return session;
};

const firebaseConfig = {
  apiKey: "AIzaSyBxEpJnzYeNQQPqQMHVq9sHkBGNRK1rPBE",
  authDomain: "reacttimer-c3e1c.firebaseapp.com",
  projectId: "reacttimer-c3e1c",
  storageBucket: "reacttimer-c3e1c.appspot.com",
  messagingSenderId: "1098977678132",
  appId: "1:1098977678132:web:c0c6d6a64676c5e7d4c6b1",
  measurementId: "G-YVDPB4NE7W",
  databaseURL: "https://reacttimer-7ed91-default-rtdb.europe-west1.firebasedatabase.app"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Realtime Database and get a reference to the service
const database = getDatabase(app);

// Initialize Firebase Storage
const storage = getStorage(app);

// Test database connection
const testDatabaseConnection = async () => {
  try {
    const testRef = ref(database, 'users/default-user/test');
    await set(testRef, {
      timestamp: Date.now(),
      message: 'Database connection test'
    });
    
    // Try reading the data back
    const snapshot = await get(testRef);
    if (snapshot.exists()) {
    }
    
    await remove(testRef); // Clean up test data
    return true;
  } catch (error) {
    console.error('Database connection failed:', error);
    return false;
  }
};

// Call test connection
testDatabaseConnection();

export class TimerService {
  private db: Database;
  private userId: string;

  constructor() {
    this.db = database;
    // For now, we'll use a fixed user ID. In a real app, this would come from authentication
    this.userId = 'default-user';
  }

  // Create a new timer (simplified defaults)
  async createTimer(timerData: Omit<TimerData, 'id' | 'levelTitle' | 'currentRate' | 'currentLevelProgress' | 'nextLevelThreshold'>): Promise<string> {
    try {
    const timerRef = push(ref(this.db, `users/${this.userId}/timers`));
      const initialLevel = getLevelData(1);
      const initialProgress = calculateLevelProgress(0);

      const timerWithDefaults: TimerData = {
        id: timerRef.key!,
        ...timerData,
        level: initialLevel.level,
        levelTitle: initialLevel.title,
        currentRate: initialLevel.ratePerHour,
        currentLevelProgress: initialProgress.progress,
        nextLevelThreshold: initialProgress.nextLevelThreshold,
        // Simplified initial state
        isActive: false,
        lastStartTime: 0,
        isPinned: false,
      };
      const { id, ...dataToSet } = timerWithDefaults;
      await set(timerRef, dataToSet);
    return timerRef.key as string;
    } catch (error) {
      console.error('Error creating timer:', error);
      throw error;
    }
  }

  // Update timer (keep for potential future use, e.g., renaming)
  async updateTimer(id: string, timer: Partial<TimerData>): Promise<void> {
    try {
    await update(ref(this.db, `users/${this.userId}/timers/${id}`), timer);
    } catch (error) {
      console.error('Error updating timer:', error);
      throw error;
    }
  }

  // Toggle pin status for a timer
  async togglePinTimer(timerId: string): Promise<boolean> {
    try {
      const timerRef = ref(this.db, `users/${this.userId}/timers/${timerId}`);
      const snapshot = await get(timerRef);
      
      if (!snapshot.exists()) {
        throw new Error('Timer not found');
      }
      
      const timerData = snapshot.val();
      const newPinStatus = !timerData.isPinned;
      
      await update(timerRef, { isPinned: newPinStatus });
      return newPinStatus;
    } catch (error) {
      console.error('Error toggling pin status:', error);
      throw error;
    }
  }

  // Start timer state in DB (simplified)
  async startTimerInDb(timerId: string): Promise<void> {
    try {
      const updates = {
        isActive: true,
        lastStartTime: Date.now(),
      };
      await update(ref(this.db, `users/${this.userId}/timers/${timerId}`), updates);
    } catch (error) {
      console.error('Error starting timer state:', error);
      throw error;
    }
  }

  // Log session (remains mostly the same, but only called from stop)
  private async logSessionInternal(timerId: string, durationSeconds: number, ratePerHour: number): Promise<void> {
    try {
      const sessionRef = push(ref(this.db, `users/${this.userId}/sessions`));
      const earnings = (durationSeconds / 3600) * ratePerHour;
      const now = Date.now();
      
      // Explicitly define all required fields with proper types
      const session: Omit<TimerSession, 'id'> = {
        timerId: timerId,
        duration: durationSeconds,
        earnings: earnings,
        timestamp: now,
        startTime: now - (durationSeconds * 1000), // Convert seconds to milliseconds
        endTime: now,
        date: new Date().toISOString().split('T')[0], // YYYY-MM-DD format
      };

      await set(sessionRef, session);
    } catch (error) {
      console.error('Error logging session:', error);
      throw error;
    }
  }

  // Stop timer, now accepting final accumulated time from UI
  async stopTimerInDb(timerId: string, finalAccumulatedTime: number): Promise<{newLevel: number, oldLevel: number, timerName: string} | null> {
    try {
      const timerRef = ref(this.db, `users/${this.userId}/timers/${timerId}`);
      const snapshot = await get(timerRef);
      if (!snapshot.exists()) {
        throw new Error('Timer not found');
      }

      const timerData = snapshot.val() as TimerData;
      const oldLevel = timerData.level;
      const timerName = timerData.name;
      const rateForSession = timerData.currentRate || 0;
      // Store the current totalTime to ensure it's preserved
      const currentTotalTime = timerData.totalTime || 0;

      // Calculate session duration based on the difference between
      // the new finalAccumulatedTime and the previously stored accumulatedTime.
      const previousAccumulatedTime = timerData.accumulatedTime || 0;
      let sessionDuration = finalAccumulatedTime - previousAccumulatedTime;
      sessionDuration = Math.max(0, sessionDuration); // Ensure non-negative

      // Log the session if its duration > 0
      if (sessionDuration > 0) {
        await this.logSessionInternal(timerId, sessionDuration, rateForSession);
      }

      // IMPORTANT: We never modify totalTime here - it should be updated separately
      // before calling this function. This ensures totalTime is only ever incremented,
      // never reset or overwritten.
      
      const sessionEarnings = (sessionDuration / 3600) * rateForSession;
      // Calculate new earnings for this session
      const newEarnings = timerData.earnings + sessionEarnings; 
      
      // Use the current totalTime to calculate level data
      const newLevelData = calculateLevelFromTime(currentTotalTime);
      const newProgressData = calculateLevelProgress(currentTotalTime);

      // Prepare final updates including resetting state
      const updates: Partial<TimerData> = {
        // Explicitly NOT updating totalTime here - we never want to reset it
        accumulatedTime: finalAccumulatedTime, // Update accumulatedTime to the final value
        earnings: newEarnings,
        level: newLevelData.level,
        levelTitle: newLevelData.title,
        currentRate: newLevelData.ratePerHour,
        currentLevelProgress: newProgressData.progress,
        nextLevelThreshold: newProgressData.nextLevelThreshold,
        // Reset state
        isActive: false,
        lastStartTime: 0, 
      };

      await update(timerRef, updates);

      return { newLevel: newLevelData.level, oldLevel, timerName };

    } catch (error) {
      console.error('Error stopping timer state:', error);
      throw error;
    }
  }

  // Delete timer (remains the same)
  async deleteTimer(timerId: string): Promise<void> {
    try {
      const timerRef = ref(this.db, `users/${this.userId}/timers/${timerId}`);
      await remove(timerRef);
      // Consider also deleting related sessions, or handle dangling sessions elsewhere
      // e.g., const sessionsRef = ref(this.db, `users/${this.userId}/sessions`);
      // query sessions where timerId === timerId and remove them
    } catch (error) {
      console.error('Error deleting timer:', error);
      throw error; // Re-throw the error so the UI can handle it
    }
  }

  // Stop all active timers
  async stopAllTimers(): Promise<Array<{newLevel: number, oldLevel: number, timerName: string} | null>> {
    try {
      const timers = await this.getTimers();
      const activeTimers = timers.filter(timer => timer.isActive);
      
      if (activeTimers.length === 0) {
        return [];
      }
      
      const results = await Promise.all(
        activeTimers.map(async (timer) => {
          // For stopAllTimers, we need to calculate the final accumulated time for each timer
          // This logic is similar to what's in Timer.tsx's handleStop before calling stopTimerInDb
          let sessionTime = 0;
          let currentElapsedTime = timer.accumulatedTime || 0;
          
          if (timer.isActive && timer.lastStartTime && timer.lastStartTime > 0) {
            sessionTime = (Date.now() - timer.lastStartTime) / 1000;
            currentElapsedTime = sessionTime + (timer.accumulatedTime || 0);
          }
          
          // First update the totalTime by adding the session time
          const updatedTotalTime = timer.totalTime + sessionTime;
          
          // Update the timer in the database with the new totalTime
          await this.updateTimer(timer.id, {
            totalTime: updatedTotalTime
          });
          
          // Then stop the timer
          return this.stopTimerInDb(timer.id, currentElapsedTime);
        })
      );
      
      return results;
    } catch (error) {
      console.error('Error stopping all timers:', error);
      throw error;
    }
  }

  // Get/Subscribe Timers (Update backwards compatibility)
  private mapDbDataToTimerData(timerId: string, dbData: any): TimerData {
    const totalTime = dbData.totalTime || 0;
    const levelData = calculateLevelFromTime(totalTime);
    const progressData = calculateLevelProgress(totalTime);

    return {
      id: timerId,
      name: dbData.name || 'Unnamed Timer',
      totalTime: totalTime,
      level: levelData.level, // Always use individually calculated level
      levelTitle: levelData.title, // Always use individually calculated title
      currentRate: levelData.ratePerHour, // Always use individually calculated rate for this field
      earnings: dbData.earnings || 0,
      
      // State fields use simpler defaults
      isActive: dbData.isActive || false,
      lastStartTime: dbData.lastStartTime || 0,
      accumulatedTime: dbData.accumulatedTime || 0, // Ensure accumulatedTime is mapped
      
      // Progress fields have backward compatibility
      currentLevelProgress: progressData.progress, // Always use individually calculated progress
      nextLevelThreshold: progressData.nextLevelThreshold, // Always use individually calculated threshold

      // Add pin status, ensure boolean
      isPinned: dbData.isPinned === true // Explicit boolean check
    };
  }

  // Get timers list (this method sorts, which is fine for direct fetching)
  async getTimers(): Promise<TimerData[]> {
    try {
      const timersRef = ref(this.db, `users/${this.userId}/timers`);
      const snapshot = await get(timersRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const timersData = snapshot.val();
      const timers: TimerData[] = Object.keys(timersData).map(key => 
        this.mapDbDataToTimerData(key, timersData[key])
      );
      
      // Sort timers: pinned first, then alphabetically
      // Sorting here is okay for a one-time fetch
      return timers.sort((a, b) => {
        const aIsPinned = a.isPinned ?? false;
        const bIsPinned = b.isPinned ?? false;
        if (aIsPinned && !bIsPinned) return -1;
        if (!aIsPinned && bIsPinned) return 1;
        return a.name.localeCompare(b.name);
      });
    } catch (error) {
      console.error('Error getting timers:', error);
      throw error;
    }
  }

  // Subscribe to timers with real-time updates (REMOVED SORTING)
  subscribeToTimers(callback: (timers: TimerData[]) => void): () => void {
    const timersRef = ref(this.db, `users/${this.userId}/timers`);
    
    const unsubscribe = onValue(timersRef, (snapshot) => {
      if (!snapshot.exists()) {
        callback([]);
        return;
      }
      
      const timersData = snapshot.val();
      const timers: TimerData[] = Object.keys(timersData).map(key => 
        this.mapDbDataToTimerData(key, timersData[key])
      );
      
      // --- SORTING REMOVED FROM HERE --- 
      // The component (TimerList.tsx) will handle sorting based on its state
      
      callback(timers); // Pass the unsorted list to the component
      }, (error) => {
        console.error('Error subscribing to timers:', error);
    });
    
    return unsubscribe;
  }

  // Get timer sessions
  async getTimerSessions(timerId?: string): Promise<TimerSession[]> {
    try {
      const sessionsRef = ref(this.db, `users/${this.userId}/sessions`);
      const snapshot = await get(sessionsRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const sessionsData: TimerSession[] = [];
      snapshot.forEach((childSnapshot) => {
        const session = childSnapshot.val();
        const id = childSnapshot.key as string;
        
        // Debug log the raw session data
        debugLogSession(session, id);
        
        // Filter by timerId if provided
        if (timerId && session.timerId !== timerId) {
          return;
        }
        
        sessionsData.push({
          id,
          timerId: session.timerId || '',
          duration: session.duration || 0,
          earnings: session.earnings || 0,
          timestamp: session.timestamp || 0,
          startTime: session.startTime || 0,
          endTime: session.endTime || 0,
          date: session.date || '',
          // Include the itemPurchased field if it exists
          ...(session.itemPurchased ? { itemPurchased: session.itemPurchased } : {})
        });
      });
      
      // Sort sessions by timestamp, newest first
      return sessionsData.sort((a, b) => b.timestamp - a.timestamp);
    } catch (error) {
      console.error('Error fetching sessions:', error);
      throw error;
    }
  }

  // New method to subscribe to session changes in real-time
  subscribeToSessions(callback: (sessions: TimerSession[]) => void): () => void {
    const sessionsRef = ref(this.db, `users/${this.userId}/sessions`);
    
    const unsubscribe = onValue(sessionsRef, (snapshot) => {
    const sessions: TimerSession[] = [];

    if (snapshot.exists()) {
      snapshot.forEach((childSnapshot) => {
          const session = childSnapshot.val();
          const id = childSnapshot.key as string;
          
          sessions.push({
            id,
            timerId: session.timerId || '',
            duration: session.duration || 0,
            earnings: session.earnings || 0,
            timestamp: session.timestamp || 0,
            startTime: session.startTime || 0,
            endTime: session.endTime || 0,
            date: session.date || '',
            itemPurchased: session.itemPurchased // Include the itemPurchased property
          });
        });
        
        // Sort sessions by timestamp, newest first
        sessions.sort((a, b) => b.timestamp - a.timestamp);
      }
      
      callback(sessions);
    });
    
    return unsubscribe;
  }

  // Reset bank by deleting all sessions
  async resetBankSessions(): Promise<void> {
    try {
      const userRef = ref(this.db, `users/${this.userId}`);
      const updates: Record<string, any> = {};
      
      // Clear sessions
      updates['sessions'] = null;
      
      // Clear earnings from each timer
      const snapshot = await get(ref(this.db, `users/${this.userId}/timers`));
      if (snapshot.exists()) {
        const timersData = snapshot.val();
        
        // Update each timer to reset earnings
        Object.keys(timersData).forEach(key => {
          if (timersData[key]) {
            updates[`timers/${key}/earnings`] = 0;
          }
        });
      }
      
      await update(userRef, updates);
    } catch (error) {
      console.error('Error resetting bank sessions:', error);
      throw error;
    }
  }

  // Reset all timers to their initial state
  async resetAllTimers(): Promise<void> {
    try {
      const userRef = ref(this.db, `users/${this.userId}`);
      const updates: Record<string, any> = {};
      
      // Clear sessions like in resetBankSessions
      updates['sessions'] = null;
      
      // Get initial level and progress data
      const initialLevel = getLevelData(1);
      const initialProgress = calculateLevelProgress(0);
      
      // Reset all timer data
      const snapshot = await get(ref(this.db, `users/${this.userId}/timers`));
      if (snapshot.exists()) {
        const timersData = snapshot.val();
        
        // Reset each timer to its initial state
        Object.keys(timersData).forEach(key => {
          if (timersData[key]) {
            // Reset all timer fields while preserving the name and isPinned status
            const name = timersData[key].name || 'Unnamed Timer';
            const isPinned = timersData[key].isPinned || false;
            
            updates[`timers/${key}/totalTime`] = 0;
            updates[`timers/${key}/earnings`] = 0;
            updates[`timers/${key}/level`] = initialLevel.level;
            updates[`timers/${key}/levelTitle`] = initialLevel.title;
            updates[`timers/${key}/currentRate`] = initialLevel.ratePerHour;
            updates[`timers/${key}/currentLevelProgress`] = initialProgress.progress;
            updates[`timers/${key}/nextLevelThreshold`] = initialProgress.nextLevelThreshold;
            updates[`timers/${key}/isActive`] = false;
            updates[`timers/${key}/lastStartTime`] = 0;
            updates[`timers/${key}/accumulatedTime`] = 0;
          }
        });
      }
      
      await update(userRef, updates);
    } catch (error) {
      console.error('Error resetting all timers:', error);
      throw error;
    }
  }

  // New marketplace methods

  // Create a new marketplace item
  async createMarketplaceItem(itemData: Omit<MarketplaceItem, 'id' | 'createdAt'>): Promise<string> {
    try {
      const itemRef = push(ref(this.db, `users/${this.userId}/marketplace/items`));
      
      const itemWithDefaults: MarketplaceItem = {
        id: itemRef.key!,
        ...itemData,
        createdAt: Date.now()
      };
      
      const { id, ...dataToSet } = itemWithDefaults;
      await set(itemRef, dataToSet);
      return itemRef.key as string;
    } catch (error) {
      console.error('Error creating marketplace item:', error);
      throw error;
    }
  }

  // Get all marketplace items
  async getMarketplaceItems(): Promise<MarketplaceItem[]> {
    try {
      const itemsRef = ref(this.db, `users/${this.userId}/marketplace/items`);
      const snapshot = await get(itemsRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const itemsData = snapshot.val();
      const items: MarketplaceItem[] = Object.keys(itemsData).map(key => ({
        id: key,
        name: itemsData[key].name || '',
        price: itemsData[key].price || 0,
        description: itemsData[key].description || '',
        imageUrl: itemsData[key].imageUrl || '',
        createdAt: itemsData[key].createdAt || Date.now()
      }));
      
      // Sort by creation date, newest first
      return items.sort((a, b) => b.createdAt - a.createdAt);
    } catch (error) {
      console.error('Error getting marketplace items:', error);
      throw error;
    }
  }

  // Delete a marketplace item
  async deleteMarketplaceItem(itemId: string): Promise<void> {
    try {
      const itemRef = ref(this.db, `users/${this.userId}/marketplace/items/${itemId}`);
      await remove(itemRef);
    } catch (error) {
      console.error('Error deleting marketplace item:', error);
      throw error;
    }
  }

  // Subscribe to marketplace items changes
  subscribeToMarketplaceItems(callback: (items: MarketplaceItem[]) => void): () => void {
    const itemsRef = ref(this.db, `users/${this.userId}/marketplace/items`);
    
    const unsubscribe = onValue(itemsRef, (snapshot) => {
      if (!snapshot.exists()) {
        callback([]);
        return;
      }
      
      const itemsData = snapshot.val();
      const items: MarketplaceItem[] = Object.keys(itemsData).map(key => ({
        id: key,
        name: itemsData[key].name || '',
        price: itemsData[key].price || 0,
        description: itemsData[key].description || '',
        imageUrl: itemsData[key].imageUrl || '',
        createdAt: itemsData[key].createdAt || Date.now()
      }));
      
      // Sort by creation date, newest first
      items.sort((a, b) => b.createdAt - a.createdAt);
      
      callback(items);
    });
    
    return unsubscribe;
  }

  // Purchase an item
  async purchaseItem(itemId: string, itemName: string, price: number): Promise<void> {
    try {
      // 1. Create purchase record
      const purchaseRef = push(ref(this.db, `users/${this.userId}/purchases`));
      
      const purchase: PurchaseHistory = {
        id: purchaseRef.key as string,
        userId: this.userId, // Need to add userId to match interface
        itemId,
        itemName,
        price,
        purchasedAt: Date.now(), // Use purchasedAt instead of timestamp
      };
      
      // 2. Create negative session entry to deduct the cost from the bank
      const deductionRef = push(ref(this.db, `users/${this.userId}/sessions`));
      
      const deduction: TimerSession = {
        id: deductionRef.key as string,
        timerId: 'marketplace',
        duration: 0,
        earnings: -price, // Negative value to deduct
        timestamp: Date.now(),
        startTime: Date.now(),
        endTime: Date.now(),
        date: new Date().toISOString().split('T')[0],
        itemPurchased: itemName // This field exists
      };
      
      await set(purchaseRef, purchase);
      await set(deductionRef, deduction);
    } catch (error) {
      console.error('Error purchasing item:', error);
      throw error;
    }
  }

  // Refund a purchase, adding the cost back to the bank
  async refundPurchase(purchaseId: string, itemId: string, itemName: string, price: number): Promise<void> {
    try {
      // 1. Create refund record
      const refundRef = push(ref(this.db, `users/${this.userId}/refunds`));
      
      const refund = {
        id: refundRef.key as string,
        purchaseId,
        itemId,
        itemName,
        price,
        timestamp: Date.now(), // Assuming refunds table needs a timestamp
      };
      
      // 2. Create positive session entry to add the refund to the bank
      const refundSessionRef = push(ref(this.db, `users/${this.userId}/sessions`));
      
      const refundSession: TimerSession = {
        id: refundSessionRef.key as string,
        timerId: 'refund',
        duration: 0, 
        earnings: price, // Positive value to add back
        timestamp: Date.now(),
        startTime: Date.now(),
        endTime: Date.now(),
        date: new Date().toISOString().split('T')[0],
        itemPurchased: `${itemName} (Refund)` // Use existing field
      };
      
      // 3. Delete the purchase record if it exists
      const purchaseRef = ref(this.db, `users/${this.userId}/purchases/${purchaseId}`);
      const snapshot = await get(purchaseRef);
      if (snapshot.exists()) {
        await remove(purchaseRef);
      }
      
      await set(refundRef, refund);
      await set(refundSessionRef, refundSession);
    } catch (error) {
      console.error('Error refunding purchase:', error);
      throw error;
    }
  }

  // Get purchase history
  async getPurchaseHistory(): Promise<PurchaseHistory[]> {
    try {
      const purchasesRef = ref(this.db, `users/${this.userId}/marketplace/purchases`);
      const snapshot = await get(purchasesRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const purchasesData = snapshot.val();
      const purchases: PurchaseHistory[] = Object.keys(purchasesData).map(key => ({
        id: key,
        userId: purchasesData[key].userId || this.userId,
        itemId: purchasesData[key].itemId || '',
        itemName: purchasesData[key].itemName || '',
        price: purchasesData[key].price || 0,
        purchasedAt: purchasesData[key].purchasedAt || Date.now()
      }));
      
      // Sort by purchase date, newest first
      return purchases.sort((a, b) => b.purchasedAt - a.purchasedAt);
    } catch (error) {
      console.error('Error getting purchase history:', error);
      throw error;
    }
  }

  // Check if user has purchased an item - Always return false to allow repurchasing
  async hasUserPurchasedItem(itemId: string): Promise<boolean> {
    // Always return false to allow repurchasing items
    return false;
  }

  // Subscribe to purchase history
  subscribeToPurchaseHistory(callback: (purchases: PurchaseHistory[]) => void): () => void {
    const purchasesRef = ref(this.db, `users/${this.userId}/marketplace/purchases`);
    
    const unsubscribe = onValue(purchasesRef, (snapshot) => {
      if (!snapshot.exists()) {
        callback([]);
        return;
      }
      
      const purchasesData = snapshot.val();
      const purchases: PurchaseHistory[] = Object.keys(purchasesData)
        .filter(key => !purchasesData[key].refunded) // Filter out refunded purchases
        .map(key => ({
          id: key,
          userId: purchasesData[key].userId || this.userId,
          itemId: purchasesData[key].itemId || '',
          itemName: purchasesData[key].itemName || '',
          price: purchasesData[key].price || 0,
          purchasedAt: purchasesData[key].purchasedAt || Date.now(),
          refunded: purchasesData[key].refunded || false,
          refundedAt: purchasesData[key].refundedAt || null
        }));
      
      // Sort by purchase date, newest first
      purchases.sort((a, b) => b.purchasedAt - a.purchasedAt);
      
      callback(purchases);
    });
    
    return unsubscribe;
  }

  // New image handling methods

  // Upload base64 image for marketplace item
  async uploadItemImage(base64Image: string, itemId: string): Promise<string> {
    try {
      // Use the local server endpoint instead of Firebase Storage
      const response = await fetch('http://localhost:3008/api/upload-base64', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ base64Image })
      });
      
      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(`Upload failed: ${errorData.error || response.statusText}`);
      }
      
      const data = await response.json();
      return data.imageUrl;
    } catch (error) {
      console.error('Error uploading image:', error);
      throw error;
    }
  }

  // Delete marketplace item image
  async deleteItemImage(itemId: string): Promise<void> {
    try {
      // First, get the item details to find the image URL
      const itemRef = ref(this.db, `users/${this.userId}/marketplace/items/${itemId}`);
      const snapshot = await get(itemRef);
      
      if (!snapshot.exists()) {
        // Item doesn't exist, nothing to delete
        return;
      }
      
      const item = snapshot.val();
      
      // If there's no image URL, nothing to delete
      if (!item.imageUrl) {
        return;
      }
      
      // Check if it's a local server URL
      if (item.imageUrl.includes('localhost:3008')) {
        // Extract the filename from the URL
        const urlParts = item.imageUrl.split('/');
        const filename = urlParts[urlParts.length - 1];
        
        // Call the delete endpoint on our local server
        const response = await fetch(`http://localhost:3008/api/delete-image/${filename}`, {
          method: 'DELETE'
        });
        
        if (!response.ok) {
          console.warn('Could not delete image from local server:', await response.text());
        }
      } else if (item.imageUrl.includes('firebasestorage')) {
        // This is a Firebase Storage URL, but we'll skip actual deletion due to CORS
        console.warn('Skipping Firebase Storage deletion due to CORS issues');
      }
      
      // Update the item to remove the image URL
      await update(itemRef, {
        imageUrl: ''
      });
    } catch (error) {
      console.error('Error deleting image:', error);
      // Don't throw here - just log the error as the image might not exist
    }
  }

  // Create a new marketplace item with image
  async createMarketplaceItemWithImage(
    itemData: Omit<MarketplaceItem, 'id' | 'createdAt'>, 
    base64Image?: string
  ): Promise<string> {
    try {
      // Create the item first
      const itemId = await this.createMarketplaceItem(itemData);
      
      // If there's an image, upload it
      if (base64Image) {
        try {
          // Attempt to upload to Firebase Storage
          const imageUrl = await this.uploadItemImage(base64Image, itemId);
          
          // Update the item with the image URL
          await update(ref(this.db, `users/${this.userId}/marketplace/items/${itemId}`), {
            imageUrl
          });
        } catch (error) {
          console.warn('Firebase Storage upload failed (likely CORS). Using data URL as fallback:', error);
          
          // DEVELOPMENT WORKAROUND: Use the base64 image directly as a data URL
          // This is not recommended for production due to database size limits
          const dataUrl = base64Image.includes('data:') 
            ? base64Image
            : `data:image/jpeg;base64,${base64Image}`;
            
          // Store the data URL directly in the database as a fallback
          await update(ref(this.db, `users/${this.userId}/marketplace/items/${itemId}`), {
            imageUrl: dataUrl
          });
        }
      }
      
      return itemId;
    } catch (error) {
      console.error('Error creating marketplace item with image:', error);
      throw error;
    }
  }

  // Delete a marketplace item and its image
  async deleteMarketplaceItemWithImage(itemId: string): Promise<void> {
    try {
      // Delete the image first
      await this.deleteItemImage(itemId);
      
      // Then delete the item
      await this.deleteMarketplaceItem(itemId);
    } catch (error) {
      console.error('Error deleting marketplace item with image:', error);
      throw error;
    }
  }

  // Update marketplace item image
  async updateItemImage(itemId: string, base64Image: string): Promise<string> {
    try {
      // Delete existing image if present
      await this.deleteItemImage(itemId);
      
      // Upload new image
      const imageUrl = await this.uploadItemImage(base64Image, itemId);
      
      // Update item with new URL
      await update(ref(this.db, `users/${this.userId}/marketplace/items/${itemId}`), {
        imageUrl
      });
      
      return imageUrl;
    } catch (error) {
      console.error('Error updating item image:', error);
      throw error;
    }
  }

  // Save audio settings to Firebase
  async saveAudioSettings(audioSettings: {
    countdownEnabled: boolean;
    stopEnabled: boolean;
    stopAllEnabled: boolean;
    globalVolume: number;
  }): Promise<void> {
    try {
      const userSettingsRef = ref(this.db, `users/${this.userId}/settings/audioSettings`);
      await set(userSettingsRef, audioSettings);
    } catch (error) {
      console.error('Error saving audio settings to Firebase:', error);
      throw error;
    }
  }

  // Get audio settings from Firebase
  async getAudioSettings(): Promise<{
    countdownEnabled: boolean;
    stopEnabled: boolean;
    stopAllEnabled: boolean;
    globalVolume: number;
  } | null> {
    try {
      const userSettingsRef = ref(this.db, `users/${this.userId}/settings/audioSettings`);
      const snapshot = await get(userSettingsRef);
      
      if (snapshot.exists()) {
        const settings = snapshot.val();
        return settings;
      }
      
      return null;
    } catch (error) {
      console.error('Error getting audio settings from Firebase:', error);
      throw error;
    }
  }

  // Subscribe to audio settings changes
  subscribeToAudioSettings(callback: (audioSettings: {
    countdownEnabled: boolean;
    stopEnabled: boolean;
    stopAllEnabled: boolean;
    globalVolume: number;
  } | null) => void): () => void {
    try {
      const userSettingsRef = ref(this.db, `users/${this.userId}/settings/audioSettings`);
      
      const unsubscribe = onValue(userSettingsRef, (snapshot) => {
        if (snapshot.exists()) {
          const settings = snapshot.val();
          callback(settings);
        } else {
          callback(null);
        }
      });
      
      return unsubscribe;
    } catch (error) {
      console.error('Error subscribing to audio settings:', error);
      // Return empty function as fallback
      return () => {};
    }
  }

  // Note methods 
  async createNote(noteData: Omit<Note, 'id' | 'createdAt' | 'updatedAt'>): Promise<string> {
    try {
      const noteRef = push(ref(this.db, `users/${this.userId}/notes`));
      const timestamp = Date.now();
      
      const noteWithDefaults: Note = {
        id: noteRef.key!,
        ...noteData,
        createdAt: timestamp,
        updatedAt: timestamp
      };
      
      const { id, ...dataToSet } = noteWithDefaults;
      await set(noteRef, dataToSet);
      return noteRef.key as string;
    } catch (error) {
      console.error('Error creating note:', error);
      throw error;
    }
  }

  async updateNote(noteId: string, updates: Partial<Omit<Note, 'id' | 'createdAt'>>): Promise<void> {
    try {
      const noteRef = ref(this.db, `users/${this.userId}/notes/${noteId}`);
      
      // Always update the updatedAt timestamp
      const updatedData = {
        ...updates,
        updatedAt: Date.now()
      };
      
      await update(noteRef, updatedData);
    } catch (error) {
      console.error('Error updating note:', error);
      throw error;
    }
  }

  async deleteNote(noteId: string): Promise<void> {
    try {
      const noteRef = ref(this.db, `users/${this.userId}/notes/${noteId}`);
      await remove(noteRef);
    } catch (error) {
      console.error('Error deleting note:', error);
      throw error;
    }
  }

  async getNotes(): Promise<Note[]> {
    try {
      const notesRef = ref(this.db, `users/${this.userId}/notes`);
      const snapshot = await get(notesRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const notesData = snapshot.val();
      const notes: Note[] = Object.keys(notesData).map(key => ({
        id: key,
        title: notesData[key].title || '',
        content: notesData[key].content || '',
        tags: notesData[key].tags || [],
        createdAt: notesData[key].createdAt || Date.now(),
        updatedAt: notesData[key].updatedAt || Date.now(),
        hasImages: notesData[key].hasImages || false,
        hasCodeSnippets: notesData[key].hasCodeSnippets || false,
        pinned: notesData[key].pinned || false
      }));
      
      // Sort by last updated, newest first
      return notes.sort((a, b) => b.updatedAt - a.updatedAt);
    } catch (error) {
      console.error('Error getting notes:', error);
      throw error;
    }
  }

  subscribeToNotes(callback: (notes: Note[]) => void): () => void {
    try {
      const notesRef = ref(this.db, `users/${this.userId}/notes`);
      
      const unsubscribe = onValue(notesRef, (snapshot) => {
        if (!snapshot.exists()) {
          callback([]);
          return;
        }
        
        const notesData = snapshot.val();
        const notes: Note[] = Object.keys(notesData).map(key => ({
          id: key,
          title: notesData[key].title || '',
          content: notesData[key].content || '',
          tags: notesData[key].tags || [],
          createdAt: notesData[key].createdAt || Date.now(),
          updatedAt: notesData[key].updatedAt || Date.now(),
          hasImages: notesData[key].hasImages || false,
          hasCodeSnippets: notesData[key].hasCodeSnippets || false,
          pinned: notesData[key].pinned || false
        }));
        
        // Sort by last updated, newest first
        notes.sort((a, b) => b.updatedAt - a.updatedAt);
        
        callback(notes);
      }, (error) => {
        console.error('Error subscribing to notes:', error);
      });
      
      return unsubscribe;
    } catch (error) {
      console.error('Error setting up notes subscription:', error);
      // Return empty function as fallback
      return () => {};
    }
  }

  async getNote(noteId: string): Promise<Note | null> {
    try {
      const noteRef = ref(this.db, `users/${this.userId}/notes/${noteId}`);
      const snapshot = await get(noteRef);
      
      if (!snapshot.exists()) {
        return null;
      }
      
      const noteData = snapshot.val();
      return {
        id: noteId,
        title: noteData.title || '',
        content: noteData.content || '',
        tags: noteData.tags || [],
        createdAt: noteData.createdAt || Date.now(),
        updatedAt: noteData.updatedAt || Date.now(),
        hasImages: noteData.hasImages || false,
        hasCodeSnippets: noteData.hasCodeSnippets || false,
        pinned: noteData.pinned || false
      };
    } catch (error) {
      console.error('Error getting note:', error);
      throw error;
    }
  }

  // Todo Methods
  async createTodoItem(todoData: Omit<TodoItem, 'id' | 'createdAt' | 'updatedAt' | 'position' | 'reward' | 'level'>): Promise<string> {
    try {
      // Validate that the title is not empty
      if (!todoData.title || todoData.title.trim() === '') {
        throw new Error('Todo item must have a non-empty title');
      }
      
      const todoRef = push(ref(this.db, `users/${this.userId}/todos`));
      const timestamp = Date.now();
      
      const existingTodos = await this.getTodoItems();
      const highestPosition = existingTodos.length > 0 
        ? Math.max(...existingTodos.map(item => item.position)) 
        : 0;
      
      const dataToSave: any = {
        ...todoData,
        title: todoData.title.trim(), // Ensure title is trimmed
        completed: todoData.completed || false,
        priority: todoData.priority || 'medium',
        reward: TODO_BASE_RATE, 
        createdAt: timestamp,
        updatedAt: timestamp,
        position: highestPosition + 1,
        level: 1, // Default level, though unused in global system
        dueDate: todoData.dueDate || null,
      };

      // Handle parentId properly
      if (todoData.parentId) {
        dataToSave.parentId = todoData.parentId;
        
        // Find parent task to calculate depth
        const parentTask = existingTodos.find(t => t.id === todoData.parentId);
        if (parentTask) {
          // Calculate depth based on parent's depth
          const parentDepth = parentTask.depth || 0;
          dataToSave.depth = parentDepth + 1;
        }
      }
      
      await set(todoRef, dataToSave);
      return todoRef.key!;
    } catch (error) {
      console.error('Error creating todo item:', error);
      throw error;
    }
  }

  async updateTodoItem(todoId: string, updates: Partial<Omit<TodoItem, 'id' | 'createdAt'>>): Promise<void> {
    try {
      const todoRef = ref(this.db, `users/${this.userId}/todos/${todoId}`);
      
      // Process dueDate to make sure it's null instead of undefined for Firebase
      const processedUpdates = {
        ...updates,
        dueDate: updates.dueDate === undefined ? null : updates.dueDate,
        updatedAt: Date.now()
      };
      
      // Handle parentId specifically to avoid Firebase errors with undefined values
      if (updates.parentId === undefined) {
        // Don't change the parentId if it's not in the updates
        delete processedUpdates.parentId;
      } else if (updates.parentId === null || updates.parentId === '') {
        // Explicitly set to empty string to remove any parent relationship
        // Firebase doesn't accept undefined, and the type definition expects string|undefined
        processedUpdates.parentId = '';
      }
      
      await update(todoRef, processedUpdates);
    } catch (error) {
      console.error('Error updating todo item:', error);
      throw error;
    }
  }

  async deleteTodoItem(todoId: string, deleteChildren: boolean = true): Promise<void> {
    try {
      if (deleteChildren) {
        // Get all todos to find children
        const todos = await this.getTodoItems();
        
        // Find all child tasks recursively
        const findChildrenRecursively = (parentId: string): string[] => {
          const directChildren = todos.filter(t => t.parentId === parentId).map(t => t.id);
          const allDescendants = [...directChildren];
          
          // Find children of each child
          directChildren.forEach(childId => {
            allDescendants.push(...findChildrenRecursively(childId));
          });
          
          return allDescendants;
        };
        
        // Get all child task IDs
        const childrenIds = findChildrenRecursively(todoId);
        
        // Delete all children
        const childDeletePromises = childrenIds.map(id => 
          remove(ref(this.db, `users/${this.userId}/todos/${id}`))
        );
        
        await Promise.all(childDeletePromises);
      }
      
      // Delete the todo itself
      await remove(ref(this.db, `users/${this.userId}/todos/${todoId}`));
    } catch (error) {
      console.error('Error deleting todo item:', error);
      throw error;
    }
  }

  async getTodoItems(): Promise<TodoItem[]> {
    try {
      const todosRef = ref(this.db, `users/${this.userId}/todos`);
      const snapshot = await get(todosRef);
      
      if (!snapshot.exists()) {
        return [];
      }
      
      const todosData = snapshot.val();
      const todos: TodoItem[] = Object.keys(todosData).map(key => ({
        id: key,
        title: todosData[key].title || '',
        description: todosData[key].description || '',
        completed: todosData[key].completed || false,
        priority: todosData[key].priority || 'medium',
        dueDate: todosData[key].dueDate,
        reward: todosData[key].reward || 0,
        createdAt: todosData[key].createdAt || Date.now(),
        updatedAt: todosData[key].updatedAt || Date.now(),
        completedAt: todosData[key].completedAt,
        position: todosData[key].position || 0,
        tags: todosData[key].tags || [],
        parentId: todosData[key].parentId,
        level: todosData[key].level || 1, // Kept for now
      }));
      
      return todos.sort((a, b) => a.position - b.position);
    } catch (error) {
      console.error('Error getting todo items:', error);
      throw error;
    }
  }

  subscribeToTodoItems(callback: (todos: TodoItem[]) => void): () => void {
    try {
      const todosRef = ref(this.db, `users/${this.userId}/todos`);
      
      const unsubscribe = onValue(todosRef, (snapshot) => {
        if (!snapshot.exists()) {
          callback([]);
          return;
        }
        
        const todosData = snapshot.val();
        const todos: TodoItem[] = Object.keys(todosData).map(key => ({
          id: key,
          title: todosData[key].title || '',
          description: todosData[key].description || '',
          completed: todosData[key].completed || false,
          priority: todosData[key].priority || 'medium',
          dueDate: todosData[key].dueDate,
          reward: todosData[key].reward || 0,
          createdAt: todosData[key].createdAt || Date.now(),
          updatedAt: todosData[key].updatedAt || Date.now(),
          completedAt: todosData[key].completedAt,
          position: todosData[key].position || 0,
          tags: todosData[key].tags || [],
          parentId: todosData[key].parentId,
          level: todosData[key].level || 1, // Kept for now
        }));
        
        todos.sort((a, b) => a.position - b.position);
        
        callback(todos);
      }, (error) => {
        console.error('Error subscribing to todos:', error);
      });
      
      return unsubscribe;
    } catch (error) {
      console.error('Error setting up todos subscription:', error);
      return () => {};
    }
  }

  async updateTodoPositions(todoPositions: {id: string, position: number}[]): Promise<void> {
    try {
      const updates: Record<string, any> = {};
      
      todoPositions.forEach(item => {
        updates[`users/${this.userId}/todos/${item.id}/position`] = item.position;
      });
      
      await update(ref(this.db), updates);
    } catch (error) {
      console.error('Error updating todo positions:', error);
      throw error;
    }
  }

  async completeTodoItem(todoId: string, globalLevel: number = 1): Promise<void> {
    try {
      const timestamp = Date.now();
      const todoRef = ref(this.db, `users/${this.userId}/todos/${todoId}`);
      const snapshot = await get(todoRef);
      
      if (!snapshot.exists()) {
        throw new Error('Todo item not found for completion');
      }
      
      const todoData = snapshot.val();
      const scaledReward = calculateReward(globalLevel);
      
      await update(todoRef, {
        ...todoData,
        completed: true,
        completedAt: timestamp,
        updatedAt: timestamp,
      });
      
      if (scaledReward > 0) {
        const sessionRef = push(ref(this.db, `users/${this.userId}/sessions`));
        const session: TimerSession = {
          id: sessionRef.key as string,
          timerId: 'todo-reward',
          duration: 0,
          earnings: scaledReward,
          timestamp: timestamp,
          startTime: timestamp,
          endTime: timestamp,
          date: new Date().toISOString().split('T')[0],
          itemPurchased: `Todo Reward (Global Lvl ${globalLevel})`
        };
        await set(sessionRef, session);
      }
    } catch (error) {
      console.error('Error completing todo item:', error);
      throw error;
    }
  }

  async uncompleteTodoItem(todoId: string, globalLevel: number = 1): Promise<void> {
    try {
      // Calculate the reward based on the global level
      const scaledReward = calculateReward(globalLevel);
      
      // Update the todo item - just mark as incomplete
      const todoRef = ref(this.db, `users/${this.userId}/todos/${todoId}`);
      await update(todoRef, {
        completed: false,
        completedAt: null,
        updatedAt: Date.now()
      });
      
      // Deduct the reward from the bank if it was added
      if (scaledReward > 0) {
        const refundSessionRef = push(ref(this.db, `users/${this.userId}/sessions`));
        
        const refundSession: TimerSession = {
          id: refundSessionRef.key as string,
          timerId: 'todo-refund',
          duration: 0,
          earnings: -scaledReward, // Negative to deduct
          timestamp: Date.now(),
          startTime: Date.now(),
          endTime: Date.now(),
          date: new Date().toISOString().split('T')[0],
          itemPurchased: `Todo Reward Refund (Global Level ${globalLevel})`
        };
        
        await set(refundSessionRef, refundSession);
      }
    } catch (error) {
      console.error('Error uncompleting todo item:', error);
      throw error;
    }
  }

  async getTodoStats(): Promise<{
    completed: number;
    active: number;
    totalEarned: number;
  }> {
    try {
      const todos = await this.getTodoItems();
      
      const completed = todos.filter(todo => todo.completed).length;
      const active = todos.length - completed;
      const totalEarned = todos
        .filter(todo => todo.completed)
        .reduce((sum, todo) => sum + (todo.reward || 0), 0);
      
      return {
        completed,
        active,
        totalEarned
      };
    } catch (error) {
      console.error('Error getting todo stats:', error);
      throw error;
    }
  }
}

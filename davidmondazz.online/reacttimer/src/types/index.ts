export interface TimerData {
  id: string;
  name: string;
  totalTime: number;        // Total accumulated time (seconds)
  level: number;            // Current level number
  levelTitle: string;       // Current level title (e.g., 'Novice')
  currentRate: number;      // Current rate per hour for this level
  earnings: number;         // Total accumulated earnings
  isPinned: boolean;       // Whether the timer is pinned to the top

  // Simplified State fields
  isActive: boolean;        // Is the timer currently running?
  lastStartTime: number;    // Timestamp (ms) when the timer was last started (0 if stopped)
  accumulatedTime?: number;  // Total active time in seconds, for resuming paused timers

  // Progress fields (remain the same)
  currentLevelProgress: number; // Progress percentage (0-1) towards next level
  nextLevelThreshold: number;   // Cumulative seconds required to reach the *next* level
}

export interface TimerSession {
  id: string;
  timerId: string;
  duration: number;       // Session duration in seconds
  earnings: number;       // Earnings for this session
  timestamp: number;      // When the session was logged (usually end time)
  startTime?: number;     // When the session started
  endTime?: number;       // When the session ended
  date?: string;          // YYYY-MM-DD format for easier grouping
  itemPurchased?: string; // Item name for marketplace purchases
}

export interface MarketplaceItem {
  id: string;
  name: string;
  price: number;          // Price in the same currency as earnings
  description?: string;   // Optional description
  imageUrl?: string;      // Optional image URL
  createdAt: number;      // Timestamp when created
}

export interface PurchaseHistory {
  id: string;
  userId: string;
  itemId: string;
  itemName: string;
  price: number;
  purchasedAt: number;    // Timestamp when purchased
  refunded?: boolean;     // Whether the purchase has been refunded
  refundedAt?: number;    // Timestamp when refunded
}

export interface UserStats {
  totalTime: number;
  level: number;
  levelTitle: string;
  currentRate: number;
  earnings: number;
  currentLevelProgress: number;
  nextLevelThreshold: number;
}

export interface UserSettings {
  baseRate: number;
  levelMultiplier: number;
  theme: 'light' | 'dark';
  soundEnabled: boolean;
  audioSettings?: {
    countdownEnabled: boolean;
    stopEnabled: boolean;
    stopAllEnabled: boolean;
    globalVolume: number; // Range 0 to 1
  };
}

export interface User {
  id: string;
  email: string;
  stats: UserStats;
  settings: UserSettings;
  sessions: TimerSession[];
}

export interface Note {
  id: string;
  title: string;
  content: string;
  tags?: string[];
  createdAt: number;
  updatedAt: number;
  hasImages: boolean;
  hasCodeSnippets: boolean;
  pinned?: boolean;
}

export interface TodoItem {
  id: string;
  title: string;
  description?: string;
  completed: boolean;
  priority: 'low' | 'medium' | 'high';
  dueDate?: number | null; // Optional due date as timestamp (can be null for Firebase)
  reward: number; // Money earned when task is completed
  createdAt: number;
  updatedAt: number;
  completedAt?: number; // When the task was marked as complete
  position: number; // For reordering tasks
  tags?: string[]; // Optional tags for categorization
  parentId?: string; // For sub-tasks (hierarchical structure)
  level: number; // Current level of the todo (will be removed for global system but kept for now to avoid breaking too much)
  children?: TodoItem[]; // For nested tasks (this field is populated client-side)
  depth?: number; // Depth in the hierarchy (populated client-side)
}

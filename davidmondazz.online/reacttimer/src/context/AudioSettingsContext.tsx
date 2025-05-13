import React, { createContext, useState, useContext, useEffect, ReactNode, useCallback, useRef } from 'react';
import { TimerService } from '../services/firebase';

// 1. Define the shape of the settings
export interface AudioSettings {
  countdownEnabled: boolean;
  stopEnabled: boolean;
  stopAllEnabled: boolean;
  globalVolume: number; // Range 0 to 1
  isTimerSpeedUpActive: boolean; // New setting
  timerSpeedMultiplier: number; // New setting (e.g., 1, 2, 5, 10)
}

// 2. Define the shape of the context value
interface AudioSettingsContextType {
  settings: AudioSettings;
  updateSettings: (newSettings: Partial<AudioSettings>) => void;
  toggleSetting: (key: keyof Omit<AudioSettings, 'globalVolume' | 'timerSpeedMultiplier'>) => void;
  setGlobalVolume: (volume: number) => void;
  setTimerSpeedMultiplier: (multiplier: number) => void; // New function
}

// Default values for the context
const defaultSettings: AudioSettings = {
  countdownEnabled: true,
  stopEnabled: true,
  stopAllEnabled: true,
  globalVolume: 0.5, // Default volume 50%
  isTimerSpeedUpActive: false, // Default to off
  timerSpeedMultiplier: 1, // Default to 1x speed
};

// 3. Create the Context
const AudioSettingsContext = createContext<AudioSettingsContextType | undefined>(
  undefined
);

// Utility function to get initial state from localStorage as a backup when Firebase is not available
const getInitialStateFromLocalStorage = (): AudioSettings => {
  try {
    const storedSettings = localStorage.getItem('audioSettings');
    if (storedSettings) {
      const parsed = JSON.parse(storedSettings);
      // Basic validation to ensure structure matches
      if (
        typeof parsed.countdownEnabled === 'boolean' &&
        typeof parsed.stopEnabled === 'boolean' &&
        typeof parsed.stopAllEnabled === 'boolean' &&
        typeof parsed.globalVolume === 'number' &&
        parsed.globalVolume >= 0 &&
        parsed.globalVolume <= 1 &&
        typeof parsed.isTimerSpeedUpActive === 'boolean' && // Added validation
        typeof parsed.timerSpeedMultiplier === 'number' && // Added validation
        parsed.timerSpeedMultiplier >= 1 // Basic validation for multiplier
      ) {
        return parsed;
      }
    }
  } catch (error) {
    console.error('Error reading audio settings from localStorage:', error);
  }
  return defaultSettings;
};

// 4. Create the Provider Component
export const AudioSettingsProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [settings, setSettings] = useState<AudioSettings>(getInitialStateFromLocalStorage);
  const [isLoading, setIsLoading] = useState(true);
  const timerService = useRef(new TimerService());
  const unsubscribeRef = useRef<(() => void) | null>(null);

  // Load settings from Firebase on mount
  useEffect(() => {
    const loadSettings = async () => {
      try {
        const savedSettings = await timerService.current.getAudioSettings();
        if (savedSettings) {
          // Ensure new fields have defaults if not present in older saved settings
          const completeSettings = { ...defaultSettings, ...savedSettings };
          setSettings(completeSettings);
        } else {
          // If no settings in Firebase, save current (default) settings
          await timerService.current.saveAudioSettings(defaultSettings);
        }
      } catch (error) {
        console.error('Error loading audio settings from Firebase:', error);
      } finally {
        setIsLoading(false);
      }
    };

    loadSettings();
    
    // Subscribe to changes
    unsubscribeRef.current = timerService.current.subscribeToAudioSettings((newSettings) => {
      if (newSettings) {
        // Ensure all required fields are present by merging with default settings
        const completeSettings = { ...defaultSettings, ...newSettings };
        setSettings(completeSettings);
      }
    });

    return () => {
      if (unsubscribeRef.current) {
        unsubscribeRef.current();
      }
    };
  }, []);

  // Save settings to localStorage as a backup and to Firebase when they change
  useEffect(() => {
    if (isLoading) return; // Skip first render to avoid overwriting Firebase data

    try {
      // Save to local storage as backup
      localStorage.setItem('audioSettings', JSON.stringify(settings));
      
      // Save to Firebase
      timerService.current.saveAudioSettings(settings)
        .then(() => {})
        .catch(error => console.error('Error saving to Firebase:', error));
    } catch (error) {
      console.error('Error saving audio settings:', error);
    }
  }, [settings, isLoading]);

  const updateSettings = useCallback((newSettings: Partial<AudioSettings>) => {
    setSettings((prev) => ({ ...prev, ...newSettings }));
  }, []);

  const toggleSetting = useCallback((key: keyof Omit<AudioSettings, 'globalVolume' | 'timerSpeedMultiplier'>) => {
    setSettings((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  }, []);

  const setGlobalVolume = useCallback((volume: number) => {
    // Clamp volume between 0 and 1
    const clampedVolume = Math.max(0, Math.min(1, volume));
    setSettings((prev) => ({
      ...prev,
      globalVolume: clampedVolume,
    }));
  }, []);

  const setTimerSpeedMultiplier = useCallback((multiplier: number) => {
    const clampedMultiplier = Math.max(1, multiplier); // Ensure multiplier is at least 1
    setSettings((prev) => ({
      ...prev,
      timerSpeedMultiplier: clampedMultiplier,
    }));
  }, []);

  const value = {
    settings,
    updateSettings,
    toggleSetting,
    setGlobalVolume,
    setTimerSpeedMultiplier, // Added new function to context value
  };

  return (
    <AudioSettingsContext.Provider value={value}>
      {children}
    </AudioSettingsContext.Provider>
  );
};

// 5. Create a custom hook for easy consumption
export const useAudioSettings = (): AudioSettingsContextType => {
  const context = useContext(AudioSettingsContext);
  if (context === undefined) {
    throw new Error('useAudioSettings must be used within an AudioSettingsProvider');
  }
  return context;
}; 
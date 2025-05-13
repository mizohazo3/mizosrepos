import React, { createContext, useState, useContext, useEffect, ReactNode } from 'react';

interface AudioSettings {
  countdownEnabled: boolean;
  stopEnabled: boolean;
  stopAllEnabled: boolean;
  globalVolume: number; // Range 0 to 1
}

interface AudioSettingsContextProps {
  settings: AudioSettings;
  updateSettings: (newSettings: Partial<AudioSettings>) => void;
}

const defaultSettings: AudioSettings = {
  countdownEnabled: true,
  stopEnabled: true,
  stopAllEnabled: true,
  globalVolume: 0.5,
};

// Load settings from localStorage or use defaults
const loadSettings = (): AudioSettings => {
  try {
    const storedSettings = localStorage.getItem('audioSettings');
    if (storedSettings) {
      const parsedSettings = JSON.parse(storedSettings);
      // Ensure all keys from defaultSettings are present
      return { ...defaultSettings, ...parsedSettings };
    }
  } catch (error) {
    // console.error('Error loading audio settings from localStorage:', error);
  }
  return defaultSettings;
};

// Save settings to localStorage
const saveSettings = (settings: AudioSettings) => {
  try {
    localStorage.setItem('audioSettings', JSON.stringify(settings));
  } catch (error) {
    // console.error('Error saving audio settings to localStorage:', error);
  }
};

const AudioSettingsContext = createContext<AudioSettingsContextProps | undefined>(
  undefined
);

export const AudioSettingsProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [settings, setSettings] = useState<AudioSettings>(loadSettings);

  const updateSettings = (newSettings: Partial<AudioSettings>) => {
    setSettings(prevSettings => {
      const updatedSettings = { ...prevSettings, ...newSettings };
      saveSettings(updatedSettings);
      return updatedSettings;
    });
  };

  return (
    <AudioSettingsContext.Provider value={{ settings, updateSettings }}>
      {children}
    </AudioSettingsContext.Provider>
  );
};

export const useAudioSettings = () => {
  const context = useContext(AudioSettingsContext);
  if (!context) {
    throw new Error('useAudioSettings must be used within an AudioSettingsProvider');
  }
  return context;
};

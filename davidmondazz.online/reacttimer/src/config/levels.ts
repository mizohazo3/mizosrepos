export interface LevelData {
  level: number;
  title: string;
  ratePerHour: number;
  cumulativeHoursRequired: number;
}

export const levelStructure: LevelData[] = [
  // Level 0 placeholder for calculations, actual levels start at 1
  { level: 0, title: '-', ratePerHour: 0, cumulativeHoursRequired: 0 },
  // Novice
  { level: 1, title: 'Novice', ratePerHour: 0.10, cumulativeHoursRequired: 0 }, // Base level
  { level: 2, title: 'Novice', ratePerHour: 0.11, cumulativeHoursRequired: 5 },
  { level: 3, title: 'Novice', ratePerHour: 0.12, cumulativeHoursRequired: 10 },
  { level: 4, title: 'Novice', ratePerHour: 0.13, cumulativeHoursRequired: 15 },
  { level: 5, title: 'Novice', ratePerHour: 0.14, cumulativeHoursRequired: 20 },
  { level: 6, title: 'Novice', ratePerHour: 0.15, cumulativeHoursRequired: 26 },
  { level: 7, title: 'Novice', ratePerHour: 0.16, cumulativeHoursRequired: 32 },
  { level: 8, title: 'Novice', ratePerHour: 0.17, cumulativeHoursRequired: 38 },
  { level: 9, title: 'Novice', ratePerHour: 0.18, cumulativeHoursRequired: 44 },
  { level: 10, title: 'Novice', ratePerHour: 0.19, cumulativeHoursRequired: 50 },
  // Apprentice
  { level: 11, title: 'Apprentice', ratePerHour: 0.25, cumulativeHoursRequired: 60 },
  { level: 12, title: 'Apprentice', ratePerHour: 0.27, cumulativeHoursRequired: 70 },
  { level: 13, title: 'Apprentice', ratePerHour: 0.29, cumulativeHoursRequired: 80 },
  { level: 14, title: 'Apprentice', ratePerHour: 0.31, cumulativeHoursRequired: 90 },
  { level: 15, title: 'Apprentice', ratePerHour: 0.33, cumulativeHoursRequired: 100 },
  { level: 16, title: 'Apprentice', ratePerHour: 0.35, cumulativeHoursRequired: 120 },
  { level: 17, title: 'Apprentice', ratePerHour: 0.37, cumulativeHoursRequired: 140 },
  { level: 18, title: 'Apprentice', ratePerHour: 0.39, cumulativeHoursRequired: 160 },
  { level: 19, title: 'Apprentice', ratePerHour: 0.41, cumulativeHoursRequired: 180 },
  { level: 20, title: 'Apprentice', ratePerHour: 0.43, cumulativeHoursRequired: 200 },
  // Intermediate
  { level: 21, title: 'Intermediate', ratePerHour: 0.50, cumulativeHoursRequired: 220 },
  { level: 22, title: 'Intermediate', ratePerHour: 0.53, cumulativeHoursRequired: 240 },
  { level: 23, title: 'Intermediate', ratePerHour: 0.56, cumulativeHoursRequired: 260 },
  { level: 24, title: 'Intermediate', ratePerHour: 0.59, cumulativeHoursRequired: 280 },
  { level: 25, title: 'Intermediate', ratePerHour: 0.62, cumulativeHoursRequired: 300 },
  { level: 26, title: 'Intermediate', ratePerHour: 0.65, cumulativeHoursRequired: 330 },
  { level: 27, title: 'Intermediate', ratePerHour: 0.68, cumulativeHoursRequired: 360 },
  { level: 28, title: 'Intermediate', ratePerHour: 0.71, cumulativeHoursRequired: 390 },
  { level: 29, title: 'Intermediate', ratePerHour: 0.74, cumulativeHoursRequired: 420 },
  { level: 30, title: 'Intermediate', ratePerHour: 0.77, cumulativeHoursRequired: 450 },
  // Advanced
  { level: 31, title: 'Advanced', ratePerHour: 0.85, cumulativeHoursRequired: 480 },
  { level: 32, title: 'Advanced', ratePerHour: 0.89, cumulativeHoursRequired: 510 },
  { level: 33, title: 'Advanced', ratePerHour: 0.93, cumulativeHoursRequired: 540 },
  { level: 34, title: 'Advanced', ratePerHour: 0.97, cumulativeHoursRequired: 570 },
  { level: 35, title: 'Advanced', ratePerHour: 1.01, cumulativeHoursRequired: 600 },
  { level: 36, title: 'Advanced', ratePerHour: 1.05, cumulativeHoursRequired: 630 },
  { level: 37, title: 'Advanced', ratePerHour: 1.09, cumulativeHoursRequired: 660 },
  { level: 38, title: 'Advanced', ratePerHour: 1.13, cumulativeHoursRequired: 690 },
  { level: 39, title: 'Advanced', ratePerHour: 1.17, cumulativeHoursRequired: 720 },
  { level: 40, title: 'Advanced', ratePerHour: 1.21, cumulativeHoursRequired: 750 },
  // Specialist
  { level: 41, title: 'Specialist', ratePerHour: 1.30, cumulativeHoursRequired: 780 },
  { level: 42, title: 'Specialist', ratePerHour: 1.35, cumulativeHoursRequired: 810 },
  { level: 43, title: 'Specialist', ratePerHour: 1.40, cumulativeHoursRequired: 840 },
  { level: 44, title: 'Specialist', ratePerHour: 1.45, cumulativeHoursRequired: 870 },
  { level: 45, title: 'Specialist', ratePerHour: 1.50, cumulativeHoursRequired: 900 },
  { level: 46, title: 'Specialist', ratePerHour: 1.55, cumulativeHoursRequired: 920 },
  { level: 47, title: 'Specialist', ratePerHour: 1.60, cumulativeHoursRequired: 940 },
  { level: 48, title: 'Specialist', ratePerHour: 1.65, cumulativeHoursRequired: 960 },
  { level: 49, title: 'Specialist', ratePerHour: 1.70, cumulativeHoursRequired: 980 },
  { level: 50, title: 'Specialist', ratePerHour: 1.75, cumulativeHoursRequired: 1000 },
  // Expert
  { level: 51, title: 'Expert', ratePerHour: 1.85, cumulativeHoursRequired: 1030 },
  { level: 52, title: 'Expert', ratePerHour: 1.91, cumulativeHoursRequired: 1060 },
  { level: 53, title: 'Expert', ratePerHour: 1.97, cumulativeHoursRequired: 1090 },
  { level: 54, title: 'Expert', ratePerHour: 2.03, cumulativeHoursRequired: 1120 },
  { level: 55, title: 'Expert', ratePerHour: 2.09, cumulativeHoursRequired: 1150 },
  { level: 56, title: 'Expert', ratePerHour: 2.15, cumulativeHoursRequired: 1180 },
  { level: 57, title: 'Expert', ratePerHour: 2.21, cumulativeHoursRequired: 1210 },
  { level: 58, title: 'Expert', ratePerHour: 2.27, cumulativeHoursRequired: 1240 },
  { level: 59, title: 'Expert', ratePerHour: 2.33, cumulativeHoursRequired: 1270 },
  { level: 60, title: 'Expert', ratePerHour: 2.39, cumulativeHoursRequired: 1300 },
  // Elite
  { level: 61, title: 'Elite', ratePerHour: 2.50, cumulativeHoursRequired: 1330 },
  { level: 62, title: 'Elite', ratePerHour: 2.58, cumulativeHoursRequired: 1360 },
  { level: 63, title: 'Elite', ratePerHour: 2.66, cumulativeHoursRequired: 1390 },
  { level: 64, title: 'Elite', ratePerHour: 2.74, cumulativeHoursRequired: 1420 },
  { level: 65, title: 'Elite', ratePerHour: 2.82, cumulativeHoursRequired: 1450 },
  { level: 66, title: 'Elite', ratePerHour: 2.90, cumulativeHoursRequired: 1480 },
  { level: 67, title: 'Elite', ratePerHour: 2.98, cumulativeHoursRequired: 1510 },
  { level: 68, title: 'Elite', ratePerHour: 3.06, cumulativeHoursRequired: 1540 },
  { level: 69, title: 'Elite', ratePerHour: 3.14, cumulativeHoursRequired: 1570 },
  { level: 70, title: 'Elite', ratePerHour: 3.22, cumulativeHoursRequired: 1600 },
  // Master
  { level: 71, title: 'Master', ratePerHour: 3.40, cumulativeHoursRequired: 1630 },
  { level: 72, title: 'Master', ratePerHour: 3.50, cumulativeHoursRequired: 1660 },
  { level: 73, title: 'Master', ratePerHour: 3.60, cumulativeHoursRequired: 1690 },
  { level: 74, title: 'Master', ratePerHour: 3.70, cumulativeHoursRequired: 1720 },
  { level: 75, title: 'Master', ratePerHour: 3.80, cumulativeHoursRequired: 1750 },
  { level: 76, title: 'Master', ratePerHour: 3.90, cumulativeHoursRequired: 1780 },
  { level: 77, title: 'Master', ratePerHour: 4.00, cumulativeHoursRequired: 1810 },
  { level: 78, title: 'Master', ratePerHour: 4.10, cumulativeHoursRequired: 1840 },
  { level: 79, title: 'Master', ratePerHour: 4.20, cumulativeHoursRequired: 1870 },
  { level: 80, title: 'Master', ratePerHour: 4.30, cumulativeHoursRequired: 1900 },
  // Grandmaster
  { level: 81, title: 'Grandmaster', ratePerHour: 4.50, cumulativeHoursRequired: 1930 },
  { level: 82, title: 'Grandmaster', ratePerHour: 4.65, cumulativeHoursRequired: 1960 },
  { level: 83, title: 'Grandmaster', ratePerHour: 4.80, cumulativeHoursRequired: 1990 },
  { level: 84, title: 'Grandmaster', ratePerHour: 4.95, cumulativeHoursRequired: 2020 },
  { level: 85, title: 'Grandmaster', ratePerHour: 5.10, cumulativeHoursRequired: 2050 },
  { level: 86, title: 'Grandmaster', ratePerHour: 5.25, cumulativeHoursRequired: 2080 },
  { level: 87, title: 'Grandmaster', ratePerHour: 5.40, cumulativeHoursRequired: 2110 },
  { level: 88, title: 'Grandmaster', ratePerHour: 5.55, cumulativeHoursRequired: 2140 },
  { level: 89, title: 'Grandmaster', ratePerHour: 5.70, cumulativeHoursRequired: 2170 },
  { level: 90, title: 'Grandmaster', ratePerHour: 5.85, cumulativeHoursRequired: 2200 },
  // Legendary
  { level: 91, title: 'Legendary', ratePerHour: 6.10, cumulativeHoursRequired: 2240 },
  { level: 92, title: 'Legendary', ratePerHour: 6.30, cumulativeHoursRequired: 2280 },
  { level: 93, title: 'Legendary', ratePerHour: 6.50, cumulativeHoursRequired: 2320 },
  { level: 94, title: 'Legendary', ratePerHour: 6.70, cumulativeHoursRequired: 2360 },
  { level: 95, title: 'Legendary', ratePerHour: 6.90, cumulativeHoursRequired: 2400 },
  { level: 96, title: 'Legendary', ratePerHour: 7.10, cumulativeHoursRequired: 2440 },
  { level: 97, title: 'Legendary', ratePerHour: 7.30, cumulativeHoursRequired: 2480 },
  { level: 98, title: 'Legendary', ratePerHour: 7.50, cumulativeHoursRequired: 2520 },
  { level: 99, title: 'Legendary', ratePerHour: 7.70, cumulativeHoursRequired: 2560 },
  // Ultimate
  { level: 100, title: 'Ultimate', ratePerHour: 8.00, cumulativeHoursRequired: 2600 },
];

/**
 * Finds the level data corresponding to a given level number.
 */
export const getLevelData = (level: number): LevelData => {
  return levelStructure[level] || levelStructure[levelStructure.length - 1]; // Default to max level if out of bounds
};

/**
 * Determines the current level based on total accumulated time in seconds.
 */
export const calculateLevelFromTime = (totalSeconds: number): LevelData => {
  const totalHours = totalSeconds / 3600;
  
  // Find the appropriate level by comparing with required hours
  for (let i = 1; i < levelStructure.length; i++) {
    const nextLevel = levelStructure[i + 1];
    const currentLevel = levelStructure[i];
    
    // If we're at the last level or if the next level requires more hours than we have,
    // this is our level
    if (!nextLevel || totalHours < nextLevel.cumulativeHoursRequired) {
      return currentLevel;
    }
  }
  
  return levelStructure[1]; // Default to level 1 if somehow less than 0 hours
};

/**
 * Calculates the progress towards the next level.
 * @returns progress (0-1), timeToNextLevel (seconds), nextLevelThreshold (seconds)
 */
export const calculateLevelProgress = (totalSeconds: number): { progress: number; timeToNextLevel: number; nextLevelThreshold: number } => {
  const currentLevelData = calculateLevelFromTime(totalSeconds);
  const nextLevelData = getLevelData(currentLevelData.level + 1);

  if (currentLevelData.level === nextLevelData.level) { // Reached max level
    return { progress: 1, timeToNextLevel: 0, nextLevelThreshold: currentLevelData.cumulativeHoursRequired * 3600 };
  }

  const currentLevelHours = currentLevelData.cumulativeHoursRequired;
  const nextLevelHours = nextLevelData.cumulativeHoursRequired;
  const hoursNeededForLevel = nextLevelHours - currentLevelHours;
  const hoursProgressedInLevel = (totalSeconds / 3600) - currentLevelHours;

  const progress = Math.max(0, Math.min(1, hoursProgressedInLevel / hoursNeededForLevel));
  const timeToNextLevel = Math.max(0, (nextLevelHours * 3600) - totalSeconds);
  const nextLevelThreshold = nextLevelHours * 3600;

  return {
    progress,
    timeToNextLevel,
    nextLevelThreshold // Store cumulative seconds required for next level
  };
};

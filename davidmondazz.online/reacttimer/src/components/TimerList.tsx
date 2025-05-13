import React, { useState, useEffect, useMemo, useRef, useCallback } from 'react';
import {
  Box,
  useToast,
  VStack,
  Text,
  SimpleGrid,
  Progress,
  Badge,
  Flex,
  Spinner,
  useColorModeValue,
  useDisclosure,
  Heading,
  Stat,
  StatLabel,
  StatNumber,
  StatHelpText,
  StatGroup,
  IconButton,
  Grid,
  Link,
  Button,
  Card,
  CardHeader,
  CardBody,
  CardFooter,
  HStack,
  Divider,
  Tooltip,
  Input,
  InputGroup,
  InputRightElement,
  ScaleFade,
  Center,
  Image,
  Icon
} from '@chakra-ui/react';
import { 
  FaTrash, 
  FaChevronRight, 
  FaPlus, 
  FaClock, 
  FaCoins, 
  FaChartLine,
  FaSearch,
  FaSort,
  FaBolt,
  FaThumbtack,
  FaChartBar,
  FaDollarSign,
  FaRegClock,
  FaStop
} from 'react-icons/fa';
import { keyframes as emotionKeyframes } from '@emotion/react';
import Timer from './Timer'; // Correct relative path assumed
import { TimerService } from '../services/firebase'; // Correct relative path assumed
import { TimerData, TimerSession } from '../types/index'; // Explicit import path
import DeleteConfirmationModal from './DeleteConfirmationModal'; // Correct relative path assumed
import { Link as RouterLink } from 'react-router-dom';
import { calculateLevelFromTime, calculateLevelProgress } from '../config/levels'; // Added import
import { useAudioSettings } from '../context/AudioSettingsContext'; // Added import

const timerService = new TimerService();

// Define animations (re-affirming definitions)
// Enhanced animations
const shine = emotionKeyframes`
  0% { background-position: 200% center; }
  100% { background-position: -200% center; }
`;

const float = emotionKeyframes`
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
`;

const pulse = emotionKeyframes`
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
`;

const glowPulse = emotionKeyframes`
  0%, 100% { opacity: 0.8; box-shadow: 0 0 10px rgba(72, 187, 120, 0.2); }
  50% { opacity: 1; box-shadow: 0 0 20px rgba(72, 187, 120, 0.4); }
`;

const shimmer = emotionKeyframes`
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
`;

const gradientShift = emotionKeyframes`
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
`;

interface TimerListProps {}

const TimerList: React.FC<TimerListProps> = (): JSX.Element => {
  const [timers, setTimers] = useState<TimerData[]>([]);
  const [allSessions, setAllSessions] = useState<TimerSession[]>([]);
  const [newTimerName, setNewTimerName] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [sortField, setSortField] = useState<'name' | 'level' | 'earnings'>('name');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [currentSessionEarnings, setCurrentSessionEarnings] = useState<Record<string, number>>({});
  const toast = useToast();
  const { isOpen: isDeleteModalOpen, onOpen: onDeleteModalOpen, onClose: onDeleteModalClose } = useDisclosure();
  const [timerToDelete, setTimerToDelete] = useState<{ id: string; name: string } | null>(null);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const { settings: audioSettings } = useAudioSettings(); // Consuming audio settings
  const [editIndex, setEditIndex] = useState<number | null>(null);
  const [editingName, setEditingName] = useState<string>("");
  const inputRefs = useRef<HTMLInputElement[]>([]);

  // Define handleLevelUp with useCallback before it's used in useEffect
  const handleLevelUp = useCallback((timerName: string, newLevel: number): void => {
    toast({
      title: 'Level Up!',
      description: `Congratulations! Your timer "${timerName}" reached level ${newLevel}!`,
      status: 'success',
      duration: 5000,
      isClosable: true,
      position: 'top',
      variant: 'solid',
    });
  }, [toast]);

  // Theme colors
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const activeBorderColor = useColorModeValue('green.400', 'green.500');
  const pinnedBorderColor = useColorModeValue('blue.300', 'blue.400');
  const linkColor = useColorModeValue('blue.600', 'blue.300');
  const cardBg = useColorModeValue('white', 'gray.800');
  const activeCardBg = useColorModeValue('green.50', 'green.900');
  const pinnedCardBg = useColorModeValue('blue.50', 'blue.900');
  const statCardBg = useColorModeValue('blue.50', 'blue.900');
  const subtleText = useColorModeValue('gray.600', 'gray.400');
  const hoverBg = useColorModeValue('gray.50', 'gray.700');
  const cardHeaderBg = useColorModeValue('gray.50', 'gray.800');
  const activeCardHeaderBg = useColorModeValue('green.100', 'green.800');
  const pinnedCardHeaderBg = useColorModeValue('blue.100', 'blue.800');
  const cardBorderColor = useColorModeValue('gray.200', 'gray.700');
  const cardHoverBorderColor = useColorModeValue('blue.300', 'blue.500');
  const footerBg = useColorModeValue('gray.50', 'gray.800');
  const activeFooterBg = useColorModeValue('green.50', 'green.900');
  const pinnedFooterBg = useColorModeValue('blue.50', 'blue.900');
  const earningsColor = useColorModeValue('green.600', 'green.300');
  const sessionEarningsColor = useColorModeValue('green.500', 'green.200');
  const progressBgColor = useColorModeValue('gray.100', 'gray.700');
  const pinnedIconColor = useColorModeValue('blue.500', 'blue.300');
  
  const hoverPinBg = useColorModeValue('whiteAlpha.800', 'blackAlpha.300');
  const hoverDeleteBg = useColorModeValue('red.50', 'rgba(254, 178, 178, 0.12)');
  const greenBg = useColorModeValue('green.50', 'green.900');
  const greenBorderColor = useColorModeValue('green.100', 'green.800');
  const greenTextColor = useColorModeValue('green.600', 'green.200');

  // New color variables for our updated UI components
  // Theme colors with enhanced gradients and glassmorphism
  const gradients = {
    active: useColorModeValue(
      'linear-gradient(135deg, rgba(72, 187, 120, 0.1) 0%, rgba(56, 161, 105, 0.2) 100%)',
      'linear-gradient(135deg, rgba(72, 187, 120, 0.2) 0%, rgba(56, 161, 105, 0.3) 100%)'
    ),
    pinned: useColorModeValue(
      'linear-gradient(135deg, rgba(66, 153, 225, 0.1) 0%, rgba(49, 130, 206, 0.2) 100%)',
      'linear-gradient(135deg, rgba(66, 153, 225, 0.2) 0%, rgba(49, 130, 206, 0.3) 100%)'
    ),
    default: useColorModeValue(
      'linear-gradient(135deg, rgba(226, 232, 240, 0.3) 0%, rgba(203, 213, 224, 0.2) 100%)',
      'linear-gradient(135deg, rgba(45, 55, 72, 0.3) 0%, rgba(26, 32, 44, 0.2) 100%)'
    ),
    earnings: useColorModeValue(
      'linear-gradient(120deg, #84fab0 0%, #8fd3f4 100%)',
      'linear-gradient(120deg, #4b7960 0%, #2c5282 100%)'
    )
  };
  
  const glassEffect = {
    light: 'blur(12px) saturate(180%)',
    dark: 'blur(12px) saturate(150%)'
  };
  
  const borders = {
    active: useColorModeValue('1px solid rgba(72, 187, 120, 0.3)', '1px solid rgba(72, 187, 120, 0.2)'),
    pinned: useColorModeValue('1px solid rgba(66, 153, 225, 0.3)', '1px solid rgba(66, 153, 225, 0.2)'),
    default: useColorModeValue('1px solid rgba(226, 232, 240, 0.5)', '1px solid rgba(45, 55, 72, 0.5)')
  };
  
  const boxBg = useColorModeValue("white", "gray.800");
  const addTimerCardBg = useColorModeValue("white", "gray.800");
  const addTimerCardBorderColor = useColorModeValue("gray.200", "gray.700");
  const addTimerSectionBg = useColorModeValue("gray.50", "gray.900");
  const addTimerSectionBorderColor = useColorModeValue("gray.100", "gray.700");
  const editableNameHoverBg = useColorModeValue("gray.100", "gray.700"); // Moved here
// Card variables defined at top level to avoid Rules of Hooks violation
  const cardBgActive = useColorModeValue("green.50", "rgba(44, 82, 60, 0.3)");
  const cardBgPinned = useColorModeValue("blue.50", "rgba(43, 88, 133, 0.3)");
  const cardBgDefault = useColorModeValue("white", "gray.750");
  const cardBorderActive = useColorModeValue("green.300", "green.600");
  const cardBorderPinned = useColorModeValue("blue.300", "blue.600");
  const cardBorderDefault = useColorModeValue("gray.200", "gray.700");
  const cardShadowActive = useColorModeValue("0 4px 10px -3px rgba(72, 187, 120, 0.3)", "0 0 12px 1px rgba(48, 140, 86, 0.3)");
  const cardShadowPinned = useColorModeValue("0 4px 10px -3px rgba(66, 153, 225, 0.3)", "0 0 12px 1px rgba(49, 130, 206, 0.3)");
  const cardHoverShadowActive = useColorModeValue("0 6px 14px -2px rgba(72, 187, 120, 0.4)", "0 0 18px 2px rgba(48, 140, 86, 0.4)");
  const cardHoverShadowPinned = useColorModeValue("0 6px 14px -2px rgba(66, 153, 225, 0.4)", "0 0 18px 2px rgba(49, 130, 206, 0.4)");
  const cardHoverBorderActive = useColorModeValue("green.400", "green.500");
  const cardHoverBorderPinned = useColorModeValue("blue.400", "blue.500");
  const cardHoverBorderDefault = useColorModeValue("gray.300", "gray.600");
  const topAccentActiveGradient = useColorModeValue('linear(to-r, green.400, green.500)', 'linear(to-r, green.500, green.600)');
  const topAccentPinnedGradient = useColorModeValue('linear(to-r, blue.400, blue.500)', 'linear(to-r, blue.500, blue.600)');
  const pinButtonHoverBgInactive = useColorModeValue("gray.100", "gray.600");
  const deleteButtonHoverBg = useColorModeValue("red.50", "red.900");
  const sessionBoxBg = useColorModeValue("green.100", "rgba(48, 140, 86, 0.2)");
  const sessionTextColor = useColorModeValue("green.700", "green.200");
  const statIconColorDefault = useColorModeValue("gray.500", "gray.400");

  // Add activeTimers as a useMemo computed property
  const activeTimers = useMemo((): TimerData[] => {
    return timers.filter(timer => timer.isActive);
  }, [timers]);

  // Calculate true total time for each timer based on all its sessions
  const timerTrueTotalTimes = useMemo(() => {
    const totals: Record<string, number> = {};
    timers.forEach(timer => {
      totals[timer.id] = 0; // Initialize
    });
    allSessions.forEach(session => {
      if (totals[session.timerId] !== undefined) {
        totals[session.timerId] += session.duration;
      }
    });
    return totals;
  }, [timers, allSessions]);

  // Calculate active session earnings at regular intervals
  useEffect(() => {
    // Function to update session earnings for display without writing to DB
    const updateSessionEarnings = (): void => {
      const now = Date.now();
      
      // Get the combined rate from the combined level data
      const combinedRate = combinedLevelData.ratePerHour;
      
      // Update earnings for active timers
      const earnings = activeTimers.reduce<Record<string, number>>((acc: Record<string, number>, timer: TimerData) => {
        if (timer.isActive && timer.lastStartTime > 0) {
          const sessionElapsed = (now - timer.lastStartTime) / 1000;
          acc[timer.id] = (sessionElapsed / 3600) * combinedRate;
        }
        return acc;
      }, {});
      
      setCurrentSessionEarnings(earnings);
    };
    
    // Function to update timer data in the database
    const updateActiveTimers = async (): Promise<void> => {
      // This function now ONLY updates the database.
      // State updates are handled by the Firebase listener.
      const now = Date.now();
      // Always use normal speed (1x) - ignore any speed settings
      const timeIncrement = 1;
      
      // Calculate combined level data once for all timers
      const totalSeconds = timers.reduce((sum: number, timer: TimerData) => sum + (timer.totalTime || 0), 0) + 
                           (activeTimers.length * timeIncrement); // Add the increment we're about to apply
      const newCombinedLevelData = calculateLevelFromTime(totalSeconds);
      const newCombinedProgressData = calculateLevelProgress(totalSeconds);

      await Promise.all(timers.map(async (timer: TimerData) => {
        if (timer.isActive && timer.lastStartTime > 0) {
          // Increment totalTime by 1 second (interval is 1000ms)
          const newTotalTime = timer.totalTime + timeIncrement; 
          
          // Use the combined level data for earnings calculation
          const newEarnings = timer.earnings + (timeIncrement / 3600) * (newCombinedLevelData.ratePerHour || 0);

          const oldLevel = timer.level;
          
          // Calculate level data using the individual timer's total time
          const levelData = calculateLevelFromTime(newTotalTime);
          const progressData = calculateLevelProgress(newTotalTime);
          
          const updatedTimerFields: Partial<TimerData> = {
            totalTime: newTotalTime,
            earnings: timer.earnings + (timeIncrement / 3600) * levelData.ratePerHour,
            level: levelData.level,
            levelTitle: levelData.title,
            currentRate: levelData.ratePerHour,
            currentLevelProgress: progressData.progress,
            nextLevelThreshold: progressData.nextLevelThreshold,
          };

          if (levelData.level > oldLevel) {
            handleLevelUp(timer.name, levelData.level);
          }
          
          // Persist to DB
          try {
            await timerService.updateTimer(timer.id, updatedTimerFields);
          } catch (error) {
            console.error("Error updating timer in interval:", error);
            // Optionally, show a toast or handle error
          }
        }
      }));
    };
    
    // Set up the intervals
    let sessionIntervalId: NodeJS.Timeout | null = null;
    let dbUpdateIntervalId: NodeJS.Timeout | null = null;
    
    // Only set up the intervals if we have active timers
    if (activeTimers.length > 0) {
      // Update session earnings display less frequently to reduce re-renders
      sessionIntervalId = setInterval(updateSessionEarnings, 500); // Changed from 100ms to 500ms
      
      // Update database less frequently (every second)
      dbUpdateIntervalId = setInterval(updateActiveTimers, 1000);
      
      // Initial update
      updateSessionEarnings();
      updateActiveTimers();
    } else {
      // Reset current session earnings when no timers are active
      setCurrentSessionEarnings({});
    }
    
    return () => {
      if (sessionIntervalId) clearInterval(sessionIntervalId);
      if (dbUpdateIntervalId) clearInterval(dbUpdateIntervalId);
    };
  }, [timers, activeTimers, handleLevelUp]);

  const totalEarnings = useMemo((): number => {
    return timers.reduce((sum: number, timer: TimerData) => sum + (timer.earnings || 0), 0);
  }, [timers]);

  const totalHours = useMemo((): number => {
    const totalSeconds = timers.reduce((sum: number, timer: TimerData) => sum + (timer.totalTime || 0), 0);
    return totalSeconds / 3600;
  }, [timers]);

  // Calculate the combined level based on total time across all timers
  const combinedLevelData = useMemo(() => {
    const totalSeconds = timers.reduce((sum: number, timer: TimerData) => sum + (timer.totalTime || 0), 0);
    return calculateLevelFromTime(totalSeconds);
  }, [timers]);

  // Calculate progress towards next level based on combined time
  const combinedLevelProgress = useMemo(() => {
    const totalSeconds = timers.reduce((sum: number, timer: TimerData) => sum + (timer.totalTime || 0), 0);
    return calculateLevelProgress(totalSeconds);
  }, [timers]);

  const averageHourlyRate = useMemo((): number => {
    if (totalHours === 0) return 0;
    // Calculate based on actual earnings and time data
    return totalEarnings / totalHours;
  }, [totalEarnings, totalHours]);

  // Calculate total current earnings across all active timers
  const totalCurrentEarnings = useMemo((): number => {
    return Object.values(currentSessionEarnings).reduce((sum: number, earnings: number) => sum + earnings, 0);
  }, [currentSessionEarnings]);

  // Check if any timer is currently active
  const hasActiveTimers = useMemo((): boolean => {
    return timers.some(timer => timer.isActive);
  }, [timers]);

  // Get the appropriate visual styles for earnings display based on active state
  const earningsStyles = useMemo(() => {
    return {
      bgGradient: hasActiveTimers 
        ? "linear(135deg, #84fab0 0%, #8fd3f4 100%)"
        : "linear(135deg, #CBD5E0 0%, #A0AEC0 100%)", // Darker gradient for inactive
      darkBgGradient: hasActiveTimers
        ? "linear(135deg, #4b7960 0%, #2c5282 100%)"
        : "linear(135deg, #4A5568 0%, #2D3748 100%)", // Darker gradient for inactive
      iconColor: hasActiveTimers ? "yellow.500" : "gray.600", // Slightly darker
      darkIconColor: hasActiveTimers ? "yellow.300" : "gray.500", // Slightly darker
      textColor: hasActiveTimers ? "gray.800" : "gray.700", // Darker text for better visibility
      darkTextColor: hasActiveTimers ? "white" : "gray.300", // Lighter text for dark mode
      animation: hasActiveTimers ? `${glowPulse} 2s ease-in-out infinite` : "none"
    };
  }, [hasActiveTimers]);

  useEffect(() => {
    setIsLoading(true); // Set loading true at the start

    let timerUnsubscribe: (() => void) | null = null;
    let sessionUnsubscribe: (() => void) | null = null;

    const loadInitialData = async () => {
      try {
        const [initialLoadedTimers, loadedSessions] = await Promise.all([
          timerService.getTimers(),
          timerService.getTimerSessions()
        ]);

        // Reconcile timer totalTime with session data
        const reconciliationPromises = initialLoadedTimers.map(async (timer) => {
          const sessionsForTimer = loadedSessions.filter(s => s.timerId === timer.id);
          const totalTimeFromSessions = sessionsForTimer.reduce((sum, s) => sum + s.duration, 0);

          // If discrepancy is more than 60 seconds, update the timer in DB
          if (Math.abs((timer.totalTime || 0) - totalTimeFromSessions) > 60) {
            console.warn(`Reconciling totalTime for timer ${timer.name} (${timer.id}). DB: ${timer.totalTime}, Sessions: ${totalTimeFromSessions}`);
            const newLevelData = calculateLevelFromTime(totalTimeFromSessions);
            const newProgressData = calculateLevelProgress(totalTimeFromSessions);
            
            const updatedFields: Partial<TimerData> = {
              totalTime: totalTimeFromSessions,
              level: newLevelData.level,
              levelTitle: newLevelData.title,
              currentRate: newLevelData.ratePerHour,
              currentLevelProgress: newProgressData.progress,
              nextLevelThreshold: newProgressData.nextLevelThreshold,
              // Note: Earnings are not recalculated here, assuming they are a separate accumulator.
            };
            try {
              await timerService.updateTimer(timer.id, updatedFields);
            } catch (updateError) {
              console.error(`Error reconciling timer ${timer.id}:`, updateError);
            }
          }
        });

        await Promise.all(reconciliationPromises);
        
        // Set initial sessions, timers will be set by subscription after reconciliation
        setAllSessions(loadedSessions);

      } catch (error) {
        toast({
          title: 'Error loading initial data',
          description: 'Could not load your timers or sessions. Please try again.',
          status: 'error',
          duration: 5000,
          isClosable: true,
        });
      } finally {
        // Subscriptions will handle setting timers and isLoading
      }
    };

    loadInitialData().then(() => {
      // Subscribe to timer updates AFTER potential reconciliation
      timerUnsubscribe = timerService.subscribeToTimers((updatedTimers) => {
        setTimers(updatedTimers);
        setIsLoading(false); // Set loading false when timers are loaded/updated
      });

      // Subscribe to session updates
      sessionUnsubscribe = timerService.subscribeToSessions((updatedSessions) => {
        // No need to setAllSessions here again if already set in loadInitialData,
        // but good for real-time updates if sessions can change independently.
        setAllSessions(updatedSessions); 
      });
    });

    // Cleanup function to unsubscribe when the component unmounts
    return () => {
      if (timerUnsubscribe) {
        timerUnsubscribe();
      }
      if (sessionUnsubscribe) {
        sessionUnsubscribe();
      }
    };
  }, [toast]); // timerService is stable, so not needed in deps.

  useEffect(() => {
    if (editIndex !== null && inputRefs.current[editIndex]) {
      inputRefs.current[editIndex]?.focus();
      inputRefs.current[editIndex]?.select();
    }
  }, [editIndex]);

  const handleTogglePin = async (timer: TimerData): Promise<void> => {
    try {
      const newPinStatus: boolean = await timerService.togglePinTimer(timer.id);
      toast({
        title: newPinStatus ? 'Timer Pinned' : 'Timer Unpinned',
        description: `"${timer.name}" has been ${newPinStatus ? 'pinned to' : 'unpinned from'} the top`,
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not update pin status. Please try again.',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
      console.error("Error toggling pin status:", error);
    }
  };

  const handleStopTimer = async (timer: TimerData): Promise<void> => {
    try {
      // Calculate final accumulated time
      let finalAccumulatedTime: number = timer.accumulatedTime || 0;
      let sessionTime: number = 0;
      
      if (timer.isActive && timer.lastStartTime > 0) {
        sessionTime = (Date.now() - timer.lastStartTime) / 1000;
        finalAccumulatedTime = sessionTime + (timer.accumulatedTime || 0);
      }
      
      // Calculate the new total time by adding this session
      const updatedTotalTime = timer.totalTime + sessionTime;
      
      // First update the timer record with the new totalTime
      // This is important to ensure totalTime is never lost and always accumulates
      await timerService.updateTimer(timer.id, {
        totalTime: updatedTotalTime
      });
      
      // Then stop the timer
      const result = await timerService.stopTimerInDb(timer.id, finalAccumulatedTime);
      
      // Double-check that totalTime was not reset by reading timer again
      const updatedTimer = timers.find(t => t.id === timer.id);
      if (updatedTimer && updatedTimer.totalTime < updatedTotalTime) {
        // If somehow totalTime was reduced, fix it immediately
        await timerService.updateTimer(timer.id, {
          totalTime: updatedTotalTime
        });
      }
      
      if (result && result.newLevel > result.oldLevel) {
        handleLevelUp(result.timerName, result.newLevel);
      }
      
      toast({
        title: 'Timer Stopped',
        description: `"${timer.name}" has been stopped`,
        status: 'info',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not stop the timer. Please try again.',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
      console.error("Error stopping timer:", error);
    }
  };

  const handleAttemptDelete = (timer: TimerData): void => {
    setTimerToDelete({ id: timer.id, name: timer.name });
    onDeleteModalOpen();
  };

  const handleConfirmDelete = async (): Promise<void> => {
    if (!timerToDelete) return;
    try {
      await timerService.deleteTimer(timerToDelete.id);
      setTimers(timers.filter(t => t.id !== timerToDelete.id)); // Update state locally
      toast({
        title: 'Timer deleted',
        description: `"${timerToDelete.name}" has been removed`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
      setTimerToDelete(null);
      onDeleteModalClose();
    } catch (error) {
      toast({
        title: 'Error deleting timer',
        description: 'Something went wrong. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
      console.error("Error confirming deletion:", error);
    }
  };

  const handleAddTimer = async (): Promise<void> => {
    const trimmedName: string = newTimerName.trim();
    if (!trimmedName) {
      toast({
        title: 'Timer name required',
        description: 'Please enter a name for your timer',
        status: 'warning',
        duration: 3000,
        isClosable: true,
      });
      return;
    }

    try {
      // Provide all necessary fields for a new timer
      await timerService.createTimer({
        name: trimmedName,
        totalTime: 0,
        earnings: 0,
        isActive: false,
        lastStartTime: 0,
        level: 1, // Ensure level is provided, service might have defaults but being explicit is safer
        isPinned: false // Explicitly set isPinned for new timers
      });
      setNewTimerName('');
      toast({
        title: 'Timer created',
        description: `"${trimmedName}" has been added`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error creating timer',
        description: 'Something went wrong. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
      console.error("Error adding timer:", error);
    }
  };

  const handleEditingNameChange = (newName: string): void => {
    setEditingName(newName);
  };

  const handleNameUpdate = async (timerId: string, currentTimerIndex: number): Promise<void> => {
    if (editIndex === null || !timers[currentTimerIndex] || timers[currentTimerIndex].name === editingName.trim()) {
      setEditIndex(null);
      return;
    }
    const finalName = editingName.trim();
    if (!finalName) {
      toast({
        title: "Timer name cannot be empty.",
        status: "warning",
        duration: 2000,
        isClosable: true,
      });
      // Optionally revert to old name or keep edit mode
      setEditingName(timers[currentTimerIndex].name); // Revert if empty
      // setEditIndex(null); // Or close edit mode
      return;
    }

    try {
      await timerService.updateTimer(timerId, { name: finalName });
      toast({
        title: "Timer Renamed",
        description: `Timer successfully renamed to "${finalName}".`,
        status: "success",
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: "Error Renaming Timer",
        description: "Could not update timer name.",
        status: "error",
        duration: 3000,
        isClosable: true,
      });
      console.error("Error updating timer name:", error);
      setEditingName(timers[currentTimerIndex].name); // Revert on error
    }
    setEditIndex(null);
  };

  const handleSortChange = (field: 'name' | 'level' | 'earnings'): void => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };

  const filteredAndSortedTimers = useMemo((): TimerData[] => {
    // First filter
    let result = timers;
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      result = result.filter(timer => 
        timer.name.toLowerCase().includes(query) || 
        (timer.levelTitle && timer.levelTitle.toLowerCase().includes(query))
      );
    }
    
    // Then sort - preserve pin status as primary sort
    return [...result].sort((a, b) => {
      // Use nullish coalescing for safety
      const aIsPinned = a.isPinned ?? false;
      const bIsPinned = b.isPinned ?? false;
      
      // Always keep pinned items at the top regardless of other sorting
      if (aIsPinned && !bIsPinned) return -1;
      if (!aIsPinned && bIsPinned) return 1;
      
      // If both have same pin status, sort by the selected field
      let comparison = 0;
      
      if (sortField === 'name') {
        comparison = a.name.localeCompare(b.name);
      } else if (sortField === 'level') {
        comparison = (a.level || 0) - (b.level || 0);
      } else if (sortField === 'earnings') {
        comparison = (a.earnings || 0) - (b.earnings || 0);
      }
      
      return sortDirection === 'asc' ? comparison : -comparison;
    });
  }, [timers, searchQuery, sortField, sortDirection]);

  if (isLoading) {
    return (
      <Center minH="300px">
        <VStack spacing={4}>
          <Spinner size="xl" color="blue.500" thickness="4px" speed="0.65s" />
          <Text color={subtleText}>Loading your timers...</Text>
        </VStack>
      </Center>
    );
  }

  return (
    <>
      <VStack spacing={8} width="100%">
        {/* Always show the earnings card - no conditional wrapper */}
        <ScaleFade in={true} initialScale={0.95}>
          <Box
            width="100%"
            position="relative"
            borderRadius="xl"
            overflow="hidden"
            boxShadow={hasActiveTimers ? "xl" : "md"}
            transition="all 0.3s ease"
            _hover={{ transform: "translateY(-4px)", boxShadow: "2xl" }}
            bg="transparent"
            borderWidth={hasActiveTimers ? "0px" : "1px"}
            borderColor={useColorModeValue("gray.300", "gray.600")}
          >
            {/* Animated gradient background with blur effect */}
            <Box
              position="absolute"
              top="0"
              right="0"
              bottom="0"
              left="0"
              bgGradient={earningsStyles.bgGradient}
              _dark={{
                bgGradient: earningsStyles.darkBgGradient
              }}
              animation={hasActiveTimers ? `${gradientShift} 8s ease infinite` : "none"}
              opacity="0.9"
              borderRadius="xl"
            />
            
            {/* Content with improved layout */}
            <Grid
              position="relative"
              p={{ base: 6, md: 8 }}
              zIndex="1"
              templateColumns={{ base: "1fr", md: "1fr 1fr" }}
              gap={4}
            >
              {/* Main earnings display */}
              <Box>
                <HStack spacing={2} mb={2}>
                  <Icon 
                    as={FaCoins} 
                    color={earningsStyles.iconColor}
                    w={5} 
                    h={5}
                    animation={earningsStyles.animation}
                    _dark={{ color: earningsStyles.darkIconColor }} 
                  />
                  <Text 
                    fontSize="sm" 
                    fontWeight="bold" 
                    color={earningsStyles.textColor}
                    textTransform="uppercase"
                    letterSpacing="wider"
                    _dark={{ color: earningsStyles.darkTextColor }}
                  >
                    CURRENTLY EARNING
                  </Text>
                </HStack>
                
                <Box 
                  position="relative" 
                  display="inline-block"
                >
                  <Text 
                    fontSize={{ base: "4xl", md: "5xl", lg: "6xl" }} 
                    fontWeight="bold" 
                    color={earningsStyles.textColor}
                    lineHeight="1"
                    _dark={{ color: earningsStyles.darkTextColor }}
                    animation={hasActiveTimers ? `${glowPulse} 4s ease-in-out infinite 1s` : "none"}
                    opacity={hasActiveTimers ? 1 : 0.9}
                    letterSpacing={hasActiveTimers ? "normal" : "tight"}
                  >
                    ${totalCurrentEarnings.toFixed(2)}
                    {!hasActiveTimers && (
                      <Text as="span" fontSize="md" fontWeight="normal" ml={2} opacity={0.7}>
                        per hour
                      </Text>
                    )}
                  </Text>
                  <Box
                    position="absolute"
                    bottom="-4px"
                    left="0"
                    right="0"
                    height="3px"
                    bg={hasActiveTimers ? "green.500" : "gray.300"}
                    borderRadius="full"
                    _dark={{
                      bg: hasActiveTimers ? "green.400" : "gray.600",
                      backgroundImage: hasActiveTimers 
                        ? "linear-gradient(to right, transparent, #68D391, transparent, transparent)"
                        : "linear-gradient(to right, transparent, #A0AEC0, transparent, transparent)"
                    }}
                    animation={hasActiveTimers ? `${shimmer} 3s linear infinite` : "none"}
                    backgroundSize="400% 100%"
                    backgroundImage={hasActiveTimers
                      ? "linear-gradient(to right, transparent, #48BB78, transparent, transparent)"
                      : "linear-gradient(to right, transparent, #A0AEC0, transparent, transparent)"}
                  />
                </Box>
                
                <HStack spacing={1} mt={3}>
                  <Text
                    fontSize="sm"
                    fontWeight="medium"
                    color={hasActiveTimers ? "gray.700" : "gray.500"}
                    _dark={{ color: hasActiveTimers ? "gray.300" : "gray.500" }}
                  >
                    {hasActiveTimers ? "from" : "no active timers"}
                  </Text>
                  {hasActiveTimers ? (
                    <Badge
                      colorScheme="green"
                      fontSize="sm"
                      py={1}
                      px={2}
                      borderRadius="md"
                      animation={`${pulse} 3s infinite ease-in-out`}
                    >
                      {activeTimers.length} active {activeTimers.length === 1 ? 'timer' : 'timers'}
                    </Badge>
                  ) : (
                    <Badge
                      colorScheme="gray"
                      fontSize="sm"
                      py={1}
                      px={2}
                      borderRadius="md"
                    >
                      Inactive
                    </Badge>
                  )}
                </HStack>
              </Box>
              
              {/* Active Timers List - Only show when there are active timers */}
              {activeTimers.length > 0 && (
                <Flex
                  flexDirection="column"
                  align="stretch"
                  gap={2}
                  maxH="200px"
                  overflowY="auto"
                  pr={2}
                  css={{
                    '&::-webkit-scrollbar': {
                      width: '4px',
                    },
                    '&::-webkit-scrollbar-track': {
                      width: '6px',
                      background: 'rgba(255,255,255,0.1)',
                    },
                    '&::-webkit-scrollbar-thumb': {
                      background: 'rgba(255,255,255,0.5)',
                      borderRadius: '24px',
                    },
                  }}
                >
                  <Text
                    fontSize="xs"
                    fontWeight="bold"
                    color="gray.600"
                    textTransform="uppercase"
                    letterSpacing="wider"
                    mb={1}
                    _dark={{ color: "gray.400" }}
                  >
                    Active Timers
                  </Text>
                  
                  <Flex
                    flexDirection="row"
                    flexWrap="wrap"
                    gap={2}
                    overflowX="auto"
                    pb={2}
                    css={{
                      '&::-webkit-scrollbar': {
                        height: '4px',
                      },
                      '&::-webkit-scrollbar-track': {
                        height: '6px',
                        background: 'rgba(255,255,255,0.1)',
                      },
                      '&::-webkit-scrollbar-thumb': {
                        background: 'rgba(255,255,255,0.5)',
                        borderRadius: '24px',
                      },
                    }}
                  >
                  {activeTimers.map(timer => {
                    const sessionEarnings = currentSessionEarnings[timer.id] || 0;
                    const sessionSeconds = sessionEarnings / (timer.currentRate || 0.01) * 3600;
                    const hours = Math.floor(sessionSeconds / 3600);
                    const minutes = Math.floor((sessionSeconds % 3600) / 60);
                    const seconds = Math.floor(sessionSeconds % 60);
                    
                    return (
                      <Box
                        key={timer.id}
                        p={1.5}
                        borderRadius="md"
                        bg="whiteAlpha.300"
                        backdropFilter="blur(12px)"
                        borderWidth="1px"
                        borderColor="whiteAlpha.300"
                        transition="all 0.2s"
                        minWidth="140px"
                        flexShrink={0}
                        _hover={{
                          bg: "whiteAlpha.400",
                          transform: "translateY(-2px)",
                          boxShadow: "sm"
                        }}
                      >
                        <VStack align="start" spacing={0.5}>
                          <Text fontWeight="bold" fontSize="xs" noOfLines={1}>{timer.name}</Text>
                          <Text fontSize="xx-small" color="gray.600" _dark={{ color: "gray.300" }}>
                            {hours > 0 ? `${hours}h ` : ''}{minutes > 0 ? `${minutes}m ` : ''}{seconds}s
                          </Text>
                          <HStack 
                            width="100%" 
                            justify="space-between" 
                            align="center" 
                            mt={0.5}
                          >
                            <Text 
                              fontSize="xs"
                              fontWeight="bold" 
                              color={sessionTextColor}
                            >
                              ${sessionEarnings.toFixed(2)}
                            </Text>
                            <IconButton
                              icon={<FaStop />}
                              size="xs"
                              aria-label="Stop timer"
                              colorScheme="red"
                              variant="ghost"
                              minW="auto"
                              h="auto"
                              p={1}
                              _hover={{
                                bg: "red.100",
                                _dark: { bg: "red.900" }
                              }}
                              onClick={(e) => {
                                e.stopPropagation();
                                handleStopTimer(timer);
                              }}
                            />
                          </HStack>
                        </VStack>
                      </Box>
                    );
                  })}
                  </Flex>
                </Flex>
              )}
            </Grid>
          </Box>
        </ScaleFade>

        {/* Add Timer and Search Section */}
        <Card 
          width="100%" 
          boxShadow="md" 
          borderRadius="xl"
          bg={addTimerCardBg}
          borderWidth="1px"
          borderColor={addTimerCardBorderColor}
          overflow="hidden"
          transition="all 0.3s"
          _hover={{ boxShadow: "lg", transform: "translateY(-2px)" }}
        >
          <CardBody py={5}>
            <Flex 
              direction={{ base: "column", md: "row" }} 
              gap={5} 
              width="100%"
              justify="space-between"
              align="center"
            >
              {/* Add Timer Section */}
              <Flex 
                flex={{ base: "1", md: "2" }} 
                width="100%" 
                position="relative"
                bg={addTimerSectionBg}
                p={4}
                borderRadius="lg"
                borderWidth="1px"
                borderColor={addTimerSectionBorderColor}
              >
                <InputGroup size="md">
                  <Input
                    placeholder="Create a new timer..."
                    value={newTimerName}
                    onChange={(e) => setNewTimerName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && handleAddTimer()}
                    borderRadius="md"
                    pr="4.5rem"
                    fontWeight="medium"
                    _focus={{
                      boxShadow: "0 0 0 1px blue.300",
                      borderColor: "blue.300"
                    }}
                    _hover={{
                      borderColor: "blue.200"
                    }}
                  />
                  <InputRightElement width="4.5rem">
                    <Button
                      h="1.75rem"
                      size="sm"
                      colorScheme="blue"
                      onClick={handleAddTimer}
                      isDisabled={!newTimerName.trim()}
                      leftIcon={<FaPlus size="0.75rem" />}
                      borderRadius="md"
                      variant="solid"
                    >
                      Add
                    </Button>
                  </InputRightElement>
                </InputGroup>
              </Flex>

              {/* Search and Filter Options */}
              <Flex 
                flex="1" 
                width="100%" 
                gap={4} 
                justifyContent="flex-end"
                alignItems="center"
                flexWrap={{ base: "wrap", md: "nowrap" }}
              >
                <InputGroup maxW={{ base: "100%", md: "220px" }}>
                  <Input
                    placeholder="Search timers..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    borderRadius="md"
                    _focus={{
                      boxShadow: "0 0 0 1px blue.300",
                      borderColor: "blue.300"
                    }}
                    _hover={{
                      borderColor: "blue.200"
                    }}
                  />
                  <InputRightElement>
                    <Icon as={FaSearch} color="gray.400" />
                  </InputRightElement>
                </InputGroup>

                <Tooltip label={`Sort by ${sortField} (${sortDirection === 'asc' ? 'ascending' : 'descending'})`}>
                  <Button
                    rightIcon={<FaSort />}
                    variant="outline"
                    size="md"
                    onClick={() => handleSortChange(sortField)}
                    colorScheme="purple"
                    fontWeight="medium"
                    minW="120px"
                    justifyContent="space-between"
                    borderRadius="md"
                  >
                    {sortField === 'name' ? 'Name' : 
                     sortField === 'level' ? 'Level' : 'Earnings'}
                  </Button>
                </Tooltip>
              </Flex>
            </Flex>
          </CardBody>
        </Card>

        {/* Timers Grid */}
        {filteredAndSortedTimers.length > 0 ? (
        <SimpleGrid columns={{ base: 1, md: 2, lg: 3 }} spacing={6} width="100%">
            {filteredAndSortedTimers.map((timer, index) => {
              const isPinned = timer.isPinned ?? false;
              const isActive = timer.isActive ?? false;
              const currentTimerIndex = timers.findIndex(t => t.id === timer.id); // To handle updates correctly
              // const editableNameHoverBg = useColorModeValue("gray.100", "gray.700"); // Moved to top level

              // const editableNameHoverBg = useColorModeValue("gray.100", "gray.700"); // Moved to top level

              const currentCardBg = isActive ? cardBgActive : isPinned ? cardBgPinned : cardBgDefault;
              const currentCardBorder = isActive ? cardBorderActive : isPinned ? cardBorderPinned : cardBorderDefault;
              const currentCardShadow = isActive ? cardShadowActive : isPinned ? cardShadowPinned : "sm";
              const currentCardHoverShadow = isActive ? cardHoverShadowActive : isPinned ? cardHoverShadowPinned : "md";
              const currentCardHoverBorder = isActive ? cardHoverBorderActive : isPinned ? cardHoverBorderPinned : cardHoverBorderDefault;
              const currentTopAccentGradient = isActive ? topAccentActiveGradient : isPinned ? topAccentPinnedGradient : undefined;
              const currentPinButtonHoverBg = isPinned ? "blue.500" : pinButtonHoverBgInactive;
              
              return (
              <ScaleFade in={true} key={timer.id}>
                <Card
                  height="100%"
                  borderRadius="md" // Sharper corners
                  bg={currentCardBg}
                  borderWidth="1px"
                  borderColor={currentCardBorder}
                  boxShadow={currentCardShadow}
                  overflow="hidden"
                  transition="all 0.25s ease-out" // Slightly faster transition
                  position="relative"
                  _hover={{
                    boxShadow: currentCardHoverShadow,
                    borderColor: currentCardHoverBorder,
                  }}
                   _before={currentTopAccentGradient ? { // Re-introduce top accent bar
                      content: '""',
                      position: 'absolute',
                      top: 0, left: 0, right: 0, height: '3px', // Thinner accent
                      bgGradient: currentTopAccentGradient,
                    } : undefined // No accent for default state
                  }
                >
                  <VStack spacing={3} p={4} align="stretch" height="100%"> {/* Main content stack */}
                    {/* Top Section: Action Buttons (will be on the right of Timer name in Timer.tsx) */}
                    {/* Timer.tsx already renders the name. We just need space for actions if decided to put here */}
                    {/* For now, action buttons are inside CardHeader. We will move them */}
                    <Flex
                      position="absolute"
                      top={3}
                      right={3}
                      gap={2}
                      zIndex={2}
                    >
                      <Tooltip label={isPinned ? "Unpin timer" : "Pin timer to top"}>
                        <IconButton
                          icon={<FaThumbtack />}
                          size="sm"
                          aria-label={isPinned ? "Unpin timer" : "Pin timer"}
                          variant="ghost"
                          colorScheme={isPinned ? "blue" : "gray"}
                          opacity={0.7}
                          transform={isPinned ? 'rotate(-45deg)' : 'none'}
                          _hover={{
                            opacity: 1,
                            transform: isPinned ? 'rotate(-45deg) scale(1.1)' : 'scale(1.1)',
                            bg: hoverPinBg
                          }}
                          onClick={(e) => {
                            e.stopPropagation();
                            handleTogglePin(timer);
                          }}
                          transition="all 0.2s cubic-bezier(0.4, 0, 0.2, 1)"
                        />
                      </Tooltip>
                      <Tooltip label="Delete timer">
                        <IconButton
                          icon={<FaTrash />}
                          size="sm"
                          aria-label="Delete timer"
                          variant="ghost"
                          colorScheme="red"
                          opacity={0.7}
                          _hover={{
                            opacity: 1,
                            transform: 'scale(1.1)',
                            bg: hoverDeleteBg
                          }}
                          onClick={(e) => {
                            e.stopPropagation();
                            handleAttemptDelete(timer);
                          }}
                          transition="all 0.2s cubic-bezier(0.4, 0, 0.2, 1)"
                        />
                      </Tooltip>
                    </Flex>

                    {/* Timer Component */}
                    <Box flexGrow={1}> {/* Allow Timer to take available space */}
                        <Timer 
                          timer={timer}
                          onLevelUp={handleLevelUp}
                        />
                    </Box>

                    {/* Progress Bar */}
                    <Box px={0} mb={2}> {/* Removed horizontal padding, increased bottom margin */}
                        <Flex justify="flex-end" mb={0.5} px={1}> {/* Added padding here for the percentage */}
                            <Text fontSize="xx-small" color={subtleText} fontWeight="medium">
                                {Math.round((timer.currentLevelProgress || 0) * 100)}%
                            </Text>
                        </Flex>
                        <Progress
                            value={(timer.currentLevelProgress || 0) * 100}
                            size="sm" // Changed from xs
                            colorScheme={isActive ? "green" : isPinned ? "blue" : "gray"}
                            borderRadius="sm"
                            // bg={progressBgColor} // Using default background
                            hasStripe={isActive}
                            isAnimated={isActive}
                            sx={{ // Ensure progress bar itself doesn't have side margins/padding
                              '> div': { transition: 'width 0.3s ease-out' }
                            }}
                        />
                    </Box>
                    
                    {/* Stats Section */}
                    {/* Active Session Earnings */}
                    {isActive && currentSessionEarnings[timer.id] > 0 && (
                      <Box
                        width="100%"
                        p={2}
                        borderRadius="lg"
                        bg={greenBg}
                        border="1px solid"
                        borderColor={greenBorderColor}
                        animation={`${float} 3s ease-in-out infinite`}
                      >
                        <HStack justify="center" spacing={2}>
                          <Icon as={FaCoins} color="green.400" />
                          <Text
                            fontSize="sm"
                            fontWeight="semibold"
                            color={greenTextColor}
                          >
                            +${currentSessionEarnings[timer.id].toFixed(2)} this session
                          </Text>
                        </HStack>
                      </Box>
                    )}

                    {/* Use pt={2} for slightly more space above stats grid */}
                    {/* Enhanced Stats Grid */}
                    <SimpleGrid columns={2} spacing={4} width="100%">
                      <Stat size="sm">
                        <StatLabel fontSize="xs" color="gray.500">
                          <HStack spacing={1}>
                            <Icon as={FaDollarSign} boxSize={3} />
                            <Text>Rate</Text>
                          </HStack>
                        </StatLabel>
                        <StatNumber color={isActive ? 'green.400' : 'gray.600'}>
                          ${(timer.currentRate ?? 0).toFixed(2)}/hr
                        </StatNumber>
                      </Stat>
                      
                      <Stat size="sm">
                        <StatLabel fontSize="xs" color="gray.500">
                          <HStack spacing={1}>
                            <Icon as={FaClock} boxSize={3} />
                            <Text>Total Time</Text>
                          </HStack>
                        </StatLabel>
                        <StatNumber>
                          {Math.floor((timerTrueTotalTimes[timer.id] || 0) / 3600)}h {Math.floor(((timerTrueTotalTimes[timer.id] || 0) % 3600) / 60)}m
                        </StatNumber>
                      </Stat>

                      <Stat size="sm">
                        <StatLabel fontSize="xs" color="gray.500">
                          <HStack spacing={1}>
                            <Icon as={FaChartLine} boxSize={3} />
                            <Text>Level</Text>
                          </HStack>
                        </StatLabel>
                        <StatNumber>
                          <Badge
                            colorScheme={isPinned ? "blue" : isActive ? "green" : "purple"}
                            variant="subtle"
                          >
                            {timer.level} • {timer.levelTitle}
                          </Badge>
                        </StatNumber>
                      </Stat>

                      <Stat size="sm">
                        <StatLabel fontSize="xs" color="gray.500">
                          <HStack spacing={1}>
                            <Icon as={FaCoins} boxSize={3} />
                            <Text>Total Earnings</Text>
                          </HStack>
                        </StatLabel>
                        <StatNumber color="green.400">
                          ${timer.earnings.toFixed(2)}
                        </StatNumber>
                      </Stat>
                    </SimpleGrid>
                  </VStack>
                </Card>
              </ScaleFade>
            )}
          )}
        </SimpleGrid>
        ) : (
          <Center p={10} borderWidth="1px" borderRadius="xl" borderStyle="dashed" width="100%">
            <VStack spacing={4}>
              <Image 
                src="https://cdn.iconscout.com/icon/free/png-256/free-data-not-found-1965034-1662569.png" 
                boxSize="100px" 
                opacity={0.6} 
                alt="No timers found"
              />
              <Text color={subtleText} textAlign="center">
                {searchQuery 
                  ? "No timers match your search. Try a different keyword."
                  : "You haven't created any timers yet. Get started by adding your first timer above!"}
              </Text>
              {searchQuery && (
                <Button 
                  leftIcon={<FaSearch />} 
                  colorScheme="blue" 
                  variant="outline"
                  onClick={() => setSearchQuery('')}
                >
                  Clear Search
                </Button>
              )}
            </VStack>
          </Center>
        )}
      </VStack>

      <DeleteConfirmationModal
        isOpen={isDeleteModalOpen}
        onClose={onDeleteModalClose}
        onConfirm={handleConfirmDelete}
        timerName={timerToDelete?.name ?? null}
      />
    </>
  );
};

export default TimerList;

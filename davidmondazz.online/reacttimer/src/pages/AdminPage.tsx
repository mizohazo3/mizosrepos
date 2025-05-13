import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  Heading,
  VStack,
  FormControl,
  FormLabel,
  Switch,
  Slider,
  SliderTrack,
  SliderFilledTrack,
  SliderThumb,
  Text,
  useToast,
  Button,
  AlertDialog,
  AlertDialogBody,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogContent,
  AlertDialogOverlay,
  Divider,
  HStack,
  useColorModeValue,
  Icon,
  Stat,
  StatLabel,
  StatNumber,
  StatHelpText,
  Badge,
  Card,
  CardBody,
} from '@chakra-ui/react';
import { FaTrash, FaExclamationTriangle, FaClock, FaCoins, FaPlay } from 'react-icons/fa';
import { useAudioSettings } from '../context/AudioSettingsContext'; // Import the hook
import { TimerService } from '../services/firebase';
import { TimerData } from '../types';

const timerService = new TimerService();

const AdminPage: React.FC = () => {
  // Use settings from context
  const { settings, toggleSetting, setGlobalVolume } = useAudioSettings();
  const toast = useToast();
  const [isResetDialogOpen, setIsResetDialogOpen] = useState(false);
  const [isResetAllTimersDialogOpen, setIsResetAllTimersDialogOpen] = useState(false);
  const [isResetting, setIsResetting] = useState(false);
  const [isResettingAllTimers, setIsResettingAllTimers] = useState(false);
  const [activeTimers, setActiveTimers] = useState<TimerData[]>([]);
  const [currentEarnings, setCurrentEarnings] = useState<number>(0);
  const [currentSeconds, setCurrentSeconds] = useState<number>(0);
  const cancelRef = useRef<HTMLButtonElement>(null);
  const cancelAllTimersRef = useRef<HTMLButtonElement>(null);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);

  // Calculate current earnings from active timers
  useEffect(() => {
    const calculateCurrentEarnings = () => {
      const now = Date.now();
      let earnings = 0;
      let totalSeconds = 0;

      activeTimers.forEach(timer => {
        if (timer.isActive && timer.lastStartTime > 0) {
          const elapsedSeconds = (now - timer.lastStartTime) / 1000;
          // Use the timer's currentRate which already has the correct rate per hour
          const hourlyRate = timer.currentRate || 0.10; // Default to 0.10 if not set
          const sessionEarnings = (elapsedSeconds / 3600) * hourlyRate;
          
          earnings += sessionEarnings;
          totalSeconds += elapsedSeconds;
        }
      });

      setCurrentEarnings(earnings);
      setCurrentSeconds(totalSeconds);
    };

    // Subscribe to timers to get active ones
    const unsubscribe = timerService.subscribeToTimers((timers) => {
      const active = timers.filter(timer => timer.isActive);
      setActiveTimers(active);
    });

    // Set up interval to recalculate earnings every second
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
    }
    
    if (activeTimers.length > 0) {
      intervalRef.current = setInterval(calculateCurrentEarnings, 500);
      // Initial calculation
      calculateCurrentEarnings();
    } else {
      setCurrentEarnings(0);
      setCurrentSeconds(0);
    }

    // Clean up
    return () => {
      unsubscribe();
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };
  }, [activeTimers]);

  // Handlers now use context functions
  const handleToggle = (key: 'countdownEnabled' | 'stopEnabled' | 'stopAllEnabled') => {
    toggleSetting(key); // Use context toggle function
    toast({ title: 'Setting updated', status: 'success', duration: 1500 });
  };

  const handleVolumeChange = (value: number) => {
    setGlobalVolume(value / 100); // Use context set volume function
  };

  const handleVolumeChangeEnd = () => {
    toast({ title: 'Volume updated', status: 'success', duration: 1500 });
  }

  const handleResetBankClick = () => {
    setIsResetDialogOpen(true);
  };

  const closeResetDialog = () => {
    setIsResetDialogOpen(false);
  };

  const handleResetAllTimersClick = () => {
    setIsResetAllTimersDialogOpen(true);
  };

  const closeResetAllTimersDialog = () => {
    setIsResetAllTimersDialogOpen(false);
  };

  const handleConfirmReset = async () => {
    setIsResetting(true);
    try {
      await timerService.resetBankSessions();
      toast({
        title: 'Bank successfully reset',
        description: 'All session records have been deleted.',
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
      closeResetDialog();
    } catch (error) {
      console.error('Error resetting bank:', error);
      toast({
        title: 'Error resetting bank',
        description: 'Could not reset bank records. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    } finally {
      setIsResetting(false);
    }
  };

  const handleConfirmResetAllTimers = async () => {
    setIsResettingAllTimers(true);
    try {
      await timerService.resetAllTimers();
      toast({
        title: 'All timers reset',
        description: 'All timers have been reset to level 1 with $0.00 earnings and 0 hours.',
        status: 'success',
        duration: 3000,
        isClosable: true,
      });
      closeResetAllTimersDialog();
    } catch (error) {
      console.error('Error resetting all timers:', error);
      toast({
        title: 'Error resetting timers',
        description: 'Could not reset all timers. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    } finally {
      setIsResettingAllTimers(false);
    }
  };

  // Format time for display
  const formatTime = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    return `${hours}h ${minutes}m ${secs}s elapsed`;
  };

  // Theme colors
  const dangerBgColor = useColorModeValue('red.50', 'red.900');
  const dangerBorderColor = useColorModeValue('red.200', 'red.700');
  const dangerTextColor = useColorModeValue('red.600', 'red.300');
  const dividerColor = useColorModeValue('gray.200', 'gray.700');
  const activeBg = useColorModeValue('green.50', 'green.900');
  const activeColor = useColorModeValue('green.600', 'green.300');

  return (
    <Box p={5}>
      <VStack spacing={8} align="stretch">
        <Box>
          <Heading size="lg" mb={6}>Admin Settings</Heading>

          {/* Current Earnings Section */}
          {activeTimers.length > 0 && (
            <Card 
              mb={8} 
              bg={activeBg} 
              borderWidth="1px" 
              borderColor={activeColor} 
              boxShadow="md"
              overflow="hidden"
            >
              <CardBody>
                <HStack spacing={6} align="flex-start">
                  <Box
                    bg="green.500"
                    color="white"
                    p={3}
                    borderRadius="lg"
                    boxShadow="sm"
                  >
                    <FaPlay size={24} />
                  </Box>
                  
                  <VStack align="flex-start" spacing={1}>
                    <HStack>
                      <Heading size="md">Active Timers</Heading>
                      <Badge colorScheme="green" fontSize="sm" borderRadius="full">
                        {activeTimers.length} running
                      </Badge>
                    </HStack>
                    
                    <Stat>
                      <StatLabel color={activeColor}>CURRENT EARNINGS (NOT YET IN BANK)</StatLabel>
                      <StatNumber fontSize="2xl" color={activeColor}>
                        ${currentEarnings.toFixed(2)}
                      </StatNumber>
                      <StatHelpText>
                        {formatTime(currentSeconds)}
                      </StatHelpText>
                    </Stat>
                    
                    <Text fontSize="sm" color="gray.500">
                      These earnings will be added to your bank when you stop the timers.
                    </Text>
                  </VStack>
                </HStack>
              </CardBody>
            </Card>
          )}

          <Heading size="md" mb={4}>Audio Settings</Heading>

          <VStack spacing={4} align="stretch">
            <FormControl display="flex" alignItems="center">
              <FormLabel htmlFor="countdown-enabled" mb="0">
                Enable Timer Start Sound (Countdown)
              </FormLabel>
              <Switch
                id="countdown-enabled"
                isChecked={settings.countdownEnabled}
                onChange={() => handleToggle('countdownEnabled')}
              />
            </FormControl>

            <FormControl display="flex" alignItems="center">
              <FormLabel htmlFor="stop-enabled" mb="0">
                Enable Timer Stop Sound
              </FormLabel>
              <Switch
                id="stop-enabled"
                isChecked={settings.stopEnabled}
                onChange={() => handleToggle('stopEnabled')}
              />
            </FormControl>

            <FormControl display="flex" alignItems="center">
              <FormLabel htmlFor="stopall-enabled" mb="0">
                Enable Stop All Sound
              </FormLabel>
              <Switch
                id="stopall-enabled"
                isChecked={settings.stopAllEnabled}
                onChange={() => handleToggle('stopAllEnabled')}
              />
            </FormControl>

            <FormControl>
              <FormLabel htmlFor="global-volume">Global Sound Volume</FormLabel>
              <Slider
                id="global-volume"
                aria-label="global-volume-slider"
                value={settings.globalVolume * 100}
                onChange={handleVolumeChange}
                onChangeEnd={handleVolumeChangeEnd}
                min={0}
                max={100}
                step={1}
              >
                <SliderTrack>
                  <SliderFilledTrack />
                </SliderTrack>
                <SliderThumb boxSize={6}>
                  <Text fontSize="xs">{Math.round(settings.globalVolume * 100)}</Text>
                </SliderThumb>
              </Slider>
            </FormControl>
          </VStack>
        </Box>
        
        <Divider borderColor={dividerColor} />
        
        {/* Data Management Section */}
        <Box>
          <Heading size="md" mb={4}>Data Management</Heading>
          
          <VStack spacing={4} align="stretch">
            {/* Reset Bank Section */}
            <Box 
              p={4} 
              borderWidth="1px" 
              borderRadius="md" 
              bg={dangerBgColor} 
              borderColor={dangerBorderColor}
            >
              <VStack align="stretch" spacing={3}>
                <HStack>
                  <Icon as={FaExclamationTriangle} color={dangerTextColor} boxSize={5} />
                  <Heading size="sm" color={dangerTextColor}>Danger Zone</Heading>
                </HStack>
                
                <Text fontSize="sm">
                  Reset your bank by deleting all session records. This action cannot be undone.
                  Timer data will be preserved, but all earning history will be permanently deleted.
                </Text>
                
                <Button
                  leftIcon={<FaTrash />}
                  colorScheme="red"
                  variant="outline"
                  size="md"
                  onClick={handleResetBankClick}
                  alignSelf="flex-start"
                >
                  Reset Bank
                </Button>
              </VStack>
            </Box>
            
            {/* Reset All Timers Section */}
            <Box 
              p={4} 
              borderWidth="1px" 
              borderRadius="md" 
              bg={dangerBgColor} 
              borderColor={dangerBorderColor}
            >
              <VStack align="stretch" spacing={3}>
                <HStack>
                  <Icon as={FaExclamationTriangle} color={dangerTextColor} boxSize={5} />
                  <Heading size="sm" color={dangerTextColor}>Reset All Timers</Heading>
                </HStack>
                
                <Text fontSize="sm">
                  Reset all timers to level 1 with $0.00 earnings and 0 hours. This action cannot be undone.
                  All progress, levels, and accumulated time will be permanently deleted.
                </Text>
                
                <Button
                  leftIcon={<FaTrash />}
                  colorScheme="red"
                  variant="outline"
                  size="md"
                  onClick={handleResetAllTimersClick}
                  alignSelf="flex-start"
                >
                  Reset All Timers
                </Button>
              </VStack>
            </Box>
          </VStack>
        </Box>
      </VStack>
      
      {/* Reset Bank Confirmation Dialog */}
      <AlertDialog
        isOpen={isResetDialogOpen}
        leastDestructiveRef={cancelRef}
        onClose={closeResetDialog}
      >
        <AlertDialogOverlay>
          <AlertDialogContent>
            <AlertDialogHeader fontSize="lg" fontWeight="bold">
              Reset Bank Confirmation
            </AlertDialogHeader>

            <AlertDialogBody>
              <VStack align="stretch" spacing={4}>
                <Text>
                  Are you absolutely sure you want to reset your bank?
                </Text>
                <Box p={3} bg={dangerBgColor} borderRadius="md">
                  <Text fontWeight="bold" color={dangerTextColor}>Warning:</Text>
                  <Text fontSize="sm" color={dangerTextColor}>
                    This will permanently delete all session records and reset your bank balance to $0.00. 
                    This action cannot be undone.
                  </Text>
                </Box>
              </VStack>
            </AlertDialogBody>

            <AlertDialogFooter>
              <Button ref={cancelRef} onClick={closeResetDialog}>
                Cancel
              </Button>
              <Button 
                colorScheme="red" 
                onClick={handleConfirmReset} 
                ml={3}
                isLoading={isResetting}
                loadingText="Resetting..."
              >
                Yes, Reset Bank
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialogOverlay>
      </AlertDialog>
      
      {/* Reset All Timers Confirmation Dialog */}
      <AlertDialog
        isOpen={isResetAllTimersDialogOpen}
        leastDestructiveRef={cancelAllTimersRef}
        onClose={closeResetAllTimersDialog}
      >
        <AlertDialogOverlay>
          <AlertDialogContent>
            <AlertDialogHeader fontSize="lg" fontWeight="bold">
              Reset All Timers Confirmation
            </AlertDialogHeader>

            <AlertDialogBody>
              <VStack align="stretch" spacing={4}>
                <Text>
                  Are you absolutely sure you want to reset all timers?
                </Text>
                <Box p={3} bg={dangerBgColor} borderRadius="md">
                  <Text fontWeight="bold" color={dangerTextColor}>Warning:</Text>
                  <Text fontSize="sm" color={dangerTextColor}>
                    This will permanently reset all your timers to level 1 with $0.00 earnings and 0 hours.
                    All progress, levels, and accumulated time will be permanently deleted.
                    This action cannot be undone.
                  </Text>
                </Box>
              </VStack>
            </AlertDialogBody>

            <AlertDialogFooter>
              <Button ref={cancelAllTimersRef} onClick={closeResetAllTimersDialog}>
                Cancel
              </Button>
              <Button 
                colorScheme="red" 
                onClick={handleConfirmResetAllTimers} 
                ml={3}
                isLoading={isResettingAllTimers}
                loadingText="Resetting..."
              >
                Yes, Reset All Timers
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialogOverlay>
      </AlertDialog>
    </Box>
  );
};

export default AdminPage; 
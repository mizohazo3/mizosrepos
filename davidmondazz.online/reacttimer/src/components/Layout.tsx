import React, { useState, useEffect, useRef } from 'react';
import {
  Container,
  VStack,
  Heading,
  Text,
  useColorMode,
  Button,
  HStack,
  Divider,
  useDisclosure,
  Spacer,
  useToast,
  IconButton,
  Badge,
  Box,
  Tooltip,
  Flex,
  Icon,
  useColorModeValue,
  AlertDialog,
  AlertDialogBody,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogContent,
  AlertDialogOverlay,
} from '@chakra-ui/react';
import { keyframes } from "@emotion/react";
import { FaSun, FaMoon, FaInfoCircle, FaStop, FaClock, FaCog, FaRegClock, FaWallet, FaCoins, FaShoppingCart, FaStickyNote, FaListAlt } from 'react-icons/fa';
import LevelInfoModal from './LevelInfoModal';
import { TimerService } from '../services/firebase';
import { TimerData, TimerSession } from '../types';
import countdownSound from '../sounds/countdown.mp3'; // Import from src/sounds
import stopAllSound from '../sounds/stopall.mp3'; // Import the stop all sound file
import { Link as RouterLink, Outlet, useLocation } from 'react-router-dom';
import { useAudioSettings } from '../context/AudioSettingsContext'; // Import context hook

const timerService = new TimerService();

const Layout: React.FC = () => {
  const { colorMode, toggleColorMode } = useColorMode();
  const { isOpen: isInfoModalOpen, onOpen: onInfoModalOpen, onClose: onInfoModalClose } = useDisclosure();
  const { isOpen: isStopAllAlertOpen, onOpen: onStopAllAlertOpen, onClose: onStopAllAlertClose } = useDisclosure();
  const cancelRef = useRef<HTMLButtonElement>(null);
  const toast = useToast();
  const [activeTimers, setActiveTimers] = useState<TimerData[]>([]);
  const [bankBalance, setBankBalance] = useState<number>(0);
  const previousCountRef = useRef<number>(0);
  const { settings } = useAudioSettings(); // Get settings from context
  const location = useLocation();
  
  // New state variables for tracking earnings and time
  const [currentEarnings, setCurrentEarnings] = useState<number>(0);
  const [timerEarnings, setTimerEarnings] = useState<Record<string, number>>({});
  const [timerElapsedTimes, setTimerElapsedTimes] = useState<Record<string, number>>({});
  const intervalRef = useRef<NodeJS.Timeout | null>(null);

  // Format duration in hours, minutes, and seconds
  const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    return `${hours}h ${minutes}m ${secs}s`;
  };

  // Fetch and calculate bank balance from sessions
  useEffect(() => {
    // Set up a subscription to sessions for real-time bank balance updates
    const unsubscribe = timerService.subscribeToSessions((sessions) => {
      const totalEarnings = sessions.reduce((sum, session) => sum + (session.earnings || 0), 0);
      setBankBalance(totalEarnings);
    });

    return () => unsubscribe();
  }, []);

  useEffect(() => {
    const unsubscribe = timerService.subscribeToTimers((timers) => {
      const active = timers.filter(timer => timer.isActive);
      setActiveTimers(active);
    });

    return () => unsubscribe();
  }, []);
  
  // Calculate earnings and elapsed time for active timers
  useEffect(() => {
    const calculateEarnings = () => {
      const now = Date.now();
      let totalEarnings = 0;
      const earnings: Record<string, number> = {};
      const elapsed: Record<string, number> = {};
      
      activeTimers.forEach(timer => {
        if (timer.isActive && timer.lastStartTime > 0) {
          const elapsedSeconds = (now - timer.lastStartTime) / 1000;
          const hourlyRate = timer.currentRate || 0;
          const sessionEarnings = (elapsedSeconds / 3600) * hourlyRate;
          
          earnings[timer.id] = sessionEarnings;
          elapsed[timer.id] = elapsedSeconds;
          totalEarnings += sessionEarnings;
        }
      });
      
      setTimerEarnings(earnings);
      setTimerElapsedTimes(elapsed);
      setCurrentEarnings(totalEarnings);
    };
    
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
    }
    
    if (activeTimers.length > 0) {
      calculateEarnings(); // Calculate immediately
      intervalRef.current = setInterval(calculateEarnings, 1000);
    }
    
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };
  }, [activeTimers]);

  useEffect(() => {
    const prevCount = previousCountRef.current;
    const currentCount = activeTimers.length;
    
    // Play sound when activeTimers count increases (a timer was started)
    if (
      settings.countdownEnabled &&
      currentCount > prevCount &&
      document.documentElement.hasAttribute('data-user-interacted')
    ) {
      try {
        // Create a new Audio object each time
        const audio = new Audio(countdownSound);
        audio.volume = settings.globalVolume * 0.6;
        // console.log('🔔 Attempting to play new countdown notification instance...'); // Optional log
        const playPromise = audio.play();
        if (playPromise !== undefined) {
          playPromise
            .catch(error => {
              console.error('❌ New countdown sound playback prevented:', error);
            });
        }
      } catch (error) {
        console.error("❌ Error creating/playing new countdown sound instance:", error);
      }
    }
    
    // Update ref to current count for next render
    previousCountRef.current = currentCount;
  }, [activeTimers.length, settings.countdownEnabled, settings.globalVolume]);

  useEffect(() => {
    const handleUserInteraction = () => {
      if (!document.documentElement.hasAttribute('data-user-interacted')) {
        // console.log('User interaction detected - setting flag and priming audio'); // Optional log
        document.documentElement.setAttribute('data-user-interacted', 'true');

        // --- Audio Priming Attempt ---
        try {
          // Use one of the existing sounds to prime
          const primeAudio = new Audio(countdownSound);
          primeAudio.volume = 0.001; // Play almost silently
          const playPromise = primeAudio.play();

          if (playPromise !== undefined) {
            playPromise
              .then(() => {
                // Optional: Immediately pause after play starts if needed,
                // but often just letting it play silently is enough.
                // primeAudio.pause(); 
                // console.log('Audio context likely primed successfully.'); // Optional log
              })
              .catch((error) => {
                // Ignore errors here, as the main goal is just to initiate playback
                // console.error('Priming audio playback failed (expected on some browsers initially):', error);
              });
          }
        } catch (e) {
           // console.error('Error attempting to prime audio context:', e);
        }
        // --- End Audio Priming Attempt ---
      }
    };

    // Add listeners for user interactions
    const events = ['click', 'keydown', 'touchstart'];
    events.forEach(event => {
      document.addEventListener(event, handleUserInteraction, { once: true }); // Use once: true to automatically remove after first interaction
    });

    // Cleanup: Remove listeners if component unmounts before interaction
    // Although `once: true` handles removal after firing, this is good practice
    return () => {
      events.forEach(event => {
        document.removeEventListener(event, handleUserInteraction);
      });
    };
  }, []); // Empty dependency array ensures this runs only once on mount

  const handleStopAllConfirm = async () => {
    // Close the alert dialog
    onStopAllAlertClose();
    
    if (activeTimers.length === 0) return;

    // Play stop-all sound
    if (
      settings.stopAllEnabled &&
      document.documentElement.hasAttribute('data-user-interacted')
    ) {
      try {
        // Create a new Audio object each time
        const audio = new Audio(stopAllSound);
        audio.volume = settings.globalVolume * 0.7;
        const playPromise = audio.play();
        if (playPromise !== undefined) {
          playPromise
            .catch(error => {
              console.error('❌ New stop-all sound playback prevented:', error);
            });
        }
      } catch (error) {
        console.error("❌ Error creating/playing new stop-all sound instance:", error);
      }
    }

    try {
      const results = await timerService.stopAllTimers();
      
      toast({
        title: 'All timers stopped',
        description: `Successfully stopped ${activeTimers.length} timer(s)`,
        status: 'success',
        duration: 3000,
        isClosable: true,
      });

      // Show level up notifications
      results.forEach(result => {
        if (result && result.newLevel > result.oldLevel) {
          toast({
            title: 'Level Up!',
            description: `Congratulations! Your timer "${result.timerName}" reached level ${result.newLevel}!`,
            status: 'success',
            duration: 5000,
            isClosable: true,
          });
        }
      });
    } catch (error) {
      console.error("Error stopping all timers:", error);
      toast({
        title: 'Error stopping timers',
        description: 'Could not stop all timers. Please try again.',
        status: 'error',
        duration: 5000,
        isClosable: true,
      });
    }
  };

  // Replaced with opening the alert dialog
  const handleStopAllTimers = () => {
    if (activeTimers.length === 0) return;
    onStopAllAlertOpen();
  };

  // Define pulsing animation
  const pulse = keyframes`
    0% { opacity: 0.6; transform: scale(0.97); }
    50% { opacity: 1; transform: scale(1.03); }
    100% { opacity: 0.6; transform: scale(0.97); }
  `;
  const pulseAnimation = `${pulse} 1.5s infinite ease-in-out`;

  const topNavBg = useColorModeValue('white', 'gray.800');
  const topNavBorder = useColorModeValue('gray.200', 'gray.700');
  const activeNavItemBg = useColorModeValue('blue.50', 'blue.900');
  const activeNavItemColor = useColorModeValue('blue.600', 'blue.300');
  const balanceBg = useColorModeValue('green.50', 'green.900');
  const balanceColor = useColorModeValue('green.600', 'green.300');

  return (
    <Box>
      {/* Static Top Navigation */}
      <Box
        as="nav"
        bg={topNavBg}
        borderBottom="1px"
        borderColor={topNavBorder}
        p={2}
        px={4}
      >
        <Flex align="center" justify="space-between" >
          {/* Left side: Logo, Title, Active Timers */}
          <HStack spacing={3}>
             <Icon as={FaRegClock} w={6} h={6} color="blue.500" />
            <Heading size="md" as={RouterLink} to="/" _hover={{ textDecoration: 'none' }}>
              Timer Tracker
            </Heading>
          </HStack>

          {/* Center: Navigation links */}
          <HStack spacing={4} display={{ base: 'none', md: 'flex' }}>
            <Tooltip label="Home">
              <Box 
                as={RouterLink} 
                to="/"
                px={3}
                py={1}
                borderRadius="md"
                bg={location.pathname === '/' ? activeNavItemBg : 'transparent'}
                color={location.pathname === '/' ? activeNavItemColor : 'inherit'}
                fontWeight={location.pathname === '/' ? 'semibold' : 'normal'}
                _hover={{ textDecoration: 'none', bg: 'gray.100', _dark: { bg: 'gray.700' } }}
                position="relative"
              >
                <HStack spacing={1}>
                  <FaRegClock size={14} />
                  <Text>Timers</Text>
                  {activeTimers.length > 0 && (
                    <Badge
                      borderRadius="full"
                      bg="red.500"
                      color="white"
                      px={1.5}
                      animation={pulseAnimation}
                      ml={1}
                    >
                      {activeTimers.length}
                    </Badge>
                  )}
                </HStack>
              </Box>
            </Tooltip>
            
            <Tooltip label="Marketplace">
              <Box 
                as={RouterLink} 
                to="/marketplace"
                px={3}
                py={1}
                borderRadius="md"
                bg={location.pathname === '/marketplace' ? activeNavItemBg : 'transparent'}
                color={location.pathname === '/marketplace' ? activeNavItemColor : 'inherit'}
                fontWeight={location.pathname === '/marketplace' ? 'semibold' : 'normal'}
                _hover={{ textDecoration: 'none', bg: 'gray.100', _dark: { bg: 'gray.700' } }}
              >
                <HStack spacing={1}>
                  <FaShoppingCart size={14} />
                  <Text>Shop</Text>
                </HStack>
              </Box>
            </Tooltip>
            
            <Tooltip label="Notes">
              <Box 
                as={RouterLink} 
                to="/notes"
                px={3}
                py={1}
                borderRadius="md"
                bg={location.pathname === '/notes' ? activeNavItemBg : 'transparent'}
                color={location.pathname === '/notes' ? activeNavItemColor : 'inherit'}
                fontWeight={location.pathname === '/notes' ? 'semibold' : 'normal'}
                _hover={{ textDecoration: 'none', bg: 'gray.100', _dark: { bg: 'gray.700' } }}
              >
                <HStack spacing={1}>
                  <FaStickyNote size={14} />
                  <Text>Notes</Text>
                </HStack>
              </Box>
            </Tooltip>
            
            <Tooltip label="Todo">
              <Box 
                as={RouterLink} 
                to="/todos"
                px={3}
                py={1}
                borderRadius="md"
                bg={location.pathname === '/todos' ? activeNavItemBg : 'transparent'}
                color={location.pathname === '/todos' ? activeNavItemColor : 'inherit'}
                fontWeight={location.pathname === '/todos' ? 'semibold' : 'normal'}
                _hover={{ textDecoration: 'none', bg: 'gray.100', _dark: { bg: 'gray.700' } }}
              >
                <HStack spacing={1}>
                  <FaListAlt size={14} />
                  <Text>Todo</Text>
                </HStack>
              </Box>
            </Tooltip>
            
            {/* Bank Balance Display with Bank button beside it - moved to last position */}
            <HStack spacing={1}>
              <Tooltip label="Bank Account">
                <Box 
                  as={RouterLink} 
                  to="/bank"
                  px={3}
                  py={1}
                  borderRadius="md"
                  bg={location.pathname === '/bank' ? activeNavItemBg : 'transparent'}
                  color={location.pathname === '/bank' ? activeNavItemColor : 'inherit'}
                  fontWeight={location.pathname === '/bank' ? 'semibold' : 'normal'}
                  _hover={{ textDecoration: 'none', bg: 'gray.100', _dark: { bg: 'gray.700' } }}
                >
                  <HStack spacing={1}>
                    <FaWallet size={14} />
                    <Text>Bank</Text>
                  </HStack>
                </Box>
              </Tooltip>
              
              <Tooltip label="Bank Balance">
                <Box 
                  as={RouterLink}
                  to="/bank"
                  px={3}
                  py={1}
                  borderRadius="md"
                  bg={balanceBg}
                  display="flex"
                  alignItems="center"
                  transition="all 0.2s"
                  _hover={{ transform: "translateY(-1px)", boxShadow: "sm", textDecoration: 'none' }}
                >
                  <HStack spacing={2}>
                    <FaCoins color={balanceColor} size={14} />
                    <Text fontWeight="medium" color={balanceColor}>
                      ${bankBalance.toFixed(2)}
                    </Text>
                  </HStack>
                </Box>
              </Tooltip>
            </HStack>
          </HStack>

          {/* Right side: Controls */}
          <HStack spacing={3}>
            {activeTimers.length > 0 && (
              <Button
                leftIcon={<FaStop />}
                colorScheme="yellow"
                variant="solid"
                size="sm"
                onClick={handleStopAllTimers}
                bg="yellow.400"
                _hover={{ bg: "yellow.500" }}
              >
                Stop All
                <Badge
                  ml={2}
                  borderRadius="full"
                  colorScheme="white"
                  color="white"
                  bg="orange.500"
                  px={1.5}
                  animation={pulseAnimation}
                >
                  {activeTimers.length}
                </Badge>
              </Button>
            )}
            
            {/* Admin button moved next to level info */}
            <Tooltip label="Admin Settings">
              <IconButton
                as={RouterLink}
                to="/admin"
                icon={<FaCog />}
                aria-label="Admin Settings"
                variant="ghost"
              />
            </Tooltip>
            
            <IconButton
              icon={<FaInfoCircle />}
              aria-label="Level Info"
              variant="ghost"
              onClick={onInfoModalOpen}
            />
            
            <IconButton
              icon={colorMode === 'light' ? <FaMoon /> : <FaSun />}
              aria-label="Toggle color mode"
              variant="ghost"
              onClick={toggleColorMode}
            />
          </HStack>
        </Flex>
      </Box>

      {/* Main Content */}
      <Container maxW="container.xl" py={4}>
        <Outlet />
      </Container>

      {/* Modals */}
      <LevelInfoModal
        isOpen={isInfoModalOpen}
        onClose={onInfoModalClose}
      />
      
      {/* Stop All Confirmation Alert */}
      <AlertDialog
        isOpen={isStopAllAlertOpen}
        leastDestructiveRef={cancelRef}
        onClose={onStopAllAlertClose}
      >
        <AlertDialogOverlay>
          <AlertDialogContent>
            <AlertDialogHeader fontSize="lg" fontWeight="bold">
              Stop All Timers
            </AlertDialogHeader>

            <AlertDialogBody>
              <VStack align="stretch" spacing={4}>
                <Text>
                  Are you sure you want to stop all {activeTimers.length} active timers? 
                  This action will save your current progress and earnings.
                </Text>
                
                <Box 
                  p={3} 
                  bg={balanceBg} 
                  borderRadius="md" 
                  borderWidth="1px"
                  borderColor={balanceColor}
                >
                  <VStack align="stretch" spacing={2}>
                    <Flex justify="space-between">
                      <Text fontWeight="bold">Total Current Earnings:</Text>
                      <Text fontWeight="bold" color={balanceColor}>${currentEarnings.toFixed(2)}</Text>
                    </Flex>
                    
                    <Divider />
                    
                    {activeTimers.map(timer => (
                      <Flex key={timer.id} justify="space-between" align="center">
                        <VStack align="start" spacing={0}>
                          <Text fontWeight="medium">{timer.name}</Text>
                          <Text fontSize="sm" color="gray.500">
                            {formatDuration(timerElapsedTimes[timer.id] || 0)}
                          </Text>
                        </VStack>
                        <Text fontWeight="medium" color={balanceColor}>
                          ${(timerEarnings[timer.id] || 0).toFixed(2)}
                        </Text>
                      </Flex>
                    ))}
                  </VStack>
                </Box>
              </VStack>
            </AlertDialogBody>

            <AlertDialogFooter>
              <Button ref={cancelRef} onClick={onStopAllAlertClose}>
                Cancel
              </Button>
              <Button colorScheme="red" onClick={handleStopAllConfirm} ml={3}>
                Stop All
              </Button>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialogOverlay>
      </AlertDialog>
    </Box>
  );
};

export default Layout;
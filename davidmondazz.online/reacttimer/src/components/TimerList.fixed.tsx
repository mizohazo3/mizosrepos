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
  IconButton,
  Grid,
  Link,
  Button,
  Card,
  CardBody,
  HStack,
  Tooltip,
  Input,
  InputGroup,
  InputRightElement,
  ScaleFade,
  Center,
  Image,
  Icon,
  ButtonGroup,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  MenuDivider
} from '@chakra-ui/react';
import { 
  FaTrash, 
  FaPlus, 
  FaClock, 
  FaCoins, 
  FaChartLine,
  FaSearch,
  FaSort,
  FaThumbtack,
  FaDollarSign,
  FaStop,
  FaPlay,
  FaPause,
  FaUndo,
  FaEllipsisH,
  FaEdit,
  FaHistory
} from 'react-icons/fa';
import { keyframes as emotionKeyframes } from '@emotion/react';
import Timer from './Timer';
import { TimerService } from '../services/firebase';
import { TimerData } from '../types/index';
import DeleteConfirmationModal from './DeleteConfirmationModal';
import { Link as RouterLink } from 'react-router-dom';

const timerService = new TimerService();

// Animation keyframes
const glowPulse = emotionKeyframes`
  0% { opacity: 0.8; }
  50% { opacity: 1; }
  100% { opacity: 0.8; }
`;

const shimmer = emotionKeyframes`
  0% { transform: translateX(-100%) rotate(30deg); }
  100% { transform: translateX(100%) rotate(30deg); }
`;

const gradientShift = emotionKeyframes`
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
`;

const pulseShadow = emotionKeyframes`
  0% { box-shadow: 0 8px 32px rgba(72, 187, 120, 0.3); }
  50% { box-shadow: 0 8px 32px rgba(72, 187, 120, 0.5); }
  100% { box-shadow: 0 8px 32px rgba(72, 187, 120, 0.3); }
`;

interface TimerItemProps {
  timer: TimerData;
  onDelete: (timerId: string) => void;
  onPin: (timer: TimerData) => void;
  onStart: (timer: TimerData) => void;
  onPause: (timer: TimerData) => void;
  onReset: (timer: TimerData) => void;
}

const TimerListItem: React.FC<TimerItemProps> = React.memo(({ timer, onDelete, onPin, onStart, onPause, onReset }) => {
  const toast = useToast();
  const isActive = timer.isActive;
  const isPinned = timer.isPinned;
  const progressBgColor = useColorModeValue("gray.100", "gray.700");
  const [currentSessionEarnings, setCurrentSessionEarnings] = useState<Record<string, number>>({});
  const [isHovered, setIsHovered] = useState(false);

  useEffect(() => {
    let intervalId: NodeJS.Timeout | null = null;
    
    if (isActive) {
      intervalId = setInterval(() => {
        const secondsElapsed = (Date.now() - timer.lastStartTime) / 1000;
        const hourlyRate = timer.currentRate || 0;
        const earned = (hourlyRate / 3600) * secondsElapsed;
        
        setCurrentSessionEarnings(prev => ({
          ...prev,
          [timer.id]: earned
        }));
      }, 1000);
    }
    
    return () => {
      if (intervalId) clearInterval(intervalId);
    };
  }, [isActive, timer.id, timer.lastStartTime, timer.currentRate]);

  const handleLevelUp = useCallback(() => {
    toast({
      title: "Level Up!",
      description: `${timer.name} is now level ${timer.level}!`,
      status: "success",
      duration: 3000,
      isClosable: true,
      position: "top-right"
    });
  }, [timer.level, timer.name, toast]);

  const sessionEarnings = currentSessionEarnings[timer.id] || 0;
  const sessionSeconds = sessionEarnings / (timer.currentRate || 0.01) * 3600;
  const hours = Math.floor(sessionSeconds / 3600);
  const minutes = Math.floor((sessionSeconds % 3600) / 60);
  const seconds = Math.floor(sessionSeconds % 60);

  return (
    <Box
      position="relative"
      width="100%"
      borderRadius="xl"
      overflow="hidden"
      boxShadow={isActive ? "lg" : "md"}
      borderWidth="1px"
      borderColor={isActive ? "green.200" : "gray.200"}
      transition="all 0.2s"
      _hover={{ transform: "translateY(-2px)" }}
      bg={isActive ? "green.50" : "white"}
      _dark={{
        bg: isActive ? "green.900" : "gray.800",
        borderColor: isActive ? "green.700" : "gray.700"
      }}
    >
      {/* Timer Card Content */}
      <Flex direction="column" height="100%">
        {/* Header Section */}
        <HStack p={4} justify="space-between" bg={isActive ? "green.100" : "gray.50"} _dark={{ bg: isActive ? "green.800" : "gray.700" }}>
          <HStack>
            <Icon 
              as={isActive ? FaPlay : FaClock} 
              color={isActive ? "green.500" : "gray.500"} 
              boxSize={4}
            />
            <Text fontWeight="bold" fontSize="lg">{timer.name}</Text>
          </HStack>
          
          {isPinned && (
            <Icon as={FaThumbtack} color="blue.500" boxSize={4} />
          )}
        </HStack>

        {/* Timer Display and Controls */}
        <Box p={4}>
          <Timer timer={timer} onLevelUp={handleLevelUp} />
          <Progress
            value={(timer.currentLevelProgress || 0) * 100}
            size="sm"
            mt={2}
            colorScheme={isActive ? "green" : "blue"}
            bg={progressBgColor}
          />
          
          {/* Stats */}
          <SimpleGrid columns={2} spacing={4} mt={4}>
            <VStack align="start">
              <Text fontSize="sm" color="gray.500">Rate</Text>
              <Text fontWeight="bold">${timer.currentRate}/hr</Text>
            </VStack>
            <VStack align="start">
              <Text fontSize="sm" color="gray.500">Total Time</Text>
              <Text fontWeight="bold">{Math.floor(timer.totalTime / 3600)}h {Math.floor((timer.totalTime % 3600) / 60)}m</Text>
            </VStack>
          </SimpleGrid>

          {/* Controls */}
          <HStack mt={4} spacing={2}>
            {isActive ? (
              <IconButton
                aria-label="Pause Timer"
                icon={<FaPause />}
                onClick={() => onPause(timer)}
                colorScheme="green"
                size="sm"
              />
            ) : (
              <IconButton
                aria-label="Start Timer"
                icon={<FaPlay />}
                onClick={() => onStart(timer)}
                colorScheme="green"
                size="sm"
              />
            )}
            
            <IconButton
              aria-label="Reset Timer"
              icon={<FaUndo />}
              onClick={() => onReset(timer)}
              size="sm"
            />
            
            <IconButton
              aria-label={isPinned ? "Unpin Timer" : "Pin Timer"}
              icon={<FaThumbtack />}
              onClick={() => onPin(timer)}
              colorScheme={isPinned ? "blue" : "gray"}
              size="sm"
            />
            
            <IconButton
              aria-label="Delete Timer"
              icon={<FaTrash />}
              onClick={() => onDelete(timer.id)}
              colorScheme="red"
              size="sm"
              ml="auto"
            />
          </HStack>

          {/* Active Session Info */}
          {isActive && (
            <Box
              mt={4}
              p={3}
              bg="green.50"
              borderRadius="md"
              borderLeftWidth="3px"
              borderLeftColor="green.500"
              _dark={{
                bg: "green.900",
                borderLeftColor: "green.400"
              }}
            >
              <HStack justify="space-between">
                <VStack align="start" spacing={1}>
                  <Text fontSize="sm" fontWeight="medium" color="green.700" _dark={{ color: "green.200" }}>
                    Current Session
                  </Text>
                  <Text fontSize="lg" fontWeight="bold" color="green.600" _dark={{ color: "green.300" }}>
                    +${sessionEarnings.toFixed(2)}
                  </Text>
                </VStack>
                <Text fontSize="sm" color="green.600" _dark={{ color: "green.300" }}>
                  {hours.toString().padStart(2, '0')}:{minutes.toString().padStart(2, '0')}:{seconds.toString().padStart(2, '0')}
                </Text>
              </HStack>
            </Box>
          )}
        </Box>
      </Flex>
    </Box>
  );
});

const TimerList: React.FC = () => {
  const [timers, setTimers] = useState<TimerData[]>([]);
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

  // Theme colors
  const bgColor = useColorModeValue('white', 'gray.800');
  const subtleText = useColorModeValue('gray.600', 'gray.400');
  const addTimerBg = useColorModeValue('gray.50', 'gray.700');

  // Load timers
  useEffect(() => {
    const unsubscribe = timerService.subscribeToTimers((updatedTimers) => {
      setTimers(updatedTimers);
      setIsLoading(false);
    });

    return () => unsubscribe();
  }, []);

  // Active timers
  const activeTimers = useMemo(() => (
    timers.filter(timer => timer.isActive)
  ), [timers]);

  // Calculate earnings
  useEffect(() => {
    const calculateEarnings = () => {
      if (!activeTimers.length) return;

      const now = Date.now();
      const newEarnings: Record<string, number> = {};

      activeTimers.forEach(timer => {
        if (timer.lastStartTime > 0) {
          const elapsedSeconds = (now - timer.lastStartTime) / 1000;
          const earned = (elapsedSeconds / 3600) * (timer.currentRate || 0);
          newEarnings[timer.id] = earned;
        }
      });

      setCurrentSessionEarnings(newEarnings);
    };

    if (activeTimers.length) {
      intervalRef.current = setInterval(calculateEarnings, 1000);
      calculateEarnings();
    }

    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
  }, [activeTimers]);

  const handleStartTimer = async (timer: TimerData) => {
    try {
      await timerService.startTimerInDb(timer.id);
      toast({
        title: 'Timer Started',
        description: `${timer.name} is now running`,
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not start timer',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const handlePauseTimer = async (timer: TimerData) => {
    try {
      const currentAccumulatedTime = timer.accumulatedTime || 0;
      const additionalTime = (Date.now() - timer.lastStartTime) / 1000;
      const finalAccumulatedTime = currentAccumulatedTime + additionalTime;
      
      const result = await timerService.stopTimerInDb(timer.id, finalAccumulatedTime);
      
      if (result && result.newLevel > result.oldLevel) {
        toast({
          title: 'Level Up!',
          description: `${timer.name} reached level ${result.newLevel}!`,
          status: 'success',
          duration: 3000,
          isClosable: true,
        });
      }
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not pause timer',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const handleResetTimer = async (timer: TimerData) => {
    try {
      await timerService.stopTimerInDb(timer.id, 0);
      toast({
        title: 'Timer Reset',
        description: `${timer.name} has been reset`,
        status: 'info',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not reset timer',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const handleTogglePin = async (timer: TimerData) => {
    try {
      const newStatus = await timerService.togglePinTimer(timer.id);
      toast({
        title: newStatus ? 'Timer Pinned' : 'Timer Unpinned',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not update pin status',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const handleAttemptDelete = (timerId: string) => {
    const timer = timers.find(t => t.id === timerId);
    if (timer) {
      setTimerToDelete({ id: timer.id, name: timer.name });
      onDeleteModalOpen();
    }
  };

  const handleConfirmDelete = async () => {
    if (!timerToDelete) return;

    try {
      await timerService.deleteTimer(timerToDelete.id);
      toast({
        title: 'Timer Deleted',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
      setTimerToDelete(null);
      onDeleteModalClose();
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not delete timer',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const handleAddTimer = async () => {
    const name = newTimerName.trim();
    if (!name) return;

    try {
      await timerService.createTimer({
        name,
        totalTime: 0,
        earnings: 0,
        isActive: false,
        lastStartTime: 0,
        level: 1,
        isPinned: false
      });
      setNewTimerName('');
      toast({
        title: 'Timer Created',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Could not create timer',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    }
  };

  const filteredAndSortedTimers = useMemo(() => {
    let result = timers;
    
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      result = result.filter(timer => 
        timer.name.toLowerCase().includes(query) ||
        (timer.levelTitle || '').toLowerCase().includes(query)
      );
    }
    
    return [...result].sort((a, b) => {
      if (a.isPinned && !b.isPinned) return -1;
      if (!a.isPinned && b.isPinned) return 1;
      
      switch (sortField) {
        case 'name':
          return sortDirection === 'asc' 
            ? a.name.localeCompare(b.name)
            : b.name.localeCompare(a.name);
        case 'level':
          return sortDirection === 'asc'
            ? (a.level || 0) - (b.level || 0)
            : (b.level || 0) - (a.level || 0);
        case 'earnings':
          return sortDirection === 'asc'
            ? (a.earnings || 0) - (b.earnings || 0)
            : (b.earnings || 0) - (a.earnings || 0);
        default:
          return 0;
      }
    });
  }, [timers, searchQuery, sortField, sortDirection]);

  if (isLoading) {
    return (
      <Center minH="300px">
        <Spinner size="xl" color="blue.500" />
      </Center>
    );
  }

  return (
    <VStack spacing={4} width="100%" align="stretch">
      {/* Add Timer Section */}
      <Card>
        <CardBody>
          <Flex gap={4} direction={{ base: "column", md: "row" }}>
            {/* Add Timer Input */}
            <InputGroup flex={1}>
              <Input
                placeholder="Create a new timer..."
                value={newTimerName}
                onChange={(e) => setNewTimerName(e.target.value)}
                onKeyPress={(e) => e.key === 'Enter' && handleAddTimer()}
              />
              <InputRightElement width="4.5rem">
                <Button
                  h="1.75rem"
                  size="sm"
                  onClick={handleAddTimer}
                  isDisabled={!newTimerName.trim()}
                  colorScheme="blue"
                >
                  Add
                </Button>
              </InputRightElement>
            </InputGroup>

            {/* Search and Sort */}
            <InputGroup maxW={{ base: "100%", md: "200px" }}>
              <Input
                placeholder="Search timers..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              <InputRightElement>
                <Icon as={FaSearch} color="gray.500" />
              </InputRightElement>
            </InputGroup>

            <Menu>
              <MenuButton
                as={Button}
                rightIcon={<FaSort />}
                variant="outline"
              >
                Sort: {sortField}
              </MenuButton>
              <MenuList>
                {['name', 'level', 'earnings'].map(field => (
                  <MenuItem
                    key={field}
                    onClick={() => {
                      if (sortField === field) {
                        setSortDirection(current => current === 'asc' ? 'desc' : 'asc');
                      } else {
                        setSortField(field as any);
                        setSortDirection('asc');
                      }
                    }}
                  >
                    {field.charAt(0).toUpperCase() + field.slice(1)}
                  </MenuItem>
                ))}
              </MenuList>
            </Menu>
          </Flex>
        </CardBody>
      </Card>

      {/* Active Timers Summary */}
      {activeTimers.length > 0 && (
        <Card bg="green.50" _dark={{ bg: "green.900" }}>
          <CardBody>
            <VStack align="stretch" spacing={3}>
              <Text fontWeight="bold">
                Active Timers ({activeTimers.length})
              </Text>
              {activeTimers.map(timer => {
                const earnings = currentSessionEarnings[timer.id] || 0;
                return (
                  <Flex
                    key={timer.id}
                    justify="space-between"
                    align="center"
                    bg="white"
                    _dark={{ bg: "gray.800" }}
                    p={3}
                    borderRadius="md"
                  >
                    <HStack>
                      <Icon as={FaPlay} color="green.500" />
                      <Text>{timer.name}</Text>
                    </HStack>
                    <Text color="green.500" fontWeight="bold">
                      +${earnings.toFixed(2)}
                    </Text>
                  </Flex>
                );
              })}
            </VStack>
          </CardBody>
        </Card>
      )}

      {/* Timer List */}
      {filteredAndSortedTimers.length > 0 ? (
        <VStack spacing={4} align="stretch">
          {filteredAndSortedTimers.map(timer => (
            <TimerListItem
              key={timer.id}
              timer={timer}
              onDelete={handleAttemptDelete}
              onPin={handleTogglePin}
              onStart={handleStartTimer}
              onPause={handlePauseTimer}
              onReset={handleResetTimer}
            />
          ))}
        </VStack>
      ) : (
        <Center p={8} borderWidth={1} borderRadius="lg" borderStyle="dashed">
          <VStack spacing={3}>
            <Text color={subtleText}>No timers found</Text>
            {searchQuery && (
              <Button
                leftIcon={<FaSearch />}
                onClick={() => setSearchQuery('')}
                size="sm"
              >
                Clear search
              </Button>
            )}
          </VStack>
        </Center>
      )}

      {/* Delete Confirmation Modal */}
      <DeleteConfirmationModal
        isOpen={isDeleteModalOpen}
        onClose={onDeleteModalClose}
        onConfirm={handleConfirmDelete}
        timerName={timerToDelete?.name || ''}
      />
    </VStack>
  );
};

export default TimerList;

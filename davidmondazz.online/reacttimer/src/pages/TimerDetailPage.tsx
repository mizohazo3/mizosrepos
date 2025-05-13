import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Heading,
  Text,
  VStack,
  HStack,
  Badge,
  Table,
  Thead,
  Tbody,
  Tr,
  Th,
  Td,
  Stat,
  StatLabel,
  StatNumber,
  StatHelpText,
  StatGroup,
  Button,
  Spinner,
  useColorModeValue,
  Divider,
  Grid,
  GridItem,
  Icon,
  Tooltip,
  Flex,
  Container,
  Card,
  CardHeader,
  CardBody,
  CardFooter,
  Tabs,
  TabList,
  TabPanels,
  Tab,
  TabPanel,
  Tag,
  Avatar,
  Skeleton,
  SlideFade,
  useDisclosure,
  Stack
} from '@chakra-ui/react';
import { 
  FaArrowLeft, 
  FaClock, 
  FaCalendarAlt, 
  FaDollarSign, 
  FaRegClock, 
  FaPlay, 
  FaStop,
  FaChartLine,
  FaHistory,
  FaInfoCircle,
  FaLevelUpAlt
} from 'react-icons/fa';
import { TimerService } from '../services/firebase';
// Import from explicit path to ensure consistent type resolution
import { TimerData, TimerSession } from '../types/index';
import Timer from '../components/Timer';

const timerService = new TimerService();

interface GroupedSessions {
  [date: string]: {
    totalDuration: number;
    totalEarnings: number;
    sessions: TimerSession[];
  };
}

const TimerDetailPage: React.FC = () => {
  const { timerId } = useParams<{ timerId: string }>();
  const navigate = useNavigate();
  const [timer, setTimer] = useState<TimerData | null>(null);
  const [sessions, setSessions] = useState<TimerSession[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const { isOpen, onToggle } = useDisclosure({ defaultIsOpen: true });
  
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const statBgColor = useColorModeValue('blue.50', 'blue.900');
  const accentColor = useColorModeValue('blue.500', 'blue.300');
  const subtleColor = useColorModeValue('gray.600', 'gray.400');
  const altBgColor = useColorModeValue('gray.50', 'gray.700');
  const headingColor = useColorModeValue('gray.800', 'white');
  const cardHoverBg = useColorModeValue('gray.50', 'gray.700');

  useEffect(() => {
    if (!timerId) return;

    // Setup real-time timer updates
    const unsubscribeTimer = timerService.subscribeToTimers((timers) => {
      const selectedTimer = timers.find(t => t.id === timerId);
      
      if (selectedTimer) {
        setTimer(selectedTimer as TimerData);
        
        // Update page title when timer name changes
        document.title = `Track: ${selectedTimer.name} | Timer Tracker`;
      } else {
        // Handle timer not found
        navigate('/');
      }
      
      setIsLoading(false);
    });
    
    // Load session history
    const loadSessions = async () => {
      try {
        const timerSessions = await timerService.getTimerSessions(timerId);
        const sortedSessions = timerSessions.sort((a, b) => 
          (b.timestamp || 0) - (a.timestamp || 0)
        );
        setSessions(sortedSessions as TimerSession[]);
      } catch (error) {
        console.error('Error loading timer sessions:', error);
      }
    };
    
    loadSessions();

    // Cleanup function to unsubscribe and reset title
    return () => {
      unsubscribeTimer();
      document.title = 'Timers | Timer Tracker'; // Reset title on unmount
    };
  }, [timerId, navigate]);

  const handleLevelUp = (timerName: string, newLevel: number) => {
    // This could show a toast or update UI
  };

  // Set up a refresh interval for session data
  useEffect(() => {
    if (!timerId) return;
    
    // Refresh sessions every 30 seconds if a timer is active
    const refreshInterval = setInterval(async () => {
      if (timer?.isActive) {
        const timerSessions = await timerService.getTimerSessions(timerId);
        const sortedSessions = timerSessions.sort((a, b) => 
          (b.timestamp || 0) - (a.timestamp || 0)
        );
        setSessions(sortedSessions as TimerSession[]);
      }
    }, 30000);
    
    return () => clearInterval(refreshInterval);
  }, [timerId, timer?.isActive]);

  const formatDate = (timestamp: number): string => {
    return new Date(timestamp).toLocaleDateString();
  };

  const formatTimeOfDay = (timestamp: number): string => {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
      return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
      return `${minutes}m ${secs}s`;
    } else {
      return `${secs}s`;
    }
  };

  // Format for time stats display
  const formatTimeStats = (seconds: number): { value: string, unit: string } => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0) {
      return { value: hours.toString(), unit: 'hours' };
    } else {
      return { value: minutes.toString(), unit: 'minutes' };
    }
  };

  // Standardize date format to ensure consistent grouping
  const getStandardizedDate = (dateString?: string, timestamp?: number): string => {
    if (dateString) {
      // If date string is provided, parse and reformat to ensure consistency
      const parts = dateString.split('-');
      if (parts.length === 3) {
        // If it's in ISO format (YYYY-MM-DD), standardize
        return `${parts[0]}-${parts[1]}-${parts[2]}`;
      }
    }
    
    // Otherwise, use timestamp to create a standardized date format
    if (timestamp) {
      const date = new Date(timestamp);
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    
    // Fallback to current date in standardized format
    const today = new Date();
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  };

  // Group sessions by date
  const groupedSessions: GroupedSessions = sessions.reduce((groups, session) => {
    // Use standardized date format for grouping key
    const standardDate = getStandardizedDate(session.date, session.timestamp);
    
    if (!groups[standardDate]) {
      groups[standardDate] = {
        totalDuration: 0,
        totalEarnings: 0,
        sessions: []
      };
    }
    
    groups[standardDate].sessions.push(session);
    groups[standardDate].totalDuration += session.duration || 0;
    groups[standardDate].totalEarnings += session.earnings || 0;
    
    return groups;
  }, {} as GroupedSessions);

  // Format the date for display
  const formatDateForDisplay = (dateString: string): string => {
    // Parse the standardized date (YYYY-MM-DD) and format for display
    const [year, month, day] = dateString.split('-');
    const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
    return date.toLocaleDateString();
  };

  // Calculate session statistics
  const calculateStats = () => {
    if (sessions.length === 0) return null;
    
    let totalDuration = 0;
    let totalEarnings = 0;
    let avgSessionDuration = 0;
    let longestSession = 0;
    
    sessions.forEach(session => {
      const duration = session.duration || 0;
      totalDuration += duration;
      totalEarnings += session.earnings || 0;
      longestSession = Math.max(longestSession, duration);
    });
    
    avgSessionDuration = totalDuration / sessions.length;
    
    return {
      totalDuration,
      totalEarnings,
      avgSessionDuration,
      longestSession,
      sessionsCount: sessions.length
    };
  };
  
  const stats = calculateStats();

  if (isLoading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minH="200px">
        <Spinner size="xl" color={accentColor} thickness="4px" />
      </Box>
    );
  }

  if (!timer) {
    return (
      <Container maxW="container.md" p={5}>
        <VStack spacing={6} align="center">
          <Heading size="md" color={headingColor}>Timer not found</Heading>
          <Button 
            leftIcon={<FaArrowLeft />} 
            colorScheme="blue" 
            onClick={() => navigate('/')}
            size="md"
            boxShadow="sm"
          >
            Back to Timers
          </Button>
        </VStack>
      </Container>
    );
  }

  return (
    <Container maxW="container.lg" py={8} px={{ base: 4, md: 8 }}>
      <SlideFade in={true} offsetY="20px">
        <Flex mb={6} align="center">
          <Button 
            leftIcon={<FaArrowLeft />} 
            variant="ghost" 
            onClick={() => navigate('/')}
            mr={4}
            size="md"
            color={accentColor}
          >
            Back
          </Button>
          <Heading 
            as="h1" 
            size="xl" 
            color={headingColor}
            fontWeight="bold"
            textAlign="center"
            flex="1"
          >
            {timer.name}
          </Heading>
          <Box w="50px" />
        </Flex>

        {/* Timer Card */}
        <Card 
          bg={bgColor} 
          shadow="lg" 
          borderRadius="xl" 
          overflow="hidden" 
          borderWidth="1px"
          borderColor={borderColor}
          mb={8}
        >
          <CardBody p={6}>
            <VStack spacing={6} align="stretch">
              {/* Timer Display */}
              <Timer timer={timer} onLevelUp={handleLevelUp} />
              
              {/* Stats Overview */}
              <Grid 
                templateColumns={{ base: "repeat(2, 1fr)", md: "repeat(4, 1fr)" }} 
                gap={4} 
                mt={4}
                p={4}
                bg={statBgColor}
                borderRadius="lg"
              >
                <GridItem>
                  <Stat>
                    <StatLabel color={subtleColor}>
                      <HStack spacing={1}>
                        <Icon as={FaLevelUpAlt} />
                        <Text>Level</Text>
                      </HStack>
                    </StatLabel>
                    <HStack>
                      <StatNumber fontWeight="bold">{timer.level}</StatNumber>
                      <Badge colorScheme="purple" py={1} px={2} borderRadius="full">
                        {timer.levelTitle || 'Unknown'}
                      </Badge>
                    </HStack>
                  </Stat>
                </GridItem>
                
                <GridItem>
                  <Stat>
                    <StatLabel color={subtleColor}>
                      <HStack spacing={1}>
                        <Icon as={FaDollarSign} />
                        <Text>Rate</Text>
                      </HStack>
                    </StatLabel>
                    <StatNumber fontWeight="bold">${(timer.currentRate ?? 0).toFixed(2)}</StatNumber>
                    <StatHelpText>per hour</StatHelpText>
                  </Stat>
                </GridItem>
                
                <GridItem>
                  <Stat>
                    <StatLabel color={subtleColor}>
                      <HStack spacing={1}>
                        <Icon as={FaClock} />
                        <Text>Total Time</Text>
                      </HStack>
                    </StatLabel>
                    <StatNumber fontWeight="bold">
                      {Math.floor(timer.totalTime / 3600)}h {Math.floor((timer.totalTime % 3600) / 60)}m
                    </StatNumber>
                  </Stat>
                </GridItem>
                
                <GridItem>
                  <Stat>
                    <StatLabel color={subtleColor}>
                      <HStack spacing={1}>
                        <Icon as={FaDollarSign} />
                        <Text>Total Earnings</Text>
                      </HStack>
                    </StatLabel>
                    <StatNumber fontWeight="bold">${timer.earnings.toFixed(2)}</StatNumber>
                  </Stat>
                </GridItem>
              </Grid>
            </VStack>
          </CardBody>
        </Card>

        {/* Session History and Stats Section */}
        <Tabs variant="soft-rounded" colorScheme="blue" isLazy>
          <TabList mb={4}>
            <Tab borderRadius="full" _selected={{ bg: accentColor, color: 'white' }}>
              <HStack spacing={2}>
                <Icon as={FaHistory} />
                <Text>Session History</Text>
              </HStack>
            </Tab>
            <Tab borderRadius="full" _selected={{ bg: accentColor, color: 'white' }}>
              <HStack spacing={2}>
                <Icon as={FaChartLine} />
                <Text>Stats</Text>
              </HStack>
            </Tab>
          </TabList>
          
          <TabPanels>
            {/* Session History Panel */}
            <TabPanel p={0}>
              {Object.entries(groupedSessions).length > 0 ? (
                Object.entries(groupedSessions).map(([date, data], index) => (
                  <SlideFade in={true} offsetY="20px" delay={0.05 * index} key={date}>
                    <Card 
                      mb={4}
                      bg={bgColor}
                      borderWidth="1px"
                      borderRadius="lg"
                      borderColor={borderColor}
                      overflow="hidden"
                      transition="all 0.2s"
                      _hover={{ boxShadow: 'md' }}
                    >
                      <CardHeader p={4} bg={altBgColor}>
                        <Flex justify="space-between" align="center">
                          <HStack spacing={2}>
                            <Icon as={FaCalendarAlt} color={accentColor} />
                            <Heading size="sm">{formatDateForDisplay(date)}</Heading>
                          </HStack>
                          
                          <HStack spacing={4}>
                            <HStack>
                              <Icon as={FaClock} color="blue.400" boxSize={4} />
                              <Text fontWeight="medium">{formatDuration(data.totalDuration)}</Text>
                            </HStack>
                            <HStack>
                              <Icon as={FaDollarSign} color="green.400" boxSize={4} />
                              <Text fontWeight="medium">${data.totalEarnings.toFixed(2)}</Text>
                            </HStack>
                          </HStack>
                        </Flex>
                      </CardHeader>
                      
                      <CardBody p={0}>
                        <Table size="sm" variant="simple">
                          <Thead bg={altBgColor}>
                            <Tr>
                              <Th>Start</Th>
                              <Th>End</Th>
                              <Th>Duration</Th>
                              <Th isNumeric>Earnings</Th>
                            </Tr>
                          </Thead>
                          <Tbody>
                            {data.sessions.map((session) => (
                              <Tr 
                                key={session.id} 
                                _hover={{ bg: cardHoverBg }}
                                transition="background 0.2s"
                              >
                                <Td>
                                  {session.startTime ? (
                                    <Tooltip label={new Date(session.startTime).toLocaleString()}>
                                      <HStack spacing={1}>
                                        <Icon as={FaPlay} color="green.400" fontSize="xs" />
                                        <Text>{formatTimeOfDay(session.startTime)}</Text>
                                      </HStack>
                                    </Tooltip>
                                  ) : (
                                    formatTimeOfDay((session.timestamp || 0) - ((session.duration || 0) * 1000))
                                  )}
                                </Td>
                                <Td>
                                  {session.endTime ? (
                                    <Tooltip label={new Date(session.endTime).toLocaleString()}>
                                      <HStack spacing={1}>
                                        <Icon as={FaStop} color="red.400" fontSize="xs" />
                                        <Text>{formatTimeOfDay(session.endTime)}</Text>
                                      </HStack>
                                    </Tooltip>
                                  ) : (
                                    formatTimeOfDay(session.timestamp || 0)
                                  )}
                                </Td>
                                <Td fontFamily="monospace" fontWeight="medium">
                                  {formatDuration(session.duration || 0)}
                                </Td>
                                <Td isNumeric fontWeight="bold" color="green.500">
                                  ${(session.earnings || 0).toFixed(2)}
                                </Td>
                              </Tr>
                            ))}
                          </Tbody>
                        </Table>
                      </CardBody>
                    </Card>
                  </SlideFade>
                ))
              ) : (
                <Card p={8} textAlign="center" borderRadius="lg" bg={bgColor} borderColor={borderColor}>
                  <VStack spacing={4}>
                    <Icon as={FaInfoCircle} boxSize={12} color={subtleColor} opacity={0.7} />
                    <Text color={subtleColor} fontSize="lg">No sessions recorded yet.</Text>
                    <Text color={subtleColor}>Start tracking time to see your sessions appear here.</Text>
                  </VStack>
                </Card>
              )}
            </TabPanel>
            
            {/* Stats Panel */}
            <TabPanel p={0}>
              <Card 
                bg={bgColor}
                borderWidth="1px"
                borderRadius="lg"
                borderColor={borderColor}
                overflow="hidden"
                shadow="md"
              >
                <CardHeader bg={altBgColor} p={4}>
                  <Heading size="md" color={headingColor}>Session Statistics</Heading>
                </CardHeader>
                <CardBody p={6}>
                  {stats ? (
                    <Grid templateColumns={{ base: "repeat(1, 1fr)", md: "repeat(2, 1fr)" }} gap={8}>
                      <GridItem>
                        <VStack align="start" spacing={6}>
                          <Stat>
                            <StatLabel color={subtleColor}>
                              <HStack spacing={1}>
                                <Icon as={FaHistory} />
                                <Text>Total Sessions</Text>
                              </HStack>
                            </StatLabel>
                            <HStack align="baseline">
                              <StatNumber fontWeight="bold" fontSize="4xl" color={accentColor}>
                                {stats.sessionsCount}
                              </StatNumber>
                              <Text fontWeight="medium" fontSize="md" color={subtleColor}>
                                sessions
                              </Text>
                            </HStack>
                          </Stat>
                          
                          <Stat>
                            <StatLabel color={subtleColor}>
                              <HStack spacing={1}>
                                <Icon as={FaClock} />
                                <Text>Average Session Duration</Text>
                              </HStack>
                            </StatLabel>
                            <HStack align="baseline">
                              <StatNumber fontWeight="bold" fontSize="4xl" color={accentColor}>
                                {Math.floor(stats.avgSessionDuration / 60)}
                              </StatNumber>
                              <Text fontWeight="medium" fontSize="md" color={subtleColor}>
                                minutes per session
                              </Text>
                            </HStack>
                          </Stat>
                        </VStack>
                      </GridItem>
                      
                      <GridItem>
                        <VStack align="start" spacing={6}>
                          <Stat>
                            <StatLabel color={subtleColor}>
                              <HStack spacing={1}>
                                <Icon as={FaClock} />
                                <Text>Longest Session</Text>
                              </HStack>
                            </StatLabel>
                            <HStack align="baseline">
                              <StatNumber fontWeight="bold" fontSize="4xl" color={accentColor}>
                                {formatTimeStats(stats.longestSession).value}
                              </StatNumber>
                              <Text fontWeight="medium" fontSize="md" color={subtleColor}>
                                {formatTimeStats(stats.longestSession).unit}
                              </Text>
                            </HStack>
                          </Stat>
                          
                          <Stat>
                            <StatLabel color={subtleColor}>
                              <HStack spacing={1}>
                                <Icon as={FaDollarSign} />
                                <Text>Total Tracked Earnings</Text>
                              </HStack>
                            </StatLabel>
                            <HStack align="baseline">
                              <StatNumber fontWeight="bold" fontSize="4xl" color="green.500">
                                ${stats.totalEarnings.toFixed(2)}
                              </StatNumber>
                              <Text fontWeight="medium" fontSize="md" color={subtleColor}>
                                from all sessions
                              </Text>
                            </HStack>
                          </Stat>
                        </VStack>
                      </GridItem>
                    </Grid>
                  ) : (
                    <VStack spacing={4} py={4}>
                      <Icon as={FaInfoCircle} boxSize={12} color={subtleColor} opacity={0.7} />
                      <Text color={subtleColor} fontSize="lg">No session data available</Text>
                      <Text color={subtleColor}>Start tracking time to see your statistics.</Text>
                    </VStack>
                  )}
                </CardBody>
              </Card>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </SlideFade>
    </Container>
  );
};

export default TimerDetailPage; 
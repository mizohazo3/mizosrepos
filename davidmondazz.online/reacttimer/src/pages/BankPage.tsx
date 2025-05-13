import React, { useState, useEffect, useMemo } from 'react';
import {
  Box,
  Container,
  Heading,
  Text,
  VStack,
  HStack,
  Table,
  Thead,
  Tbody,
  Tr,
  Th,
  Td,
  Badge,
  Stat,
  StatLabel,
  StatNumber,
  StatHelpText,
  StatGroup,
  Card,
  CardHeader,
  CardBody,
  Button,
  Input,
  InputGroup,
  InputLeftElement,
  Stack,
  Flex,
  Divider,
  Tag,
  useColorModeValue,
  Spinner,
  Center,
  Select,
  Tabs,
  TabList,
  TabPanels,
  Tab,
  TabPanel,
  IconButton,
  Tooltip,
  useToast
} from '@chakra-ui/react';
import { 
  FaCoins, 
  FaClock, 
  FaCalendarAlt, 
  FaMoneyBillWave, 
  FaChartLine, 
  FaSearch, 
  FaFilter, 
  FaSortAmountDown, 
  FaSortAmountUpAlt,
  FaDownload,
  FaWallet
} from 'react-icons/fa';
import { TimerService } from '../services/firebase';
import { TimerData, TimerSession } from '../types';

const timerService = new TimerService();

// Helper function to format date
const formatDate = (timestamp: number): string => {
  return new Date(timestamp).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Helper function to format time
const formatTime = (timestamp: number): string => {
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Helper function to format duration
const formatDuration = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = Math.floor(seconds % 60);
  
  return `${hours}h ${minutes}m ${secs}s`;
};

// Interface for combined session data with timer name
interface EnhancedSession extends TimerSession {
  timerName: string;
  itemPurchased?: string; // Optional field for marketplace purchases
}

// Interface for daily grouped transactions
interface DailyGroup {
  date: string;
  sessions: EnhancedSession[];
  totalGained: number;
  totalPurchased: number;
}

const BankPage: React.FC = () => {
  const [sessions, setSessions] = useState<EnhancedSession[]>([]);
  const [timers, setTimers] = useState<TimerData[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [filterTimerId, setFilterTimerId] = useState<string>('all');
  const [dateRange, setDateRange] = useState<'all' | 'today' | 'week' | 'month'>('all');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  const toast = useToast();
  
  // Theme colors
  const cardBg = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const textColor = useColorModeValue('gray.600', 'gray.400');
  const highlightColor = useColorModeValue('blue.500', 'blue.300');
  const statCardBg = useColorModeValue('blue.50', 'blue.900');
  const tableBg = useColorModeValue('white', 'gray.800');
  const tableHeaderBg = useColorModeValue('gray.50', 'gray.700');
  const tableRowHoverBg = useColorModeValue('gray.50', 'gray.700');
  const earningsColor = useColorModeValue('green.600', 'green.300');
  const expenseColor = useColorModeValue('red.600', 'red.400'); // For purchased items
  const balanceBg = useColorModeValue('green.50', 'green.900');
  
  useEffect(() => {
    const fetchAllData = async () => {
      try {
        setIsLoading(true);
        
        // Fetch timers first
        const loadedTimers = await timerService.getTimers();
        setTimers(loadedTimers);
        
        // Subscribe to sessions instead of fetching once
        const unsubscribeSessions = timerService.subscribeToSessions((loadedSessions) => {
          // Combine sessions with timer names
          const enhancedSessions: EnhancedSession[] = loadedSessions.map(session => {
            const matchingTimer = loadedTimers.find(timer => timer.id === session.timerId);
            
            return {
              ...session,
              timerName: matchingTimer?.name || 'Unknown Timer',
              itemPurchased: session.itemPurchased
            };
          });
          
          setSessions(enhancedSessions);
          setIsLoading(false);
        });
        
        // Return cleanup function
        return () => {
          unsubscribeSessions();
        };
      } catch (error) {
        console.error('Error loading bank data:', error);
        toast({
          title: 'Error loading data',
          description: 'Could not load your earnings data. Please try again.',
          status: 'error',
          duration: 5000,
          isClosable: true,
        });
        setIsLoading(false);
      }
    };
    
    fetchAllData();
  }, [toast]);
  
  // Filter and sort sessions
  const filteredSessions = useMemo(() => {
    // Start with all sessions
    let result = [...sessions];
    
    // Apply search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      result = result.filter(session => 
        session.timerName.toLowerCase().includes(query)
      );
    }
    
    // Apply timer filter
    if (filterTimerId !== 'all') {
      result = result.filter(session => session.timerId === filterTimerId);
    }
    
    // Apply date range filter
    if (dateRange !== 'all') {
      const now = new Date();
      let startDate: Date;
      
      switch (dateRange) {
        case 'today':
          startDate = new Date(now.setHours(0, 0, 0, 0));
          break;
        case 'week':
          startDate = new Date(now);
          startDate.setDate(now.getDate() - 7);
          break;
        case 'month':
          startDate = new Date(now);
          startDate.setMonth(now.getMonth() - 1);
          break;
        default:
          startDate = new Date(0); // Beginning of time
      }
      
      result = result.filter(session => {
        return new Date(session.timestamp) >= startDate;
      });
    }
    
    // Apply sorting (always sort by timestamp)
    result.sort((a, b) => {
      const comparison = a.timestamp - b.timestamp;
      return sortOrder === 'asc' ? comparison : -comparison;
    });
    
    return result;
  }, [sessions, searchQuery, filterTimerId, dateRange, sortOrder]);
  
  // Calculate statistics
  const stats = useMemo(() => {
    const totalEarnings = filteredSessions.reduce((sum, session) => sum + session.earnings, 0);
    const totalHours = filteredSessions.reduce((sum, session) => sum + (session.duration / 3600), 0);
    const totalSessions = filteredSessions.length;
    const averageRate = totalHours > 0 ? totalEarnings / totalHours : 0;
    const averageSessionEarnings = totalSessions > 0 ? totalEarnings / totalSessions : 0;
    
    return {
      totalEarnings,
      totalHours,
      totalSessions,
      averageRate,
      averageSessionEarnings
    };
  }, [filteredSessions]);

  // Calculate monthly stats
  const monthlyStats = useMemo(() => {
    const months: { [key: string]: { earnings: number, hours: number } } = {};
    
    filteredSessions.forEach(session => {
      const date = new Date(session.timestamp);
      const monthKey = `${date.getFullYear()}-${date.getMonth() + 1}`;
      
      if (!months[monthKey]) {
        months[monthKey] = { earnings: 0, hours: 0 };
      }
      
      months[monthKey].earnings += session.earnings;
      months[monthKey].hours += session.duration / 3600;
    });
    
    // Convert to array for easier display
    return Object.entries(months).map(([monthKey, data]) => {
      const [year, month] = monthKey.split('-');
      const monthName = new Date(parseInt(year), parseInt(month) - 1).toLocaleString('en-US', { month: 'long' });
      
      return {
        month: `${monthName} ${year}`,
        earnings: data.earnings,
        hours: data.hours,
        averageRate: data.hours > 0 ? data.earnings / data.hours : 0
      };
    }).sort((a, b) => {
      // Sort months in reverse chronological order (newest first)
      return monthKey(b.month).localeCompare(monthKey(a.month));
    });
  }, [filteredSessions]);
  
  // Group transactions by day with daily totals
  const dailyGroupedTransactions = useMemo(() => {
    if (filteredSessions.length === 0) {
      return [];
    }

    const groups: DailyGroup[] = [];
    let currentGroup: DailyGroup | null = null;

    // filteredSessions is already sorted by timestamp based on sortOrder
    // If sortOrder is 'asc', dates will be oldest to newest.
    // If sortOrder is 'desc', dates will be newest to oldest.
    // The grouping logic below handles either order correctly by creating a new group
    // whenever the date changes.
    for (const session of filteredSessions) {
      const sessionDate = formatDate(session.timestamp);

      if (!currentGroup || currentGroup.date !== sessionDate) {
        if (currentGroup) {
          groups.push(currentGroup);
        }
        currentGroup = {
          date: sessionDate,
          sessions: [],
          totalGained: 0,
          totalPurchased: 0,
        };
      }

      currentGroup.sessions.push(session);
      if (session.timerId === 'marketplace') {
        // Assuming marketplace 'earnings' are costs/purchases
        currentGroup.totalPurchased += session.earnings;
      } else {
        currentGroup.totalGained += session.earnings;
      }
    }

    if (currentGroup) {
      groups.push(currentGroup);
    }
    return groups;
  }, [filteredSessions, sortOrder]); // Added sortOrder to dependencies as it affects the order of groups

  // Helper to create a sortable month key
  const monthKey = (monthStr: string) => {
    const [month, year] = monthStr.split(' ');
    const monthIndex = new Date(Date.parse(`${month} 1, 2000`)).getMonth() + 1;
    return `${year}-${monthIndex.toString().padStart(2, '0')}`;
  };
  
  // Handle export to CSV
  const exportToCSV = () => {
    if (filteredSessions.length === 0) {
      toast({
        title: 'Nothing to export',
        description: 'There are no sessions matching your filters to export.',
        status: 'warning',
        duration: 3000,
        isClosable: true,
      });
      return;
    }
    
    // Create CSV content
    const headers = ['Date', 'Time', 'Source', 'Duration', 'Earnings'];
    const rows = filteredSessions.map(session => [
      formatDate(session.timestamp),
      formatTime(session.timestamp),
      session.timerId === 'marketplace' 
        ? `Marketplace: ${session.itemPurchased || 'Purchase'}`
        : `Timer: ${session.timerName}`,
      formatDuration(session.duration),
      `$${session.earnings.toFixed(2)}`
    ]);
    
    // Add a summary row
    rows.push(['']);
    rows.push(['Total', '', '', `${stats.totalHours.toFixed(2)} hours`, `$${stats.totalEarnings.toFixed(2)}`]);
    
    // Convert to CSV string
    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.join(','))
    ].join('\n');
    
    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `timer-earnings-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toast({
      title: 'Export successful',
      description: 'Your earnings data has been exported to CSV.',
      status: 'success',
      duration: 3000,
      isClosable: true,
    });
  };
  
  if (isLoading) {
    return (
      <Center minH="300px">
        <VStack spacing={4}>
          <Spinner size="xl" color="blue.500" thickness="4px" speed="0.65s" />
          <Text color={textColor}>Loading your earnings data...</Text>
        </VStack>
      </Center>
    );
  }
  
  return (
    <Container maxW="container.xl" py={6}>
      <VStack spacing={6} align="stretch">
        <Flex justify="space-between" align="center">
          <Box>
            <Heading size="xl" mb={1}>Bank Account</Heading>
            <Text color={textColor}>Track your timer earnings and sessions</Text>
          </Box>
          <HStack>
            <Tooltip label="Export your earnings data as CSV">
              <Button
                leftIcon={<FaDownload />}
                colorScheme="blue"
                variant="outline"
                onClick={exportToCSV}
                isDisabled={filteredSessions.length === 0}
              >
                Export
              </Button>
            </Tooltip>
          </HStack>
        </Flex>
        
        {/* Balance Card */}
        <Card
          bg={balanceBg}
          borderWidth="1px"
          borderColor={borderColor}
          boxShadow="xl"
          borderRadius="xl"
          overflow="hidden"
          p={6}
        >
          <HStack spacing={8} align="flex-start">
            <Box
              bg="green.500"
              color="white"
              p={4}
              borderRadius="lg"
              boxShadow="md"
            >
              <FaWallet size={36} />
            </Box>
            <VStack align="flex-start" spacing={1}>
              <Text color={textColor} fontSize="lg">Your Balance</Text>
              <Heading size="2xl" color="green.600" _dark={{ color: "green.300" }}>
                ${stats.totalEarnings.toFixed(2)}
              </Heading>
              <Text color={textColor} fontSize="sm">
                {stats.totalSessions} {stats.totalSessions === 1 ? 'session' : 'sessions'} • {stats.totalHours.toFixed(2)} hours tracked
              </Text>
            </VStack>
          </HStack>
        </Card>
        
        {/* Filters Card */}
        <Card p={4} bg={cardBg} borderRadius="lg" borderWidth="1px" borderColor={borderColor}>
          <CardBody>
            <Stack direction={{ base: 'column', md: 'row' }} spacing={4} align="flex-end">
              <Box flex="1">
                <Text fontSize="sm" mb={1} fontWeight="medium">Search</Text>
                <InputGroup>
                  <InputLeftElement pointerEvents="none">
                    <FaSearch color="gray.300" />
                  </InputLeftElement>
                  <Input
                    placeholder="Search by timer name..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                  />
                </InputGroup>
              </Box>
              
              <Box width={{ base: "100%", md: "200px" }}>
                <Text fontSize="sm" mb={1} fontWeight="medium">Filter by Timer</Text>
                <Select
                  value={filterTimerId}
                  onChange={(e) => setFilterTimerId(e.target.value)}
                  icon={<FaFilter />}
                >
                  <option value="all">All Timers</option>
                  {timers.map(timer => (
                    <option key={timer.id} value={timer.id}>
                      {timer.name}
                    </option>
                  ))}
                </Select>
              </Box>
              
              <Box width={{ base: "100%", md: "150px" }}>
                <Text fontSize="sm" mb={1} fontWeight="medium">Date Range</Text>
                <Select
                  value={dateRange}
                  onChange={(e) => setDateRange(e.target.value as any)}
                  icon={<FaCalendarAlt />}
                >
                  <option value="all">All Time</option>
                  <option value="today">Today</option>
                  <option value="week">Last 7 Days</option>
                  <option value="month">Last 30 Days</option>
                </Select>
              </Box>
              
              <Box width={{ base: "100%", md: "120px" }}>
                <Text fontSize="sm" mb={1} fontWeight="medium">Sort Order</Text>
                <Button
                  width="100%"
                  rightIcon={sortOrder === 'desc' ? <FaSortAmountDown /> : <FaSortAmountUpAlt />}
                  onClick={() => setSortOrder(sortOrder === 'desc' ? 'asc' : 'desc')}
                  variant="outline"
                >
                  {sortOrder === 'desc' ? 'Newest' : 'Oldest'}
                </Button>
              </Box>
            </Stack>
          </CardBody>
        </Card>
        
        {/* Stats & Transactions Tabs */}
        <Tabs variant="enclosed" colorScheme="blue" borderRadius="lg" overflow="hidden">
          <TabList>
            <Tab><Box as="span" mr={2}><FaCoins /></Box> Transactions</Tab>
            <Tab><Box as="span" mr={2}><FaChartLine /></Box> Monthly Statistics</Tab>
          </TabList>
          
          <TabPanels>
            {/* Transactions Tab */}
            <TabPanel p={0}>
              <Card borderTopWidth="0" borderTopRadius="0" minHeight="300px">
                <CardBody p={0}>
                  {dailyGroupedTransactions.length > 0 ? (
                    <VStack spacing={6} align="stretch" p={{ base: 2, md: 4 }}>
                      {dailyGroupedTransactions.map((dayGroup) => (
                        <Box key={dayGroup.date} borderWidth="1px" borderRadius="lg" borderColor={borderColor} overflow="hidden" bg={cardBg}>
                          <Box bg={tableHeaderBg} p={3} borderBottomWidth="1px" borderColor={borderColor}>
                            <Flex justify="space-between" align="center" wrap="wrap">
                              <Heading size="md" mb={{ base: 2, md: 0 }}>{dayGroup.date}</Heading>
                              <HStack spacing={4} wrap="wrap">
                                <Text fontSize="sm" fontWeight="medium">
                                  Gained: <Text as="span" color={earningsColor} fontWeight="bold">${dayGroup.totalGained.toFixed(2)}</Text>
                                </Text>
                                <Text fontSize="sm" fontWeight="medium">
                                  Purchased: <Text as="span" color={expenseColor} fontWeight="bold">${dayGroup.totalPurchased.toFixed(2)}</Text>
                                </Text>
                              </HStack>
                            </Flex>
                          </Box>
                          <Box overflowX="auto">
                            <Table variant="simple" size="sm">
                              <Thead>
                                <Tr>
                                  <Th>Time</Th>
                                  <Th>Source</Th>
                                  <Th>Duration</Th>
                                  <Th isNumeric>Amount</Th>
                                </Tr>
                              </Thead>
                              <Tbody>
                                {dayGroup.sessions.map((session) => (
                                  <Tr
                                    key={session.id}
                                    _hover={{ bg: tableRowHoverBg }}
                                    transition="background 0.2s"
                                  >
                                    <Td>
                                      <Text fontSize="sm" color={textColor}>{formatTime(session.timestamp)}</Text>
                                    </Td>
                                    <Td>
                                      {session.timerId === 'marketplace' ? (
                                        <Text fontSize="sm">
                                          Marketplace: {session.itemPurchased || 'Purchase'}
                                        </Text>
                                      ) : (
                                        <Text fontSize="sm">
                                          Timer: {session.timerName}
                                        </Text>
                                      )}
                                    </Td>
                                    <Td fontSize="sm">
                                      {session.timerId === 'marketplace'
                                        ? 'N/A'
                                        : (session.duration > 0 ? formatDuration(session.duration) : '0s')
                                      }
                                    </Td>
                                    <Td isNumeric>
                                      <Text
                                        fontWeight="medium"
                                        fontSize="sm"
                                        color={session.timerId === 'marketplace' ? expenseColor : earningsColor}
                                      >
                                        {session.timerId === 'marketplace' ? '-' : '+'}
                                        ${session.earnings.toFixed(2)}
                                      </Text>
                                    </Td>
                                  </Tr>
                                ))}
                              </Tbody>
                            </Table>
                          </Box>
                        </Box>
                      ))}
                    </VStack>
                  ) : (
                    <Center py={10} minH="200px">
                      <VStack spacing={3}>
                        <FaCoins size={48} color={textColor} />
                        <Heading size="md" color={textColor}>No transactions found</Heading>
                        <Text fontSize="sm" color={textColor}>
                          Try adjusting your filters or complete some timer sessions to see them here.
                        </Text>
                      </VStack>
                    </Center>
                  )}
                </CardBody>
              </Card>
            </TabPanel>
            
            {/* Monthly Statistics Tab */}
            <TabPanel p={0}>
              <Card borderTopWidth="0" borderTopRadius="0">
                <CardBody p={0}>
                  {monthlyStats.length > 0 ? (
                    <Box overflowX="auto">
                      <Table variant="simple">
                        <Thead bg={tableHeaderBg}>
                          <Tr>
                            <Th>Month</Th>
                            <Th isNumeric>Hours</Th>
                            <Th isNumeric>Earnings</Th>
                            <Th isNumeric>Avg. Rate</Th>
                          </Tr>
                        </Thead>
                        <Tbody>
                          {monthlyStats.map((month, index) => (
                            <Tr 
                              key={index} 
                              _hover={{ bg: tableRowHoverBg }}
                              transition="background 0.2s"
                            >
                              <Td fontWeight="medium">{month.month}</Td>
                              <Td isNumeric>{month.hours.toFixed(2)} hrs</Td>
                              <Td isNumeric fontWeight="bold" color={earningsColor}>
                                ${month.earnings.toFixed(2)}
                              </Td>
                              <Td isNumeric>
                                ${month.averageRate.toFixed(2)}/hr
                              </Td>
                            </Tr>
                          ))}
                        </Tbody>
                      </Table>
                    </Box>
                  ) : (
                    <Center py={10}>
                      <VStack spacing={3}>
                        <FaChartLine size={40} color="gray" opacity={0.5} />
                        <Text color={textColor}>No monthly statistics available</Text>
                        <Text fontSize="sm" color={textColor}>
                          Start using timers to generate monthly statistics
                        </Text>
                      </VStack>
                    </Center>
                  )}
                </CardBody>
              </Card>
            </TabPanel>
          </TabPanels>
        </Tabs>
        
        {/* Stats Cards */}
        <StatGroup>
          <Card flex="1" bg={statCardBg} boxShadow="sm" borderRadius="xl" overflow="hidden">
            <CardBody>
              <Stat>
                <StatLabel>Average Hourly Rate</StatLabel>
                <StatNumber>${stats.averageRate.toFixed(2)}/hr</StatNumber>
                <StatHelpText>Based on all tracked time</StatHelpText>
              </Stat>
            </CardBody>
          </Card>
          
          <Card flex="1" bg={statCardBg} boxShadow="sm" borderRadius="xl" overflow="hidden">
            <CardBody>
              <Stat>
                <StatLabel>Total Time Tracked</StatLabel>
                <StatNumber>{stats.totalHours.toFixed(2)} hours</StatNumber>
                <StatHelpText>Across {stats.totalSessions} sessions</StatHelpText>
              </Stat>
            </CardBody>
          </Card>
          
          <Card flex="1" bg={statCardBg} boxShadow="sm" borderRadius="xl" overflow="hidden">
            <CardBody>
              <Stat>
                <StatLabel>Average Session</StatLabel>
                <StatNumber>${stats.averageSessionEarnings.toFixed(2)}</StatNumber>
                <StatHelpText>Per timer session</StatHelpText>
              </Stat>
            </CardBody>
          </Card>
        </StatGroup>
      </VStack>
    </Container>
  );
};

export default BankPage;
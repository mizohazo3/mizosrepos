import React from 'react';
import {
  Box,
  Text,
  Progress,
  VStack,
  HStack,
  useColorModeValue,
  Stat,
  StatLabel,
  StatNumber,
  StatHelpText,
  SimpleGrid,
} from '@chakra-ui/react';
import { UserStats } from '../types';

interface StatsProps {
  stats: UserStats | null;
}

export const Stats: React.FC<StatsProps> = ({ stats }) => {
  const bg = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.600');

  if (!stats) {
    return (
      <SimpleGrid columns={{ base: 1, md: 3 }} spacing={6} width="100%">
        {[1, 2, 3].map(i => (
          <Box key={i} p={6} borderRadius="xl" bg={bg} boxShadow="lg" border="1px" borderColor={borderColor} minH="150px" />
        ))}
      </SimpleGrid>
    );
  }

  return (
    <Box>
      <VStack spacing={4} align="stretch">
        <HStack spacing={8} align="flex-start">
          <Stat>
            <StatLabel>Level</StatLabel>
            <StatNumber>{stats.level} ({stats.levelTitle})</StatNumber>
            <StatHelpText>
              {Math.round((stats.currentLevelProgress ?? 0) * 100)}% to next level
            </StatHelpText>
          </Stat>
          <Box>
            <Text mb={2}>Level Progress</Text>
            <Progress
              value={(stats.currentLevelProgress ?? 0) * 100}
              colorScheme="blue"
              height="8px"
              borderRadius="full"
            />
          </Box>
        </HStack>

        <VStack spacing={4} align="stretch">
          <Stat>
            <StatLabel>Current Rate</StatLabel>
            <StatNumber>${(stats.currentRate ?? 0).toFixed(2)}/hr</StatNumber>
          </Stat>
        </VStack>

        <VStack spacing={4} align="stretch">
          <Stat>
            <StatLabel>Total Earnings</StatLabel>
            <StatNumber>${(stats.earnings ?? 0).toFixed(2)}</StatNumber>
            <StatHelpText>
              {Math.floor((stats.totalTime ?? 0) / 3600)} hours tracked
            </StatHelpText>
          </Stat>
        </VStack>
      </VStack>
    </Box>
  );
}; 
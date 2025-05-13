import React from 'react';
import {
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalCloseButton,
  ModalBody,
  ModalFooter,
  Button,
  Table,
  Thead,
  Tbody,
  Tr,
  Th,
  Td,
  Text,
  Badge,
  VStack,
  Box,
  HStack,
  Icon,
  useColorModeValue,
} from '@chakra-ui/react';
import { FaCoins, FaTrophy, FaArrowUp, FaTasks } from 'react-icons/fa';

// Define the fixed base rate
export const TODO_BASE_RATE = 0.005; // Set to $0.005 as requested

// Define number of sample levels to show in the table
const LEVELS_TO_SHOW = 100; // Show 100 levels

// Calculate completions needed with a more realistic, manageable curve
export const getCompletionsRequired = (level: number): number => {
  if (level <= 1) return 0;
  // Progression: L2=5, L3=14, L4=26, L10=135, L20=414, L50=1732, L100=4925
  return Math.round(5 * Math.pow(level - 1, 1.5)); 
};

// Calculate reward with a linear growth, more realistic for real-world feel
export const calculateReward = (level: number): number => {
  // Base rate + 20% of base rate per level (linear)
  return TODO_BASE_RATE * (1 + (level - 1) * 0.20);
};

// Calculate total completions from all tasks
export const calculateTotalCompletions = (completedCount: number): number => {
  return completedCount;
};

// Calculate current level based on total completions
export const calculateGlobalLevel = (totalCompletions: number): number => {
  let level = 1;
  // Cap level search for safety, though with new curve it's less likely to be an issue.
  while (getCompletionsRequired(level + 1) <= totalCompletions && level < 2000) { 
    if (getCompletionsRequired(level + 1) <= getCompletionsRequired(level) && level > 1) {
      // Safety break if progression isn't increasing
      break;
    }
    level++;
  }
  return level;
};

// Calculate progress to next level
export const calculateLevelProgress = (totalCompletions: number): number => {
  const currentLevel = calculateGlobalLevel(totalCompletions);
  const currentLevelReq = getCompletionsRequired(currentLevel);
  const nextLevelReq = getCompletionsRequired(currentLevel + 1);
  
  if (nextLevelReq <= currentLevelReq && currentLevel > 1) return 100; // If no next level or error in progression
  if (nextLevelReq === currentLevelReq && currentLevel === 1 && totalCompletions >= nextLevelReq) return 100; // Lvl 1 completed all for Lvl 2
  if (nextLevelReq <= currentLevelReq) return 0; // Default to 0 if requirements don't make sense for progress

  const progress = ((totalCompletions - currentLevelReq) / (nextLevelReq - currentLevelReq)) * 100;
  return Math.max(0, Math.min(100, progress)); // Clamp progress between 0 and 100
};

interface TodoLevelInfoModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentLevel: number;
  totalCompletions: number;
  levelProgress: number;
}

const TodoLevelInfoModal: React.FC<TodoLevelInfoModalProps> = ({ 
  isOpen, 
  onClose,
  currentLevel = 1,
  totalCompletions = 0,
  levelProgress = 0
}) => {
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const accentColor = useColorModeValue('purple.500', 'purple.300');
  
  // Generate level data for the table
  const levelData = Array.from({ length: LEVELS_TO_SHOW }, (_, i) => {
    const level = i + 1;
    const bonusPercent = (level - 1) * 20; // Linear 20% bonus from base per level
    const reward = calculateReward(level);
    const tasksToComplete = getCompletionsRequired(level); 
    
    return {
      level,
      bonusPercent,
      reward,
      tasksToComplete,
    };
  });
  
  return (
    <Modal isOpen={isOpen} onClose={onClose} size="xl">
      <ModalOverlay />
      <ModalContent>
        <ModalHeader>Global Todo Level System (Realistic Progression)</ModalHeader>
        <ModalCloseButton />
        
        <ModalBody>
          <VStack spacing={6} align="stretch">
            {/* Current Level Info */}
            <Box 
              p={4} 
              bg={useColorModeValue('blue.50', 'blue.900')} 
              borderRadius="md"
              borderWidth="1px"
              borderColor={useColorModeValue('blue.200', 'blue.700')}
            >
              <VStack spacing={3} align="center">
                <Badge colorScheme="blue" fontSize="lg" px={3} py={1}>
                  Current Level: {currentLevel}
                </Badge>
                
                <HStack>
                  <Text fontWeight="medium">Total Completions:</Text>
                  <Badge colorScheme="green">{totalCompletions}</Badge>
                </HStack>
                
                <Box w="100%" bg="gray.200" h="8px" borderRadius="full" overflow="hidden">
                  <Box 
                    bg="blue.500" 
                    h="100%" 
                    w={`${Math.max(0, Math.min(100, levelProgress))}%`}
                    transition="width 0.5s"
                  />
                </Box>
                
                <HStack>
                  <Text fontSize="sm">
                    Next level in {Math.max(0, getCompletionsRequired(currentLevel + 1) - totalCompletions)} more task completions
                  </Text>
                </HStack>
                
                <HStack>
                  <Icon as={FaCoins} color="yellow.500" />
                  <Text fontWeight="medium">
                    Current reward per task: ${calculateReward(currentLevel).toFixed(4)} {/* Showing 4 decimal places for $0.005 base */}
                  </Text>
                </HStack>
              </VStack>
            </Box>
            
            {/* How it Works */}
            <Box 
              p={4} 
              bg={useColorModeValue('purple.50', 'purple.900')} 
              borderRadius="md"
              borderWidth="1px"
              borderColor={useColorModeValue('purple.200', 'purple.700')}
            >
              <VStack spacing={3} align="start">
                <HStack>
                  <Icon as={FaTrophy} color={accentColor} boxSize={6} />
                  <Text fontWeight="bold" fontSize="lg">How Global Leveling Works</Text>
                </HStack>
                
                <Text>
                  Your Todo system has a single global level that increases as you complete tasks.
                  Level requirements and rewards grow at a balanced pace for a realistic sense of progression.
                </Text>
                
                <HStack>
                  <Icon as={FaTasks} color="blue.500" />
                  <Text fontWeight="medium">Complete any task to gain progress toward the next level</Text>
                </HStack>
                
                <HStack>
                  <Icon as={FaCoins} color="yellow.500" />
                  <Text fontWeight="medium">Base rate: ${TODO_BASE_RATE.toFixed(4)} per task at level 1</Text>
                </HStack>
                
                <HStack>
                  <Icon as={FaArrowUp} color="green.500" />
                  <Text fontWeight="medium">Level Bonus: +20% reward per level (linear increase)</Text>
                </HStack>
              </VStack>
            </Box>
            
            <Box overflowX="auto">
              <Table variant="simple" size="sm">
                <Thead>
                  <Tr>
                    <Th>Level</Th>
                    <Th>Bonus</Th>
                    <Th>Reward Per Task</Th>
                    <Th>Required Completions</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {levelData.map(item => (
                    <Tr 
                      key={item.level} 
                      bg={item.level === currentLevel ? 'blue.50' : undefined}
                      _dark={{ bg: item.level === currentLevel ? 'blue.900' : undefined }}
                    >
                      <Td>
                        <HStack spacing={1}>
                          <Badge colorScheme={item.level === currentLevel ? "blue" : "purple"} fontSize="sm">
                            Lvl {item.level}
                          </Badge>
                          {item.level === currentLevel && (
                            <Badge colorScheme="green">Current</Badge>
                          )}
                        </HStack>
                      </Td>
                      <Td>
                        <Text>{item.bonusPercent < 1 ? '-' : `+${item.bonusPercent.toFixed(0)}%`}</Text>
                      </Td>
                      <Td>
                        <HStack spacing={1}>
                          <Icon as={FaCoins} color="yellow.500" fontSize="xs" />
                          <Text fontWeight={item.level === currentLevel ? "bold" : "normal"}>
                            ${item.reward.toFixed(4)} {/* Showing 4 decimal places */}
                          </Text>
                        </HStack>
                      </Td>
                      <Td>
                        <Text>{item.level === 1 ? 'None' : `${item.tasksToComplete.toLocaleString()} completions`}</Text>
                      </Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </Box>
          </VStack>
        </ModalBody>
        
        <ModalFooter>
          <Button colorScheme="blue" onClick={onClose}>
            Close
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
};

export default TodoLevelInfoModal; 
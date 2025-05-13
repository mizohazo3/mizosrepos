import React, { useState, useEffect, useMemo } from 'react';
import {
  Box,
  Container,
  Heading,
  Text,
  VStack,
  HStack,
  Tab,
  Tabs,
  TabList,
  TabPanel,
  TabPanels,
  useColorModeValue,
  Stat,
  StatLabel,
  StatNumber,
  StatGroup,
  Divider,
  useDisclosure,
  Button,
  Icon,
  Badge,
  Flex,
  Tooltip,
  IconButton,
  Progress,
  useToast,
} from '@chakra-ui/react';
import { FaPlus, FaCheckCircle, FaExclamationCircle, FaCoins, FaListAlt, FaProjectDiagram, FaInfoCircle, FaTrophy } from 'react-icons/fa';
import { TimerService } from '../services/firebase';
import { TodoItem } from '../types';
import TodoList from '../components/Todo/TodoList';
import TodoForm from '../components/Todo/TodoForm';
import MindMapView from '../components/Todo/MindMapView';
import TodoLevelInfoModal, { calculateGlobalLevel, calculateLevelProgress, getCompletionsRequired, calculateReward } from '../components/Todo/TodoLevelInfoModal';

const timerService = new TimerService();

const TodoPage: React.FC = () => {
  const [todos, setTodos] = useState<TodoItem[]>([]);
  const [expandedTodoIds, setExpandedTodoIds] = useState<string[]>([]);
  const [stats, setStats] = useState<{
    completed: number; 
    active: number; 
    totalEarned: number;
    totalCompletions: number;
  }>({
    completed: 0,
    active: 0,
    totalEarned: 0,
    totalCompletions: 0
  });
  const [activeFilter, setActiveFilter] = useState<'active' | 'completed'>('active');
  const [viewMode, setViewMode] = useState<'list' | 'mindmap'>('list');
  const { isOpen: isFormOpen, onOpen: onFormOpen, onClose: onFormClose } = useDisclosure();
  const { isOpen: isLevelInfoOpen, onOpen: onLevelInfoOpen, onClose: onLevelInfoClose } = useDisclosure();
  const [selectedTodo, setSelectedTodo] = useState<TodoItem | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const toast = useToast();
  
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const accentColor = useColorModeValue('blue.500', 'blue.300');
  
  // Calculate global level data based on total completions
  const globalLevelData = useMemo(() => {
    const currentLevel = calculateGlobalLevel(stats.totalCompletions);
    const levelProgress = calculateLevelProgress(stats.totalCompletions);
    const remainingCompletions = getCompletionsRequired(currentLevel + 1) - stats.totalCompletions;
    const currentReward = calculateReward(currentLevel);
    
    return {
      currentLevel,
      levelProgress,
      remainingCompletions,
      currentReward
    };
  }, [stats.totalCompletions]);
  
  useEffect(() => {
    const unsubscribe = timerService.subscribeToTodoItems(async (fetchedTodos) => {
      setTodos(fetchedTodos);
      
      // Calculate basic stats from current todos
      const completedCount = fetchedTodos.filter(todo => todo.completed).length;
      const activeCount = fetchedTodos.length - completedCount;
      
      // Calculate total completions and earned from sessions for global level
      try {
        const sessions = await timerService.getTimerSessions();
        const todoRewardSessions = sessions.filter(s => s.timerId === 'todo-reward');
        const todoRefundSessions = sessions.filter(s => s.timerId === 'todo-refund');

        // Simplistic approach: count rewards, subtract refunds
        // Note: This assumes refund description perfectly matches reward description, which might be brittle.
        // A more robust system might store completion/refund events differently.
        let netCompletions = 0;
        const completionMap = new Map<string, number>();

        todoRewardSessions.forEach(s => {
          const desc = s.itemPurchased || '';
          completionMap.set(desc, (completionMap.get(desc) || 0) + 1);
        });

        todoRefundSessions.forEach(s => {
          const desc = (s.itemPurchased || '').replace(' Refund', ' Reward'); // Attempt to match refund to reward desc
          completionMap.set(desc, (completionMap.get(desc) || 0) - 1);
        });
        
        netCompletions = Array.from(completionMap.values()).reduce((sum, count) => sum + Math.max(0, count), 0);

        // Calculate total earned from net positive reward sessions
        const totalEarnedFromTodos = todoRewardSessions.reduce((sum, session) => {
            // Check if this reward has a corresponding refund
            const refundDesc = (session.itemPurchased || '').replace(' Reward', ' Refund');
            const hasRefund = todoRefundSessions.some(refund => refund.itemPurchased === refundDesc);
            return sum + (hasRefund ? 0 : session.earnings); // Only sum earnings if not refunded
          }, 0);

        
        setStats({
          completed: completedCount,
          active: activeCount,
          totalEarned: totalEarnedFromTodos, // Base earned only on non-refunded todo completions
          totalCompletions: netCompletions, // Use net completions from sessions
        });

      } catch (error) {
        console.error("Error fetching sessions for stats:", error);
        // Set defaults if session fetch fails
        setStats({
          completed: completedCount,
          active: activeCount,
          totalEarned: 0,
          totalCompletions: 0
        });
      }
    });
    
    return () => unsubscribe();
  }, []);
  
  const handleAddTodo = () => {
    // Create a new top-level task (no parent)
    setSelectedTodo(null);
    onFormOpen();
  };
  
  const handleAddSubtask = (parentId: string) => {
    // If form is already open, don't open another one
    if (isFormOpen) {
      return;
    }

    // Find the parent task to get its title
    const parentTask = todos.find(t => t.id === parentId);
    
    // Validate parent exists
    if (!parentTask) {
      toast({
        title: 'Error',
        description: 'Parent task not found',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
      return;
    }

    // Expand the parent task immediately
    setExpandedTodoIds(prev => {
      // Create a new array with the parentId if it's not already included
      if (!prev.includes(parentId)) {
        return [...prev, parentId];
      }
      return prev;
    });
    
    const parentTitle = parentTask.title;

    // Calculate the depth level of the parent to prevent excessive nesting
    const calculateDepth = (task: TodoItem): number => {
      if (!task.parentId) return 0;
      
      const parent = todos.find(t => t.id === task.parentId);
      if (!parent) return 1; // If parent not found, assume depth 1
      
      return 1 + calculateDepth(parent);
    };
    
    const parentDepth = calculateDepth(parentTask);
    
    // Check if adding another level would exceed max depth (3)
    if (parentDepth >= 2) {
      toast({
        title: 'Maximum nesting depth reached',
        description: 'Cannot add subtasks beyond 3 levels of nesting',
        status: 'warning',
        duration: 3000,
        isClosable: true,
      });
      return;
    }

    // Create a new todo with parent ID
    const newSubtask: Partial<TodoItem> = {
      title: '',
      description: '',
      completed: false,
      priority: 'medium',
      reward: 0,
      createdAt: Date.now(),
      updatedAt: Date.now(),
      position: todos.length,
      level: 1,
      parentId: parentId // Set the parent ID
    };

    // Set the selected todo for the form
    setSelectedTodo(newSubtask as TodoItem);
    
    // Set a toast notification to indicate which parent the subtask is being added to
    if (parentTitle) {
      toast({
        title: 'Adding subtask',
        description: `Creating a subtask under "${parentTitle}"`,
        status: 'info',
        duration: 3000,
        isClosable: true,
      });
    }
    
    onFormOpen();
  };
  
  const handleEditTodo = (todo: TodoItem) => {
    setSelectedTodo(todo);
    onFormOpen();
  };
  
  const handleDeleteTodo = async (id: string) => {
    setIsLoading(true);
    
    try {
      // Check if this task has children
      const hasChildren = todos.some(t => t.parentId === id);
      
      if (hasChildren) {
        // Find all child tasks recursively
        const findChildrenRecursively = (parentId: string): TodoItem[] => {
          const directChildren = todos.filter(t => t.parentId === parentId);
          const allDescendants = [...directChildren];
          
          // Find children of each child
          directChildren.forEach(child => {
            allDescendants.push(...findChildrenRecursively(child.id));
          });
          
          return allDescendants;
        };
        
        const childTasks = findChildrenRecursively(id);
        
        // Confirm with the user if they want to delete all children
        const shouldDeleteChildren = window.confirm(
          `This task has ${childTasks.length} subtask(s). Would you like to delete them too?`
        );
        
        // Delete with the appropriate flag
        await timerService.deleteTodoItem(id, shouldDeleteChildren);
      } else {
        await timerService.deleteTodoItem(id);
      }
      
      toast({
        title: 'Task deleted',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error deleting todo:', error);
      
      toast({
        title: 'Error',
        description: 'Failed to delete task',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsLoading(false);
    }
  };
  
  const handleToggleComplete = async (todo: TodoItem) => {
    setIsLoading(true);
    
    try {
      if (todo.completed) {
        // Simply mark as incomplete
        await timerService.uncompleteTodoItem(todo.id, globalLevelData.currentLevel);
      } else {
        // When completing a task, ask if child tasks should also be completed
        const hasChildren = todos.some(t => t.parentId === todo.id);
        
        if (hasChildren) {
          // Find all child tasks recursively
          const findChildrenRecursively = (parentId: string): TodoItem[] => {
            const directChildren = todos.filter(t => t.parentId === parentId);
            const allDescendants = [...directChildren];
            
            // Find children of each child
            directChildren.forEach(child => {
              allDescendants.push(...findChildrenRecursively(child.id));
            });
            
            return allDescendants;
          };
          
          // Get all incomplete child tasks
          const incompleteChildren = findChildrenRecursively(todo.id).filter(t => !t.completed);
          
          if (incompleteChildren.length > 0) {
            // Confirm with the user if they want to complete all children
            const shouldCompleteChildren = window.confirm(
              `This task has ${incompleteChildren.length} incomplete subtask(s). Would you like to mark them as complete too?`
            );
            
            if (shouldCompleteChildren) {
              // Complete all children first
              const childPromises = incompleteChildren.map(child => 
                timerService.completeTodoItem(child.id, globalLevelData.currentLevel)
              );
              await Promise.all(childPromises);
            }
          }
        }
        
        // Complete the parent task
        await timerService.completeTodoItem(todo.id, globalLevelData.currentLevel);
      }
      
      toast({
        title: todo.completed ? 'Task marked as incomplete' : 'Task completed!',
        status: 'success',
        duration: 2000,
        isClosable: true,
      });
    } catch (error) {
      console.error('Error toggling todo completion:', error);
      
      toast({
        title: 'Error',
        description: 'Failed to update task status',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsLoading(false);
    }
  };
  
  const handleUpdatePositions = async (reorderedTodos: TodoItem[]) => {
    try {
      const positions = reorderedTodos.map((todo, index) => ({
        id: todo.id,
        position: index
      }));
      
      await timerService.updateTodoPositions(positions);
    } catch (error) {
      console.error('Error updating positions:', error);
    }
  };
  
  // Filter todos based on active filter
  const filteredTodos = todos.filter(todo => {
    if (activeFilter === 'active') return !todo.completed;
    if (activeFilter === 'completed') return todo.completed;
    return false;
  });
  
  return (
    <Container maxW="container.xl" py={8}>
      <VStack spacing={6} align="stretch">
        <HStack justifyContent="space-between" alignItems="center">
          <HStack>
            <Heading as="h1" size="xl">Todo Manager</Heading>
            <Tooltip label="View Level System">
              <IconButton
                icon={<FaInfoCircle />}
                aria-label="View Level System"
                size="sm"
                variant="ghost"
                colorScheme="purple"
                onClick={onLevelInfoOpen}
              />
            </Tooltip>
          </HStack>
          <HStack spacing={2}>
            <Tooltip label="List View">
              <Button
                size="sm"
                colorScheme={viewMode === 'list' ? 'blue' : 'gray'}
                leftIcon={<Icon as={FaListAlt} />}
                onClick={() => setViewMode('list')}
              >
                List
              </Button>
            </Tooltip>
            <Tooltip label="Mind Map View">
              <Button
                size="sm"
                colorScheme={viewMode === 'mindmap' ? 'blue' : 'gray'}
                leftIcon={<Icon as={FaProjectDiagram} />}
                onClick={() => setViewMode('mindmap')}
              >
                Mind Map
              </Button>
            </Tooltip>
          </HStack>
        </HStack>
        
        {/* Level Status Bar */}
        <Box 
          p={4} 
          bg={useColorModeValue('blue.50', 'blue.900')} 
          borderRadius="lg" 
          borderWidth="1px"
          borderColor={useColorModeValue('blue.200', 'blue.700')}
        >
          <HStack spacing={4} align="center">
            <HStack>
              <Badge
                colorScheme="blue"
                fontSize="lg"
                py={1}
                px={3}
                borderRadius="md"
              >
                <HStack>
                  <Icon as={FaTrophy} />
                  <Text>Level {globalLevelData.currentLevel}</Text>
                </HStack>
              </Badge>
              
              <Button 
                size="sm" 
                colorScheme="purple" 
                variant="outline"
                onClick={onLevelInfoOpen}
              >
                View Levels
              </Button>
            </HStack>
            
            <Box flex="1">
              <HStack mb={1} justifyContent="space-between">
                <Text fontSize="sm" fontWeight="medium">
                  Total Completions: {stats.totalCompletions}
                </Text>
                <Text fontSize="sm" color="gray.600" _dark={{ color: 'gray.400' }}>
                  Next Level: {globalLevelData.remainingCompletions} more completions
                </Text>
              </HStack>
              <Progress 
                value={globalLevelData.levelProgress} 
                size="sm" 
                colorScheme="blue"
                borderRadius="full"
              />
            </Box>
            
            <Tooltip label="Current reward per task">
              <Badge colorScheme="yellow" fontSize="md" py={1} px={2}>
                <HStack>
                  <Icon as={FaCoins} />
                  <Text>${globalLevelData.currentReward.toFixed(4)}</Text>
                </HStack>
              </Badge>
            </Tooltip>
          </HStack>
        </Box>
        
        <StatGroup 
          bg={bgColor} 
          p={4} 
          borderRadius="lg" 
          borderWidth="1px" 
          borderColor={borderColor}
          boxShadow="sm"
        >
          <Stat>
            <StatLabel fontSize="sm" fontWeight="medium">Active Tasks</StatLabel>
            <StatNumber color={accentColor}>
              <HStack>
                <Icon as={FaExclamationCircle} />
                <Text>{stats.active}</Text>
              </HStack>
            </StatNumber>
          </Stat>
          
          <Stat>
            <StatLabel fontSize="sm" fontWeight="medium">Completed</StatLabel>
            <StatNumber color="green.500">
              <HStack>
                <Icon as={FaCheckCircle} />
                <Text>{stats.completed}</Text>
              </HStack>
            </StatNumber>
          </Stat>
          
          <Stat>
            <StatLabel fontSize="sm" fontWeight="medium">Total Earned</StatLabel>
            <StatNumber color="yellow.500">
              <HStack>
                <Icon as={FaCoins} />
                <Text>${stats.totalEarned.toFixed(2)}</Text>
              </HStack>
            </StatNumber>
          </Stat>
        </StatGroup>
        
        <Box 
          bg={bgColor} 
          p={4} 
          borderRadius="lg" 
          borderWidth="1px" 
          borderColor={borderColor}
          boxShadow="sm"
        >
          <VStack spacing={4} align="stretch">
            <Tabs variant="soft-rounded" colorScheme="blue">
              <TabList>
                <Tab onClick={() => setActiveFilter('active')}>Active ({stats.active})</Tab>
                <Tab onClick={() => setActiveFilter('completed')}>Completed ({stats.completed})</Tab>
              </TabList>
              
              <TabPanels>
                <TabPanel px={0} pt={4}>
                  {/* Add New Main Task button */}
                  <Flex justifyContent="flex-end" mb={3}>
                    <Button
                      size="sm"
                      leftIcon={<Icon as={FaPlus} />}
                      colorScheme="blue"
                      onClick={handleAddTodo}
                    >
                      New Main Task
                    </Button>
                  </Flex>
                  
                  {viewMode === 'list' ? (
                    <TodoList 
                      todos={filteredTodos.filter(todo => !todo.completed)} 
                      onEdit={handleEditTodo}
                      onDelete={handleDeleteTodo}
                      onToggleComplete={handleToggleComplete}
                      onReorder={handleUpdatePositions}
                      globalLevel={globalLevelData.currentLevel}
                      globalReward={globalLevelData.currentReward}
                      onAddSubtask={handleAddSubtask}
                      expandedTodoIds={expandedTodoIds}
                    />
                  ) : (
                    <MindMapView 
                      todos={filteredTodos.filter(todo => !todo.completed)}
                      onEdit={handleEditTodo}
                      onToggleComplete={handleToggleComplete}
                      globalLevel={globalLevelData.currentLevel}
                      globalReward={globalLevelData.currentReward}
                    />
                  )}
                </TabPanel>
                
                <TabPanel px={0} pt={4}>
                  {viewMode === 'list' ? (
                    <TodoList 
                      todos={filteredTodos.filter(todo => todo.completed)} 
                      onEdit={handleEditTodo}
                      onDelete={handleDeleteTodo}
                      onToggleComplete={handleToggleComplete}
                      onReorder={handleUpdatePositions}
                      globalLevel={globalLevelData.currentLevel}
                      globalReward={globalLevelData.currentReward}
                    />
                  ) : (
                    <MindMapView 
                      todos={filteredTodos.filter(todo => todo.completed)}
                      onEdit={handleEditTodo}
                      onToggleComplete={handleToggleComplete}
                      globalLevel={globalLevelData.currentLevel}
                      globalReward={globalLevelData.currentReward}
                    />
                  )}
                </TabPanel>
              </TabPanels>
            </Tabs>
          </VStack>
        </Box>
      </VStack>
      
      <TodoForm 
        isOpen={isFormOpen} 
        onClose={onFormClose} 
        todo={selectedTodo}
        globalLevel={globalLevelData.currentLevel}
        globalReward={globalLevelData.currentReward}
        allTodos={todos}
      />
      
      <TodoLevelInfoModal 
        isOpen={isLevelInfoOpen} 
        onClose={onLevelInfoClose}
        currentLevel={globalLevelData.currentLevel}
        totalCompletions={stats.totalCompletions}
        levelProgress={globalLevelData.levelProgress}
      />
    </Container>
  );
};

export default TodoPage; 
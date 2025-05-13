import React, { useEffect, useState } from 'react';
import {
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalCloseButton,
  ModalBody,
  ModalFooter,
  Button,
  FormControl,
  FormLabel,
  Input,
  Textarea,
  Select,
  HStack,
  NumberInput,
  NumberInputField,
  NumberInputStepper,
  NumberIncrementStepper,
  NumberDecrementStepper,
  FormHelperText,
  VStack,
  InputGroup,
  InputLeftElement,
  Icon,
  useToast,
  Badge,
  Text,
  Box,
} from '@chakra-ui/react';
import { FaCoins, FaCalendarAlt, FaTasks } from 'react-icons/fa';
import { TimerService } from '../../services/firebase';
import { TodoItem } from '../../types';
import { TODO_BASE_RATE } from './TodoLevelInfoModal';

interface TodoFormProps {
  isOpen: boolean;
  onClose: () => void;
  todo: TodoItem | null;
  globalLevel?: number;
  globalReward?: number;
  allTodos?: TodoItem[];
}

const TodoForm: React.FC<TodoFormProps> = ({ 
  isOpen, 
  onClose, 
  todo, 
  globalLevel = 1, 
  globalReward = TODO_BASE_RATE,
  allTodos = [],
}) => {
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [priority, setPriority] = useState<'low' | 'medium' | 'high'>('medium');
  const [dueDate, setDueDate] = useState<string>('');
  const [parentId, setParentId] = useState<string>('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const toast = useToast();
  const timerService = new TimerService();
  
  // Reset form when modal opens or todo changes
  useEffect(() => {
    if (todo) {
      setTitle(todo.title);
      setDescription(todo.description || '');
      setPriority(todo.priority);
      setParentId(todo.parentId || '');
      
      // Convert timestamp to date string if exists
      if (todo.dueDate) {
        const date = new Date(todo.dueDate);
        setDueDate(date.toISOString().split('T')[0]);
      } else {
        setDueDate('');
      }
    } else {
      // Default values for new todo
      setTitle('');
      setDescription('');
      setPriority('medium');
      setDueDate('');
      setParentId('');
    }
  }, [todo, isOpen]);

  // Filter out potential circular parent references
  // A task cannot be a parent of itself, and we should prevent creating loops
  const getValidParentOptions = () => {
    if (!todo) return allTodos;
    
    // Function to check if a task is in the ancestry chain of another task
    const isInAncestryChain = (potentialParentId: string, childId: string, todos: TodoItem[]): boolean => {
      if (potentialParentId === childId) return true;
      
      const child = todos.find(t => t.id === childId);
      if (!child || !child.parentId) return false;
      
      return isInAncestryChain(potentialParentId, child.parentId, todos);
    };
    
    return allTodos.filter(t => t.id !== todo.id && !isInAncestryChain(todo.id, t.id, allTodos));
  };
  
  const validParentOptions = getValidParentOptions();
  
  // Debounce submit to prevent multiple rapid submissions
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (isSubmitting) return;
    setIsSubmitting(true);
    
    try {
      const trimmedTitle = title.trim();
      if (!trimmedTitle) {
        toast({
          title: 'Error',
          description: 'Title cannot be empty',
          status: 'error',
          duration: 3000,
          isClosable: true,
        });
        return;
      }

      const todoData: Partial<TodoItem> = {
        title: trimmedTitle,
        description,
        completed: false,
        priority,
      };

      if (dueDate) {
        todoData.dueDate = new Date(dueDate).getTime();
      }

      // If parentId exists, add it to the todo data
      if (parentId) {
        todoData.parentId = parentId;
      }

      if (todo?.id) {
        // Update existing todo
        await timerService.updateTodoItem(todo.id, todoData);
        toast({
          title: 'Success',
          description: 'Todo updated successfully',
          status: 'success',
          duration: 2000,
          isClosable: true,
        });
      } else {
        // Create new todo
        const newTodoId = await timerService.createTodoItem(todoData as any);
        if (newTodoId) {
          toast({
            title: 'Success',
            description: 'Todo created successfully',
            status: 'success',
            duration: 2000,
            isClosable: true,
          });
        }
      }

      // Reset form
      setTitle('');
      setDescription('');
      setPriority('medium');
      setDueDate('');
      
      // Close form
      onClose();
    } catch (error) {
      console.error('Error saving todo:', error);
      toast({
        title: 'Error',
        description: 'Failed to save todo',
        status: 'error',
        duration: 3000,
        isClosable: true,
      });
    } finally {
      setIsSubmitting(false);
    }
  };
  
  return (
    <Modal isOpen={isOpen} onClose={onClose} size="lg">
      <ModalOverlay />
      <ModalContent data-testid="todo-form">
        <ModalHeader>{todo ? 'Edit Task' : 'Create New Task'}</ModalHeader>
        <ModalCloseButton />
        
        <ModalBody>
          <VStack spacing={4}>
            <FormControl isRequired>
              <FormLabel>Title</FormLabel>
              <Input
                placeholder="Enter task title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
              />
            </FormControl>
            
            <FormControl>
              <FormLabel>Description</FormLabel>
              <Textarea
                placeholder="Enter task description (optional)"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={3}
              />
            </FormControl>
            
            <FormControl>
              <FormLabel>Priority</FormLabel>
              <Select
                value={priority}
                onChange={(e) => setPriority(e.target.value as 'low' | 'medium' | 'high')}
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </Select>
            </FormControl>
            
            <FormControl>
              <FormLabel>Due Date</FormLabel>
              <InputGroup>
                <InputLeftElement>
                  <Icon as={FaCalendarAlt} color="gray.500" />
                </InputLeftElement>
                <Input
                  type="date"
                  value={dueDate}
                  onChange={(e) => setDueDate(e.target.value)}
                />
              </InputGroup>
              <FormHelperText>Optional due date for the task</FormHelperText>
            </FormControl>
            
            {/* Show parent task info if this is a subtask */}
            {parentId && (
              <FormControl>
                <FormLabel>Parent Task</FormLabel>
                <HStack 
                  bg="blue.50" 
                  p={2} 
                  borderRadius="md" 
                  borderWidth="1px"
                  borderColor="blue.200"
                  _dark={{ 
                    bg: 'blue.900', 
                    borderColor: 'blue.700' 
                  }}
                >
                  <Icon as={FaTasks} color="blue.500" />
                  <Text fontSize="sm">
                    {!todo?.id ? 'Creating subtask under: ' : 'Subtask of: '}
                    <Text as="span" fontWeight="bold">
                      {allTodos.find(t => t.id === parentId)?.title || 'Parent Task'}
                    </Text>
                  </Text>
                </HStack>
              </FormControl>
            )}
            
            {/* Base reward information (not editable) */}
            <Box 
              p={3} 
              bg="purple.50" 
              borderRadius="md"
              borderWidth="1px"
              borderColor="purple.200"
              width="100%"
              _dark={{ 
                bg: 'purple.900', 
                borderColor: 'purple.700' 
              }}
            >
              <FormLabel mb={1}>Global Reward System</FormLabel>
              <HStack>
                <Icon as={FaCoins} color="yellow.500" />
                <Text>Current reward: ${globalReward.toFixed(3)} per task</Text>
              </HStack>
            </Box>

            {todo && (
              <FormControl>
                <FormLabel>Task Info</FormLabel>
                <HStack bg="purple.50" p={2} borderRadius="md" _dark={{ bg: 'purple.900' }}>
                  <Text fontSize="sm">
                    Current reward (based on Global Level {globalLevel}): ${globalReward.toFixed(4)}
                  </Text>
                </HStack>
                <FormHelperText>
                  Rewards are based on the global level, not individual task completions.
                </FormHelperText>
              </FormControl>
            )}
          </VStack>
        </ModalBody>
        
        <ModalFooter>
          <HStack spacing={3}>
            <Button variant="ghost" onClick={onClose}>
              Cancel
            </Button>
            <Button 
              colorScheme="blue" 
              onClick={handleSubmit}
              isLoading={isSubmitting}
            >
              {todo ? 'Update' : 'Create'}
            </Button>
          </HStack>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
};

export default TodoForm; 
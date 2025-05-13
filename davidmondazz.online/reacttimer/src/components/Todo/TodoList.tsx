import React, { useState, useEffect, useMemo } from 'react';
import {
  Box,
  VStack,
  Text,
  HStack,
  IconButton,
  Badge,
  useColorModeValue,
  Tooltip,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  Flex,
  Collapse,
  Button,
} from '@chakra-ui/react';
import { FaCheck, FaEdit, FaTrash, FaClock, FaEllipsisV, FaCoins, FaGripVertical, FaRegSquare, FaChevronDown, FaChevronRight, FaPlus } from 'react-icons/fa';
import { TodoItem } from '../../types';
import { 
  DragDropContext, 
  Droppable, 
  Draggable, 
  DropResult,
  DroppableProvided,
  DraggableProvided
} from '@hello-pangea/dnd';
import { TODO_BASE_RATE } from './TodoLevelInfoModal';

interface TodoListProps {
  todos: TodoItem[];
  onToggleComplete: (todo: TodoItem) => void;
  onEdit: (todo: TodoItem) => void;
  onDelete: (id: string) => void;
  onReorder: (reorderedTodos: TodoItem[]) => void;
  globalLevel?: number;
  globalReward?: number;
  onAddSubtask?: (parentId: string) => void;
  expandedTodoIds?: string[];
}

const TodoList: React.FC<TodoListProps> = ({
  todos,
  onToggleComplete,
  onEdit,
  onDelete,
  onReorder,
  globalLevel = 1,
  globalReward = TODO_BASE_RATE,
  onAddSubtask,
  expandedTodoIds = []
}) => {
  const bgColor = useColorModeValue('white', 'gray.800');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const completedBgColor = useColorModeValue('gray.50', 'gray.700');
  
  // Add colors for nesting levels at the top level of component
  const nestedBgColor = useColorModeValue('gray.50', 'gray.750');
  const nestedBgColorAlt = useColorModeValue('white', 'gray.800');
  const borderColorLevel1 = useColorModeValue('blue.100', 'blue.700');
  const borderColorLevel2 = useColorModeValue('blue.200', 'blue.600');
  const borderColorLevel3 = useColorModeValue('blue.300', 'blue.500');
  const borderColorLevel4 = useColorModeValue('blue.400', 'blue.400');
  
  // By default, all todos are expanded
  const [collapsedTodos, setCollapsedTodos] = useState<Set<string>>(new Set());
  
  // State to store the hierarchical structure
  const [hierarchicalTodos, setHierarchicalTodos] = useState<TodoItem[]>([]);
  
  // Build the hierarchical structure when todos change
  useEffect(() => {
    const buildTodoHierarchy = (todos: TodoItem[]): TodoItem[] => {
      const todoMap = new Map<string, TodoItem & { children: TodoItem[] }>();
      const rootTodos: (TodoItem & { children: TodoItem[] })[] = [];

      // First pass: create map of all todos with empty children arrays
      todos.forEach(todo => {
        todoMap.set(todo.id, { ...todo, children: [] });
      });

      // Second pass: build parent-child relationships
      todos.forEach(todo => {
        const todoWithChildren = todoMap.get(todo.id)!;
        
        if (todo.parentId && todoMap.has(todo.parentId)) {
          // Add to parent's children
          const parent = todoMap.get(todo.parentId)!;
          parent.children.push(todoWithChildren);
        } else {
          // Add to root level if no parent or parent doesn't exist
          rootTodos.push(todoWithChildren);
        }
      });

      // Sort root todos by position
      rootTodos.sort((a, b) => a.position - b.position);

      // Sort children of each todo by position
      const sortChildren = (todo: TodoItem & { children: TodoItem[] }) => {
        todo.children.sort((a, b) => a.position - b.position);
        todo.children.forEach(child => {
          if (child.children && child.children.length > 0) {
            sortChildren(child as TodoItem & { children: TodoItem[] });
          }
        });
      };

      rootTodos.forEach(sortChildren);
      
      return rootTodos;
    };
    
    setHierarchicalTodos(buildTodoHierarchy(todos));
  }, [todos]);
  
  const toggleExpand = (todoId: string) => {
    setCollapsedTodos(prev => {
      const newSet = new Set(prev);
      if (newSet.has(todoId)) {
        newSet.delete(todoId);
      } else {
        newSet.add(todoId);
      }
      return newSet;
    });
  };
  
  const formatDate = (timestamp?: number) => {
    if (!timestamp) return 'No due date';
    return new Date(timestamp).toLocaleDateString();
  };
  
  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high':
        return 'red';
      case 'medium':
        return 'orange';
      case 'low':
        return 'green';
      default:
        return 'gray';
    }
  };
  
  // Get background color based on depth and completion status
  const getBgColor = (depth: number, isCompleted: boolean) => {
    if (isCompleted) return completedBgColor;
    
    // Alternate between subtle background colors based on depth
    if (depth === 0) return bgColor;
    if (depth % 2 === 1) return nestedBgColor;
    return nestedBgColorAlt;
  };
  
  // Get border style based on depth
  const getBorderStyle = (depth: number) => {
    if (depth === 0) return {};
    
    let borderColor;
    switch(Math.min(depth, 4)) {
      case 1: borderColor = borderColorLevel1; break;
      case 2: borderColor = borderColorLevel2; break;
      case 3: borderColor = borderColorLevel3; break;
      case 4: borderColor = borderColorLevel4; break;
      default: borderColor = borderColorLevel1;
    }
    
    return {
      borderLeftWidth: '2px',
      borderLeftColor: borderColor,
      borderLeftStyle: 'solid' as const,
    };
  };
  
  // Flatten hierarchical structure for rendering while preserving parent-child relationships
  const flattenedTodos = useMemo(() => {
    const flattened: TodoItem[] = [];
    
    const flattenHierarchy = (items: TodoItem[], level: number = 0) => {
      items.forEach(item => {
        flattened.push({ ...item, depth: level });
        if (item.children && !collapsedTodos.has(item.id)) {
          flattenHierarchy(item.children, level + 1);
        }
      });
    };
    
    flattenHierarchy(hierarchicalTodos);
    return flattened;
  }, [hierarchicalTodos, collapsedTodos]);
  
  const handleDragEnd = (result: DropResult) => {
    if (!result.destination) return;
    
    // We need to update both the flat structure (for position) and maintain parent-child relationships
    const newTodos = Array.from(todos);
    const [movedItem] = newTodos.splice(result.source.index, 1);
    newTodos.splice(result.destination.index, 0, movedItem);
    
    // Update positions but preserve parent-child relationships
    const updatedTodos = newTodos.map((todo, index) => ({
      ...todo,
      position: index
    }));
    
    // Call the parent handler to update positions in Firebase
    onReorder(updatedTodos);
  };
  
  // Custom render function for a todo item
  const renderTodoItem = (todo: TodoItem, index: number) => {
    const hasChildren = todo.children && todo.children.length > 0;
    const isCollapsed = collapsedTodos.has(todo.id);
    const indentPadding = (todo.depth || 0) * 20; // 20px indentation per level
    const depth = todo.depth || 0;
    
    return (
      <Draggable key={todo.id} draggableId={todo.id} index={index}>
        {(provided: DraggableProvided) => (
          <Box
            ref={provided.innerRef}
            {...provided.draggableProps}
            bg={getBgColor(depth, todo.completed)}
            p={3}
            borderRadius="md"
            borderWidth="1px"
            borderColor={borderColor}
            opacity={todo.completed ? 0.7 : 1}
            boxShadow="sm"
            transition="all 0.2s"
            position="relative"
            _hover={{ boxShadow: 'md' }}
            mb={2}
            {...getBorderStyle(depth)}
            data-testid="todo-item"
            data-depth={depth}
          >
            <HStack spacing={4}>
              <Box {...provided.dragHandleProps} color="gray.500" cursor="grab">
                <FaGripVertical />
              </Box>
              
              {/* Indentation and expand/collapse control */}
              <Box pl={`${indentPadding}px`} display="flex" alignItems="center">
                {hasChildren ? (
                  <IconButton
                    size="xs"
                    aria-label={isCollapsed ? 'Expand' : 'Collapse'}
                    icon={isCollapsed ? <FaChevronRight /> : <FaChevronDown />}
                    variant="ghost"
                    onClick={() => toggleExpand(todo.id)}
                  />
                ) : (
                  <Box w="24px" /> // Spacer for alignment
                )}
                
                <IconButton
                  size="md"
                  aria-label={todo.completed ? 'Mark as incomplete' : 'Mark as complete'}
                  icon={todo.completed ? <FaCheck /> : <FaRegSquare />}
                  colorScheme={todo.completed ? 'green' : 'gray'}
                  variant={todo.completed ? 'solid' : 'outline'}
                  onClick={() => onToggleComplete(todo)}
                  ml={2}
                />
              </Box>
              
              <VStack align="start" spacing={1} flex={1}>
                <Text
                  fontWeight="medium"
                  textDecoration={todo.completed ? 'line-through' : 'none'}
                >
                  {todo.title}
                </Text>
                
                {todo.description && (
                  <Text 
                    fontSize="sm" 
                    color="gray.500"
                    noOfLines={2}
                  >
                    {todo.description}
                  </Text>
                )}
                
                <Flex wrap="wrap" gap={2} mt={1}>
                  <Badge colorScheme={getPriorityColor(todo.priority)}>
                    {todo.priority}
                  </Badge>
                  
                  {todo.dueDate && (
                    <Badge colorScheme="blue">
                      <HStack spacing={1} alignItems="center">
                        <FaClock size="0.7em" />
                        <Text fontSize="xs">{formatDate(todo.dueDate)}</Text>
                      </HStack>
                    </Badge>
                  )}
                  
                  <Tooltip label={`System Level: ${globalLevel}`}>
                    <Badge colorScheme="purple">
                      <HStack spacing={1} alignItems="center">
                        <Text fontSize="xs">Lvl {globalLevel}</Text>
                      </HStack>
                    </Badge>
                  </Tooltip>
                  
                  <Tooltip label="Reward for completing this task">
                    <Badge colorScheme="yellow">
                      <HStack spacing={1} alignItems="center">
                        <FaCoins size="0.7em" />
                        <Text fontSize="xs">${globalReward.toFixed(4)}</Text>
                      </HStack>
                    </Badge>
                  </Tooltip>
                  
                  {hasChildren && (
                    <Badge colorScheme="teal">
                      <Text fontSize="xs">{(todo.children || []).length} subtask{(todo.children || []).length !== 1 ? 's' : ''}</Text>
                    </Badge>
                  )}
                </Flex>
              </VStack>
              
              {!todo.completed && onAddSubtask && (depth < 3) && (
                <Tooltip label="Add subtask">
                  <Button
                    size="sm"
                    aria-label="Add subtask"
                    leftIcon={<FaPlus />}
                    colorScheme="teal"
                    variant="outline"
                    onClick={(e) => {
                      // Prevent event bubbling
                      e.stopPropagation();
                      e.preventDefault();
                      
                      // Ensure parent is expanded
                      setCollapsedTodos(prev => {
                        const newSet = new Set(prev);
                        newSet.delete(todo.id);
                        return newSet;
                      });
                      
                      onAddSubtask(todo.id);
                      // Add a data attribute to help with targeting in tests
                      e.currentTarget.setAttribute('data-subtask-added', 'true');
                    }}
                  >
                    Add subtask
                  </Button>
                </Tooltip>
              )}
              
              <Menu>
                <MenuButton
                  as={IconButton}
                  size="sm"
                  aria-label="Options"
                  icon={<FaEllipsisV />}
                  variant="ghost"
                />
                <MenuList fontSize="sm">
                  <MenuItem 
                    icon={<FaEdit />} 
                    onClick={() => onEdit(todo)}
                  >
                    Edit
                  </MenuItem>
                  <MenuItem 
                    icon={<FaTrash />} 
                    onClick={() => onDelete(todo.id)}
                    color="red.500"
                  >
                    Delete
                  </MenuItem>
                </MenuList>
              </Menu>
            </HStack>
          </Box>
        )}
      </Draggable>
    );
  };
  
  if (todos.length === 0) {
    return (
      <Box p={8} textAlign="center" borderWidth="1px" borderRadius="lg" borderStyle="dashed" borderColor="gray.300" data-testid="todo-list">
        <VStack spacing={4}>
          <Text fontSize="lg">No tasks found yet</Text>
          {onAddSubtask && (
            <Button
              colorScheme="blue"
              size="md"
              leftIcon={<FaPlus />}
              onClick={() => onAddSubtask('')}
            >
              Create First Task
            </Button>
          )}
        </VStack>
      </Box>
    );
  }
  
  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      <Droppable droppableId="todo-list">
        {(provided: DroppableProvided) => (
          <VStack
            spacing={0}
            align="stretch"
            ref={provided.innerRef}
            {...provided.droppableProps}
            data-testid="todo-list-container"
          >
            {flattenedTodos.map((todo, index) => renderTodoItem(todo, index))}
            {provided.placeholder}
          </VStack>
        )}
      </Droppable>
    </DragDropContext>
  );
};

export default TodoList; 